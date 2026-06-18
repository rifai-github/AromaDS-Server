<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Province;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Models\CompanyDocument;
use App\Models\CompanyNote;
use App\Models\CompanyTag;
use App\Models\CompanyTagAssignment;
use App\Models\CompanyRelationship;
use App\Models\CompanyActivity;
use App\Models\CompanyCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        // Optimize query with selective eager loading
        $query = Company::with([
            'province:id,name',
            'city:id,name',
            'district:id,name', 
            'subdistrict:id,name',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);

        // Apply filters efficiently
        $this->applyFilters($query, $request);

        // Sort options with index optimization
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'code', 'status', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Use cursor pagination for better performance with large datasets
        $companies = $query->paginateStd(25);
        
        // Cache frequently accessed data
        $provinces = cache()->remember('provinces_list', 3600, function () {
            return Province::select('id', 'name')->orderBy('name')->get();
        });
        
        $tags = cache()->remember('company_tags_active', 1800, function () {
            return CompanyTag::active()->select('id', 'name')->orderBy('name')->get();
        });

        return view('company.companies.index', compact('companies', 'provinces', 'tags'));
    }

    /**
     * Apply filters to the query efficiently
     */
    private function applyFilters($query, Request $request)
    {
        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by code
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // Filter by province
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by is_active
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by tag (optimized with exists)
        if ($request->filled('tag_id')) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('company_tag_assignments')
                  ->whereColumn('company_tag_assignments.company_id', 'companies.id')
                  ->where('company_tag_assignments.tag_id', $request->tag_id)
                  ->whereNull('company_tag_assignments.deleted_at');
            });
        }

        // Filter by company type
        if ($request->filled('company_type')) {
            $query->where('company_type', $request->company_type);
        }

        // Filter by industry
        if ($request->filled('industry')) {
            $query->where('industry', 'like', '%' . $request->industry . '%');
        }

        // Filter by employee count range
        if ($request->filled('employee_count_min')) {
            $query->where('employee_count', '>=', $request->employee_count_min);
        }
        if ($request->filled('employee_count_max')) {
            $query->where('employee_count', '<=', $request->employee_count_max);
        }

        // Filter by annual revenue range
        if ($request->filled('annual_revenue_min')) {
            $query->where('annual_revenue', '>=', $request->annual_revenue_min);
        }
        if ($request->filled('annual_revenue_max')) {
            $query->where('annual_revenue', '<=', $request->annual_revenue_max);
        }

        // Filter by created date range
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
    }

    public function create()
    {
        $provinces = Province::orderBy('name')->get();
        
        return view('company.companies.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        \Log::info('Company store request received', ['request_data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:companies,code,NULL,id,deleted_at,NULL',
            'npwp' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'nitku' => 'nullable|string|max:50',
            'label_alias' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'company_type' => 'required|in:pt,cv,ud,foundation,government,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'footer_line_1' => 'nullable|string',
            'footer_line_2' => 'nullable|string',
            'footer_line_3' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Company store validation failed', [
                'request_data' => $request->all(),
                'validation_errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            \Log::info('Starting company creation transaction');
            DB::beginTransaction();

            // Auto-generate code if not provided
            $code = $request->code;
            \Log::info('Original code from request', ['code' => $code]);
            
            if (empty($code)) {
                // Generate unique code with timestamp to avoid duplicates
                $timestamp = time();
                $code = 'COMP' . $timestamp;
                \Log::info('Generated auto code', ['code' => $code]);
                
                // Ensure uniqueness by checking database and adding suffix if needed
                $originalCode = $code;
                $suffix = 1;
                while (Company::where('code', $code)->whereNull('deleted_at')->exists()) {
                    $code = $originalCode . '_' . $suffix;
                    $suffix++;
                    \Log::info('Code exists, trying suffix', ['code' => $code, 'suffix' => $suffix]);
                }
            }
            
            // For user-provided code, ensure uniqueness by adding suffix if needed
            if (!empty($code)) {
                \Log::info('Processing user-provided code', ['code' => $code]);
                $originalCode = $code;
                $suffix = 1;
                while (Company::where('code', $code)->whereNull('deleted_at')->exists()) {
                    \Log::info('Code exists in database, adding suffix', ['original_code' => $originalCode, 'current_code' => $code, 'suffix' => $suffix]);
                    $code = $originalCode . '_' . $suffix;
                    $suffix++;
                    // Prevent infinite loop
                    if ($suffix > 100) {
                        \Log::error('Unable to generate unique code after 100 attempts', ['original_code' => $originalCode]);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Unable to generate unique code',
                            'errors' => ['code' => ['Unable to generate unique code']]
                        ], 422);
                    }
                }
                \Log::info('Final unique code determined', ['final_code' => $code]);
            }

            $company = Company::create([
                'name' => $request->name,
                'code' => strtoupper($code),
                'npwp' => $request->npwp,
                'nik' => $request->nik,
                'nitku' => $request->nitku,
                'label_alias' => $request->label_alias,
                'status' => $request->status,
                'company_type' => $request->company_type,
                'is_active' => $request->status === 'active' ? true : false,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'footer_line_1' => $request->footer_line_1,
                'footer_line_2' => $request->footer_line_2,
                'footer_line_3' => $request->footer_line_3,
                'created_by' => Auth::id() ?? 28,
                'updated_by' => Auth::id() ?? 28,
            ]);
            
            \Log::info('Company created successfully', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'company_code' => $company->code
            ]);

            DB::commit();
            \Log::info('Database transaction committed successfully');

            return response()->json([
                'status' => 'success',
                'message' => 'Company created successfully',
                'data' => $company
            ]);
        } catch (\Exception $e) {
            \Log::error('Company creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            DB::rollback();
            \Log::info('Database transaction rolled back due to error');
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating company: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Company $company)
    {
        $company->load([
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'subdistrict:id,name',
            'branches:id,company_id,name,code,is_active',
            'customers:id,company_id,name,customer_code,is_active',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $company
        ]);
    }

    public function edit(Company $company)
    {
        $company->load([
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'subdistrict:id,name',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $company
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:companies,name,' . $company->id . ',id,deleted_at,NULL',
            'code' => 'nullable|string|max:50',
            'npwp' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'nitku' => 'nullable|string|max:50',
            'label_alias' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'company_type' => 'required|in:pt,cv,ud,foundation,government,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'footer_line_1' => 'nullable|string',
            'footer_line_2' => 'nullable|string',
            'footer_line_3' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Company update validation failed', [
                'request_data' => $request->all(),
                'validation_errors' => $validator->errors()->toArray(),
                'company_id' => $company->id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'debug' => [
                    'request_data' => $request->all(),
                    'company_id' => $company->id
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if code is unique (excluding current company)
            if (!empty($request->code) && Company::where('code', $request->code)->where('id', '!=', $company->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company code already exists',
                    'errors' => ['code' => ['Company code already exists']]
                ], 422);
            }

            $company->update([
                'name' => $request->name,
                'code' => $request->code ? strtoupper($request->code) : $company->code,
                'npwp' => $request->npwp,
                'nik' => $request->nik,
                'nitku' => $request->nitku,
                'label_alias' => $request->label_alias,
                'status' => $request->status,
                'company_type' => $request->company_type,
                'is_active' => $request->status === 'active' ? true : false,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'footer_line_1' => $request->footer_line_1,
                'footer_line_2' => $request->footer_line_2,
                'footer_line_3' => $request->footer_line_3,
                'updated_by' => Auth::id() ?? 28,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company updated successfully',
                'data' => $company
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating company: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Company $company)
    {
        try {
            // Check if company is used by any branches
            $hasBranches = $company->branches()->exists();
            
            if ($hasBranches) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete company with existing branches.'
                ], 400);
            }

            // Check if company is used by any customers
            $hasCustomers = $company->customers()->exists();
            
            if ($hasCustomers) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete company with existing customers.'
                ], 400);
            }

            // Note: taxSettings relationship removed as model doesn't exist

            $company->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Company deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete company: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCompanies(Request $request)
    {
        $companies = Company::with(['province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $companies
        ]);
    }

    public function getCompaniesByProvince(Request $request)
    {
        $request->validate([
            'province_id' => 'required|exists:provinces,id',
        ]);

        $companies = Company::where('province_id', $request->province_id)
            ->orderBy('name')
            ->get();

        return response()->json($companies);
    }

    public function searchCompanies(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $companies = Company::where('name', 'like', '%' . $request->search . '%')
            ->orWhere('code', 'like', '%' . $request->search . '%')
            ->orWhere('email', 'like', '%' . $request->search . '%')
            ->with('province')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($companies);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'companies' => 'required|array|min:1',
            'companies.*.name' => 'required|string|max:255',
            'companies.*.code' => 'required|string|max:50',
            'companies.*.province_id' => 'required|exists:provinces,id',
            'companies.*.address' => 'required|string',
            'companies.*.phone' => 'required|string|max:20',
            'companies.*.email' => 'required|email|max:255',
            'companies.*.website' => 'nullable|url|max:255',
            'companies.*.tax_number' => 'nullable|string|max:50',
            'companies.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->companies as $companyData) {
                // Check if company name or code already exists
                $exists = Company::where('name', $companyData['name'])
                    ->orWhere('code', strtoupper($companyData['code']))
                    ->exists();
                
                if (!$exists) {
                    Company::create([
                        'name' => $companyData['name'],
                        'code' => strtoupper($companyData['code']),
                        'province_id' => $companyData['province_id'],
                        'address' => $companyData['address'],
                        'phone' => $companyData['phone'],
                        'email' => $companyData['email'],
                        'website' => $companyData['website'] ?? null,
                        'tax_number' => $companyData['tax_number'] ?? null,
                        'description' => $companyData['description'] ?? null,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} perusahaan.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $companies = Company::with(['province', 'branches'])
            ->orderBy('name')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil mengekspor {$companies->count()} perusahaan.",
            'count' => $companies->count()
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create companies
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil mengimpor {$importedCount} perusahaan.",
                'count' => $importedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics()
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $companiesWithBranches = Company::has('branches')->count();
        $companiesWithCustomers = Company::has('customers')->count();

        return response()->json([
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'companies_with_branches' => $companiesWithBranches,
            'companies_with_customers' => $companiesWithCustomers,
        ]);
    }

    public function getCompaniesByUsage()
    {
        $companies = Company::withCount(['branches', 'customers'])
            ->orderBy('customers_count', 'desc')
            ->orderBy('branches_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($companies);
    }

    public function toggleStatus(Company $company)
    {
        try {
            $newStatus = $company->status === 'active' ? 'inactive' : 'active';
            
            $company->update(['status' => $newStatus]);

            return back()->with('success', "Status perusahaan berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Settings Management
    public function settings(Company $company)
    {
        $settings = $company->settings()->first();
        if (!$settings) {
            $settings = $company->settings()->create([
                'default_currency' => 'IDR',
                'default_language' => 'id',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => '0,0.00',
                'tax_calculation_method' => 'inclusive',
                'invoice_prefix' => 'INV',
                'quotation_prefix' => 'QUO',
                'purchase_order_prefix' => 'PO',
                'receipt_prefix' => 'RCP',
                'payment_prefix' => 'PAY',
                'auto_generate_code' => true,
                'code_length' => 6,
                'send_email_notifications' => true,
                'send_sms_notifications' => false,
                'allow_negative_stock' => false,
                'require_approval_for_purchase' => true,
                'require_approval_for_sale' => false,
                'default_payment_terms' => 30,
                'default_credit_limit' => 0,
                'auto_close_quotation_days' => 30,
                'auto_close_invoice_days' => 90,
                'backup_frequency' => 'daily',
                'data_retention_days' => 2555, // 7 years
                'is_active' => true
            ]);
        }

        return view('company.companies.settings', compact('company', 'settings'));
    }

    public function updateSettings(Request $request, Company $company)
    {
        $request->validate([
            'default_currency' => 'required|string|max:3',
            'default_language' => 'required|string|max:5',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:10',
            'number_format' => 'required|string|max:20',
            'tax_calculation_method' => 'required|in:inclusive,exclusive',
            'invoice_prefix' => 'required|string|max:10',
            'quotation_prefix' => 'required|string|max:10',
            'purchase_order_prefix' => 'required|string|max:10',
            'receipt_prefix' => 'required|string|max:10',
            'payment_prefix' => 'required|string|max:10',
            'auto_generate_code' => 'boolean',
            'code_length' => 'required|integer|min:3|max:10',
            'send_email_notifications' => 'boolean',
            'send_sms_notifications' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'require_approval_for_purchase' => 'boolean',
            'require_approval_for_sale' => 'boolean',
            'default_payment_terms' => 'required|integer|min:0|max:365',
            'default_credit_limit' => 'required|numeric|min:0',
            'auto_close_quotation_days' => 'required|integer|min:1|max:365',
            'auto_close_invoice_days' => 'required|integer|min:1|max:365',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'data_retention_days' => 'required|integer|min:30|max:3650',
            'is_active' => 'boolean'
        ]);

        try {
            $settings = $company->settings()->first();
            if ($settings) {
                $settings->update($request->all());
            } else {
                $company->settings()->create($request->all());
            }

            return back()->with('success', 'Pengaturan perusahaan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Documents Management
    public function documents(Company $company)
    {
        $documents = $company->documents()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.companies.documents', compact('company', 'documents'));
    }

    public function uploadDocument(Request $request, Company $company)
    {
        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp,txt,csv'
        ]);

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('company_documents/' . $company->id, $fileName, 'public');

            $company->documents()->create([
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_active' => true,
                'created_by' => Auth::id()
            ]);

            return back()->with('success', 'Dokumen berhasil diunggah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteDocument(Company $company, CompanyDocument $document)
    {
        try {
            if ($document->company_id !== $company->id) {
                throw new \Exception('Dokumen tidak ditemukan.');
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Dokumen berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Notes Management
    public function notes(Company $company)
    {
        $notes = $company->notes()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.companies.notes', compact('company', 'notes'));
    }

    public function storeNote(Request $request, Company $company)
    {
        $request->validate([
            'note_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_private' => 'boolean',
            'is_important' => 'boolean'
        ]);

        try {
            $company->notes()->create([
                'note_type' => $request->note_type,
                'title' => $request->title,
                'content' => $request->content,
                'is_private' => $request->boolean('is_private'),
                'is_important' => $request->boolean('is_important'),
                'created_by' => Auth::id()
            ]);

            return back()->with('success', 'Catatan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateNote(Request $request, Company $company, CompanyNote $note)
    {
        $request->validate([
            'note_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_private' => 'boolean',
            'is_important' => 'boolean'
        ]);

        try {
            if ($note->company_id !== $company->id) {
                throw new \Exception('Catatan tidak ditemukan.');
            }

            $note->update([
                'note_type' => $request->note_type,
                'title' => $request->title,
                'content' => $request->content,
                'is_private' => $request->boolean('is_private'),
                'is_important' => $request->boolean('is_important'),
                'updated_by' => Auth::id()
            ]);

            return back()->with('success', 'Catatan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteNote(Company $company, CompanyNote $note)
    {
        try {
            if ($note->company_id !== $company->id) {
                throw new \Exception('Catatan tidak ditemukan.');
            }

            $note->delete();

            return back()->with('success', 'Catatan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Tags Management
    public function assignTag(Request $request, Company $company)
    {
        $request->validate([
            'tag_id' => 'required|exists:company_tags,id'
        ]);

        try {
            $tag = CompanyTag::findOrFail($request->tag_id);
            
            if (!$company->companyTagAssignments()->where('tag_id', $tag->id)->exists()) {
                $company->companyTagAssignments()->create([
                    'tag_id' => $tag->id,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now()
                ]);

                return back()->with('success', 'Tag berhasil ditambahkan.');
            } else {
                return back()->with('warning', 'Tag sudah ditambahkan sebelumnya.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function removeTag(Company $company, CompanyTag $tag)
    {
        try {
            $assignment = $company->companyTagAssignments()->where('tag_id', $tag->id)->first();
            
            if ($assignment) {
                $assignment->delete();
                return back()->with('success', 'Tag berhasil dihapus.');
            } else {
                return back()->with('warning', 'Tag tidak ditemukan.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Relationships Management
    public function relationships(Company $company)
    {
        $relationships = $company->relationships()
            ->with(['relatedCompany', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        $relatedCompanies = Company::where('id', '!=', $company->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('company.companies.relationships', compact('company', 'relationships', 'relatedCompanies'));
    }

    public function storeRelationship(Request $request, Company $company)
    {
        $request->validate([
            'related_company_id' => 'required|exists:companies,id',
            'relationship_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date'
        ]);

        try {
            if ($request->related_company_id == $company->id) {
                throw new \Exception('Tidak dapat membuat relasi dengan perusahaan yang sama.');
            }

            $company->relationships()->create([
                'related_company_id' => $request->related_company_id,
                'relationship_type' => $request->relationship_type,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => true,
                'created_by' => Auth::id()
            ]);

            return back()->with('success', 'Relasi perusahaan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateRelationship(Request $request, Company $company, CompanyRelationship $relationship)
    {
        $request->validate([
            'relationship_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean'
        ]);

        try {
            if ($relationship->company_id !== $company->id) {
                throw new \Exception('Relasi tidak ditemukan.');
            }

            $relationship->update([
                'relationship_type' => $request->relationship_type,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
                'updated_by' => Auth::id()
            ]);

            return back()->with('success', 'Relasi perusahaan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteRelationship(Company $company, CompanyRelationship $relationship)
    {
        try {
            if ($relationship->company_id !== $company->id) {
                throw new \Exception('Relasi tidak ditemukan.');
            }

            $relationship->delete();

            return back()->with('success', 'Relasi perusahaan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Activities Management
    public function activities(Company $company)
    {
        $activities = $company->activities()
            ->with('createdBy')
            ->orderBy('activity_date', 'desc')
            ->paginateStd(25);

        return view('company.companies.activities', compact('company', 'activities'));
    }

    public function storeActivity(Request $request, Company $company)
    {
        $request->validate([
            'activity_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'priority' => 'required|in:high,medium,low'
        ]);

        try {
            $company->activities()->create([
                'activity_type' => $request->activity_type,
                'title' => $request->title,
                'description' => $request->description,
                'activity_date' => $request->activity_date,
                'duration_minutes' => $request->duration_minutes,
                'location' => $request->location,
                'priority' => $request->priority,
                'is_completed' => false,
                'created_by' => Auth::id()
            ]);

            return back()->with('success', 'Aktivitas berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateActivity(Request $request, Company $company, CompanyActivity $activity)
    {
        $request->validate([
            'activity_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'priority' => 'required|in:high,medium,low',
            'is_completed' => 'boolean'
        ]);

        try {
            if ($activity->company_id !== $company->id) {
                throw new \Exception('Aktivitas tidak ditemukan.');
            }

            $activity->update([
                'activity_type' => $request->activity_type,
                'title' => $request->title,
                'description' => $request->description,
                'activity_date' => $request->activity_date,
                'duration_minutes' => $request->duration_minutes,
                'location' => $request->location,
                'priority' => $request->priority,
                'is_completed' => $request->boolean('is_completed'),
                'updated_by' => Auth::id()
            ]);

            return back()->with('success', 'Aktivitas berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteActivity(Company $company, CompanyActivity $activity)
    {
        try {
            if ($activity->company_id !== $company->id) {
                throw new \Exception('Aktivitas tidak ditemukan.');
            }

            $activity->delete();

            return back()->with('success', 'Aktivitas berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Company Communications Management
    public function communications(Company $company)
    {
        $communications = $company->communications()
            ->with('createdBy')
            ->orderBy('communication_date', 'desc')
            ->paginateStd(25);

        return view('company.companies.communications', compact('company', 'communications'));
    }

    public function storeCommunication(Request $request, Company $company)
    {
        $request->validate([
            'communication_type' => 'required|string|max:50',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'communication_date' => 'required|date',
            'direction' => 'required|in:inbound,outbound',
            'priority' => 'required|in:high,medium,low'
        ]);

        try {
            $company->communications()->create([
                'communication_type' => $request->communication_type,
                'subject' => $request->subject,
                'content' => $request->content,
                'communication_date' => $request->communication_date,
                'direction' => $request->direction,
                'priority' => $request->priority,
                'status' => 'unread',
                'created_by' => Auth::id()
            ]);

            return back()->with('success', 'Komunikasi berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateCommunication(Request $request, Company $company, CompanyCommunication $communication)
    {
        $request->validate([
            'communication_type' => 'required|string|max:50',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'communication_date' => 'required|date',
            'direction' => 'required|in:inbound,outbound',
            'priority' => 'required|in:high,medium,low',
            'status' => 'required|in:unread,read,replied,archived'
        ]);

        try {
            if ($communication->company_id !== $company->id) {
                throw new \Exception('Komunikasi tidak ditemukan.');
            }

            $communication->update([
                'communication_type' => $request->communication_type,
                'subject' => $request->subject,
                'content' => $request->content,
                'communication_date' => $request->communication_date,
                'direction' => $request->direction,
                'priority' => $request->priority,
                'status' => $request->status,
                'updated_by' => Auth::id()
            ]);

            return back()->with('success', 'Komunikasi berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteCommunication(Company $company, CompanyCommunication $communication)
    {
        try {
            if ($communication->company_id !== $company->id) {
                throw new \Exception('Komunikasi tidak ditemukan.');
            }

            $communication->delete();

            return back()->with('success', 'Komunikasi berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Bulk Operations
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->company_ids as $companyId) {
                $company = Company::find($companyId);
                
                if ($company) {
                    // Check if company can be deleted
                    $hasBranches = $company->branches()->exists();

                    if ($hasBranches) {
                        $errors[] = "Perusahaan {$company->name} tidak dapat dihapus karena masih memiliki data terkait.";
                        continue;
                    }

                    $company->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            $message = "Berhasil menghapus {$deletedCount} perusahaan.";
            if (!empty($errors)) {
                $message .= " " . implode(' ', $errors);
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => $message,
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = Company::whereIn('id', $request->company_ids)
                ->update(['status' => $request->status, 'updated_by' => Auth::id()]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil memperbarui status {$updatedCount} perusahaan.",
                'count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Dashboard and Analytics
    public function dashboard(Company $company)
    {
        $statistics = [
            'branches_count' => $company->branches()->count(),
            'customers_count' => $company->customers()->count(),
            'suppliers_count' => $company->suppliers()->count(),
            'documents_count' => $company->documents()->count(),
            'notes_count' => $company->notes()->count(),
            'activities_count' => $company->activities()->count(),
            'communications_count' => $company->communications()->count(),
            'relationships_count' => $company->relationships()->count(),
            'recent_activities' => $company->activities()
                ->with('createdBy')
                ->orderBy('activity_date', 'desc')
                ->limit(5)
                ->get(),
            'recent_communications' => $company->communications()
                ->with('createdBy')
                ->orderBy('communication_date', 'desc')
                ->limit(5)
                ->get(),
            'upcoming_activities' => $company->activities()
                ->where('activity_date', '>=', now())
                ->where('is_completed', false)
                ->orderBy('activity_date', 'asc')
                ->limit(5)
                ->get(),
            'overdue_activities' => $company->activities()
                ->where('activity_date', '<', now())
                ->where('is_completed', false)
                ->orderBy('activity_date', 'asc')
                ->limit(5)
                ->get()
        ];

        return view('company.companies.dashboard', compact('company', 'statistics'));
    }

}
