<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerStatusController extends Controller
{
    /**
     * Update customer PKP status
     */
    public function updatePkpStatus(Request $request, Customer $customer)
    {
        $request->validate([
            'is_pkp' => 'required|boolean',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $customer->update([
                'is_pkp' => $request->boolean('is_pkp'),
                'updated_by' => Auth::id()
            ]);

            // Log the change
            \App\Models\CustomerStatusLog::create([
                'customer_id' => $customer->id,
                'field_changed' => 'is_pkp',
                'old_value' => !$request->boolean('is_pkp'),
                'new_value' => $request->boolean('is_pkp'),
                'reason' => $request->reason,
                'changed_by' => Auth::id(),
                'changed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Customer PKP status updated successfully',
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'is_pkp' => $customer->is_pkp
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer PKP status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer active status
     */
    public function updateActiveStatus(Request $request, Customer $customer)
    {
        $request->validate([
            'is_active' => 'required|boolean',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $customer->update([
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id()
            ]);

            // Log the change
            \App\Models\CustomerStatusLog::create([
                'customer_id' => $customer->id,
                'field_changed' => 'is_active',
                'old_value' => !$request->boolean('is_active'),
                'new_value' => $request->boolean('is_active'),
                'reason' => $request->reason,
                'changed_by' => Auth::id(),
                'changed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Customer active status updated successfully',
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'is_active' => $customer->is_active
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer active status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer status history
     */
    public function getStatusHistory(Customer $customer)
    {
        $history = \App\Models\CustomerStatusLog::where('customer_id', $customer->id)
            ->with('changedBy')
            ->orderBy('changed_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}
