<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KpiController extends Controller
{
    public function index(Request $request)
    {
        $query = KpiDefinition::with(['creator', 'values']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kpi_name', 'like', "%{$search}%")
                  ->orWhere('kpi_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $kpis = $query->orderBy('created_at', 'desc')->paginateStd(25);

        return view('reports.kpi.index', compact('kpis'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('reports.kpi.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kpi_name' => 'required|string|max:255|unique:kpi_definitions,kpi_name',
            'kpi_description' => 'nullable|string',
            'calculation_formula' => 'required|string',
            'target_value' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $kpi = KpiDefinition::create([
                'kpi_name' => $request->kpi_name,
                'kpi_description' => $request->kpi_description,
                'calculation_formula' => $request->calculation_formula,
                'target_value' => $request->target_value,
                'unit' => $request->unit,
                'frequency' => $request->frequency,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'KPI created successfully',
                    'data' => $kpi
                ]);
            }

            return redirect()->route('reports.kpi.index')
                           ->with('success', 'KPI created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating KPI: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error creating KPI: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function show($id)
    {
        $kpi = KpiDefinition::with(['creator', 'values' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($kpi);
        }

        return view('reports.kpi.show', compact('kpi'));
    }

    public function edit($id)
    {
        $kpi = KpiDefinition::findOrFail($id);
        $users = User::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json($kpi);
        }

        return view('reports.kpi.edit', compact('kpi', 'users'));
    }

    public function update(Request $request, $id)
    {
        $kpi = KpiDefinition::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kpi_name' => 'required|string|max:255|unique:kpi_definitions,kpi_name,' . $id,
            'kpi_description' => 'nullable|string',
            'calculation_formula' => 'required|string',
            'target_value' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $kpi->update([
                'kpi_name' => $request->kpi_name,
                'kpi_description' => $request->kpi_description,
                'calculation_formula' => $request->calculation_formula,
                'target_value' => $request->target_value,
                'unit' => $request->unit,
                'frequency' => $request->frequency,
                'is_active' => $request->is_active ?? $kpi->is_active,
                'updated_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'KPI updated successfully',
                    'data' => $kpi
                ]);
            }

            return redirect()->route('reports.kpi.index')
                           ->with('success', 'KPI updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating KPI: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error updating KPI: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $kpi = KpiDefinition::findOrFail($id);
            $kpi->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'KPI deleted successfully'
                ]);
            }

            return redirect()->route('reports.kpi.index')
                           ->with('success', 'KPI deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting KPI: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting KPI: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:kpi_definitions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = KpiDefinition::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} KPI(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting KPIs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addValue(Request $request, $id)
    {
        $kpi = KpiDefinition::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $kpiValue = KpiValue::create([
                'kpi_id' => $kpi->id,
                'value' => $request->value,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KPI value added successfully',
                'data' => $kpiValue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding KPI value: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getValues($id, Request $request)
    {
        $kpi = KpiDefinition::findOrFail($id);
        
        $query = $kpi->values();

        if ($request->filled('date_from')) {
            $query->whereDate('period_start', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('period_end', '<=', $request->date_to);
        }

        $values = $query->orderBy('created_at', 'desc')->paginateStd(25);

        if ($request->ajax()) {
            return response()->json($values);
        }

        return view('reports.kpi.values', compact('kpi', 'values'));
    }

    public function calculate($id)
    {
        try {
            $kpi = KpiDefinition::findOrFail($id);
            
            // Here you would implement the actual KPI calculation logic
            // based on the calculation_formula
            $calculatedValue = $this->performCalculation($kpi->calculation_formula);
            
            // Create a new KPI value record
            $periodStart = $this->getPeriodStart($kpi->frequency);
            $periodEnd = $this->getPeriodEnd($kpi->frequency);
            
            $kpiValue = KpiValue::create([
                'kpi_id' => $kpi->id,
                'value' => $calculatedValue,
                'period_start' => $periodStart,
                'period_end' => $periodEnd
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KPI calculated successfully',
                'data' => $kpiValue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating KPI: ' . $e->getMessage()
            ], 500);
        }
    }

    private function performCalculation($formula)
    {
        // This is a simplified calculation
        // In a real application, you would parse the formula and execute it
        // For now, we'll return a random value
        return rand(50, 150);
    }

    private function getPeriodStart($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::today();
            case 'weekly':
                return Carbon::now()->startOfWeek();
            case 'monthly':
                return Carbon::now()->startOfMonth();
            case 'quarterly':
                return Carbon::now()->startOfQuarter();
            case 'yearly':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::today();
        }
    }

    private function getPeriodEnd($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::today();
            case 'weekly':
                return Carbon::now()->endOfWeek();
            case 'monthly':
                return Carbon::now()->endOfMonth();
            case 'quarterly':
                return Carbon::now()->endOfQuarter();
            case 'yearly':
                return Carbon::now()->endOfYear();
            default:
                return Carbon::today();
        }
    }
}
