<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FakturPajak;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Http\Traits\AutoFilterable;
use App\Http\Traits\ColumnFilterTrait;

class FakturPajakController extends Controller
{
    use AutoFilterable, ColumnFilterTrait;

    public function index(Request $request)
    {
        $query = FakturPajak::with(['invoice:id,invoice_number,customer_id,grand_total,tax_amount', 'invoice.customer:id,name', 'creator:id,name', 'updater:id,name']);

        // Apply column filters
        // Apply column filters
        $this->applyColumnFilters($query, $request, [
            'faktur_number' => ['column' => 'faktur_number'],
            'faktur_date' => ['column' => 'faktur_date', 'type' => 'date'],
            'customer.name' => ['relation' => 'invoice.customer', 'column' => 'name'],
            'invoice.invoice_number' => ['relation' => 'invoice', 'column' => 'invoice_number'],
            'invoice_amount' => ['relation' => 'invoice', 'column' => 'grand_total', 'type' => 'numeric'],
            'tax_amount' => ['column' => 'tax_amount', 'type' => 'numeric'],
            'status' => ['column' => 'status'],
            'creator.name' => ['relation' => 'creator', 'column' => 'name'],
            'updater.name' => ['relation' => 'updater', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        $fakturPajaks = $query->orderBy('faktur_date', 'desc')->paginateStd(25);

        // Get filter options - optimized queries
        $invoices = Invoice::select('id', 'invoice_number', 'customer_id', 'grand_total', 'tax_amount')
            ->with('customer:id,name')
            ->where('invoice_status', '!=', 'cancelled')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A',
                    'total_invoice' => $invoice->grand_total,
                    'tax_amount' => $invoice->tax_amount
                ];
            });

        return view('finance.faktur-pajak.index', compact('fakturPajaks', 'invoices'));
    }

    public function create()
    {
        $invoices = Invoice::select('id', 'invoice_number', 'customer_id', 'grand_total', 'tax_amount')
            ->with('customer:id,name')
            ->where('invoice_status', '!=', 'cancelled')
            ->whereNull('faktur_pajak')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A',
                    'total_invoice' => $invoice->grand_total,
                    'tax_amount' => $invoice->tax_amount
                ];
            });
        
        return view('finance.faktur-pajak.create', compact('invoices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'faktur_date' => 'required|date',
            'tax_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);
            
            // Check if invoice already has faktur pajak
            if ($invoice->faktur_pajak) {
                throw new \Exception('Invoice sudah memiliki faktur pajak.');
            }

            $fakturPajak = FakturPajak::create([
                'invoice_id' => $request->invoice_id,
                'faktur_date' => $request->faktur_date,
                'tax_amount' => $request->tax_amount,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            // Update invoice with faktur pajak number
            $invoice->update([
                'faktur_pajak' => $fakturPajak->faktur_number,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Faktur Pajak berhasil dibuat.',
                    'data' => $fakturPajak->load(['invoice.customer', 'creator'])
                ]);
            }

            return redirect()->route('finance.faktur-pajak.show', $fakturPajak)
                ->with('success', 'Faktur Pajak berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(FakturPajak $fakturPajak)
    {
        $fakturPajak->load(['invoice.customer', 'creator', 'updater']);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $fakturPajak
            ]);
        }
        
        return view('finance.faktur-pajak.show', compact('fakturPajak'));
    }

    public function edit(FakturPajak $fakturPajak)
    {
        $fakturPajak->load(['invoice.customer', 'creator', 'updater']);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $fakturPajak
            ]);
        }
        
        $invoices = Invoice::select('id', 'invoice_number', 'customer_id', 'grand_total', 'tax_amount')
            ->with('customer:id,name')
            ->where('invoice_status', '!=', 'cancelled')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A',
                    'total_invoice' => $invoice->grand_total,
                    'tax_amount' => $invoice->tax_amount
                ];
            });
        
        return view('finance.faktur-pajak.edit', compact('fakturPajak', 'invoices'));
    }

    public function update(Request $request, FakturPajak $fakturPajak)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'faktur_date' => 'required|date',
            'tax_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);
            
            // Check if invoice already has faktur pajak (excluding current one)
            if ($invoice->faktur_pajak && $invoice->faktur_pajak != $fakturPajak->faktur_number) {
                throw new \Exception('Invoice sudah memiliki faktur pajak.');
            }

            $fakturPajak->update([
                'invoice_id' => $request->invoice_id,
                'faktur_date' => $request->faktur_date,
                'tax_amount' => $request->tax_amount,
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            // Update invoice with faktur pajak number
            $invoice->update([
                'faktur_pajak' => $fakturPajak->faktur_number,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Faktur Pajak berhasil diperbarui.',
                    'data' => $fakturPajak->load(['invoice.customer', 'creator', 'updater'])
                ]);
            }

            return redirect()->route('finance.faktur-pajak.show', $fakturPajak)
                ->with('success', 'Faktur Pajak berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(FakturPajak $fakturPajak)
    {
        try {
            DB::beginTransaction();

            // Update invoice to remove faktur pajak reference
            if ($fakturPajak->invoice) {
                $fakturPajak->invoice->update(['faktur_pajak' => null]);
            }

            $fakturPajak->delete();
            
            DB::commit();
            
            return redirect()->route('finance.faktur-pajak.index')
                ->with('success', 'Faktur Pajak berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function submit(FakturPajak $fakturPajak)
    {
        try {
            $fakturPajak->submit();
            return back()->with('success', 'Faktur Pajak berhasil disubmit.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function approve(FakturPajak $fakturPajak)
    {
        try {
            $fakturPajak->approve();
            return back()->with('success', 'Faktur Pajak berhasil disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(FakturPajak $fakturPajak)
    {
        try {
            $fakturPajak->reject();
            return back()->with('success', 'Faktur Pajak berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function draft(FakturPajak $fakturPajak)
    {
        try {
            $fakturPajak->draft();
            return back()->with('success', 'Faktur Pajak berhasil dikembalikan ke draft.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $query = FakturPajak::with(['invoice', 'creator']);

        // Apply same filters as index
        if ($request->filled('faktur_number')) {
            $query->where('faktur_number', 'like', '%' . $request->faktur_number . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('faktur_date', [$request->start_date, $request->end_date]);
        }

        $fakturPajaks = $query->orderBy('faktur_date', 'desc')->get();

        // Generate CSV for e-SPT integration
        $filename = 'faktur_pajak_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($fakturPajaks) {
            $file = fopen('php://output', 'w');
            
            // CSV headers for e-SPT
            fputcsv($file, [
                'Faktur Number',
                'Invoice Number',
                'Customer Name',
                'Faktur Date',
                'Tax Amount',
                'Status',
                'Created By',
                'Created Date'
            ]);

            foreach ($fakturPajaks as $faktur) {
                fputcsv($file, [
                    $faktur->faktur_number,
                    $faktur->invoice->invoice_number ?? '',
                    $faktur->invoice->customer->name ?? '',
                    $faktur->faktur_date->format('Y-m-d'),
                    $faktur->tax_amount,
                    $faktur->status,
                    $faktur->creator->name ?? '',
                    $faktur->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
