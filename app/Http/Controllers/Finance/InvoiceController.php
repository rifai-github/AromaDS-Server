<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceDetail;
use App\Models\Finance\InvoiceRentalDetail;
use App\Models\Finance\InvoiceFile;
use App\Models\Finance\InvoiceActivity;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerTax;
use App\Models\FinanceTaxCode;
use App\Models\User;
use App\Models\TaxSetting;
use App\Services\Finance\InvoiceGenerationService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;
    
    public function index(Request $request)
    {
        $query = Invoice::with(['contract.billingGroup', 'contract.quotation.survey', 'billingGroup', 'customer:id,name,address,city', 'creator:id,name', 'updater:id,name', 'jobSchedules:id,job_number,contract_number']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // Filter by created_by and also by contract.created_by
        $user = Auth::user();
        if (!$user->hasRoleStartingWith('Management')) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            $query->where(function($q) use ($accessibleUserIds) {
                $q->whereIn('created_by', $accessibleUserIds)
                  ->orWhereHas('contract', function($subQ) use ($accessibleUserIds) {
                      $subQ->whereIn('created_by', $accessibleUserIds)
                           ->orWhereIn('marketing_id', $accessibleUserIds);
                  });
            });
        }

        // Apply filters (skip if using column-specific filters)
        if ($request->filled('search') && !$request->has('filter')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('contract_number', 'like', '%' . $search . '%')
                  // Add job number search from invoiceRentalDetails
                  ->orWhereHas('invoiceRentalDetails', function($subQ) use ($search) {
                      $subQ->where('job_no', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Apply per-column filters
        try {
            // Capture flat structure filters
            $customFilters = [];
            if ($request->has('invoice_number')) $customFilters['invoice_number'] = $request->invoice_number;
            if ($request->has('contract_number')) $customFilters['contract_number'] = $request->contract_number;
            if ($request->has('invoice_date')) $customFilters['invoice_date'] = $request->invoice_date;
            if ($request->has('due_date')) $customFilters['due_date'] = $request->due_date;
            if ($request->has('subtotal')) $customFilters['subtotal'] = $request->subtotal;
            if ($request->has('tax_amount')) $customFilters['tax_amount'] = $request->tax_amount;
            if ($request->has('grand_total')) $customFilters['grand_total'] = $request->grand_total;
            if ($request->has('total_paid')) $customFilters['total_paid'] = $request->total_paid;
            if ($request->has('outstanding')) $customFilters['outstanding'] = $request->outstanding;
            if ($request->has('invoice_status')) $customFilters['invoice_status'] = $request->invoice_status;
            if ($request->has('payment_method')) $customFilters['payment_method'] = $request->payment_method;
            if ($request->has('created_at')) $customFilters['created_at'] = $request->created_at;

            // Skip AutoFilter for manually handled columns to avoid conflicts
            if (!empty($customFilters)) {
                $request->merge([
                    '_skip_auto_filter' => array_merge(
                        $request->input('_skip_auto_filter', []),
                        array_fill_keys(array_keys($customFilters), true)
                    )
                ]);
            }

            $this->applyColumnFilters($query, 'invoicesTable', [
                // 0 => checkbox
                1 => ['column' => 'invoice_number'],
                'invoice_number' => ['column' => 'invoice_number'],
                
                3 => ['column' => 'contract_number'],
                'contract_number' => ['column' => 'contract_number'],
                
                4 => ['relation' => 'customer', 'column' => 'name'],
                'customer.name' => ['relation' => 'customer', 'column' => 'name'],
                
                5 => ['column' => 'invoice_date', 'type' => 'date'],
                'invoice_date' => ['column' => 'invoice_date', 'type' => 'date'],
                
                6 => ['column' => 'due_date', 'type' => 'date'],
                'due_date' => ['column' => 'due_date', 'type' => 'date'],
                
                7 => ['column' => 'subtotal'],
                'subtotal' => ['column' => 'subtotal'],
                
                8 => ['column' => 'tax_amount'],
                'tax_amount' => ['column' => 'tax_amount'],
                
                9 => ['column' => 'total_amount'],
                'grand_total' => ['column' => 'total_amount'],
                
                10 => ['column' => 'total_paid'],
                'total_paid' => ['column' => 'total_paid'],
                
                11 => ['column' => 'outstanding'],
                'outstanding' => ['column' => 'outstanding'],
                
                12 => ['column' => 'invoice_status'],
                'invoice_status' => ['column' => 'invoice_status'],
                
                13 => ['column' => 'payment_method'],
                'payment_method' => ['column' => 'payment_method'],
                
                14 => ['relation' => 'creator', 'column' => 'name'],
                'creator.name' => ['relation' => 'creator', 'column' => 'name'],
                
                15 => ['column' => 'created_at', 'type' => 'date'],
                'created_at' => ['column' => 'created_at', 'type' => 'date'],
            ], $customFilters);
        } catch (\Exception $e) {
            \Log::error('Error applying column filters in InvoiceController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        if ($request->filled('status') && !$request->has('filter')) {
            $query->where('invoice_status', $request->status);
        }

        if ($request->filled('contract') && !$request->has('filter')) {
            $query->where('contract_number', $request->contract);
        }

        if ($request->filled('date_from') && !$request->has('filter')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to') && !$request->has('filter')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        // Print Status Filter
        if ($request->input('print_status') === 'sudah') {
            $query->where('is_printed', true);
        } elseif ($request->input('print_status') === 'semua') {
            // No filter
        } else {
            // Default: Belum
            $query->where('is_printed', false);
        }

        if ($request->filled('payment_method') && !$request->has('filter')) {
            $query->where('payment_method', $request->payment_method);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options - optimized queries
        $contracts = Contract::select('contract_number', 'customer_id')->with('customer:id,name')->get();
        $invoiceRegenerationContracts = $this->getInvoiceRegenerationContracts()
            ->map(function (Contract $contract) {
                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $contract->customer?->name ?? '-',
                    'payment_method' => $contract->quotation?->payment_method ?? $contract->quotation?->billing_methods ?? '-',
                ];
            })
            ->values();
        $customers = Customer::select('id', 'name')->where('is_active', true)->get();
        $taxSettings = TaxSetting::active()->orderBy('name')->get();
        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $financeTaxCodeRules = FinanceTaxCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function (FinanceTaxCode $taxCode) {
                return [
                    $taxCode->code => [
                        'code' => $taxCode->code,
                        'description' => $taxCode->description,
                        'ppn_status' => $taxCode->ppn_status,
                        'invoice_status' => $taxCode->invoice_status,
                        'faktur_pajak_status' => $taxCode->faktur_pajak_status,
                        'customer_status' => $taxCode->customer_status,
                        'applies_ppn_to_invoice' => $taxCode->appliesPpnToInvoice(),
                        'is_collected_by_seller' => $taxCode->isCollectedBySeller(),
                        'is_collected_by_customer' => $taxCode->isCollectedByCustomer(),
                        'has_zero_tax_print' => $taxCode->hasZeroTaxPrint(),
                        'print_mode' => $taxCode->printModeLabel(),
                    ],
                ];
            });
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        $paymentMethods = ['virtual_account', 'bank_transfer', 'cash'];

        return view('finance.invoices.index', compact('invoices', 'contracts', 'invoiceRegenerationContracts', 'customers', 'taxSettings', 'defaultVatSetting', 'financeTaxCodeRules', 'statuses', 'paymentMethods'));
    }

    public function create()
    {
        $contracts = Contract::select('contract_number', 'customer_id')->with('customer:id,name')->get();
        $customers = Customer::select('id', 'name')->where('is_active', true)->get();
        $taxSettings = TaxSetting::active()->orderBy('name')->get();
        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $financeTaxCodeRules = FinanceTaxCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        $paymentMethods = ['virtual_account', 'bank_transfer', 'cash'];

        return view('finance.invoices.create', compact('contracts', 'customers', 'taxSettings', 'defaultVatSetting', 'financeTaxCodeRules', 'statuses', 'paymentMethods'));
    }

    private function getInvoiceRegenerationContracts()
    {
        $invoiceService = app(InvoiceGenerationService::class);

        return Contract::with(['customer:id,name', 'quotation:id,payment_method,billing_methods,existing_contract_id'])
            ->where(function ($query) {
                $query->where('contract_status', 'active')
                    ->orWhere('status', 'active');
            })
            ->whereDoesntHave('renewedByContract')
            ->whereDoesntHave('renewals', function ($query) {
                $query->where('status', \App\Models\ContractRenewal::STATUS_COMPLETED)
                    ->whereNotNull('new_contract_id');
            })
            ->orderByDesc('id')
            ->get()
            ->filter(function (Contract $contract) use ($invoiceService) {
                try {
                    return collect($invoiceService->getRentalPeriodsForContract($contract->id))
                        ->contains(fn ($period) => ($period['status'] ?? null) === 'completed');
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->values();
    }

    public function regenerateMissing(Request $request)
    {
        $validated = $request->validate([
            'select_all' => 'nullable|boolean',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'integer|exists:contracts,id',
        ]);

        $selectAll = (bool) ($validated['select_all'] ?? false);
        $allowedContracts = $this->getInvoiceRegenerationContracts();
        $contractIds = $selectAll
            ? $allowedContracts->pluck('id')
            : collect($validated['contract_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($contractIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu contract atau gunakan Select All Contract.',
            ], 422);
        }

        $allowedIds = $allowedContracts->pluck('id')->flip();
        $contracts = $allowedContracts
            ->whereIn('id', $contractIds)
            ->values();

        $blockedIds = $contractIds->reject(fn ($id) => $allowedIds->has($id))->values();
        $invoiceService = app(InvoiceGenerationService::class);
        $summary = [
            'contracts_checked' => $contracts->count(),
            'generated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'blocked' => $blockedIds->count(),
        ];
        $details = [];

        foreach ($contracts as $contract) {
            try {
                $periods = $invoiceService->getRentalPeriodsForContract($contract->id);

                if (empty($periods)) {
                    $summary['skipped']++;
                    $details[] = [
                        'contract_number' => $contract->contract_number,
                        'status' => 'skipped',
                        'message' => 'Tidak ada periode invoice yang bisa dicek.',
                    ];
                    continue;
                }

                foreach ($periods as $period) {
                    $periodName = $period['rental_period'] ?? '-';

                    if (($period['status'] ?? null) !== 'completed') {
                        $summary['skipped']++;
                        $details[] = [
                            'contract_number' => $contract->contract_number,
                            'period' => $periodName,
                            'status' => 'skipped',
                            'message' => 'Syarat invoice belum terpenuhi.',
                        ];
                        continue;
                    }

                    $result = $invoiceService->autoGenerateInvoiceForRentalPeriod(
                        $contract->id,
                        $periodName,
                        Carbon::parse($period['period_start']),
                        Carbon::parse($period['period_end'])
                    );

                    if (! empty($result['success'])) {
                        $summary['generated']++;
                        $details[] = [
                            'contract_number' => $contract->contract_number,
                            'period' => $periodName,
                            'status' => 'generated',
                            'message' => $result['invoice']->invoice_number ?? 'Invoice berhasil dibuat.',
                        ];
                        continue;
                    }

                    $summary['skipped']++;
                    $details[] = [
                        'contract_number' => $contract->contract_number,
                        'period' => $periodName,
                        'status' => 'skipped',
                        'message' => $result['message'] ?? 'Invoice tidak dibuat.',
                    ];
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $details[] = [
                    'contract_number' => $contract->contract_number,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        foreach ($blockedIds as $blockedId) {
            $details[] = [
                'contract_id' => $blockedId,
                'status' => 'blocked',
                'message' => 'Contract tidak eligible: bukan active/current atau sudah punya renewal successor.',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Scan selesai. Generated: {$summary['generated']}, skipped: {$summary['skipped']}, failed: {$summary['failed']}.",
            'summary' => $summary,
            'details' => $details,
        ]);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'contract_number' => 'nullable|exists:contracts,contract_number',
            'invoice_number' => 'nullable|string|max:50',
            'po_number' => 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'billing_address' => 'nullable|string',
            'period_invoice' => 'nullable|string',
            'invoice_status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_obligation' => 'boolean',
            'tax_setting_id' => 'nullable|exists:tax_settings,id',
            'tax_code' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'npwp_number' => 'nullable|string',
            'tax_address' => 'nullable|string',
            'province_name' => 'nullable|string',
            'city_name' => 'nullable|string',
            'district_name' => 'nullable|string',
            'village_name' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal_after_discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'outstanding' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'virtual_account_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $taxPayload = $this->buildInvoiceTaxPayload(
                $customer,
                (float) $request->subtotal,
                (float) ($request->discount_amount ?? 0),
                $request->tax_code
            );

            $contract = $request->contract_number
                ? Contract::where('contract_number', $request->contract_number)->first()
                : null;

            // Always use DocumentNumberService for branch-aware invoice numbers.
            $invoiceNumber = $request->invoice_number ?: $this->generateStandardInvoiceNumber($contract);

            $invoice = Invoice::create([
                'contract_number' => $request->contract_number,
                'invoice_number' => $invoiceNumber,
                'po_number' => $request->po_number,
                'customer_id' => $request->customer_id,
                'billing_address' => $request->billing_address,
                'period_invoice' => $request->period_invoice,
                'invoice_status' => $request->invoice_status,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_obligation' => $request->tax_obligation ?? false,
                'tax_setting_id' => $taxPayload['tax_setting_id'],
                'tax_code' => $taxPayload['tax_code'],
                'tax_number' => $taxPayload['tax_number'],
                'npwp_number' => $taxPayload['npwp_number'],
                'tax_address' => $taxPayload['tax_address'],
                'province_name' => $request->province_name,
                'city_name' => $request->city_name,
                'district_name' => $request->district_name,
                'village_name' => $request->village_name,
                'postal_code' => $request->postal_code,
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount ?? 0,
                'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
                'tax_amount' => $taxPayload['tax_amount'],
                'grand_total' => $taxPayload['grand_total'],
                'total_amount' => $taxPayload['grand_total'],
                'total_paid' => $request->total_paid ?? 0,
                'outstanding' => max($taxPayload['grand_total'] - ($request->total_paid ?? 0), 0),
                'internal_notes' => $request->internal_notes,
                'additional_notes' => $request->additional_notes,
                'terms_conditions' => $request->terms_conditions,
                'payment_method' => $request->payment_method ?? 'virtual_account',
                'virtual_account_number' => $request->virtual_account_number,
                'created_by' => Auth::id(),
            ]);

            // Create activity log
            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'activity_type' => 'created',
                'notes' => 'Invoice created',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Invoice Store Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $this->syncDraftInvoiceFinancialSnapshot($invoice);
        $regenerationContext = $this->resolveInvoiceRegenerationContext($invoice);

        $invoice->load([
            'invoiceDetails', 
            'invoiceRentalDetails.masterRental', 
            'invoiceFiles.creator', 
            'invoiceActivities.creator', 
            'bankReceipts.createdBy',
            'contract.billingGroup', 
            'customer',
            'invoiceFollowUps' => function($query) {
                $query->with('creator')
                      ->orderBy('follow_up_date', 'desc')
                      ->orderBy('created_at', 'desc');
            }
        ]);

        // Aggregate Files Logic using helper
        $files = $this->getAllInvoiceAttachments($invoice);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $invoice,
                'files' => $files, // Return files for AJAX usage too
                'meta' => [
                    'can_regenerate' => $regenerationContext['can_regenerate'],
                    'regenerate_message' => $regenerationContext['message'] ?? null,
                ],
            ]);
        }

        // Calculate potential dates for dropdown options
        $dateOptions = [];
        if ($invoice->invoice_status === 'draft' && $invoice->contract) {
            $contract = $invoice->contract;
            
            // 1. First Service
            $firstServiceDate = $contract->calculateInvoiceDate('first_service');
            if ($firstServiceDate) {
                $dateOptions['first_service'] = [
                    'label' => $firstServiceDate->format('d M Y') . ' (First Service)',
                    'value' => 'first_service',
                    'date' => $firstServiceDate->format('Y-m-d')
                ];
            }

            // 2. Contract Date
            $contractDate = $contract->calculateInvoiceDate('contract_date');
            if ($contractDate) {
                $dateOptions['contract_date'] = [
                    'label' => $contractDate->format('d M Y') . ' (Contract Date)',
                    'value' => 'contract_date',
                    'date' => $contractDate->format('Y-m-d')
                ];
            }

            // 3. Monthly Range (formerly End of Month)
            // For End of Month, we need a base date. 
            // If invoice has a date, use it. If not, use Today.
            $baseDate = $invoice->invoice_date ?? now();
            $endOfMonthDate = $contract->calculateInvoiceDate('end_of_month', null, $baseDate);
            
            if ($endOfMonthDate) {
                $startOfMonth = $endOfMonthDate->copy()->startOfMonth();
                $rangeLabel = $startOfMonth->format('d M Y') . ' - ' . $endOfMonthDate->format('d M Y');
                
                $dateOptions['end_of_month'] = [
                    'label' => $rangeLabel . ' (Monthly Range)',
                    'value' => 'end_of_month',
                    'date' => $endOfMonthDate->format('Y-m-d')
                ];
            }
        }
        
        return view('finance.invoices.show', compact('invoice', 'files', 'dateOptions', 'regenerationContext'));
    }

    /**
     * Helper to gather all attachments for an invoice
     */
    private function getAllInvoiceAttachments(Invoice $invoice)
    {
        $allFiles = collect();

        // 1. Invoice Files (Directly attached)
        // Ensure relation is loaded
        if (!$invoice->relationLoaded('invoiceFiles')) {
            $invoice->load('invoiceFiles.creator');
        }

        foreach ($invoice->invoiceFiles as $f) {
            $allFiles->push((object)[
                'id' => 'inv-' . $f->id,
                'invoice_number' => $invoice->invoice_number,
                'file_type_label' => $f->file_type_label ?? strtoupper($f->file_type),
                'file_name' => $f->file_name,
                'file_path' => $f->file_path, // Needed for download
                // Use a secure download route instead of direct asset link
                'file_url' => route('finance.invoices.download-attachment', ['type' => 'inv', 'id' => $f->id]),
                'description' => $f->description,
                'updated_at' => $f->updated_at,
                'uploaded_by_name' => $f->creator->name ?? 'System',
                'source_badge' => '<span class="badge bg-primary">Invoice</span>',
                'is_downloadable' => true
            ]);
        }

        // 2. Contract Files (Verified only)
        if ($invoice->contract) {
            $contractFiles = \App\Models\ContractFile::where('contract_id', $invoice->contract->id)
                ->where('verification_status', 'verified')
                ->with('uploader')
                ->get();

            foreach ($contractFiles as $f) {
                $allFiles->push((object)[
                    'id' => 'cont-' . $f->id,
                    'invoice_number' => $invoice->invoice_number,
                    'file_type_label' => 'CONTRACT - ' . strtoupper($f->file_type),
                    'file_name' => $f->file_name,
                    'file_path' => $f->file_path, // Needed for download
                    'file_url' => route('finance.invoices.download-attachment', ['type' => 'cont', 'id' => $f->id]),
                    'description' => $f->description ?? $f->verification_notes,
                    'updated_at' => $f->verified_at ?? $f->uploaded_at,
                    'uploaded_by_name' => $f->uploader->name ?? 'System',
                    'source_badge' => '<span class="badge bg-info">Contract</span>',
                    'is_downloadable' => true
                ]);
            }

            // 3. BA Files & System CSR
            $query = \App\Models\JobSchedule::where('contract_number', $invoice->contract->contract_number);

            if ($invoice->period_invoice && preg_match('/Period (\d+)/i', $invoice->period_invoice, $m)) {
                $periodNum = $m[1];
                $query->where('period', $periodNum);
            }
            
            $jobSchedules = $query->with(['baFiles' => function($q) {
                $q->where('verification_status', 'verified')->with('uploader');
            }, 'room', 'assignedTechnician', 'jobAssignSchedules.team'])->get();

            $groupedByJob = $jobSchedules->groupBy('job_number');

            foreach ($groupedByJob as $jobNumber => $schedules) {
                $mainJob = $schedules->first();
                $isCsr = in_array($mainJob->type, ['service_first', 'service']);
                
                // Collect all technicians and teams for this job group
                $technicians = collect();
                $teams = collect();
                foreach($schedules as $sch) {
                    if ($sch->assignedTechnician) $technicians->push($sch->assignedTechnician->name);
                    foreach($sch->jobAssignSchedules as $assign) {
                        if ($assign->team) $teams->push($assign->team->team_name);
                    }
                }
                $techDisplay = $technicians->merge($teams)->unique()->filter()->implode(', ');

                // 3a. Add System CSR if Job is CSR and Done
                if ($isCsr && in_array($mainJob->status, ['done_job', 'completed'])) {
                    $allFiles->push((object)[
                        'id' => 'sys-csr-' . $mainJob->id,
                        'invoice_number' => $invoice->invoice_number,
                        'file_type_label' => 'CSR - SYSTEM GENERATED',
                        'file_name' => "Print_{$jobNumber}.pdf",
                        'file_path' => null, 
                        'file_url' => route('operational.job-schedules.print-csr', ['ids' => $mainJob->id, 'view_mode' => 'job']),
                        'description' => "System generated report for Job #{$jobNumber}. Teams: " . ($techDisplay ?: '-'),
                        'updated_at' => $schedules->max('updated_at'),
                        'uploaded_by_name' => 'System',
                        'source_badge' => '<span class="badge bg-success">System Report</span>',
                        'is_downloadable' => true
                    ]);
                }

                // 3b. Add uploaded BA Files
                foreach ($schedules as $sch) {
                    foreach ($sch->baFiles as $f) {
                        $allFiles->push((object)[
                            'id' => 'ba-' . $f->id,
                            'invoice_number' => $invoice->invoice_number,
                            'file_type_label' => strtoupper($isCsr ? 'CSR' : 'BA') . ' - ' . strtoupper($f->file_type),
                            'file_name' => $f->file_name,
                            'file_path' => $f->file_path,
                            'file_url' => route('finance.invoices.download-attachment', ['type' => 'ba', 'id' => $f->id]),
                            'description' => "Job #{$jobNumber} (Room: " . ($sch->room->room_name ?? 'General Area') . "). Technician: " . ($f->uploader->name ?? '-'),
                            'updated_at' => $f->updated_at,
                            'uploaded_by_name' => $f->uploader->name ?? 'System',
                            'source_badge' => '<span class="badge ' . ($isCsr ? 'bg-indigo text-white' : 'bg-warning text-dark') . '">Job File</span>',
                            'is_downloadable' => true
                        ]);
                    }
                }
            }
        }

        return $allFiles->sortByDesc('updated_at');
    }

    /**
     * Resolve a robust path for an attachment given its stored DB path
     */
    private function resolveAttachmentPath($dbPath)
    {
        if (!$dbPath) return null;

        // Clean path for easier concatenation
        $basePath = ltrim($dbPath, '/');
        if (strpos($basePath, 'public/') === 0) {
            $basePath = substr($basePath, 7);
        }

        $possiblePaths = [
            storage_path('app/public/' . $basePath),
            storage_path('app/private/public/' . $basePath),
            storage_path('app/' . $basePath),
            storage_path('app/public/' . $dbPath),
            public_path('storage/' . $basePath),
            public_path('storage/' . $dbPath),
            public_path('uploads/' . basename($dbPath)),
            base_path('storage/app/public/' . $basePath)
        ];

        foreach ($possiblePaths as $p) {
            if (file_exists($p) && is_file($p)) {
                return $p;
            }
        }
        
        // Final fallback: check the exact DB string as an absolute path
        if (file_exists($dbPath) && is_file($dbPath)) {
            return $dbPath;
        }

        return null;
    }

    /**
     * Serve invoice attachments (Private/Public)
     */
    public function downloadFile(Request $request)
    {
        $type = $request->query('type');
        $id = $request->query('id');

        $fileModel = null;
        if ($type === 'inv') {
            $fileModel = \App\Models\Finance\InvoiceFile::find($id);
        } elseif ($type === 'cont') {
            $fileModel = \App\Models\ContractFile::find($id);
        } elseif ($type === 'ba') {
            $fileModel = \App\Models\JobScheduleBaFile::find($id);
        }

        if (!$fileModel) abort(404, 'File record not found.');

        // Robust Path Resolution (Using Helper)
        $filePath = $this->resolveAttachmentPath($fileModel->file_path);

        if (!$filePath) {
             \Log::error("Download failed - File not found: " . json_encode([
                 'type' => $type,
                 'id' => $id,
                 'db_path' => $fileModel->file_path
             ]));
             abort(404, 'File not found on server.');
        }

        return response()->download($filePath, $fileModel->file_name);
    }

    public function updateDeliveryInfo(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'kirim' => 'nullable|in:hard_copy,soft_copy,both,manual', // Method
            'dikirim_oleh' => 'nullable|string|max:255', // Sender Name
            'dikirim_pada' => 'nullable|date',
            'diterima_oleh' => 'nullable|string|max:255',
            'pada' => 'nullable|date',
            'catatan_pengiriman' => 'nullable|string',
        ]);

        try {
            $invoice->update([
                'kirim' => $validated['kirim'] ?? null,
                'dikirim_oleh' => $validated['dikirim_oleh'] ?? null,
                'dikirim_pada' => $validated['dikirim_pada'] ?? null,
                'diterima_oleh' => $validated['diterima_oleh'] ?? null,
                'pada' => $validated['pada'] ?? null,
                'catatan_pengiriman' => $validated['catatan_pengiriman'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery information updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating delivery information: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Invoice $invoice)
    {
        $this->syncDraftInvoiceFinancialSnapshot($invoice);

        $contracts = Contract::select('contract_number', 'customer_id')->with('customer:id,name')->get();
        $customers = Customer::select('id', 'name')->where('is_active', true)->get();
        $taxSettings = TaxSetting::active()->get();
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        $paymentMethods = ['virtual_account', 'bank_transfer', 'cash'];

        if (request()->ajax()) {
            $invoice->load(['customer', 'taxSetting']);
            $data = $invoice->toArray();
            if ($invoice->taxSetting) {
                $data['tax_setting'] = $invoice->taxSetting->toArray();
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'customers' => $customers,
                'taxSettings' => $taxSettings,
                'contracts' => $contracts,
                'statuses' => $statuses,
                'paymentMethods' => $paymentMethods
            ]);
        }

        // For non-AJAX requests, redirect to index page
        return redirect()->route('finance.invoices.index');
    }

    public function update(Request $request, Invoice $invoice)
    {
        
        $validator = Validator::make($request->all(), [
            'contract_number' => 'nullable|exists:contracts,contract_number',
            'invoice_number' => 'nullable|string|max:50',
            'po_number' => 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'billing_address' => 'nullable|string',
            'period_invoice' => 'nullable|string',
            'invoice_status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_obligation' => 'boolean',
            'tax_setting_id' => 'nullable|exists:tax_settings,id',
            'tax_code' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'npwp_number' => 'nullable|string',
            'tax_address' => 'nullable|string',
            'province_name' => 'nullable|string',
            'city_name' => 'nullable|string',
            'district_name' => 'nullable|string',
            'village_name' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal_after_discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'outstanding' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'virtual_account_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $taxPayload = $this->buildInvoiceTaxPayload(
                $customer,
                (float) $request->subtotal,
                (float) ($request->discount_amount ?? 0),
                $request->tax_code
            );

            $invoice->update([
                'contract_number' => $request->contract_number,
                'invoice_number' => $request->invoice_number,
                'po_number' => $request->po_number,
                'customer_id' => $request->customer_id,
                'billing_address' => $request->billing_address,
                'period_invoice' => $request->period_invoice,
                'invoice_status' => $request->invoice_status,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_obligation' => $request->tax_obligation ?? false,
                'tax_setting_id' => $taxPayload['tax_setting_id'],
                'tax_code' => $taxPayload['tax_code'],
                'tax_number' => $taxPayload['tax_number'],
                'npwp_number' => $taxPayload['npwp_number'],
                'tax_address' => $taxPayload['tax_address'],
                'province_name' => $request->province_name,
                'city_name' => $request->city_name,
                'district_name' => $request->district_name,
                'village_name' => $request->village_name,
                'postal_code' => $request->postal_code,
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount ?? 0,
                'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
                'tax_amount' => $taxPayload['tax_amount'],
                'grand_total' => $taxPayload['grand_total'],
                'total_amount' => $taxPayload['grand_total'],
                'total_paid' => $request->total_paid ?? 0,
                'outstanding' => max($taxPayload['grand_total'] - ($request->total_paid ?? 0), 0),
                'internal_notes' => $request->internal_notes,
                'additional_notes' => $request->additional_notes,
                'terms_conditions' => $request->terms_conditions,
                'payment_method' => $request->payment_method ?? 'virtual_account',
                'virtual_account_number' => $request->virtual_account_number,
                'updated_by' => Auth::id(),
            ]);

            // Create activity log
            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'activity_type' => 'updated',
                'notes' => 'Invoice updated',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice->load(['customer', 'taxSetting'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Invoice Update Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildInvoiceTaxPayload(Customer $customer, float $subtotal, float $discountAmount = 0, ?string $requestedTaxCode = null): array
    {
        $customerTax = CustomerTax::query()
            ->where('customer_id', $customer->id)
            ->where('is_active', true)
            ->where('effective_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        $resolvedTaxCode = $requestedTaxCode
            ?: $customerTax?->ppn_code
            ?: $customer->ppn_code
            ?: '01';

        $financeTaxCode = FinanceTaxCode::query()
            ->where('code', $resolvedTaxCode)
            ->where('is_active', true)
            ->first();

        if (!$financeTaxCode) {
            $financeTaxCode = FinanceTaxCode::query()
                ->where('code', '01')
                ->where('is_active', true)
                ->first();
        }

        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $subtotalAfterDiscount = max(round($subtotal - $discountAmount, 2), 0);
        $shouldApplyPpn = $financeTaxCode?->appliesPpnToInvoice() && $defaultVatSetting;
        $taxRate = $shouldApplyPpn ? (float) $defaultVatSetting->tax_rate : 0;
        $taxAmount = round($subtotalAfterDiscount * ($taxRate / 100), 2);
        $grandTotal = round($subtotalAfterDiscount + $taxAmount, 2);

        return [
            'tax_setting_id' => $defaultVatSetting?->id,
            'tax_code' => $financeTaxCode?->code ?: $resolvedTaxCode,
            'tax_number' => $customerTax?->tax_number ?: $customer->npwp,
            'npwp_number' => $customerTax?->tax_number ?: $customer->npwp,
            'tax_address' => $customerTax?->tax_address ?: ($customer->npwp_address ?: $customer->address),
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'finance_tax_code' => $financeTaxCode,
            'default_vat_setting' => $defaultVatSetting,
        ];
    }

    private function calculateInvoiceSubtotal(Invoice $invoice): float
    {
        $rentalSubtotal = (float) $invoice->invoiceRentalDetails()->sum('total_price');
        $detailSubtotal = (float) $invoice->invoiceDetails()->sum('total_price');

        return round($rentalSubtotal + $detailSubtotal, 2);
    }

    private function syncDraftInvoiceFinancialSnapshot(Invoice $invoice): void
    {
        if ($invoice->invoice_status !== 'draft') {
            return;
        }

        $invoice->loadMissing('customer');

        if (!$invoice->customer) {
            return;
        }

        $subtotal = $this->calculateInvoiceSubtotal($invoice);
        $taxPayload = $this->buildInvoiceTaxPayload(
            $invoice->customer,
            $subtotal,
            (float) ($invoice->discount_amount ?? 0),
            $invoice->tax_code
        );

        $expectedOutstanding = max($taxPayload['grand_total'] - ((float) $invoice->total_paid), 0);

        $shouldSync = round((float) $invoice->subtotal, 2) !== $subtotal
            || round((float) $invoice->subtotal_after_discount, 2) !== round($taxPayload['subtotal_after_discount'], 2)
            || round((float) $invoice->tax_amount, 2) !== round($taxPayload['tax_amount'], 2)
            || round((float) $invoice->grand_total, 2) !== round($taxPayload['grand_total'], 2)
            || (int) ($invoice->tax_setting_id ?? 0) !== (int) ($taxPayload['tax_setting_id'] ?? 0)
            || (string) ($invoice->tax_code ?? '') !== (string) ($taxPayload['tax_code'] ?? '')
            || (string) ($invoice->tax_number ?? '') !== (string) ($taxPayload['tax_number'] ?? '')
            || (string) ($invoice->npwp_number ?? '') !== (string) ($taxPayload['npwp_number'] ?? '')
            || (string) ($invoice->tax_address ?? '') !== (string) ($taxPayload['tax_address'] ?? '')
            || round((float) $invoice->outstanding, 2) !== round($expectedOutstanding, 2);

        if (!$shouldSync) {
            return;
        }

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'tax_setting_id' => $taxPayload['tax_setting_id'],
            'tax_code' => $taxPayload['tax_code'],
            'tax_number' => $taxPayload['tax_number'],
            'npwp_number' => $taxPayload['npwp_number'],
            'tax_address' => $taxPayload['tax_address'],
            'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
            'tax_amount' => $taxPayload['tax_amount'],
            'total_amount' => $taxPayload['grand_total'],
            'grand_total' => $taxPayload['grand_total'],
            'outstanding' => $expectedOutstanding,
        ])->saveQuietly();

        $invoice->refresh();
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $invoice->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_invoices = Invoice::count();
        $draft_invoices = Invoice::where('invoice_status', 'draft')->count();
        $sent_invoices = Invoice::where('invoice_status', 'sent')->count();
        $paid_invoices = Invoice::where('invoice_status', 'paid')->count();
        $overdue_invoices = Invoice::where('invoice_status', 'overdue')->count();
        $cancelled_invoices = Invoice::where('invoice_status', 'cancelled')->count();

        $total_revenue = Invoice::where('invoice_status', 'paid')->sum('total_invoice');
        $outstanding_amount = Invoice::whereIn('invoice_status', ['sent', 'overdue'])->sum('total_invoice');

        $recent_invoices = Invoice::with(['contract'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $invoices_by_status = [
            'draft' => $draft_invoices,
            'sent' => $sent_invoices,
            'paid' => $paid_invoices,
            'overdue' => $overdue_invoices,
            'cancelled' => $cancelled_invoices
        ];

        return view('finance.dashboard', compact(
            'total_invoices',
            'draft_invoices',
            'sent_invoices',
            'paid_invoices',
            'overdue_invoices',
            'cancelled_invoices',
            'total_revenue',
            'outstanding_amount',
            'recent_invoices',
            'invoices_by_status'
        ));
    }

    public function generateInvoiceNumber()
    {
        return $this->generateStandardInvoiceNumber();
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = Invoice::whereIn('id', $request->ids)->delete(); // Soft delete
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} invoice(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function send(Invoice $invoice)
    {
        // Rule 44: invoice must be approved, have tax number, and have PDF file
        $allowedStatuses = ['approved', 'tax_approved', 'paid'];
        if (!in_array($invoice->invoice_status, $allowedStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice must be in Approved or Tax Approved status before sending email.'
            ], 422);
        }

        if (empty($invoice->faktur_pajak)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Faktur Pajak number is missing.'
            ], 422);
        }

        // Check for PDF file (Assuming type 'faktur_pajak' or similar)
        $hasPdf = $invoice->invoiceFiles()->where('file_name', 'like', '%.pdf')->exists();
        if (!$hasPdf) {
            return response()->json([
                'status' => 'error',
                'message' => 'Faktur Pajak PDF file is missing.'
            ], 422);
        }

        try {
            $invoice->update([
                'invoice_status' => 'sent',
                'is_emailed' => true,
                'emailed_at' => now()
            ]);
            
            // Create activity log
            $invoice->invoiceActivities()->create([
                'activity_type' => 'sent',
                'notes' => 'Invoice sent to customer via email',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error sending invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markPaid(Invoice $invoice)
    {
        try {
            DB::beginTransaction();

            $oldStatus = $invoice->invoice_status;
            
            $invoice->update([
                'invoice_status' => 'paid',
                'total_paid' => $invoice->grand_total,
                'outstanding' => 0
            ]);
            
            // Create activity log
            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'activity_type' => 'paid',
                'notes' => 'Invoice marked as paid',
                'created_by' => Auth::id(),
            ]);

            // AUTO-CALCULATE COMMISSION when invoice is marked as paid (Berdasarkan BRD)
            if ($oldStatus !== 'paid') {
                $this->triggerAutoCommissionCalculation($invoice);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice marked as paid successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking invoice as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadFile(Request $request, Invoice $invoice)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240', // 10MB
            'file_category' => 'required|in:tax_invoice,attachment',
            'description' => 'nullable|string'
        ]);

        try {
            $file = $request->file('file');
            $category = $request->file_category;
            
            // Upload Logic
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $filePath = 'uploads/invoices/' . $invoice->id . '/' . $fileName;
            
            // Store file
            $file->storeAs('public/uploads/invoices/' . $invoice->id, $fileName);
            $publicPath = 'uploads/invoices/' . $invoice->id . '/' . $fileName;

            // Create InvoiceFile record
            $invoiceFile = new \App\Models\Finance\InvoiceFile();
            $invoiceFile->invoice_id = $invoice->id;
            $invoiceFile->file_name = $originalName;
            $invoiceFile->file_path = $publicPath;
            $invoiceFile->file_type = strtolower($extension);
            $invoiceFile->description = $request->description;
            $invoiceFile->created_by = auth()->id();
            $invoiceFile->updated_by = auth()->id();

            if ($category === 'tax_invoice') {
                $invoiceFile->description = $invoiceFile->description ? $invoiceFile->description . ' (Faktur Pajak)' : 'Faktur Pajak';
                
                // Update Invoice specific fields
                $invoice->faktur_pajak = $originalName; // Store filename as reference
                // $invoice->faktur_pajak_status = 'uploaded'; // Assuming logic exists, but let's stick to user request "trigger"
                $invoice->save();
            }

            $invoiceFile->save();

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully.',
                'data' => $invoiceFile
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading invoice file: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadCombined(Request $request, Invoice $invoice)
    {
        $request->validate([
            'file_ids' => 'nullable|array',
            'file_ids.*' => 'string'
        ]);

        $selectedIds = $request->input('file_ids', []);
        
        // If no files selected (e.g. from Print button), auto-select ALL files
        if (empty($selectedIds)) {
            $allFiles = $this->getAllInvoiceAttachments($invoice);
            $selectedIds = $allFiles->pluck('id')->toArray();
        }

        // 0. Validation: Ensure invoice is ready for print/download.
        // Approved invoices can be printed even before Faktur Pajak is uploaded.
        if ($invoice->invoice_status === 'draft') {
            abort(403, 'Invoice must be approved before printing.');
        }

        try {
            // 1. Generate Invoice PDF
            // Ensure necessary data is loaded (Matching printInvoice method)
            $invoice->load([
                'invoiceDetails', 
                'invoiceRentalDetails.masterRental', 
                'invoiceRentalDetails.jobSchedule.room',
                'customer', 
                'contract.billingGroup',
                'contract.branch.invoiceAuthorizedByUser',
                'bankReceipts',
                'taxSetting'
            ]);
            
            // Generate PDF using existing view
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.invoices.print_template', compact('invoice'));
            $invoicePdfContent = $pdf->output();

            // Save temp invoice PDF
            $tempInvoicePath = storage_path('app/temp/invoice_' . $invoice->id . '_' . time() . '.pdf');
            if (!file_exists(dirname($tempInvoicePath))) {
                mkdir(dirname($tempInvoicePath), 0755, true);
            }
            file_put_contents($tempInvoicePath, $invoicePdfContent);

            // 2. Prepare paths for Node.js script
            $mergePaths = [$tempInvoicePath];

            // 3. (REMOVED) Static CSR Generation block was here. 
            // Now handled dynamically in Step 4 below based on selectedIds (including sys-csr virtual IDs).
            
            $generatedTempPdfs = []; // Track CSR PDFs to cleanup later

            // 4. Collect Additional Attachments
            foreach ($selectedIds as $id) {
                if (strpos($id, 'sys-csr-') === 0) {
                    // Logic for System Generated CSR
                    try {
                        $jobId = str_replace('sys-csr-', '', $id);
                        $baseJob = \App\Models\JobSchedule::find($jobId);

                        if ($baseJob && in_array($baseJob->status, ['done_job', 'completed'])) {
                            // Fetch all sibling jobs in the same group to include all rooms
                            $jobs = \App\Models\JobSchedule::with([
                                'jobAdvice.customer', 'jobAdvice.contract', 'building', 
                                'assignedTechnician', 'jobAssignSchedules.team',
                                'jobScheduleRooms.room', 'jobScheduleRooms.jobAdviceRoom.rentalProduct'
                            ])
                            ->where('job_number', $baseJob->job_number)
                            ->whereIn('status', ['done_job', 'completed'])
                            ->get();

                            // Match grouping logic from JobScheduleController.printCsr
                            $groupedJobs = $jobs->groupBy('job_number');
                            
                            $csrPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('operational.job-schedules.pdf-csr', [
                                'groupedJobs' => $groupedJobs,
                                'selectedRoomIds' => null
                            ]);
                            
                            $tempCsrPath = storage_path('app/temp/dyn_csr_' . $jobId . '_' . time() . '.pdf');
                            file_put_contents($tempCsrPath, $csrPdf->output());
                            
                            $mergePaths[] = $tempCsrPath;
                            $generatedTempPdfs[] = $tempCsrPath;
                        }
                    } catch (\Exception $e) {
                        \Log::warning("Failed to include Sys CSR $id in merge: " . $e->getMessage());
                    }
                    continue;
                }

                $parts = explode('-', $id);
                $prefix = $parts[0];
                $dbId = $parts[1] ?? 0;

                $fileModel = null;
                if ($prefix === 'inv') {
                    $fileModel = \App\Models\Finance\InvoiceFile::find($dbId);
                } elseif ($prefix === 'cont') {
                    $fileModel = \App\Models\ContractFile::find($dbId);
                } elseif ($prefix === 'ba') {
                    $fileModel = \App\Models\JobScheduleBaFile::find($dbId);
                }

                if ($fileModel && $fileModel->file_path) {
                    $filePath = $this->resolveAttachmentPath($fileModel->file_path);
                    if ($filePath) {
                        $mergePaths[] = $filePath;
                    }
                }
            }

            // 5. Call Node.js script to merge
            $tempOutputPath = storage_path('app/temp/merged_' . $invoice->id . '_' . time() . '.pdf');
            $scriptPath = base_path('app/Scripts/pdf-merge.js');
            
            // Escape paths for shell
            $escapedOutput = escapeshellarg($tempOutputPath);
            $escapedInputs = array_map('escapeshellarg', $mergePaths);
            $command = "node $scriptPath $escapedOutput " . implode(' ', $escapedInputs);
            
            exec($command . " 2>&1", $output, $returnVar);

            // Cleanup temp PDFs
            if (file_exists($tempInvoicePath)) unlink($tempInvoicePath);
            foreach ($generatedTempPdfs as $tp) {
                if (file_exists($tp)) unlink($tp);
            }

            if ($returnVar !== 0) {
                $errorMsg = implode("\n", $output);
                throw new \Exception("Node.js merge failed ($returnVar): " . $errorMsg);
            }

            if (!file_exists($tempOutputPath)) {
                throw new \Exception("Merged PDF output file not created.");
            }

            // Mark as printed
            $invoice->update([
                'is_printed' => true,
                'printed_at' => now(),
            ]);

            // If combined download with multiple files, we should also mark them?
            // User requested: "jika invoice itu sudah pernah di print dari button print di show.blade"
            // The downloadCombined is used by both button print and combined download.
            // So this covers both.

            // 6. Serve Merged PDF
            $sanitizedNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice->invoice_number);
            $outputFilename = 'Invoice_Combined_' . $sanitizedNumber . '.pdf';

            $response = response()->stream(function() use ($tempOutputPath) {
                $stream = fopen($tempOutputPath, 'rb');
                fpassthru($stream);
                fclose($stream);
                // Unlink after streaming
                if (file_exists($tempOutputPath)) unlink($tempOutputPath);
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($request->query('inline') === 'true' ? 'inline' : 'attachment') . '; filename="' . $outputFilename . '"',
            ]);

            return $response;

        } catch (\Exception $e) {
            \Log::error('Error generating combined PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating combined PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rule 43: Cancel Faktur Pajak
     */
    public function cancelFakturPajak(Invoice $invoice)
    {
        try {
            $invoice->update([
                'faktur_pajak_status' => 'cancelled'
            ]);

            $invoice->invoiceActivities()->create([
                'activity_type' => 'cancelled',
                'notes' => 'Faktur Pajak cancelled by user',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Faktur Pajak cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling Faktur Pajak: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Invoice $invoice)
    {
        if ($invoice->invoice_status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only Draft invoices can be approved.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoice->update([
                'invoice_status' => 'approved'
            ]);

            $invoice->invoiceActivities()->create([
                'activity_type' => 'updated',
                'notes' => 'Invoice approved',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error approving invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function taxApprove(Invoice $invoice)
    {
        if ($invoice->invoice_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved invoices can be Tax Approved.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoice->update([
                'invoice_status' => 'tax_approved'
            ]);

            $invoice->invoiceActivities()->create([
                'activity_type' => 'updated',
                'notes' => 'Invoice tax approved',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error tax approving invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Invoice $invoice)
    {
        // Rule 43: cancellation only if no faktur or faktur cancelled
        if (!$invoice->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel invoice. Please cancel the Faktur Pajak first.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoice->update([
                'invoice_status' => 'cancelled',
                'status' => 'cancelled',
            ]);
            
            $invoice->invoiceActivities()->create([
                'activity_type' => 'cancelled',
                'notes' => 'Invoice cancelled',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Invoice cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error cancelling invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reload tax data from customer master to invoice
     * Only allowed for draft invoices without faktur pajak
     */
    public function reloadTaxData(Invoice $invoice)
    {
        // Validasi: hanya draft dan faktur pajak belum di-upload
        if ($invoice->invoice_status !== 'draft' || !empty($invoice->faktur_pajak)) {
            return response()->json([
                'success' => false,
                'message' => 'Tax data can only be reloaded for draft invoices without tax invoice.'
            ], 422);
        }

        try {
            $taxPayload = $this->buildInvoiceTaxPayload(
                $invoice->customer,
                (float) $invoice->subtotal,
                (float) ($invoice->discount_amount ?? 0),
                $invoice->tax_code
            );

            $invoice->update([
                'tax_setting_id' => $taxPayload['tax_setting_id'],
                'tax_code' => $taxPayload['tax_code'],
                'tax_number' => $taxPayload['tax_number'],
                'npwp_number' => $taxPayload['npwp_number'],
                'tax_address' => $taxPayload['tax_address'],
                'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
                'tax_amount' => $taxPayload['tax_amount'],
                'grand_total' => $taxPayload['grand_total'],
                'total_amount' => $taxPayload['grand_total'],
                'outstanding' => max($taxPayload['grand_total'] - ($invoice->total_paid ?? 0), 0),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tax data reloaded successfully from customer master.',
                'data' => [
                    'tax_code' => $invoice->tax_code,
                    'tax_number' => $invoice->tax_number,
                    'npwp_number' => $invoice->npwp_number,
                    'tax_address' => $invoice->tax_address,
                    'tax_amount' => $invoice->tax_amount,
                    'grand_total' => $invoice->grand_total,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reloading tax data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print invoice template
     * Load with necessary relationships for print
     */
    public function printInvoice(Invoice $invoice)
    {
        // Berdasarkan User Request: "file pendukung yang gw upload, kenapa ga ikut ke print"
        // Kita alihkan print standar ke downloadCombined dengan parameter inline=true
        // agar lampiran otomatis digabung ke PDF saat user klik PRINT.
        return $this->downloadCombined(request()->merge(['inline' => 'true']), $invoice);
    }

    /**
     * Export Delivery Receipt PDF
     */
    public function exportDeliveryReceipt(Invoice $invoice)
    {
        // Validasi: delivery info harus terisi
        if (empty($invoice->dikirim_oleh) || empty($invoice->pada)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery information (Dikirim Oleh & Diterima Pada) must be completed first.'
            ], 422);
        }
        
        $invoice->load(['customer', 'invoiceDetails']);

        // Generate PDF tanda terima
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.invoices.delivery_receipt_pdf', compact('invoice'));
        $filename = 'TandaTerima_' . str_replace(['/', '\\'], '-', $invoice->invoice_number) . '.pdf';
        return $pdf->download($filename);
    }

    public function bulkSend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoices = Invoice::whereIn('id', $request->ids)->get();
            $count = 0;

            foreach ($invoices as $invoice) {
                $invoice->update(['invoice_status' => 'sent']);
                
                // Create activity log
                InvoiceActivity::create([
                    'invoice_id' => $invoice->id,
                    'activity_type' => 'sent',
                    'description' => 'Invoice sent to customer',
                    'performed_by' => Auth::id(),
                    'performed_at' => now(),
                ]);
                
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully sent {$count} invoice(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = Invoice::with(['contract', 'creator']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('po_number', 'like', '%' . $search . '%')
                  ->orWhere('contract_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('invoice_status', $request->status);
        }


        if ($request->filled('contract')) {
            $query->where('contract_number', $request->contract);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = 'invoices_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Invoice Number',
                'PO Number',
                'Contract Number',
                'Company Name',
                'Invoice Date',
                'Due Date',
                'Status',
                'Subtotal',
                'Tax Amount',
                'Grand Total',
                'Total Paid',
                'Outstanding',
                'Payment Method',
                'Created By',
                'Created Date'
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->po_number ?? '',
                    $invoice->contract_number ?? '',
                    $invoice->customer->name ?? '',
                    $invoice->invoice_date->format('Y-m-d'),
                    $invoice->due_date->format('Y-m-d'),
                    $invoice->invoice_status,
                    $invoice->subtotal,
                    $invoice->tax_amount,
                    $invoice->grand_total,
                    $invoice->total_paid,
                    $invoice->outstanding,
                    $invoice->payment_method,
                    $invoice->creator->name ?? '',
                    $invoice->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Auto-generate invoice after all jobs in rental period are completed
     */
    public function autoGenerateInvoice(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'rental_period' => 'required|string',
                'period_start' => 'required|date',
                'period_end' => 'required|date'
            ]);

            $contract = Contract::with(['customer', 'quotation'])->find($request->contract_id);
            
            if (!$contract) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contract not found'
                ], 404);
            }

            // Check if all jobs in the rental period are completed
            $completedJobs = $this->checkAllJobsCompleted($contract->id, $request->period_start, $request->period_end);
            
            if (!$completedJobs) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not all jobs in the rental period are completed yet'
                ], 422);
            }

            // Generate invoice number using the shared document numbering format.
            $invoiceNumber = $this->generateStandardInvoiceNumber($contract);

            // Create invoice
            // Calculate Invoice Date based on Contract Preference
            $invoiceDate = $contract->calculateInvoiceDate() ?? now();
            
            // Calculate Due Date based on terms
            $term = (int) ($contract->term_of_payment ?? 30);
            if ($term <= 0) $term = 30;
            $dueDate = $invoiceDate->copy()->addDays($term);

            // Create invoice
            $invoice = Invoice::create([
                'contract_number' => $contract->contract_number,
                'invoice_number' => $invoiceNumber,
                'po_number' => $contract->quotation->quotation_number ?? null,
                'customer_id' => $contract->customer_id,
                'billing_address' => $contract->customer->address ?? '',
                'period_invoice' => $request->rental_period,
                'invoice_status' => 'draft',
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $contract->contract_value ?? 0,
                'tax_amount' => 0,
                'total_amount' => $contract->contract_value ?? 0,
                'notes' => 'Auto-generated after all jobs completed for period: ' . $request->rental_period,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice auto-generated successfully after all jobs completed',
                'data' => $invoice->load(['contract', 'creator'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to auto-generate invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if all jobs in rental period are completed
     */
    private function checkAllJobsCompleted($contractId, $periodStart, $periodEnd)
    {
        // This is a simplified check - in real implementation, you would check actual job completion status
        // For now, we'll assume jobs are completed if they exist in the period
        
        $jobsInPeriod = DB::table('job_schedules')
            ->join('job_advices', 'job_schedules.job_advice_id', '=', 'job_advices.id')
            ->where('job_advices.contract_id', $contractId)
            ->whereBetween('job_schedules.scheduled_date', [$periodStart, $periodEnd])
            ->count();

        $completedJobs = DB::table('job_schedules')
            ->join('job_advices', 'job_schedules.job_advice_id', '=', 'job_advices.id')
            ->where('job_advices.contract_id', $contractId)
            ->whereBetween('job_schedules.scheduled_date', [$periodStart, $periodEnd])
            ->where('job_schedules.status', 'completed')
            ->count();

        return $jobsInPeriod > 0 && $jobsInPeriod === $completedJobs;
    }

    /**
     * Get contracts ready for auto-invoice generation
     */
    public function getContractsReadyForInvoice()
    {
        try {
            $contracts = Contract::with(['customer', 'quotation'])
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->get()
                ->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'customer_name' => $contract->customer->name,
                        'contract_value' => $contract->contract_value,
                        'start_date' => $contract->start_date,
                        'end_date' => $contract->end_date,
                        'rental_period' => $contract->quotation->rental_period ?? '12 months'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $contracts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get contracts ready for invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger auto commission calculation when invoice is paid (Berdasarkan BRD)
     */
    private function triggerAutoCommissionCalculation(Invoice $invoice)
    {
        try {
            // Check if invoice is paid within 180 days (BRD requirement)
            $daysSinceInvoice = now()->diffInDays($invoice->invoice_date);
            
            if ($daysSinceInvoice > 180) {
                \Log::info("Commission calculation skipped for invoice {$invoice->invoice_number}: Payment after 180 days");
                return;
            }

            // Get contract and marketing user
            $contract = $invoice->contract;
            if (!$contract) {
                \Log::info("No contract found for invoice {$invoice->invoice_number}");
                return;
            }

            $marketingUser = $contract->marketing_id ? \App\Models\User::find($contract->marketing_id) : null;
            if (!$marketingUser) {
                \Log::info("No marketing user found for contract {$contract->contract_number}");
                return;
            }

            // Use CommissionCalculationService to calculate commission on cash receipt
            $commissionService = new \App\Services\Finance\CommissionCalculationService();
            $cashReceiptDate = now(); // Use current date as cash receipt date
            
            $result = $commissionService->calculateCommissionOnCashReceipt(
                $invoice,
                $cashReceiptDate->toDateString()
            );

            if ($result['success']) {
                \Log::info("Auto-calculated commission for invoice {$invoice->invoice_number}: {$result['commission']->final_amount} for user {$marketingUser->name}");
            } else {
                \Log::warning("Commission calculation for invoice {$invoice->invoice_number}: {$result['message']}");
            }

        } catch (\Exception $e) {
            \Log::error("Failed to trigger auto commission calculation for invoice {$invoice->invoice_number}: " . $e->getMessage());
            // Don't throw exception to avoid breaking the main workflow
        }
    }

    /**
     * Auto-generate invoice for rental period (Invoice Generation Enhancement)
     */
    public function autoGenerateInvoiceForRentalPeriod(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'rental_period' => 'required|string',
                'period_start' => 'required|date',
                'period_end' => 'required|date'
            ]);

            $invoiceGenerationService = new InvoiceGenerationService();
            
            $result = $invoiceGenerationService->autoGenerateInvoiceForRentalPeriod(
                $request->contract_id,
                $request->rental_period,
                Carbon::parse($request->period_start),
                Carbon::parse($request->period_end)
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => [
                        'invoice' => $result['invoice'],
                        'rental_period' => $result['rental_period'],
                        'period_start' => $result['period_start'],
                        'period_end' => $result['period_end']
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'data' => $result
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to auto-generate invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rental periods for contract
     */
    public function getRentalPeriods(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:contracts,id'
            ]);

            $invoiceGenerationService = new InvoiceGenerationService();
            $periods = $invoiceGenerationService->getRentalPeriodsForContract($request->contract_id);

            return response()->json([
                'status' => 'success',
                'data' => $periods
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get rental periods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoices for multiple rental periods
     */
    public function generateInvoicesForMultiplePeriods(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'rental_periods' => 'required|array',
                'rental_periods.*.rental_period' => 'required|string',
                'rental_periods.*.period_start' => 'required|date',
                'rental_periods.*.period_end' => 'required|date'
            ]);

            $invoiceGenerationService = new InvoiceGenerationService();
            
            $result = $invoiceGenerationService->generateInvoicesForMultiplePeriods(
                $request->contract_id,
                $request->rental_periods
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice generation completed',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate invoices for multiple periods: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDatePreference(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $contract = $invoice->contract;
        
        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Contract not found']);
        }

        $preference = $request->input('preference');
        $manualDate = $request->input('manual_date') ?? $request->input('custom_date');

        // Update Contract Preference
        $contract->update(['invoice_date_preference' => $preference]);

        // Calculate New Date using Contract method
        $newDate = $contract->calculateInvoiceDate($preference, $manualDate, $invoice->invoice_date);

        if ($newDate) {
            $invoice->invoice_date = $newDate;
            
            // Update Due Date based on contract terms
            $term = (int) ($contract->term_of_payment ?? 30);
            if ($term <= 0) $term = 30;
            $invoice->due_date = $newDate->copy()->addDays($term);
            
            $invoice->save();

            return response()->json([
                'success' => true, 
                'message' => 'Invoice date preference updated.',
                'new_date' => $newDate->format('Y-m-d')
            ]);
        }
        
        return response()->json(['success' => false, 'message' => 'Could not calculate a valid date based on preference.']);
    }

    /**
     * Update rental detail price and recalculate invoice totals (AJAX)
     */
    public function updateRentalPrice(Request $request, Invoice $invoice, $rentalDetailId)
    {
        $request->validate([
            'unit_price' => 'required|numeric|min:0'
        ]);
        
        // Only allow update if invoice is draft
        if ($invoice->invoice_status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya invoice dengan status Draft yang dapat diedit.'
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            // Update rental detail price
            $rentalDetail = \App\Models\Finance\InvoiceRentalDetail::findOrFail($rentalDetailId);
            $rentalDetail->update([
                'unit_price' => $request->unit_price,
                'total_price' => $request->unit_price * $rentalDetail->quantity,
                'updated_by' => Auth::id()
            ]);
            
            // Recalculate invoice totals
            $subtotal = $invoice->invoiceRentalDetails()->sum('total_price');
            $taxPayload = $this->buildInvoiceTaxPayload(
                $invoice->customer,
                (float) $subtotal,
                (float) ($invoice->discount_amount ?? 0),
                $invoice->tax_code
            );
            $taxAmount = $taxPayload['tax_amount'];
            $grandTotal = $taxPayload['grand_total'];
            
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_setting_id' => $taxPayload['tax_setting_id'],
                'tax_code' => $taxPayload['tax_code'],
                'tax_number' => $taxPayload['tax_number'],
                'npwp_number' => $taxPayload['npwp_number'],
                'tax_address' => $taxPayload['tax_address'],
                'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
                'tax_amount' => $taxAmount,
                'total_amount' => $grandTotal,
                'grand_total' => $grandTotal,
                'outstanding' => max($grandTotal - ($invoice->total_paid ?? 0), 0),
                'updated_by' => Auth::id()
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Price updated successfully',
                'rental_total' => $rentalDetail->total_price,
                'formatted_rental_total' => number_format($rentalDetail->total_price, 0, ',', '.'),
                'subtotal' => $subtotal,
                'formatted_subtotal' => number_format($subtotal, 0, ',', '.'),
                'discount_amount' => $invoice->discount_amount,
                'formatted_discount' => number_format($invoice->discount_amount, 0, ',', '.'),
                'subtotal_after_discount' => $invoice->subtotal_after_discount,
                'formatted_subtotal_after_discount' => number_format($invoice->subtotal_after_discount, 0, ',', '.'),
                'tax_amount' => $taxAmount,
                'formatted_tax' => number_format($taxAmount, 0, ',', '.'),
                'show_tax' => $taxPayload['tax_amount'] > 0 || ($taxPayload['finance_tax_code']?->hasZeroTaxPrint() ?? false),
                'tax_label' => ($taxPayload['finance_tax_code']?->hasZeroTaxPrint() ?? false) ? 'PPN (0%)' : 'PPN',
                'show_discount' => (float) $invoice->discount_amount > 0,
                'grand_total' => $grandTotal,
                'formatted_grand_total' => number_format($grandTotal, 0, ',', '.'),
                'outstanding' => $invoice->outstanding,
                'formatted_outstanding' => number_format($invoice->outstanding, 0, ',', '.')
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update price: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDiscount(Request $request, Invoice $invoice)
    {
        $request->validate([
            'discount_amount' => 'required|numeric|min:0'
        ]);

        if ($invoice->invoice_status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya invoice dengan status Draft yang dapat diedit.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $subtotal = (float) $invoice->invoiceRentalDetails()->sum('total_price');
            $discountAmount = (float) $request->discount_amount;
            $taxPayload = $this->buildInvoiceTaxPayload(
                $invoice->customer,
                $subtotal,
                $discountAmount,
                $invoice->tax_code
            );

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_setting_id' => $taxPayload['tax_setting_id'],
                'tax_code' => $taxPayload['tax_code'],
                'tax_number' => $taxPayload['tax_number'],
                'npwp_number' => $taxPayload['npwp_number'],
                'tax_address' => $taxPayload['tax_address'],
                'subtotal_after_discount' => $taxPayload['subtotal_after_discount'],
                'tax_amount' => $taxPayload['tax_amount'],
                'total_amount' => $taxPayload['grand_total'],
                'grand_total' => $taxPayload['grand_total'],
                'outstanding' => max($taxPayload['grand_total'] - ($invoice->total_paid ?? 0), 0),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Discount updated successfully',
                'subtotal' => $invoice->subtotal,
                'formatted_subtotal' => number_format($invoice->subtotal, 0, ',', '.'),
                'discount_amount' => $invoice->discount_amount,
                'formatted_discount' => number_format($invoice->discount_amount, 0, ',', '.'),
                'subtotal_after_discount' => $invoice->subtotal_after_discount,
                'formatted_subtotal_after_discount' => number_format($invoice->subtotal_after_discount, 0, ',', '.'),
                'tax_amount' => $invoice->tax_amount,
                'formatted_tax' => number_format($invoice->tax_amount, 0, ',', '.'),
                'show_tax' => $taxPayload['tax_amount'] > 0 || ($taxPayload['finance_tax_code']?->hasZeroTaxPrint() ?? false),
                'tax_label' => ($taxPayload['finance_tax_code']?->hasZeroTaxPrint() ?? false) ? 'PPN (0%)' : 'PPN',
                'show_discount' => (float) $invoice->discount_amount > 0,
                'grand_total' => $invoice->grand_total,
                'formatted_grand_total' => number_format($invoice->grand_total, 0, ',', '.'),
                'outstanding' => $invoice->outstanding,
                'formatted_outstanding' => number_format($invoice->outstanding, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update discount: ' . $e->getMessage()
            ], 500);
        }
    }

    public function regenerate(Invoice $invoice)
    {
        $context = $this->resolveInvoiceRegenerationContext($invoice);

        if (!$context['can_regenerate']) {
            return response()->json([
                'success' => false,
                'message' => $context['message'] ?? 'Invoice ini tidak bisa diregenerate.',
            ], 422);
        }

        try {
            if ($context['type'] === 'billing_group') {
                $result = app(\App\Services\Finance\BillingGroupService::class)
                    ->autoGenerateInvoiceWhenJobsCompleted(
                        $context['billing_group_id'],
                        $context['billing_date'] ?? null,
                        $invoice
                    );
            } else {
                $result = app(InvoiceGenerationService::class)
                    ->autoGenerateInvoiceForRentalPeriod(
                        $context['contract_id'],
                        $context['rental_period'],
                        $context['period_start'],
                        $context['period_end']
                    );
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Regenerate invoice gagal.',
                    'data' => $result,
                ], 422);
            }

            $newInvoice = $result['invoice']->load(['customer', 'contract', 'billingGroup']);

            $invoice->invoiceActivities()->create([
                'activity_type' => 'updated',
                'notes' => 'Invoice regenerated to ' . ($newInvoice->invoice_number ?? 'new invoice'),
                'created_by' => Auth::id(),
            ]);

            $newInvoice->invoiceActivities()->create([
                'activity_type' => 'created',
                'notes' => 'Invoice regenerated from cancelled invoice ' . $invoice->invoice_number,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil diregenerate menjadi ' . $newInvoice->invoice_number,
                'data' => [
                    'invoice_id' => $newInvoice->id,
                    'invoice_number' => $newInvoice->invoice_number,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error regenerate invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update additional notes (AJAX)
     */
    public function updateNotes(Request $request, Invoice $invoice)
    {
        $request->validate([
            'additional_notes' => 'nullable|string|max:1000'
        ]);
        
        // Only allow update if invoice is draft
        if ($invoice->invoice_status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya invoice dengan status Draft yang dapat diedit.'
            ], 403);
        }
        
        $invoice->update([
            'additional_notes' => $request->additional_notes,
            'updated_by' => Auth::id()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notes updated successfully'
        ]);
    }
    
    /**
     * Update invoice internal notes (AJAX)
     */
    public function updateInternalNotes(Request $request, Invoice $invoice)
    {
        // Only allow editing draft invoices
        if ($invoice->invoice_status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya invoice dengan status Draft yang dapat diedit.'
            ], 403);
        }
        
        $request->validate([
            'internal_notes' => 'nullable|string'
        ]);
        
        $invoice->update([
            'internal_notes' => $request->internal_notes,
            'updated_by' => Auth::id()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Internal notes updated successfully'
        ]);
    }

    private function generateStandardInvoiceNumber(?Contract $contract = null): string
    {
        return app(DocumentNumberService::class)->generate(
            'invoice',
            null,
            null,
            $contract?->id,
            null,
            null,
            null,
            Auth::user()?->branch_id
        );
    }

    private function resolveInvoiceRegenerationContext(Invoice $invoice): array
    {
        if ($invoice->invoice_status !== Invoice::STATUS_CANCELLED) {
            return [
                'can_regenerate' => false,
                'message' => 'Hanya invoice berstatus cancelled yang bisa diregenerate.',
            ];
        }

        if ($invoice->billing_group_id) {
            $invoiceDate = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date) : now();
            $existingInvoice = Invoice::where('billing_group_id', $invoice->billing_group_id)
                ->where('id', '!=', $invoice->id)
                ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
                ->whereMonth('invoice_date', $invoiceDate->month)
                ->whereYear('invoice_date', $invoiceDate->year)
                ->first();

            if ($existingInvoice) {
                return [
                    'can_regenerate' => false,
                    'message' => 'Invoice pengganti sudah ada: ' . $existingInvoice->invoice_number,
                ];
            }

            return [
                'can_regenerate' => true,
                'type' => 'billing_group',
                'billing_group_id' => $invoice->billing_group_id,
                'billing_date' => $invoiceDate,
            ];
        }

        $contract = $invoice->contract;
        if (!$contract || empty($invoice->period_invoice)) {
            return [
                'can_regenerate' => false,
                'message' => 'Regenerate hanya tersedia untuk invoice otomatis yang punya billing group atau periode invoice.',
            ];
        }

        $period = collect(app(InvoiceGenerationService::class)->getRentalPeriodsForContract($contract->id))
            ->firstWhere('rental_period', $invoice->period_invoice);

        if (!$period || empty($period['period_start']) || empty($period['period_end'])) {
            return [
                'can_regenerate' => false,
                'message' => 'Periode invoice tidak bisa dipetakan ulang untuk regenerate.',
            ];
        }

        $existingInvoice = Invoice::where(function ($query) use ($contract, $invoice) {
                $query->where('contract_id', $contract->id)
                    ->orWhere('contract_number', $invoice->contract_number);
            })
            ->where('id', '!=', $invoice->id)
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $invoice->period_invoice)
            ->first();

        if ($existingInvoice) {
            return [
                'can_regenerate' => false,
                'message' => 'Invoice pengganti sudah ada: ' . $existingInvoice->invoice_number,
            ];
        }

        return [
            'can_regenerate' => true,
            'type' => 'rental_period',
            'contract_id' => $contract->id,
            'rental_period' => $invoice->period_invoice,
            'period_start' => Carbon::parse($period['period_start']),
            'period_end' => Carbon::parse($period['period_end']),
        ];
    }
}
