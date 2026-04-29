<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CrVariable;
use Illuminate\Support\Facades\Auth;

class CrVariableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $crVariables = CrVariable::with(['createdBy', 'updatedBy'])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $crVariables
            ]);
        }

        return view('finance.cr-variables.index', compact('crVariables'));
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

        return view('finance.cr-variables.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cr_days' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean'
        ]);

        try {
            // If setting as default, unset other defaults
            if ($request->is_default) {
                CrVariable::where('is_default', true)->update(['is_default' => false]);
            }

            $crVariable = CrVariable::create([
                'name' => $request->name,
                'cr_days' => $request->cr_days,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'is_default' => $request->is_default ?? false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'CR Variable created successfully',
                    'data' => $crVariable
                ], 201);
            }

            return redirect()->route('finance.cr-variables.index')
                ->with('success', 'CR Variable created successfully.');
        } catch (\Exception $e) {
            \Log::error('CR Variable Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create CR Variable: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create CR Variable: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CrVariable $crVariable)
    {
        $crVariable->load(['createdBy', 'updatedBy']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $crVariable
            ]);
        }

        return view('finance.cr-variables.show', compact('crVariable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CrVariable $crVariable)
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $crVariable
            ]);
        }

        return view('finance.cr-variables.edit', compact('crVariable'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CrVariable $crVariable)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cr_days' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean'
        ]);

        try {
            // If setting as default, unset other defaults
            if ($request->is_default && !$crVariable->is_default) {
                CrVariable::where('is_default', true)->update(['is_default' => false]);
            }

            $crVariable->update([
                'name' => $request->name,
                'cr_days' => $request->cr_days,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $crVariable->is_active,
                'is_default' => $request->is_default ?? $crVariable->is_default,
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'CR Variable updated successfully',
                    'data' => $crVariable
                ]);
            }

            return redirect()->route('finance.cr-variables.index')
                ->with('success', 'CR Variable updated successfully.');
        } catch (\Exception $e) {
            \Log::error('CR Variable Update Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update CR Variable: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update CR Variable: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CrVariable $crVariable)
    {
        try {
            // Check if CR variable is used in calculations
            if ($crVariable->commissionCalculations()->count() > 0) {
                throw new \Exception('Cannot delete CR Variable that is used in commission calculations.');
            }

            // Cannot delete if it's default
            if ($crVariable->is_default) {
                throw new \Exception('Cannot delete default CR Variable. Please set another variable as default first.');
            }

            $crVariable->delete();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'CR Variable deleted successfully'
                ]);
            }

            return redirect()->route('finance.cr-variables.index')
                ->with('success', 'CR Variable deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete CR Variable: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete CR Variable: ' . $e->getMessage());
        }
    }

    /**
     * Set as default CR Variable
     */
    public function setDefault(CrVariable $crVariable)
    {
        try {
            $crVariable->setAsDefault();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'CR Variable set as default successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'CR Variable set as default successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to set default: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to set default: ' . $e->getMessage());
        }
    }
}
