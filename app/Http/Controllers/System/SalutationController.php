<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Http\Traits\ColumnFilterTrait;

class SalutationController extends Controller
{
    use ColumnFilterTrait;

    /**
     * Display a listing of salutations
     */
    public function index(Request $request)
    {
        $salutationOption = MasterOption::where('name', 'Salutation')->first();
        
        if (!$salutationOption) {
            $salutations = collect();
        } else {
            // Build query
            $query = OptionDetail::where('master_option_id', $salutationOption->id);
            
            // Apply column filters
            $this->applyColumnFilters($query, 'salutationsTable', [
                // 0 => No, Checkbox (skip)
                1 => ['column' => 'option_name'],
                2 => ['column' => 'option_description'],
                3 => ['column' => 'is_active', 'boolean' => true],
            ]);
            
            $salutations = $query->with(['createdBy', 'updatedBy'])->orderBy('option_name')->get();
        }
        
        return view('system.salutations.index', compact('salutations'));
    }

    /**
     * Show the form for creating a new salutation
     */
    public function create()
    {
        return view('system.salutations.create');
    }

    /**
     * Store a newly created salutation
     */
    public function store(Request $request)
    {
        $request->validate([
            'option_name' => 'required|string|max:255',
            'option_description' => 'nullable|string|max:500',
            'is_active' => 'nullable|in:0,1',
        ]);

        $salutationOption = MasterOption::where('name', 'Salutation')->first();
        
        if (!$salutationOption) {
            return redirect()->back()->with('error', 'Salutation master option not found');
        }

        try {
            OptionDetail::create([
                'master_option_id' => $salutationOption->id,
                'option_name' => $request->option_name,
                'option_description' => $request->option_description,
                'is_active' => $request->has('is_active') && $request->get('is_active') === '1',
            ]);

            return redirect()->route('system.salutations.index')->with('success', 'Salutation created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating salutation:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return back()->withInput()->with('error', 'Error creating salutation: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified salutation
     */
    public function edit(OptionDetail $salutation)
    {
        return view('system.salutations.edit', compact('salutation'));
    }

    /**
     * Update the specified salutation
     */
    public function update(Request $request, OptionDetail $salutation)
    {
        // Debug: Log the incoming request data
        \Log::info('Update Salutation Request Data:', [
            'all_data' => $request->all(),
            'is_active_checkbox' => $request->has('is_active'),
            'is_active_value' => $request->get('is_active'),
        ]);

        $request->validate([
            'option_name' => 'required|string|max:255|unique:option_details,option_name,' . $salutation->id . ',id,master_option_id,' . $salutation->master_option_id,
            'option_description' => 'nullable|string|max:500',
            'is_active' => 'nullable|in:0,1',
        ]);

        try {
            $salutation->update([
                'option_name' => $request->option_name,
                'option_description' => $request->option_description,
                'is_active' => $request->has('is_active') && $request->get('is_active') === '1',
            ]);

            \Log::info('Salutation updated successfully:', [
                'salutation_id' => $salutation->id,
                'updated_data' => $salutation->toArray()
            ]);

            return redirect()->route('system.salutations.index')->with('success', 'Salutation updated successfully');
        } catch (\Exception $e) {
            \Log::error('Error updating salutation:', [
                'error' => $e->getMessage(),
                'salutation_id' => $salutation->id,
                'request_data' => $request->all()
            ]);

            return back()->withInput()->with('error', 'Error updating salutation: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified salutation
     */
    public function destroy(OptionDetail $salutation)
    {
        $salutation->delete();
        return redirect()->route('system.salutations.index')->with('success', 'Salutation deleted successfully');
    }
}
