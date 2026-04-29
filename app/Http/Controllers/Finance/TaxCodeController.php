<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceTaxCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TaxCodeController extends Controller
{
    public function index(): View
    {
        $taxCodes = FinanceTaxCode::query()
            ->select([
                'id',
                'code',
                'description',
                'ppn_status',
                'invoice_status',
                'faktur_pajak_status',
                'customer_status',
                'sort_order',
                'is_active',
            ])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $canEdit = auth()->user()->hasPermission('finance.tax-codes.edit')
            || auth()->user()->hasRoleStartingWith('Management')
            || auth()->user()->hasRole('Admin');

        $ppnStatusOptions = FinanceTaxCode::PPN_STATUS_OPTIONS;
        $invoiceStatusOptions = FinanceTaxCode::PRINT_STATUS_OPTIONS;
        $customerStatusOptions = FinanceTaxCode::CUSTOMER_STATUS_OPTIONS;

        return view('finance.tax-codes.index', compact(
            'taxCodes',
            'canEdit',
            'ppnStatusOptions',
            'invoiceStatusOptions',
            'customerStatusOptions'
        ));
    }

    public function update(Request $request, FinanceTaxCode $taxCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'ppn_status' => ['required', 'string', Rule::in(FinanceTaxCode::PPN_STATUS_OPTIONS)],
            'invoice_status' => ['required', 'string', Rule::in(FinanceTaxCode::PRINT_STATUS_OPTIONS)],
            'faktur_pajak_status' => ['required', 'string', Rule::in(FinanceTaxCode::PRINT_STATUS_OPTIONS)],
            'customer_status' => ['required', 'string', Rule::in(FinanceTaxCode::CUSTOMER_STATUS_OPTIONS)],
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $taxCode->update([
            'description' => $request->description,
            'ppn_status' => $request->ppn_status,
            'invoice_status' => $request->invoice_status,
            'faktur_pajak_status' => $request->faktur_pajak_status,
            'customer_status' => $request->customer_status,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kode pajak berhasil diperbarui.',
            'data' => $taxCode->fresh(),
        ]);
    }
}
