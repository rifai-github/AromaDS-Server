<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\MarketingLevel;
use Illuminate\Support\Facades\Auth;

class MarketingLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $marketingLevels = MarketingLevel::with(['createdBy', 'updatedBy'])
            ->ordered()
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingLevels
            ]);
        }

        return view('finance.marketing-levels.index', compact('marketingLevels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success'
            ]);
        }

        return view('finance.marketing-levels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'level_code' => 'required|string|max:50|unique:marketing_levels,level_code',
            'level_name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $marketingLevel = MarketingLevel::create([
                'level_code' => $request->level_code,
                'level_name' => $request->level_name,
                'sort_order' => $request->sort_order ?? MarketingLevel::max('sort_order') + 1,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Marketing level created successfully',
                    'data' => $marketingLevel
                ], 201);
            }

            return redirect()->route('marketing-levels.index')
                ->with('success', 'Marketing level created successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create marketing level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create marketing level: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketingLevel $marketingLevel)
    {
        $marketingLevel->load(['createdBy', 'updatedBy', 'users']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingLevel
            ]);
        }

        return view('finance.marketing-levels.show', compact('marketingLevel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketingLevel $marketingLevel)
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingLevel
            ]);
        }

        return view('finance.marketing-levels.edit', compact('marketingLevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketingLevel $marketingLevel)
    {
        $request->validate([
            'level_code' => 'required|string|max:50|unique:marketing_levels,level_code,' . $marketingLevel->id,
            'level_name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $marketingLevel->update([
                'level_code' => $request->level_code,
                'level_name' => $request->level_name,
                'sort_order' => $request->sort_order ?? $marketingLevel->sort_order,
                'is_active' => $request->is_active ?? $marketingLevel->is_active,
                'description' => $request->description,
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Marketing level updated successfully',
                    'data' => $marketingLevel
                ]);
            }

            return redirect()->route('marketing-levels.index')
                ->with('success', 'Marketing level updated successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update marketing level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update marketing level: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketingLevel $marketingLevel)
    {
        try {
            // Check if marketing level is assigned to users
            if ($marketingLevel->users()->count() > 0) {
                throw new \Exception('Cannot delete marketing level that is assigned to users.');
            }

            $marketingLevel->delete();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Marketing level deleted successfully'
                ]);
            }

            return redirect()->route('marketing-levels.index')
                ->with('success', 'Marketing level deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete marketing level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete marketing level: ' . $e->getMessage());
        }
    }
}
