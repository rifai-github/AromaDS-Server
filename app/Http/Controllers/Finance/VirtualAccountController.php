<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\VirtualAccount;
use App\Models\VirtualAccountImport;
use App\Models\VirtualAccountExport;
use App\Models\BankPayment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VirtualAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = VirtualAccount::with(['customer', 'bankPayment', 'creator', 'updater']);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by bank payment
        if ($request->filled('bank_payment_id')) {
            $query->where('bank_payment_id', $request->bank_payment_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_active', $request->status === 'active');
        }

        // Filter by virtual account number
        if ($request->filled('va_number')) {
            $query->where('va_number', 'like', '%' . $request->va_number . '%');
        }

        $virtualAccounts = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('finance.virtual-accounts.index', compact('virtualAccounts'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        $banks = BankPayment::where('is_active', true)->get();
        
        return view('finance.virtual-accounts.create', compact('customers', 'banks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bank_payment_id' => 'required|exists:bank_payments,id',
            'va_number' => 'required|string|max:50|unique:virtual_accounts',
            'va_name' => 'required|string|max:255',
            'status_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $virtualAccount = VirtualAccount::create([
                'customer_id' => $request->customer_id,
                'bank_payment_id' => $request->bank_payment_id,
                'va_number' => $request->va_number,
                'va_name' => $request->va_name,
                'status_active' => $request->status_active ?? true,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.virtual-accounts.show', $virtualAccount)
                ->with('success', 'Virtual Account berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(VirtualAccount $virtualAccount)
    {
        $virtualAccount->load(['customer', 'bankPayment']);
        
        return view('finance.virtual-accounts.show', compact('virtualAccount'));
    }

    public function edit(VirtualAccount $virtualAccount)
    {
        $customers = Customer::where('status', 'active')->get();
        $banks = BankPayment::where('is_active', true)->get();
        
        return view('finance.virtual-accounts.edit', compact('virtualAccount', 'customers', 'banks'));
    }

    public function update(Request $request, VirtualAccount $virtualAccount)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bank_payment_id' => 'required|exists:bank_payments,id',
            'va_number' => 'required|string|max:50|unique:virtual_accounts,va_number,' . $virtualAccount->id,
            'va_name' => 'required|string|max:255',
            'status_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $virtualAccount->update([
                'customer_id' => $request->customer_id,
                'bank_payment_id' => $request->bank_payment_id,
                'va_number' => $request->va_number,
                'va_name' => $request->va_name,
                'status_active' => $request->status_active ?? true,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.virtual-accounts.show', $virtualAccount)
                ->with('success', 'Virtual Account berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(VirtualAccount $virtualAccount)
    {
        try {
            $virtualAccount->delete();
            return redirect()->route('finance.virtual-accounts.index')
                ->with('success', 'Virtual Account berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Additional methods for virtual account management
    public function activate(VirtualAccount $virtualAccount)
    {
        $virtualAccount->activate();
        return back()->with('success', 'Virtual Account berhasil diaktifkan.');
    }

    public function deactivate(VirtualAccount $virtualAccount)
    {
        $virtualAccount->deactivate();
        return back()->with('success', 'Virtual Account berhasil dinonaktifkan.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'bank_payment_id' => 'required|exists:bank_payments,id',
        ]);

        try {
            DB::beginTransaction();

            $import = VirtualAccountImport::create([
                'bank_payment_id' => $request->bank_payment_id,
                'file_path' => $request->file('file')->store('virtual-account-imports'),
                'import_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.virtual-account-imports.show', $import)
                ->with('success', 'File import berhasil diupload.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'bank_payment_id' => 'required|exists:bank_payments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'export_format' => 'required|in:csv,xlsx,pdf',
        ]);

        try {
            DB::beginTransaction();

            $export = VirtualAccountExport::create([
                'bank_payment_id' => $request->bank_payment_id,
                'export_type' => 'virtual_accounts',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'export_format' => $request->export_format,
                'export_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.virtual-account-exports.show', $export)
                ->with('success', 'Export berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
