<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

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

        $paymentMethods = $query->with(['createdBy', 'updatedBy'])->orderBy('name')->paginateStd(25);

        return view('finance.payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('finance.payment-methods.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            PaymentMethod::create([
                'name' => $request->name,
                'code' => $request->code,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('finance.payment-methods.index')
                ->with('success', 'Payment method created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating payment method: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        return view('finance.payment-methods.show', compact('paymentMethod'));
    }

    public function edit($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        return view('finance.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $id,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $paymentMethod->update([
                'name' => $request->name,
                'code' => $request->code,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('finance.payment-methods.index')
                ->with('success', 'Payment method updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating payment method: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            $paymentMethod->delete();

            return redirect()->route('finance.payment-methods.index')
                ->with('success', 'Payment method deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting payment method: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payment_methods,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = PaymentMethod::whereIn('id', $request->ids)->delete();
            
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
            $paymentMethod = PaymentMethod::findOrFail($id);
            $paymentMethod->update([
                'is_active' => !$paymentMethod->is_active,
            ]);

            $status = $paymentMethod->is_active ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "Payment method {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating payment method status: ' . $e->getMessage());
        }
    }

    public function getActivePaymentMethods()
    {
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($paymentMethods);
    }
}
