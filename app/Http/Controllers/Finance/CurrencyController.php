<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query();

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');
        }

        $currencies = $query->orderBy('code')->paginateStd(25);

        return view('finance.currencies.index', compact('currencies'));
    }

    public function create()
    {
        return view('finance.currencies.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:3|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            Currency::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'exchange_rate' => $request->exchange_rate,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('finance.currencies.index')
                ->with('success', 'Currency created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating currency: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);

        return view('finance.currencies.show', compact('currency'));
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);

        return view('finance.currencies.edit', compact('currency'));
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:3|unique:currencies,code,' . $id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $currency->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'exchange_rate' => $request->exchange_rate,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('finance.currencies.index')
                ->with('success', 'Currency updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating currency: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->delete();

            return redirect()->route('finance.currencies.index')
                ->with('success', 'Currency deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting currency: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:currencies,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = Currency::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->update([
                'is_active' => !$currency->is_active,
            ]);

            $status = $currency->is_active ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "Currency {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating currency status: ' . $e->getMessage());
        }
    }

    public function updateExchangeRate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid exchange rate',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currency = Currency::findOrFail($id);
            $currency->update([
                'exchange_rate' => $request->exchange_rate,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate updated successfully',
                'exchange_rate' => $currency->exchange_rate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating exchange rate: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getActiveCurrencies()
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get();

        return response()->json($currencies);
    }

    public function getExchangeRates()
    {
        $currencies = Currency::where('is_active', true)
            ->select('code', 'name', 'symbol', 'exchange_rate')
            ->orderBy('code')
            ->get();

        return response()->json($currencies);
    }
}
