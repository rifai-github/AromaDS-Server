<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class PositionController extends Controller
{
    /**
     * Display a listing of positions
     */
    public function index(Request $request)
    {
        $positionOption = MasterOption::where('name', 'Position')->first();
        
        if (!$positionOption) {
            $positions = new LengthAwarePaginator([], 0, 15);
        } else {
            // Apply AutoFilterable
            $query = OptionDetail::where('master_option_id', $positionOption->id)
                ->with(['createdBy', 'updatedBy']);
            $query->filter($request->all());
            
            $positions = $query->orderBy('option_name')->paginateStd(25);
        }
        
        return view('system.positions.index', compact('positions'));
    }

    /**
     * Show the form for creating a new position
     */
    public function create()
    {
        return view('system.positions.create');
    }

    /**
     * Store a newly created position
     */
    public function store(Request $request)
    {
        $request->validate([
            'option_name' => 'required|string|max:255',
            'option_description' => 'nullable|string|max:500',
        ]);

        $positionOption = MasterOption::where('name', 'Position')->first();
        
        if (!$positionOption) {
            return redirect()->back()->with('error', 'Position master option not found');
        }

        OptionDetail::create([
            'master_option_id' => $positionOption->id,
            'option_name' => $request->option_name,
            'option_description' => $request->option_description,
            'is_active' => true,
        ]);

        return redirect()->route('system.positions.index')->with('success', 'Position created successfully');
    }

    /**
     * Show the form for editing the specified position
     */
    public function edit(OptionDetail $position)
    {
        return view('system.positions.edit', compact('position'));
    }

    /**
     * Update the specified position
     */
    public function update(Request $request, OptionDetail $position)
    {
        $request->validate([
            'option_name' => 'required|string|max:255',
            'option_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $position->update([
            'option_name' => $request->option_name,
            'option_description' => $request->option_description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('system.positions.index')->with('success', 'Position updated successfully');
    }

    /**
     * Remove the specified position
     */
    public function destroy(OptionDetail $position)
    {
        $position->delete();
        return redirect()->route('system.positions.index')->with('success', 'Position deleted successfully');
    }
}
