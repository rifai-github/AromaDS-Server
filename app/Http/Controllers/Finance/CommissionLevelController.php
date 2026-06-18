<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionLevel;
use Illuminate\Support\Facades\Auth;

class CommissionLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $commissionLevels = CommissionLevel::with(['createdBy', 'updatedBy'])
            ->ordered()
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $commissionLevels
            ]);
        }

        return view('finance.commission-levels.index', compact('commissionLevels'));
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

        return view('finance.commission-levels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'target_type' => 'required|in:new,renewal,both',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $commissionLevel = CommissionLevel::create([
                'name' => $request->name,
                'min_percentage' => $request->min_percentage,
                'max_percentage' => $request->max_percentage,
                'commission_rate' => $request->commission_rate,
                'target_type' => $request->target_type,
                'sort_order' => $request->sort_order ?? CommissionLevel::max('sort_order') + 1,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission level created successfully',
                    'data' => $commissionLevel
                ], 201);
            }

            return redirect()->route('commission-levels.index')
                ->with('success', 'Commission level created successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create commission level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create commission level: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CommissionLevel $commissionLevel)
    {
        $commissionLevel->load(['createdBy', 'updatedBy', 'commissionCalculations']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $commissionLevel
            ]);
        }

        return view('finance.commission-levels.show', compact('commissionLevel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommissionLevel $commissionLevel)
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $commissionLevel
            ]);
        }

        return view('finance.commission-levels.edit', compact('commissionLevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CommissionLevel $commissionLevel)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'target_type' => 'required|in:new,renewal,both',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $commissionLevel->update([
                'name' => $request->name,
                'min_percentage' => $request->min_percentage,
                'max_percentage' => $request->max_percentage,
                'commission_rate' => $request->commission_rate,
                'target_type' => $request->target_type,
                'sort_order' => $request->sort_order ?? $commissionLevel->sort_order,
                'is_active' => $request->is_active ?? $commissionLevel->is_active,
                'description' => $request->description,
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission level updated successfully',
                    'data' => $commissionLevel
                ]);
            }

            return redirect()->route('commission-levels.index')
                ->with('success', 'Commission level updated successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update commission level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update commission level: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommissionLevel $commissionLevel)
    {
        try {
            // Check if commission level is used in calculations
            if ($commissionLevel->commissionCalculations()->count() > 0) {
                throw new \Exception('Cannot delete commission level that is used in commission calculations.');
            }

            $commissionLevel->delete();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission level deleted successfully'
                ]);
            }

            return redirect()->route('commission-levels.index')
                ->with('success', 'Commission level deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete commission level: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete commission level: ' . $e->getMessage());
        }
    }
}
