<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customerTypes = CustomerType::with(['createdBy', 'updatedBy'])->orderBy('name')->get();
        return view('system.customer-types.index', compact('customerTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('system.customer-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customer_types,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        CustomerType::create($request->all());

        return redirect()->route('system.customer-types.index')
            ->with('success', 'Customer type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerType $customerType)
    {
        return view('system.customer-types.show', compact('customerType'));
    }

    /**
     * Get customer types for API
     */
    public function getCustomerTypes()
    {
        $customerTypes = CustomerType::active()->orderBy('name')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $customerTypes
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerType $customerType)
    {
        return view('system.customer-types.edit', compact('customerType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerType $customerType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customer_types,name,' . $customerType->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $customerType->update($request->all());

        return redirect()->route('system.customer-types.index')
            ->with('success', 'Customer type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerType $customerType)
    {
        $customerType->delete();

        return redirect()->route('system.customer-types.index')
            ->with('success', 'Customer type deleted successfully.');
    }

}