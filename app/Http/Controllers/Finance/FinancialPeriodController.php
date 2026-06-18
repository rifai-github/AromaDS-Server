<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinancialPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialPeriod::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('period_name', 'like', '%' . $search . '%');
        }

        $periods = $query->orderBy('start_date', 'desc')->paginateStd(25);

        // Get filter options
        $statuses = ['open', 'closed', 'locked'];

        return view('finance.financial-periods.index', compact('periods', 'statuses'));
    }

    public function create()
    {
        $statuses = ['open', 'closed', 'locked'];

        return view('finance.financial-periods.create', compact('statuses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:open,closed,locked',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            FinancialPeriod::create([
                'period_name' => $request->period_name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
            ]);

            return redirect()->route('finance.financial-periods.index')
                ->with('success', 'Financial period created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating financial period: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $period = FinancialPeriod::findOrFail($id);

        return view('finance.financial-periods.show', compact('period'));
    }

    public function edit($id)
    {
        $period = FinancialPeriod::findOrFail($id);
        $statuses = ['open', 'closed', 'locked'];

        return view('finance.financial-periods.edit', compact('period', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $period = FinancialPeriod::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'period_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:open,closed,locked',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $period->update([
                'period_name' => $request->period_name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
            ]);

            return redirect()->route('finance.financial-periods.index')
                ->with('success', 'Financial period updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating financial period: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $period = FinancialPeriod::findOrFail($id);
            $period->delete();

            return redirect()->route('finance.financial-periods.index')
                ->with('success', 'Financial period deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting financial period: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:financial_periods,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = FinancialPeriod::whereIn('id', $request->ids)->delete();
            
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

    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,closed,locked',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = FinancialPeriod::findOrFail($id);
            $period->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Period status updated successfully',
                'status' => $period->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating period status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCurrentPeriod()
    {
        $period = FinancialPeriod::current()->first();

        return response()->json($period);
    }

    public function getPeriodByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date',
                'errors' => $validator->errors()
            ], 422);
        }

        $period = FinancialPeriod::byDate($request->date)->first();

        return response()->json($period);
    }

    public function getActivePeriods()
    {
        $periods = FinancialPeriod::where('status', 'open')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($periods);
    }
}
