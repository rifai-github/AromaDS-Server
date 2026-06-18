<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\BankPayment;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankPaymentController extends Controller
{
    use ColumnFilterTrait;

    public function index(Request $request)
    {
        $query = BankPayment::with(['bank', 'createdBy', 'updatedBy']);

        // Apply AutoFilterable filters
        $query->filter($request->all());

        if ($request->ajax() || $request->expectsJson()) {
            $bankPayments = $query->orderBy('created_at', 'desc')->get();
        } else {
            $bankPayments = $query->orderBy('created_at', 'desc')->paginateStd(25);
        }

        // Get banks for create form
        $banks = \App\Models\Bank::where('is_active', true)->orderBy('bank_name')->get();

        // Check if there's already a default VA
        $hasDefaultVa = BankPayment::where('is_default_va', true)->exists();
        $defaultVaId = null;
        if ($hasDefaultVa) {
            $defaultVa = BankPayment::where('is_default_va', true)->first();
            $defaultVaId = $defaultVa ? $defaultVa->id : null;
        }

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'bankPayments' => ($bankPayments instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $bankPayments->items() : $bankPayments,
                'banks' => $banks,
                'hasDefaultVa' => $hasDefaultVa,
                'defaultVaId' => $defaultVaId
            ]);
        }

        return view('company.bank-payments.index', compact('bankPayments', 'banks', 'hasDefaultVa', 'defaultVaId'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $banks = \App\Models\Bank::where('is_active', true)->orderBy('bank_name')->get();
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'banks' => $banks
                ]
            ]);
        }
        
        return view('company.bank-payments.create', compact('banks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'branch_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'is_default_va' => 'boolean',
            'bank_va_number' => 'nullable|string|max:50',
            'start_number' => 'nullable|string|max:20',
            'end_number' => 'nullable|string|max:20',
            'length' => 'nullable|integer|min:1|max:20',
            'current_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // If setting as default VA, unset all other default VAs first
            $isDefaultVa = $request->boolean('is_default_va', false);
            if ($isDefaultVa) {
                BankPayment::where('is_default_va', true)->update(['is_default_va' => false]);
            }

            $bankPayment = BankPayment::create([
                'bank_id' => $request->bank_id,
                'branch_name' => $request->branch_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'address' => $request->address,
                'phone' => $request->phone,
                'fax' => $request->fax,
                'is_default_va' => $isDefaultVa,
                'bank_va_number' => $request->bank_va_number,
                'start_number' => $request->start_number,
                'end_number' => $request->end_number,
                'length' => $request->length,
                'current_number' => $request->current_number,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank payment created successfully',
                'data' => $bankPayment->load(['bank'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating bank payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $bankPayment = BankPayment::with(['bank', 'createdBy', 'updatedBy'])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $bankPayment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bank payment not found'
            ], 404);
        }
    }

    public function edit($id)
    {
        try {
            $bankPayment = BankPayment::with(['bank', 'createdBy', 'updatedBy'])->findOrFail($id);
            
            // Check if there's already a default VA (excluding current one)
            $hasDefaultVa = BankPayment::where('is_default_va', true)
                ->where('id', '!=', $id)
                ->exists();
            $defaultVaId = null;
            if ($hasDefaultVa) {
                $defaultVa = BankPayment::where('is_default_va', true)
                    ->where('id', '!=', $id)
                    ->first();
                $defaultVaId = $defaultVa ? $defaultVa->id : null;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $bankPayment,
                'hasDefaultVa' => $hasDefaultVa,
                'defaultVaId' => $defaultVaId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bank payment not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        \Log::info('Bank Payment Update Request:', [
            'id' => $id,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'branch_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'is_default_va' => 'boolean',
            'bank_va_number' => 'nullable|string|max:50',
            'start_number' => 'nullable|string|max:20',
            'end_number' => 'nullable|string|max:20',
            'length' => 'nullable|integer|min:1|max:20',
            'current_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();
            
            $bankPayment = BankPayment::findOrFail($id);
            
            // Check if trying to set as default VA
            $isDefaultVa = $request->boolean('is_default_va', false);
            
            // If trying to set as default, but there's already another default VA
            if ($isDefaultVa && !$bankPayment->is_default_va) {
                $existingDefault = BankPayment::where('is_default_va', true)
                    ->where('id', '!=', $id)
                    ->first();
                
                if ($existingDefault) {
                    // Unset the existing default first
                    $existingDefault->update(['is_default_va' => false]);
                }
            }
            
            // If unsetting default (changing from Yes to No), allow it
            // If setting as default, we already handled it above
            
            \Log::info('Before update:', [
                'current_branch_name' => $bankPayment->branch_name,
                'request_branch_name' => $request->branch_name
            ]);
            
            // Try direct assignment instead of mass assignment
            $bankPayment->bank_id = $request->bank_id;
            $bankPayment->branch_name = $request->branch_name;
            $bankPayment->account_name = $request->account_name;
            $bankPayment->account_number = $request->account_number;
            $bankPayment->address = $request->address;
            $bankPayment->phone = $request->phone;
            $bankPayment->fax = $request->fax;
            $bankPayment->is_default_va = $isDefaultVa;
            $bankPayment->bank_va_number = $request->bank_va_number;
            $bankPayment->start_number = $request->start_number;
            $bankPayment->end_number = $request->end_number;
            $bankPayment->length = $request->length;
            $bankPayment->current_number = $request->current_number;
            $bankPayment->is_active = $request->is_active ?? true;
            $bankPayment->updated_by = Auth::id();
            
            \Log::info('Before save:', [
                'branch_name' => $bankPayment->branch_name
            ]);
            
            $result = $bankPayment->save();
            
            \Log::info('Save result:', [
                'result' => $result,
                'new_branch_name' => $bankPayment->branch_name
            ]);
            
            DB::commit();
            
            // Force refresh from database
            $bankPayment->refresh();
            
            \Log::info('After refresh:', [
                'refreshed_branch_name' => $bankPayment->branch_name
            ]);
            
            // Try fresh query
            $freshPayment = BankPayment::find($id);
            \Log::info('Fresh query:', [
                'fresh_branch_name' => $freshPayment->branch_name
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bank payment updated successfully',
                'data' => $bankPayment->load(['bank'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Bank Payment Update Error:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating bank payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bankPayment = BankPayment::findOrFail($id);
            $bankPayment->delete(); // This will use soft delete

            return response()->json([
                'status' => 'success',
                'message' => 'Bank payment hidden successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error hiding bank payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:bank_payments,id'
            ]);

            $count = BankPayment::whereIn('id', $request->ids)->delete(); // This will use soft delete

            return response()->json([
                'success' => true,
                'message' => 'Bank payments hidden successfully',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding bank payments: ' . $e->getMessage()
            ], 500);
        }
    }

}
