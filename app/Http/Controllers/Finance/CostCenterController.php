<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CostCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = CostCenter::with(['creator', 'updater']);

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
        }

        $costCenters = $query->orderBy('code')->paginate(15);

        return view('finance.cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        return view('finance.cost-centers.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:cost_centers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            CostCenter::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->has('is_active'),
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('finance.cost-centers.index')
                ->with('success', 'Cost center created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating cost center: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $costCenter = CostCenter::with(['creator', 'updater'])
            ->findOrFail($id);

        return view('finance.cost-centers.show', compact('costCenter'));
    }

    public function edit($id)
    {
        $costCenter = CostCenter::findOrFail($id);

        return view('finance.cost-centers.edit', compact('costCenter'));
    }

    public function update(Request $request, $id)
    {
        $costCenter = CostCenter::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:cost_centers,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $costCenter->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->has('is_active'),
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('finance.cost-centers.index')
                ->with('success', 'Cost center updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating cost center: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $costCenter = CostCenter::findOrFail($id);
            $costCenter->delete();

            return redirect()->route('finance.cost-centers.index')
                ->with('success', 'Cost center deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting cost center: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:cost_centers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = CostCenter::whereIn('id', $request->ids)->delete();
            
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
            $costCenter = CostCenter::findOrFail($id);
            $costCenter->update([
                'is_active' => !$costCenter->is_active,
                'updated_by' => auth()->id(),
            ]);

            $status = $costCenter->is_active ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "Cost center {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating cost center status: ' . $e->getMessage());
        }
    }

    public function getActiveCostCenters()
    {
        $costCenters = CostCenter::where('is_active', true)
            ->orderBy('code')
            ->get();

        return response()->json($costCenters);
    }

    public function getCostCenterByCode($code)
    {
        $costCenter = CostCenter::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$costCenter) {
            return response()->json([
                'success' => false,
                'message' => 'Cost center not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $costCenter
        ]);
    }
}
