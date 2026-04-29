<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContractRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ContractRentalController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'unit_price' => 'required|numeric|min:0',
            'rental_alias' => 'nullable|string|max:255',
        ]);

        try {
            $rental = ContractRental::findOrFail($id);
            
            // Calculate total price
            $totalPrice = $request->quantity * $request->unit_price;

            $rental->update([
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $totalPrice, // Explicitly set total price
                'rental_alias' => $request->rental_alias,
                'updated_by' => Auth::id(),
            ]);
            
            // Recalculate contract total value if needed? 
            // For now just update the rental item.
            // Ideally we should update the contract's total info, but that might be complex. 
            // Let's stick to updating the item for now as per immediate requirement.

            return response()->json([
                'status' => 'success',
                'message' => 'Contract rental updated successfully',
                'data' => $rental
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating rental: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rental = ContractRental::findOrFail($id);
            $rental->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Contract rental deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting rental: ' . $e->getMessage()
            ], 500);
        }
    }
}
