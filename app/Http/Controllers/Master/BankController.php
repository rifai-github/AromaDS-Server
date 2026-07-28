<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    use ColumnFilterTrait;

    public function index(Request $request)
    {
        $query = Bank::with(['createdBy:id,name', 'updatedBy:id,name']);

        // Apply AutoFilterable filters
        $query->filter($request->all());

        // Sort and paginate
        $banks = $query->orderBy('bank_name', 'asc')->paginateStd(25);

        // Check if this is an API request
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $banks
            ]);
        }

        return view('master.banks.index', compact('banks'));
    }

    public function create()
    {
        return view('master.banks.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_code' => 'required|string|max:10|unique:banks,bank_code',
            'bank_name' => 'required|string|max:255',
            'is_active' => 'required|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $bank = Bank::create([
                'bank_code' => $request->bank_code,
                'bank_name' => $request->bank_name,
                'name' => $request->bank_name, // Add name field
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Bank created successfully',
                    'data' => $bank
                ]);
            }

            return redirect()->route('company.master-banks.index')->with('success', 'Bank created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating bank: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error creating bank: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Bank $master_bank)
    {
        $master_bank->load(['createdBy:id,name', 'updatedBy:id,name']);
        
        // Check if this is an API request, AJAX request, or fetch request
        if (request()->expectsJson() || request()->is('api/*') || request()->ajax() || request()->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'success',
                'data' => $master_bank
            ]);
        }
        
        return view('master.banks.show', compact('master_bank'));
    }

    public function edit(Bank $master_bank)
    {
        $master_bank->load(['createdBy:id,name', 'updatedBy:id,name']);
        
        // Check if this is an API request, AJAX request, or fetch request
        if (request()->expectsJson() || request()->is('api/*') || request()->ajax() || request()->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'success',
                'data' => $master_bank
            ]);
        }
        
        return view('master.banks.edit', compact('master_bank'));
    }

    public function update(Request $request, Bank $master_bank)
    {
        $validator = Validator::make($request->all(), [
            'bank_code' => 'required|string|max:10|unique:banks,bank_code,' . $master_bank->id,
            'bank_name' => 'required|string|max:255',
            'is_active' => 'required|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $master_bank->update([
                'bank_code' => $request->bank_code,
                'bank_name' => $request->bank_name,
                'name' => $request->bank_name, // Add name field
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Bank updated successfully',
                    'data' => $master_bank->fresh()
                ]);
            }

            return redirect()->route('company.master-banks.index')->with('success', 'Bank updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error updating bank: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error updating bank: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Bank $bank)
    {
        try {
            DB::beginTransaction();

            // Check if bank is being used in bank payments
            $bankPaymentsCount = $bank->bankPayments()->count();
            if ($bankPaymentsCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot delete bank. It is being used in {$bankPaymentsCount} bank payment(s)."
                ], 422);
            }

            $bank->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting bank: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:banks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                $bank = Bank::find($id);
                
                // Check if bank is being used
                $bankPaymentsCount = $bank->bankPayments()->count();
                if ($bankPaymentsCount > 0) {
                    $errors[] = "Bank '{$bank->bank_name}' cannot be deleted. It is being used in {$bankPaymentsCount} bank payment(s).";
                    continue;
                }

                $bank->delete();
                $deletedCount++;
            }

            DB::commit();

            $message = "Successfully deleted {$deletedCount} bank(s).";
            if (!empty($errors)) {
                $message .= " " . implode(' ', $errors);
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting banks: ' . $e->getMessage()
            ], 500);
        }
    }
}
