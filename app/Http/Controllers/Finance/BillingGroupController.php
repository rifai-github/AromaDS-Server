<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\BillingGroupBuilding;
use App\Models\Contract;
use App\Models\Building;
use App\Models\User;
use App\Models\BankPayment;
use App\Models\CustomerContact;
use App\Services\Finance\VirtualAccountRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class BillingGroupController extends Controller
{
    use AccessControlFilterTrait;

    private function prepareBillingGroupAttributes(array $attributes): array
    {
        static $hasPpnCodeColumn = null;

        $hasPpnCodeColumn ??= Schema::hasColumn('billing_groups', 'ppn_code');

        if (!$hasPpnCodeColumn) {
            unset($attributes['ppn_code']);
        }

        return $attributes;
    }

    public function index(Request $request)
    {
        $query = BillingGroup::with(['contract:id,contract_number,customer_id', 'creator:id,name', 'updater:id,name']);
        $this->applyContractRelatedAccessControlFilter($query, auth()->user());

        // Filter by contract
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filter by billing frequency
        if ($request->filled('billing_frequency')) {
            $query->where('billing_frequency', $request->billing_frequency);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('billing_start_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('billing_group_name', 'like', '%' . $search . '%')
                  ->orWhereHas('contract', function($contractQuery) use ($search) {
                      $contractQuery->where('contract_number', 'like', '%' . $search . '%');
                  });
            });
        }

        $billingGroups = $query->orderBy('billing_start_date', 'desc')->paginate(15);

        // Get filter options - optimized queries
        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'created_by', 'marketing_id')->where('status', 'active'),
            auth()->user()
        )->get();
        $frequencies = ['monthly', 'quarterly', 'yearly', 'one_time'];

        return view('finance.billing-groups.index', compact('billingGroups', 'contracts', 'frequencies'));
    }

    public function create()
    {
        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'created_by', 'marketing_id')->where('status', 'active'),
            auth()->user()
        )->get();
        $frequencies = ['monthly', 'quarterly', 'yearly', 'one_time'];
        $bankPayments = \App\Models\Finance\BankPayment::with('bank')->active()->get();

        return view('finance.billing-groups.addbg', compact('contracts', 'frequencies', 'bankPayments'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billing_group_name' => 'required|string|max:255',
            'contract_id' => 'required|exists:contracts,id',
            'billing_frequency' => 'required|in:monthly,quarterly,yearly,one_time',
            'billing_start_date' => 'required|date',
            'billing_end_date' => 'nullable|date|after:billing_start_date',
            'billing_amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            // PIC Information
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'pic_email' => 'nullable|email|max:255',
            'pic_address' => 'nullable|string',
            // NPWP Information
            // Tax Information (Aligning with update method)
            'npwp' => 'nullable|string|max:30',
            'nitku' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:30',
            'tax_type' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'npwp_address' => 'nullable|string',
            // Invoice Type
            'invoice_type' => 'required|in:hard_copy,soft_copy,both,manual',
            // Payment Method
            'payment_method' => 'nullable|string|max:50',
            'virtual_account_number' => 'nullable|string|max:255', // Removed regex strictness for legacy compat
            'bank_name' => 'nullable|string|max:100',
            'account_name' => 'nullable|string|max:255',
            'account_no' => 'nullable|string|max:50',
            // Buildings
            'buildings' => 'nullable|array',
            'buildings.*.building_id' => 'required_with:buildings|exists:buildings,id',
            'buildings.*.billing_amount' => 'nullable|numeric|min:0',
            'buildings.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $canUseContract = $this->applyContractAccessControlFilter(Contract::query(), auth()->user())
            ->whereKey($request->contract_id)
            ->exists();

        if (!$canUseContract) {
            return redirect()->back()
                ->with('error', 'Contract is outside your accessible data scope.')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Auto-generate VA number if not provided
            $vaNumber = $request->virtual_account_number;
            // Removed strict VA generation logic for now to rely on manual/auto-fill input
            // if (empty($vaNumber) && $request->payment_method === 'va_bca') {
            //     $vaNumber = $this->getNextVirtualAccountNumber();
            // }

            // Build combined tax_type and tax_number (logic from update method)
            $npwp = $request->npwp;
            $nitku = $request->nitku;
            $nik = $request->nik;
            
            $taxTypes = [];
            $taxNumbers = [];
            
            if ($npwp) {
                $taxTypes[] = 'NPWP';
                $taxNumbers[] = $npwp;
            }
            if ($nitku) {
                $taxTypes[] = 'NITKU';
                $taxNumbers[] = $nitku;
            }
            if ($nik) {
                $taxTypes[] = 'NIK';
                $taxNumbers[] = $nik;
            }
            
            // Allow manual override if tax_type/tax_number are explicitly provided
            $taxType = !empty($taxTypes) ? implode(', ', $taxTypes) : ($request->tax_type ?? null);
            $taxNumber = !empty($taxNumbers) ? implode(', ', $taxNumbers) : ($request->tax_number ?? $request->npwp_number ?? null);

            $billingGroup = BillingGroup::create($this->prepareBillingGroupAttributes([
                'billing_group_name' => $request->billing_group_name,
                'contract_id' => $request->contract_id,
                'billing_frequency' => $request->billing_frequency,
                'billing_start_date' => $request->billing_start_date,
                'billing_end_date' => $request->billing_end_date,
                'billing_amount' => $request->billing_amount,
                'is_active' => $request->has('is_active'),
                'pic_name' => $request->pic_name,
                'pic_phone' => $request->pic_phone,
                'pic_email' => $request->pic_email,
                'pic_address' => $request->pic_address,
                'npwp_number' => $request->npwp_number ?? $npwp, // Keep legacy populated if possible
                'npwp_name' => $request->npwp_name,
                'npwp_address' => $request->npwp_address,
                'npwp' => $npwp,
                'nitku' => $nitku,
                'nik' => $nik,
                'tax_type' => $taxType,
                'tax_number' => $taxNumber,
                'ppn_code' => $request->ppn_code,
                'invoice_type' => $request->invoice_type,
                'payment_method' => $request->payment_method, // This will store 'va_bca', 'transfer' etc.
                'virtual_account_number' => $vaNumber,
                'bank_name' => $request->bank_name,
                'created_by' => auth()->id(),
            ]));

            // Handle building assignments
            if ($request->has('buildings')) {
                foreach ($request->buildings as $buildingData) {
                    BillingGroupBuilding::create([
                        'billing_group_id' => $billingGroup->id,
                        'building_id' => $buildingData['building_id'],
                        'billing_amount' => $buildingData['billing_amount'] ?? $billingGroup->billing_amount,
                        'notes' => $buildingData['notes'] ?? null,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('finance.billing-groups.index')
                ->with('success', 'Billing group created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error creating billing group: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $billingGroup = $this->applyContractRelatedAccessControlFilter(
            BillingGroup::with(['contract', 'creator', 'updater', 'buildings']),
            auth()->user()
        )->findOrFail($id);

        // Return JSON for AJAX/API requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $billingGroup
            ]);
        }

        return view('finance.billing-groups.show', compact('billingGroup'));
    }

    public function edit($id)
    {
        $billingGroup = $this->applyContractRelatedAccessControlFilter(BillingGroup::query(), auth()->user())
            ->findOrFail($id);
        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'created_by', 'marketing_id')->where('status', 'active'),
            auth()->user()
        )->get();
        $frequencies = ['monthly', 'quarterly', 'yearly', 'one_time'];
        $bankPayments = \App\Models\Finance\BankPayment::with('bank')->active()->get();

        return view('finance.billing-groups.editbg', compact('billingGroup', 'contracts', 'frequencies', 'bankPayments'));
    }

    public function update(Request $request, $id)
    {
        $billingGroup = $this->applyContractRelatedAccessControlFilter(BillingGroup::query(), auth()->user())
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            // PIC Information
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'pic_email' => 'nullable|email|max:255',
            // Tax Information (NEW: individual fields)
            'npwp' => 'nullable|string|max:30',
            'nitku' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:30',
            'tax_type' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'npwp_address' => 'nullable|string',
            // Invoice Type
            'invoice_type' => 'nullable|in:hard_copy,soft_copy,both,manual',
            // Payment Method
            'payment_method' => 'nullable|string|max:50',
            'virtual_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:100',
            'building_ids' => 'nullable|array',
            'building_ids.*' => 'exists:buildings,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Build combined tax_type and tax_number from individual fields
            $npwp = $request->npwp;
            $nitku = $request->nitku;
            $nik = $request->nik;
            
            $taxTypes = [];
            $taxNumbers = [];
            
            if ($npwp) {
                $taxTypes[] = 'NPWP';
                $taxNumbers[] = $npwp;
            }
            if ($nitku) {
                $taxTypes[] = 'NITKU';
                $taxNumbers[] = $nitku;
            }
            if ($nik) {
                $taxTypes[] = 'NIK';
                $taxNumbers[] = $nik;
            }
            
            $taxType = !empty($taxTypes) ? implode(', ', $taxTypes) : ($request->tax_type ?? $billingGroup->tax_type);
            $taxNumber = !empty($taxNumbers) ? implode(', ', $taxNumbers) : ($request->tax_number ?? $billingGroup->tax_number);
            
            $billingGroup->update([
                // PIC Information
                'pic_name' => $request->pic_name,
                'pic_phone' => $request->pic_phone,
                'pic_email' => $request->pic_email,
                // Tax Information (built from individual fields)
                'tax_type' => $taxType,
                'tax_number' => $taxNumber,
                'npwp' => $npwp,
                'nitku' => $nitku,
                'nik' => $nik,
                'npwp_address' => $request->npwp_address,
                // Invoice Type
                'invoice_type' => $request->invoice_type ?? $billingGroup->invoice_type ?? 'soft_copy',
                // Payment Method
                'bank_name' => $request->bank_name,
                'payment_method' => $request->payment_method,
                'virtual_account_number' => $request->virtual_account_number,
                'updated_by' => auth()->id(),
            ]);

            // Sync building assignments if provided
            if ($request->has('building_ids')) {
                $this->syncBuildingAssignments($billingGroup, $request->building_ids);
            }

            // Get contract_id from request or billing group
            $contractId = $request->contract_id ?? $billingGroup->contract_id;

            return redirect()->route('marketing.contracts.show', $contractId)
                ->with('success', 'Billing group updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating billing group: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $billingGroup = BillingGroup::findOrFail($id);

                // Delete all active building assignments first. The pivot model uses
                // SoftDeletes, so stale assignments are hidden and can be restored later.
                $billingGroup->billingGroupBuildings()->delete();

                $billingGroup->delete();
            });

            // Return JSON for AJAX/API requests
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Billing group deleted successfully'
                ]);
            }

            return redirect()->route('finance.billing-groups.index')
                ->with('success', 'Billing group deleted successfully.');
        } catch (\Exception $e) {
            // Return JSON for AJAX/API requests
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error deleting billing group: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error deleting billing group: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:billing_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = BillingGroup::whereIn('id', $request->ids)->delete();
            
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
            $billingGroup = BillingGroup::findOrFail($id);
            $billingGroup->update([
                'is_active' => !$billingGroup->is_active,
                'updated_by' => auth()->id(),
            ]);

            $status = $billingGroup->is_active ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "Billing group {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating billing group status: ' . $e->getMessage());
        }
    }

    public function generateInvoices($id)
    {
        try {
            $billingGroup = BillingGroup::with('contract')->findOrFail($id);
            
            // This would typically generate invoices based on billing frequency
            // For now, just return a success message
            return redirect()->back()
                ->with('success', 'Invoice generation process started for billing group: ' . $billingGroup->billing_group_name);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error generating invoices: ' . $e->getMessage());
        }
    }

    // Building Management Methods
    public function getBuildings($id)
    {
        $billingGroup = BillingGroup::with(['buildings', 'billingGroupBuildings.building'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $billingGroup->billingGroupBuildings
        ]);
    }

    public function assignBuilding(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'building_id' => 'required|exists:buildings,id',
            'billing_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $billingGroup = BillingGroup::with('contract')->findOrFail($id);
            
            // Check if building is already assigned to this billing group.
            // Include trashed rows so a re-assignment restores the old pivot instead
            // of creating a duplicate row for the same building.
            $existing = BillingGroupBuilding::withTrashed()
                ->where('billing_group_id', $id)
                ->where('building_id', $request->building_id)
                ->first();

            if ($existing && ! $existing->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Building is already assigned to this billing group'
                ], 400);
            }

            // IMPORTANT: Check if building is already assigned to another billing group in the same contract
            $contractId = $billingGroup->contract_id;
            $otherBillingGroups = BillingGroup::where('contract_id', $contractId)
                ->where('id', '!=', $id)
                ->pluck('id');
            
            $duplicateAssignment = BillingGroupBuilding::whereIn('billing_group_id', $otherBillingGroups)
                ->where('building_id', $request->building_id)
                ->with('billingGroup')
                ->first();

            if ($duplicateAssignment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Building is already assigned to another billing group: ' . $duplicateAssignment->billingGroup->billing_group_name
                ], 400);
            }

            if ($existing && $existing->trashed()) {
                $existing->restore();
                $existing->update([
                    'billing_amount' => $request->billing_amount ?? $existing->billing_amount ?? $billingGroup->billing_amount,
                    'notes' => $request->notes ?? $existing->notes,
                    'is_active' => true,
                    'updated_by' => auth()->id(),
                ]);
                $billingGroupBuilding = $existing;
            } else {
                $billingGroupBuilding = BillingGroupBuilding::create([
                    'billing_group_id' => $id,
                    'building_id' => $request->building_id,
                    'billing_amount' => $request->billing_amount ?? $billingGroup->billing_amount,
                    'notes' => $request->notes,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Building assigned successfully',
                'data' => $billingGroupBuilding->load('building')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning building: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeBuilding($id, $buildingId)
    {
        try {
            $billingGroupBuilding = BillingGroupBuilding::where('billing_group_id', $id)
                ->where('building_id', $buildingId)
                ->firstOrFail();

            $billingGroupBuilding->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Building removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing building: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateBuildingAssignment(Request $request, $id, $buildingId)
    {
        $validator = Validator::make($request->all(), [
            'billing_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $billingGroupBuilding = BillingGroupBuilding::where('billing_group_id', $id)
                ->where('building_id', $buildingId)
                ->firstOrFail();

            $billingGroupBuilding->update([
                'billing_amount' => $request->billing_amount ?? $billingGroupBuilding->billing_amount,
                'notes' => $request->notes ?? $billingGroupBuilding->notes,
                'is_active' => $request->has('is_active') ? $request->is_active : $billingGroupBuilding->is_active,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Building assignment updated successfully',
                'data' => $billingGroupBuilding->load('building')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating building assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    // VA Generation and Validation Methods
    public function generateVirtualAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $companyCode = $request->company_code ?? '88997'; // Default company code
        $nextVaNumber = $this->getNextVirtualAccountNumber($companyCode);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Virtual Account number generated successfully',
            'data' => [
                'full_va_number' => $nextVaNumber,
                'company_code' => $companyCode,
                'free_digits' => substr($nextVaNumber, 5, 6),
                'total_digits' => strlen($nextVaNumber)
            ]
        ]);
    }

    public function validateVirtualAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'virtual_account_number' => 'required|string|size:11|regex:/^[0-9]{11}$/',
            'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vaNumber = $request->virtual_account_number;
        $companyCode = $request->company_code ?? '88997'; // Default company code
        
        // Check if VA number starts with company code
        if (!str_starts_with($vaNumber, $companyCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Virtual Account number must start with company code ' . $companyCode
            ], 422);
        }

        // Check if VA number already exists
        $existingVa = BillingGroup::where('virtual_account_number', $vaNumber)->first();
        if ($existingVa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Virtual Account number already exists'
            ], 422);
        }

        // Extract the 6 free digits (last 6 digits)
        $freeDigits = substr($vaNumber, 5, 6);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Virtual Account number is valid and available',
            'data' => [
                'full_va_number' => $vaNumber,
                'company_code' => $companyCode,
                'free_digits' => $freeDigits,
                'total_digits' => strlen($vaNumber),
                'is_available' => true
            ]
        ]);
    }

    private function getNextVirtualAccountNumber($companyCode = '88997')
    {
        // Get the highest existing VA number for this company code
        $lastVa = BillingGroup::where('virtual_account_number', 'like', $companyCode . '%')
            ->orderBy('virtual_account_number', 'desc')
            ->first();

        if (!$lastVa) {
            // No existing VA numbers, start with 000001
            return $companyCode . '000001';
        }

        // Extract the 6-digit sequence from the last VA number
        $lastSequence = substr($lastVa->virtual_account_number, 5, 6);
        $nextSequence = str_pad((int)$lastSequence + 1, 6, '0', STR_PAD_LEFT);
        
        return $companyCode . $nextSequence;
    }

    public function getVirtualAccountList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $companyCode = $request->company_code ?? '88997';
        $limit = $request->limit ?? 20;

        $vaNumbers = BillingGroup::where('virtual_account_number', 'like', $companyCode . '%')
            ->whereNotNull('virtual_account_number')
            ->orderBy('virtual_account_number', 'desc')
            ->limit($limit)
            ->get(['id', 'billing_group_name', 'virtual_account_number', 'payment_method', 'bank_name', 'created_at']);

        $nextVaNumber = $this->getNextVirtualAccountNumber($companyCode);

        return response()->json([
            'status' => 'success',
            'data' => [
                'company_code' => $companyCode,
                'next_va_number' => $nextVaNumber,
                'used_va_numbers' => $vaNumbers,
                'total_used' => $vaNumbers->count(),
                'next_sequence' => substr($nextVaNumber, 5, 6)
            ]
        ]);
    }

    // TOP Synchronization Methods
    public function validateTopSynchronization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billing_frequency' => 'required|in:monthly,quarterly,yearly,one_time',
            'service_frequency' => 'required|integer|min:1',
            'term_of_payment' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $billingFrequency = $request->billing_frequency;
        $serviceFrequency = $request->service_frequency;
        $termOfPayment = $request->term_of_payment;

        $isValid = $this->checkTopSynchronization($billingFrequency, $serviceFrequency, $termOfPayment);

        return response()->json([
            'status' => $isValid ? 'success' : 'error',
            'message' => $isValid ? 'TOP synchronization is valid' : 'TOP synchronization is invalid',
            'data' => [
                'billing_frequency' => $billingFrequency,
                'service_frequency' => $serviceFrequency,
                'term_of_payment' => $termOfPayment,
                'is_valid' => $isValid,
                'recommendation' => $this->getTopRecommendation($billingFrequency, $serviceFrequency)
            ]
        ]);
    }

    private function checkTopSynchronization($billingFrequency, $serviceFrequency, $termOfPayment)
    {
        // Convert billing frequency to months
        $billingMonths = $this->getBillingFrequencyInMonths($billingFrequency);
        
        // Calculate service interval in months
        $serviceIntervalMonths = $billingMonths / $serviceFrequency;
        
        // TOP should not be longer than service interval
        return $termOfPayment <= $serviceIntervalMonths;
    }

    private function getBillingFrequencyInMonths($billingFrequency)
    {
        switch ($billingFrequency) {
            case 'monthly':
                return 1;
            case 'quarterly':
                return 3;
            case 'yearly':
                return 12;
            case 'one_time':
                return 1;
            default:
                return 1;
        }
    }

    private function getTopRecommendation($billingFrequency, $serviceFrequency)
    {
        $billingMonths = $this->getBillingFrequencyInMonths($billingFrequency);
        $serviceIntervalMonths = $billingMonths / $serviceFrequency;
        
        return [
            'max_term_of_payment' => floor($serviceIntervalMonths),
            'recommended_term_of_payment' => max(1, floor($serviceIntervalMonths / 2)),
            'service_interval_months' => $serviceIntervalMonths
        ];
    }

    // Invoice Generation Methods
    public function generateInvoicesForPeriod(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'force_generate' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $billingGroup = BillingGroup::with(['buildings', 'contract'])->findOrFail($id);
            
            // Check if invoices already exist for this period
            $existingInvoices = $billingGroup->invoices()
                ->whereBetween('invoice_date', [$request->period_start, $request->period_end])
                ->count();

            if ($existingInvoices > 0 && !$request->force_generate) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invoices already exist for this period',
                    'data' => [
                        'existing_count' => $existingInvoices,
                        'period_start' => $request->period_start,
                        'period_end' => $request->period_end
                    ]
                ]);
            }

            // Generate invoices based on billing frequency and buildings
            $generatedInvoices = $this->processInvoiceGeneration($billingGroup, $request->period_start, $request->period_end);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoices generated successfully',
                'data' => [
                    'generated_count' => $generatedInvoices,
                    'period_start' => $request->period_start,
                    'period_end' => $request->period_end,
                    'billing_group' => $billingGroup->billing_group_name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processInvoiceGeneration($billingGroup, $periodStart, $periodEnd)
    {
        $generatedCount = 0;
        
        // Logic to generate invoices based on billing frequency
        // This would typically involve:
        // 1. Calculate billing periods within the date range
        // 2. Generate invoice for each building in the billing group
        // 3. Set appropriate amounts based on building assignments
        // 4. Create invoice records
        
        // For now, return a placeholder
        return $generatedCount;
    }

    /**
     * Generate VA number following 6-digit rule (VA Rule Enhancement)
     */
    public function generateVirtualAccountNumber(Request $request)
    {
        try {
            $request->validate([
                'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/'
            ]);

            $vaRuleService = new VirtualAccountRuleService();
            $result = $vaRuleService->generateVirtualAccountNumber($request->company_code);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate VA number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate VA number following 6-digit rule
     */
    public function validateVirtualAccountNumber(Request $request)
    {
        try {
            $request->validate([
                'virtual_account_number' => 'required|string|size:11|regex:/^[0-9]{11}$/'
            ]);

            $vaRuleService = new VirtualAccountRuleService();
            $result = $vaRuleService->validateVirtualAccountNumber($request->virtual_account_number);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR',
                    'data' => $result['existing_data'] ?? null
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate VA number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available VA numbers
     */
    public function getAvailableVirtualAccounts(Request $request)
    {
        try {
            $request->validate([
                'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $vaRuleService = new VirtualAccountRuleService();
            $result = $vaRuleService->getAvailableVirtualAccounts(
                $request->company_code,
                $request->limit ?? 10
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'count' => $result['count']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get available VA numbers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get VA statistics
     */
    public function getVirtualAccountStatistics(Request $request)
    {
        try {
            $request->validate([
                'company_code' => 'nullable|string|size:5|regex:/^[0-9]{5}$/'
            ]);

            $vaRuleService = new VirtualAccountRuleService();
            $result = $vaRuleService->getVirtualAccountStatistics($request->company_code);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get VA statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reserve VA number
     */
    public function reserveVirtualAccountNumber(Request $request)
    {
        try {
            $request->validate([
                'virtual_account_number' => 'required|string|size:11|regex:/^[0-9]{11}$/',
                'reserved_by' => 'nullable|string'
            ]);

            $vaRuleService = new VirtualAccountRuleService();
            $result = $vaRuleService->reserveVirtualAccountNumber(
                $request->virtual_account_number,
                $request->reserved_by
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reserve VA number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show create form for specific contract
     */
    public function createForContract(Contract $contract)
    {
        // Get existing billing groups from same customer for reuse option
        $existingBillingGroups = BillingGroup::whereHas('contract', function($query) use ($contract) {
            $query->where('customer_id', $contract->customer_id);
        })->where('is_active', true)->get();
        
        // Get active bank payments
        $bankPayments = BankPayment::with('bank')->active()->get();

        // Get customer contacts for PIC selection (Multi PIC support)
        // We load them through the customer relationships later
            
        // Get all active buildings linked to the contract through rooms or surveys.
        $assignedBuildingIds = $this->getAssignedBuildingIdsForContract($contract);
        $buildings = $this->getContractBuildings($contract)->filter(function($b) use ($assignedBuildingIds) {
            return !in_array($b->id, $assignedBuildingIds);
        });

        // Load tax settings, default bank payment, and all contacts
        $contract->customer->load(['customerTaxSettings', 'defaultBankPayment', 'customerContacts', 'contacts']);
        
        return view('finance.billing-groups.addbg', compact('contract', 'existingBillingGroups', 'bankPayments', 'buildings'));
    }
    
    /**
     * Store billing group for specific contract
     */
    public function storeForContract(Request $request, Contract $contract)
    {
        // Check if using existing billing group
        if ($request->use_existing && $request->existing_billing_group_id) {
            $validator = Validator::make($request->all(), [
                'existing_billing_group_id' => 'required|exists:billing_groups,id',
                'building_ids' => 'required|array',
                'building_ids.*' => 'exists:buildings,id'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'building_ids' => 'required|array',
                'building_ids.*' => 'exists:buildings,id',
                // PIC Information
                'pic_name' => 'nullable|string|max:255',
                'pic_phone' => 'nullable|string|max:20',
                'pic_email' => 'nullable|email|max:255',
                'pic_address' => 'nullable|string',
                // NPWP Information
                'npwp_number' => 'nullable|string|max:255',
                'npwp_name' => 'nullable|string|max:255',
                'npwp_address' => 'nullable|string',
                // Tax Information
                'tax_type' => 'nullable|in:NPWP,NIK,NITKU',
                'tax_number' => 'nullable|string|max:50',
                // Invoice Type
                'invoice_type' => 'nullable|in:hard_copy,soft_copy,both,manual',
                // Payment Method
                'payment_method' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:100',
                // Billing period validation removed as it is now auto-populated from contract
            ]);
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            if ($request->use_existing && $request->existing_billing_group_id) {
                // Link existing billing group (create copy for this contract)
                $existingBg = BillingGroup::findOrFail($request->existing_billing_group_id);
                
                $billingGroup = BillingGroup::create([
                    'billing_group_name' => $existingBg->billing_group_name,
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'billing_frequency' => $existingBg->billing_frequency,
                    'billing_start_date' => $existingBg->billing_start_date,
                    'billing_end_date' => $existingBg->billing_end_date,
                    'billing_amount' => $existingBg->billing_amount,
                    'is_active' => $existingBg->is_active,
                    // PIC Information
                    'pic_name' => $existingBg->pic_name,
                    'pic_phone' => $existingBg->pic_phone,
                    'pic_email' => $existingBg->pic_email,
                    'pic_address' => $existingBg->pic_address,
                    // NPWP Information
                    'npwp_number' => $existingBg->npwp_number,
                    'npwp_name' => $existingBg->npwp_name,
                    'npwp_address' => $existingBg->npwp_address,
                    // Tax Information
                    'tax_type' => $existingBg->tax_type,
                    'tax_number' => $existingBg->tax_number,
                    // Invoice Type
                    'invoice_type' => $existingBg->invoice_type,
                    // Payment Method
                    'payment_method' => $existingBg->payment_method,
                    'virtual_account_number' => $existingBg->virtual_account_number,
                    'bank_name' => $existingBg->bank_name,
                    'created_by' => auth()->id() ?? 1,
                ]);
            } else {
                // Generate billing group name if not provided
                $billingGroupName = 'Billing Group ' . ($contract->billingGroups()->count() + 1);
                
                // Create new billing group
                // Auto-populate billing period from contract
                $billingFrequency = 'monthly'; // Default
                if ($contract->term_of_payment) {
                    $top = strtolower($contract->term_of_payment);
                    if (str_contains($top, 'triwulan') || str_contains($top, 'quarter') || str_contains($top, '3 bulan')) {
                        $billingFrequency = 'quarterly';
                    } elseif (str_contains($top, 'semester') || str_contains($top, '6 bulan')) {
                        $billingFrequency = 'semi_annually'; // Check if this is valid enum, otherwise fallback to monthly or closest valid
                    } elseif (str_contains($top, 'tahun') || str_contains($top, 'annual') || str_contains($top, 'year') || str_contains($top, '12 bulan')) {
                        $billingFrequency = 'yearly';
                    }
                }

                // Process Tax ID (Multi-select)
                $taxType = null;
                $taxNumber = null;
                
                if ($request->has('tax_id') && $request->tax_id) {
                    $taxIds = explode(',', $request->tax_id);
                    $taxTypes = [];
                    $taxNumbers = [];
                    $customer = $contract->customer;
                    // Load tax settings if not loaded
                    if (!$customer->relationLoaded('customerTaxSettings')) {
                        $customer->load('customerTaxSettings');
                    }

                    foreach ($taxIds as $taxId) {
                        $taxId = trim($taxId);
                        
                        if ($taxId === 'legacy_npwp' && $customer->npwp) {
                            $taxTypes[] = 'NPWP';
                            $taxNumbers[] = $customer->npwp;
                        } elseif ($taxId === 'legacy_nik' && $customer->nik) {
                            $taxTypes[] = 'NIK';
                            $taxNumbers[] = $customer->nik;
                        } elseif ($taxId === 'legacy_nitku' && $customer->nitku) {
                            $taxTypes[] = 'NITKU';
                            $taxNumbers[] = $customer->nitku;
                        } elseif (str_starts_with($taxId, 'tax_setting_')) {
                            $settingId = str_replace('tax_setting_', '', $taxId);
                            $taxSetting = \App\Models\CustomerTax::find($settingId);
                            if ($taxSetting) {
                                $taxTypes[] = strtoupper($taxSetting->tax_type);
                                $taxNumbers[] = $taxSetting->tax_number;
                                if ($taxSetting->ppn_code && !isset($taxPpnCode)) {
                                    $taxPpnCode = $taxSetting->ppn_code;
                                }
                            }
                        } elseif ($taxId === 'npwp' && $customer->npwp) { // Backward compatibility
                            $taxTypes[] = 'NPWP';
                            $taxNumbers[] = $customer->npwp;
                        } elseif ($taxId === 'nik' && $customer->nik) { // Backward compatibility
                            $taxTypes[] = 'NIK';
                            $taxNumbers[] = $customer->nik;
                        } elseif ($taxId === 'nitku' && $customer->nitku) { // Backward compatibility
                            $taxTypes[] = 'NITKU';
                            $taxNumbers[] = $customer->nitku;
                        } elseif ($request->tax_type && $request->tax_number) {
                            // Fallback to manual input if no specific ID matched but inputs exist
                            $taxTypes[] = $request->tax_type;
                            $taxNumbers[] = $request->tax_number;
                        }
                    }
                    
                    $taxType = !empty($taxTypes) ? implode(', ', array_unique($taxTypes)) : ($request->tax_type ?? null);
                    $taxNumber = !empty($taxNumbers) ? implode(', ', $taxNumbers) : ($request->tax_number ?? null);
                    $ppnCode = $taxPpnCode ?? $request->ppn_code ?? null;
                } else {
                    // Fallback to manual inputs
                    $taxType = $request->tax_type;
                    $taxNumber = $request->tax_number;
                    $ppnCode = $request->ppn_code;
                }

                $billingGroup = BillingGroup::create($this->prepareBillingGroupAttributes([
                    'billing_group_name' => $contract->contract_number . ' - ' . ($request->pic_name ?? 'BG'),
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'billing_frequency' => $billingFrequency,
                    'billing_start_date' => $contract->start_date,
                    'billing_end_date' => $contract->end_date,
                    'billing_amount' => 0, // Should be calculated or validated elsewhere?
                    'is_active' => true,
                    // PIC Information
                    'pic_name' => $request->pic_name,
                    'pic_phone' => $request->pic_phone,
                    'pic_email' => $request->pic_email,
                    'pic_address' => $request->pic_address,
                    // NPWP Information
                    'npwp_number' => $request->npwp_number, // Not in form yet but good to have
                    'npwp_name' => $request->npwp_name,
                    'npwp_address' => $request->npwp_address,
                    // Tax Information (Multi-select)
                    'tax_type' => $taxType,
                    'tax_number' => $taxNumber,
                    'npwp' => !empty($taxNumbers) ? ($taxNumbers[0] ?? null) : null, // Simple fallback for first number
                    'nitku' => (isset($taxMap['NITKU'])) ? $taxMap['NITKU'] : null,
                    'nik' => (isset($taxMap['NIK'])) ? $taxMap['NIK'] : null,
                    'ppn_code' => $ppnCode,
                    // Invoice Type
                    'invoice_type' => $request->invoice_type ?? 'soft_copy',
                    // Payment Method
                    'payment_method' => $request->payment_method_type, // Storing 'transfer', 'va' etc. from metadata
                    'virtual_account_number' => $request->virtual_account_number,
                    'bank_name' => $request->bank_name,
                    'created_by' => auth()->id() ?? 1,
                ]));
                
            }

            $this->syncBuildingAssignments($billingGroup, $request->building_ids ?? []);

            DB::commit();

            return redirect()->route('marketing.contracts.show', $contract->id)
                ->with('success', 'Billing group created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error creating billing group: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show edit form for billing group from contract
     */
    public function editForContract(Contract $contract, BillingGroup $billingGroup)
    {
        // Load customer tax settings and all types of contacts for the dropdown
        $contract->customer->load(['customerTaxSettings', 'customerContacts', 'contacts', 'defaultBankPayment']);
        
        // Get active bank payments
        $bankPayments = BankPayment::with('bank')->active()->get();

        // Get buildings already assigned to OTHER active billing groups for this contract.
        $otherAssignedBuildingIds = $this->getAssignedBuildingIdsForContract($contract, $billingGroup->id);

        return view('finance.billing-groups.editbg', compact('contract', 'billingGroup', 'bankPayments', 'otherAssignedBuildingIds'));
    }

    /**
     * Link existing billing group to contract
     */
    public function linkExistingToContract(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'billing_group_id' => 'required|exists:billing_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $billingGroup = BillingGroup::findOrFail($request->billing_group_id);

            // Verify that billing group belongs to the same customer
            if ($billingGroup->contract->customer_id !== $contract->customer_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Billing group belongs to a different customer'
                ], 400);
            }

            // Create a copy/link of the billing group for this contract
            // (In this implementation, we create a new billing group with same settings)
            $newBillingGroup = BillingGroup::create([
                'billing_group_name' => $billingGroup->billing_group_name,
                'contract_id' => $contract->id,
                'billing_frequency' => $billingGroup->billing_frequency,
                'billing_start_date' => $billingGroup->billing_start_date,
                'billing_end_date' => $billingGroup->billing_end_date,
                'billing_amount' => $billingGroup->billing_amount,
                'is_active' => $billingGroup->is_active,
                'created_by' => auth()->id() ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Billing group linked successfully',
                'data' => $newBillingGroup->load(['contract', 'buildings'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error linking billing group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get building coverage for a contract
     */
    public function getBuildingCoverage(Contract $contract)
    {
        try {
            // Get all buildings from contract rooms and contract surveys.
            $allBuildingIds = $this->getContractBuildings($contract)
                ->pluck('id')
                ->values()
                ->toArray();
            
            $totalBuildings = count($allBuildingIds);

            // Get assigned building IDs from active billing groups and active pivots.
            $assignedBuildingIds = $this->getAssignedBuildingIdsForContract($contract);
            
            $assignedBuildings = count($assignedBuildingIds);

            // Get unassigned buildings
            $unassignedBuildingIds = array_diff($allBuildingIds, $assignedBuildingIds);
            $unassignedBuildings = Building::whereIn('id', $unassignedBuildingIds)
                ->get(['id', 'nama_gedung', 'name'])
                ->map(function($building) {
                    return [
                        'id' => $building->id,
                        'name' => $building->nama_gedung ?? $building->name ?? 'Building #' . $building->id
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_buildings' => $totalBuildings,
                    'assigned_buildings' => $assignedBuildings,
                    'unassigned_buildings' => $unassignedBuildings->values(),
                    'coverage_percentage' => $totalBuildings > 0 ? round(($assignedBuildings / $totalBuildings) * 100) : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting building coverage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get buildings for a billing group (available vs assigned)
     */
    public function getBuildingsForBillingGroup(Contract $contract, BillingGroup $billingGroup)
    {
        try {
            if ((int) $billingGroup->contract_id !== (int) $contract->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Billing group does not belong to this contract'
                ], 404);
            }

            // Get all buildings from contract rooms and contract surveys.
            $contractBuildingIds = $this->getContractBuildings($contract)
                ->pluck('id')
                ->values()
                ->toArray();

            // Get buildings assigned to THIS billing group
            $assignedBuildings = BillingGroupBuilding::where('billing_group_id', $billingGroup->id)
                ->with('building')
                ->get()
                ->map(function($bgBuilding) {
                    $building = $bgBuilding->building;
                    return [
                        'id' => $building->id,
                        'name' => $building->nama_gedung ?? $building->name ?? 'Building #' . $building->id
                    ];
                });

            // Get buildings assigned to OTHER billing groups
            $otherAssignedBuildingIds = BillingGroupBuilding::whereIn(
                    'billing_group_id',
                    BillingGroup::where('contract_id', $contract->id)
                        ->where('id', '!=', $billingGroup->id)
                        ->pluck('id')
                )
                ->with('billingGroup')
                ->get()
                ->keyBy('building_id');

            // Get available buildings
            $assignedBuildingIds = $assignedBuildings->pluck('id')->toArray();
            $availableBuildingIds = $contractBuildingIds;

            $availableBuildings = Building::whereIn('id', $availableBuildingIds)
                ->get(['id', 'nama_gedung', 'name'])
                ->map(function($building) use ($assignedBuildingIds, $otherAssignedBuildingIds) {
                    $assignedToOther = isset($otherAssignedBuildingIds[$building->id]);
                    $assignedToThis = in_array($building->id, $assignedBuildingIds);

                    return [
                        'id' => $building->id,
                        'name' => $building->nama_gedung ?? $building->name ?? 'Building #' . $building->id,
                        'assigned_to_other' => $assignedToOther,
                        'assigned_to_billing_group' => $assignedToOther ? $otherAssignedBuildingIds[$building->id]->billingGroup->billing_group_name : null,
                        'assigned_to_this' => $assignedToThis
                    ];
                })
                ->filter(function($building) {
                    return !$building['assigned_to_this']; // Don't show buildings already assigned to this BG
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'available' => $availableBuildings->values(),
                    'assigned' => $assignedBuildings->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting buildings: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getContractBuildings(Contract $contract)
    {
        $contract->loadMissing([
            'contractRooms.room.building',
            'contractSurveys.survey.building',
        ]);

        $buildings = collect();

        foreach ($contract->contractRooms as $contractRoom) {
            $building = $contractRoom->room?->building ?? $contractRoom->building;
            if ($building) {
                $buildings->push($building);
            }
        }

        foreach ($contract->contractSurveys as $contractSurvey) {
            $building = $contractSurvey->survey?->building;
            if ($building) {
                $buildings->push($building);
            }
        }

        return $buildings->unique('id')->values();
    }

    private function getAssignedBuildingIdsForContract(Contract $contract, ?int $exceptBillingGroupId = null): array
    {
        $billingGroupIds = BillingGroup::where('contract_id', $contract->id)
            ->when($exceptBillingGroupId, function ($query) use ($exceptBillingGroupId) {
                $query->where('id', '!=', $exceptBillingGroupId);
            })
            ->pluck('id');

        if ($billingGroupIds->isEmpty()) {
            return [];
        }

        return BillingGroupBuilding::whereIn('billing_group_id', $billingGroupIds)
            ->distinct()
            ->pluck('building_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();
    }

    private function syncBuildingAssignments(BillingGroup $billingGroup, array $buildingIds): void
    {
        $buildingIds = collect($buildingIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingAssignments = BillingGroupBuilding::withTrashed()
            ->where('billing_group_id', $billingGroup->id)
            ->get()
            ->keyBy('building_id');

        foreach ($buildingIds as $buildingId) {
            $assignment = $existingAssignments->get($buildingId);

            if ($assignment) {
                if ($assignment->trashed()) {
                    $assignment->restore();
                }

                $assignment->update([
                    'billing_amount' => $assignment->billing_amount ?? $billingGroup->billing_amount,
                    'is_active' => true,
                    'updated_by' => auth()->id(),
                ]);

                continue;
            }

            BillingGroupBuilding::create([
                'billing_group_id' => $billingGroup->id,
                'building_id' => $buildingId,
                'billing_amount' => $billingGroup->billing_amount,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $deleteQuery = BillingGroupBuilding::where('billing_group_id', $billingGroup->id);

        if ($buildingIds->isNotEmpty()) {
            $deleteQuery->whereNotIn('building_id', $buildingIds->all());
        }

        $deleteQuery->delete();
    }

    /**
     * Get billing groups by customer (for reuse)
     */
    public function getByCustomer($customerId)
    {
        try {
            $billingGroups = BillingGroup::whereHas('contract', function($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->with('contract:id,contract_number,customer_id')
            ->where('is_active', true)
            ->get(['id', 'billing_group_name', 'contract_id', 'billing_frequency'])
            ->map(function($bg) {
                return [
                    'id' => $bg->id,
                    'billing_group_name' => $bg->billing_group_name,
                    'billing_frequency' => $bg->billing_frequency,
                    'contract_number' => $bg->contract->contract_number ?? '-'
                ];
            });

            return response()->json($billingGroups);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting billing groups: ' . $e->getMessage()
            ], 500);
        }
    }
}
