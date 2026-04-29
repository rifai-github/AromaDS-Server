<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PriceSlabController extends Controller
{
    public function index()
    {
        return view('company.price-slabs.index');
    }

    public function create()
    {
        return view('company.price-slabs.create');
    }

    public function store(Request $request)
    {
        // Implementation will be added later
    }

    public function show($id)
    {
        return view('company.price-slabs.show', compact('id'));
    }

    public function edit($id)
    {
        return view('company.price-slabs.edit', compact('id'));
    }

    public function update(Request $request, $id)
    {
        // Implementation will be added later
    }

    public function destroy($id)
    {
        // Implementation will be added later
    }
}
