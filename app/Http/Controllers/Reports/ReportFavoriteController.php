<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportFavorite;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReportFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('reports.favorites.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id'
        ]);

        $favorite = ReportFavorite::firstOrCreate([
            'user_id' => auth()->id(),
            'report_id' => $request->report_id
        ], [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Report added to favorites',
            'data' => $favorite->load('report')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportFavorite $favorite): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $favorite->load('report')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReportFavorite $favorite): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $favorite->update([
            'notes' => $request->notes,
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Favorite updated successfully',
            'data' => $favorite->load('report')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportFavorite $favorite): JsonResponse
    {
        $favorite->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Report removed from favorites'
        ]);
    }

    /**
     * Get user's favorite reports
     */
    public function getFavorites(Request $request): JsonResponse
    {
        $query = ReportFavorite::with('report')
            ->where('user_id', auth()->id())
            ->when($request->search, function ($q, $search) {
                $q->whereHas('report', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $favorites = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $favorites->items(),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
                'from' => $favorites->firstItem(),
                'to' => $favorites->lastItem()
            ]
        ]);
    }

    /**
     * Toggle favorite status
     */
    public function toggle(Request $request, $reportId): JsonResponse
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id'
        ]);

        $favorite = ReportFavorite::where('user_id', auth()->id())
            ->where('report_id', $reportId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'Report removed from favorites';
            $is_favorite = false;
        } else {
            ReportFavorite::create([
                'user_id' => auth()->id(),
                'report_id' => $reportId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);
            $message = 'Report added to favorites';
            $is_favorite = true;
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'is_favorite' => $is_favorite
            ]
        ]);
    }
}
