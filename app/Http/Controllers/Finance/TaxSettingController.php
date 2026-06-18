<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TaxSetting;
use App\Http\Traits\ColumnFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxSettingController extends Controller
{
    use ColumnFilterTrait;

    /**
     * Display a listing of tax settings
     */
    public function index(Request $request): View
    {
        $query = TaxSetting::with(['createdBy', 'updatedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by tax type
        if ($request->filled('tax_type')) {
            $query->byTaxType($request->tax_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Apply column filters
        $columnMap = [
            'name' => ['column' => 'name'],
            'tax_code' => ['column' => 'tax_code'],
            'tax_type' => ['column' => 'tax_type'],
            'tax_rate' => ['column' => 'tax_rate'],
            'is_default' => ['column' => 'is_default', 'boolean' => true],
            'status' => ['column' => 'status'],
            'effective_date' => ['column' => 'effective_date', 'type' => 'date'],
            'end_date' => ['column' => 'end_date', 'type' => 'date'],
            'calculation_method' => ['column' => 'calculation_method'],
            'creator.name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updater.name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ];

        $this->applyColumnFilters($query, 'tax_settings', $columnMap);

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        if ($sortField !== 'is_default') {
            $query->orderByDesc('is_default');
        }

        $query->orderBy($sortField, $sortDirection);

        $taxSettings = $query->paginateStd(25)->withQueryString();

        return view('finance.tax-settings.index', compact('taxSettings'));
    }

    /**
     * Store a newly created tax setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_code' => 'required|string|max:20|unique:tax_settings,tax_code',
            'tax_type' => 'required|in:income,sales,vat,withholding,other',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'is_default' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'status' => 'required|in:active,inactive',
            'is_compound' => 'boolean',
            'calculation_method' => 'required|in:percentage,fixed,tiered',
            'rounding_method' => 'required|in:nearest,up,down,none',
            'decimal_places' => 'required|integer|min:0|max:4',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0|gte:minimum_amount',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $taxSetting = TaxSetting::create([
                'name' => $request->name,
                'tax_code' => $request->tax_code,
                'tax_type' => $request->tax_type,
                'tax_rate' => $request->tax_rate,
                'is_default' => $request->boolean('is_default'),
                'description' => $request->description,
                'effective_date' => $request->effective_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'is_compound' => $request->boolean('is_compound'),
                'calculation_method' => $request->calculation_method,
                'rounding_method' => $request->rounding_method,
                'decimal_places' => $request->decimal_places,
                'minimum_amount' => $request->minimum_amount,
                'maximum_amount' => $request->maximum_amount,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            $this->syncDefaultTaxSetting($taxSetting, $request->boolean('is_default'));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax setting created successfully.',
                    'data' => $taxSetting->load('createdBy')
                ]);
            }

            return redirect()->route('tax-settings.index')
                ->with('success', 'Tax setting created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create tax setting: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create tax setting: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified tax setting
     */
    public function show(TaxSetting $taxSetting)
    {
        $taxSetting->load(['createdBy', 'updatedBy']);
        
        if (request()->ajax()) {
            // Add accessor data to response
            $taxSettingData = $taxSetting->toArray();
            $taxSettingData['tax_type_label'] = $taxSetting->tax_type_label;
            $taxSettingData['calculation_method_label'] = $taxSetting->calculation_method_label;
            $taxSettingData['rounding_method_label'] = $taxSetting->rounding_method_label;
            $taxSettingData['formatted_tax_rate'] = $taxSetting->formatted_tax_rate;
            $taxSettingData['is_default'] = $taxSetting->is_default;
            $taxSettingData['formatted_effective_date'] = $taxSetting->formatted_effective_date;
            $taxSettingData['formatted_end_date'] = $taxSetting->formatted_end_date;
            $taxSettingData['formatted_created_at'] = $taxSetting->formatted_created_at;
            $taxSettingData['formatted_updated_at'] = $taxSetting->formatted_updated_at;
            $taxSettingData['status_badge'] = $taxSetting->status_badge;
            $taxSettingData['formatted_minimum_amount'] = $taxSetting->formatted_minimum_amount;
            $taxSettingData['formatted_maximum_amount'] = $taxSetting->formatted_maximum_amount;
            
            return response()->json([
                'status' => 'success',
                'data' => $taxSettingData
            ]);
        }
        
        return view('finance.tax-settings.show', compact('taxSetting'));
    }

    /**
     * Show the form for editing the specified tax setting
     */
    public function edit(TaxSetting $taxSetting)
    {
        $taxSetting->load(['createdBy', 'updatedBy']);
        
        if (request()->ajax()) {
            // Add accessor data to response
            $taxSettingData = $taxSetting->toArray();
            $taxSettingData['tax_type_label'] = $taxSetting->tax_type_label;
            $taxSettingData['calculation_method_label'] = $taxSetting->calculation_method_label;
            $taxSettingData['rounding_method_label'] = $taxSetting->rounding_method_label;
            $taxSettingData['formatted_tax_rate'] = $taxSetting->formatted_tax_rate;
            $taxSettingData['is_default'] = $taxSetting->is_default;
            $taxSettingData['formatted_effective_date'] = $taxSetting->formatted_effective_date;
            $taxSettingData['formatted_end_date'] = $taxSetting->formatted_end_date;
            $taxSettingData['formatted_created_at'] = $taxSetting->formatted_created_at;
            $taxSettingData['formatted_updated_at'] = $taxSetting->formatted_updated_at;
            $taxSettingData['status_badge'] = $taxSetting->status_badge;
            $taxSettingData['formatted_minimum_amount'] = $taxSetting->formatted_minimum_amount;
            $taxSettingData['formatted_maximum_amount'] = $taxSetting->formatted_maximum_amount;
            
            return response()->json([
                'status' => 'success',
                'data' => $taxSettingData
            ]);
        }
        
        return view('finance.tax-settings.edit', compact('taxSetting'));
    }

    /**
     * Update the specified tax setting
     */
    public function update(Request $request, TaxSetting $taxSetting)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_code' => 'required|string|max:20|unique:tax_settings,tax_code,' . $taxSetting->id,
            'tax_type' => 'required|in:income,sales,vat,withholding,other',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'is_default' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'status' => 'required|in:active,inactive',
            'is_compound' => 'boolean',
            'calculation_method' => 'required|in:percentage,fixed,tiered',
            'rounding_method' => 'required|in:nearest,up,down,none',
            'decimal_places' => 'required|integer|min:0|max:4',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0|gte:minimum_amount',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $taxSetting->update([
                'name' => $request->name,
                'tax_code' => $request->tax_code,
                'tax_type' => $request->tax_type,
                'tax_rate' => $request->tax_rate,
                'is_default' => $request->boolean('is_default'),
                'description' => $request->description,
                'effective_date' => $request->effective_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'is_compound' => $request->boolean('is_compound'),
                'calculation_method' => $request->calculation_method,
                'rounding_method' => $request->rounding_method,
                'decimal_places' => $request->decimal_places,
                'minimum_amount' => $request->minimum_amount,
                'maximum_amount' => $request->maximum_amount,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            $this->syncDefaultTaxSetting($taxSetting, $request->boolean('is_default'));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax setting updated successfully.',
                    'data' => $taxSetting->load('updatedBy')
                ]);
            }

            return redirect()->route('tax-settings.index')
                ->with('success', 'Tax setting updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update tax setting: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update tax setting: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tax setting
     */
    public function destroy(TaxSetting $taxSetting)
    {
        try {
            if (!$taxSetting->canBeDeleted()) {
                if (request()->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot delete tax setting that is being used by invoices.'
                    ], 422);
                }
                return back()->with('error', 'Cannot delete tax setting that is being used by invoices.');
            }

            $taxSetting->delete();

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax setting deleted successfully.'
                ]);
            }

            return redirect()->route('tax-settings.index')
                ->with('success', 'Tax setting deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete tax setting: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete tax setting: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete tax settings
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_setting_ids' => 'required|array|min:1',
            'tax_setting_ids.*' => 'exists:tax_settings,id'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->tax_setting_ids as $id) {
                $taxSetting = TaxSetting::find($id);
                if ($taxSetting && $taxSetting->canBeDeleted()) {
                    $taxSetting->delete();
                    $deletedCount++;
                } else {
                    $errors[] = "Tax setting {$taxSetting->name} cannot be deleted as it is being used.";
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} tax settings.",
                    'count' => $deletedCount,
                    'errors' => $errors
                ]);
            }

            return back()->with('success', "Successfully deleted {$deletedCount} tax settings.");
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete tax settings: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete tax settings: ' . $e->getMessage());
        }
    }

    /**
     * Activate the specified tax setting
     */
    public function activate(TaxSetting $taxSetting)
    {
        try {
            $taxSetting->update(['status' => 'active', 'updated_by' => Auth::id()]);

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax setting activated successfully.'
                ]);
            }

            return back()->with('success', 'Tax setting activated successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to activate tax setting: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to activate tax setting: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate the specified tax setting
     */
    public function deactivate(TaxSetting $taxSetting)
    {
        try {
            $taxSetting->update(['status' => 'inactive', 'updated_by' => Auth::id()]);

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax setting deactivated successfully.'
                ]);
            }

            return back()->with('success', 'Tax setting deactivated successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to deactivate tax setting: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to deactivate tax setting: ' . $e->getMessage());
        }
    }

    /**
     * Get tax settings statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = [
                'total' => TaxSetting::count(),
                'active' => TaxSetting::active()->count(),
                'inactive' => TaxSetting::inactive()->count(),
                'by_type' => TaxSetting::selectRaw('tax_type, COUNT(*) as count')
                    ->groupBy('tax_type')
                    ->get()
                    ->pluck('count', 'tax_type'),
                'recent' => TaxSetting::where('created_at', '>=', now()->subDays(30))->count()
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tax settings to CSV
     */
    public function export(Request $request)
    {
        $query = TaxSetting::with(['createdBy', 'updatedBy']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('tax_type')) {
            $query->byTaxType($request->tax_type);
        }
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $taxSettings = $query->get();

        $filename = 'tax_settings_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($taxSettings) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Name', 'Tax Code', 'Tax Type', 'Tax Rate (%)', 'Description',
                'Effective Date', 'End Date', 'Status', 'Is Compound', 'Calculation Method',
                'Rounding Method', 'Decimal Places', 'Minimum Amount', 'Maximum Amount',
                'Notes', 'Created By', 'Created At', 'Updated By', 'Updated At'
            ]);

            // CSV Data
            foreach ($taxSettings as $setting) {
                fputcsv($file, [
                    $setting->id,
                    $setting->name,
                    $setting->tax_code,
                    $setting->tax_type_label,
                    $setting->tax_rate,
                    $setting->description,
                    $setting->formatted_effective_date,
                    $setting->formatted_end_date,
                    $setting->status,
                    $setting->is_compound ? 'Yes' : 'No',
                    $setting->calculation_method_label,
                    $setting->rounding_method_label,
                    $setting->decimal_places,
                    $setting->minimum_amount,
                    $setting->maximum_amount,
                    $setting->notes,
                    $setting->createdBy->name ?? '-',
                    $setting->formatted_created_at,
                    $setting->updatedBy->name ?? '-',
                    $setting->formatted_updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function syncDefaultTaxSetting(TaxSetting $taxSetting, bool $shouldBeDefault): void
    {
        if ($taxSetting->tax_type !== 'vat') {
            if ($taxSetting->is_default) {
                $taxSetting->forceFill(['is_default' => false])->saveQuietly();
            }

            return;
        }

        if (!$shouldBeDefault) {
            return;
        }

        TaxSetting::query()
            ->where('id', '!=', $taxSetting->id)
            ->where('tax_type', 'vat')
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);

        if ($taxSetting->tax_type === 'vat' && !$taxSetting->is_default) {
            $taxSetting->forceFill(['is_default' => true])->saveQuietly();
        }
    }
}
