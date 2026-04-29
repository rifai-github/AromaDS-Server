<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanyDocument;
use App\Models\CompanyNote;
use App\Models\CompanyTag;
use App\Models\CompanyTagAssignment;
use App\Models\CompanyRelationship;
use App\Models\CompanyActivity;
use App\Models\CompanyCommunication;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;

class CompanyService
{
    protected $companyRepository;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * Create a new company with default settings
     */
    public function createCompany(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'company_type' => $data['company_type'] ?? null,
                'industry' => $data['industry'] ?? null,
                'employee_count' => $data['employee_count'] ?? null,
                'annual_revenue' => $data['annual_revenue'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'website' => $data['website'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? null,
                'subdistrict_id' => $data['subdistrict_id'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => Auth::id()
            ]);

            // Create default company settings
            $this->createDefaultSettings($company);

            return $company;
        });
    }

    /**
     * Update company information
     */
    public function updateCompany(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data) {
            $company->update([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'company_type' => $data['company_type'] ?? $company->company_type,
                'industry' => $data['industry'] ?? $company->industry,
                'employee_count' => $data['employee_count'] ?? $company->employee_count,
                'annual_revenue' => $data['annual_revenue'] ?? $company->annual_revenue,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'website' => $data['website'] ?? $company->website,
                'tax_number' => $data['tax_number'] ?? $company->tax_number,
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? $company->district_id,
                'subdistrict_id' => $data['subdistrict_id'] ?? $company->subdistrict_id,
                'postal_code' => $data['postal_code'] ?? $company->postal_code,
                'description' => $data['description'] ?? $company->description,
                'status' => $data['status'] ?? $company->status,
                'updated_by' => Auth::id()
            ]);

            return $company;
        });
    }

    /**
     * Delete company and related data
     */
    public function deleteCompany(Company $company): bool
    {
        return DB::transaction(function () use ($company) {
            // Check if company can be deleted
            $this->validateCompanyDeletion($company);

            // Delete related data
            $company->settings()->delete();
            $company->documents()->delete();
            $company->notes()->delete();
            $company->companyTagAssignments()->delete();
            $company->relationships()->delete();
            $company->activities()->delete();
            $company->communications()->delete();

            // Delete company
            return $company->delete();
        });
    }

    /**
     * Create default company settings
     */
    public function createDefaultSettings(Company $company): CompanySetting
    {
        return $company->settings()->create([
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

    /**
     * Update company settings
     */
    public function updateSettings(Company $company, array $data): CompanySetting
    {
        $settings = $company->settings()->first();
        
        if (!$settings) {
            $settings = $this->createDefaultSettings($company);
        }

        $settings->update($data);
        
        return $settings;
    }

    /**
     * Upload document for company
     */
    public function uploadDocument(Company $company, UploadedFile $file, array $data): CompanyDocument
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('company_documents/' . $company->id, $fileName, 'public');

        return $company->documents()->create([
            'document_type' => $data['document_type'],
            'document_name' => $data['document_name'],
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_active' => true,
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Delete company document
     */
    public function deleteDocument(Company $company, CompanyDocument $document): bool
    {
        if ($document->company_id !== $company->id) {
            throw new \Exception('Document not found for this company.');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        return $document->delete();
    }

    /**
     * Create company note
     */
    public function createNote(Company $company, array $data): CompanyNote
    {
        return $company->notes()->create([
            'note_type' => $data['note_type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'is_private' => $data['is_private'] ?? false,
            'is_important' => $data['is_important'] ?? false,
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Update company note
     */
    public function updateNote(Company $company, CompanyNote $note, array $data): CompanyNote
    {
        if ($note->company_id !== $company->id) {
            throw new \Exception('Note not found for this company.');
        }

        $note->update([
            'note_type' => $data['note_type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'is_private' => $data['is_private'] ?? $note->is_private,
            'is_important' => $data['is_important'] ?? $note->is_important,
            'updated_by' => Auth::id()
        ]);

        return $note;
    }

    /**
     * Delete company note
     */
    public function deleteNote(Company $company, CompanyNote $note): bool
    {
        if ($note->company_id !== $company->id) {
            throw new \Exception('Note not found for this company.');
        }

        return $note->delete();
    }

    /**
     * Assign tag to company
     */
    public function assignTag(Company $company, CompanyTag $tag): CompanyTagAssignment
    {
        if ($company->companyTagAssignments()->where('tag_id', $tag->id)->exists()) {
            throw new \Exception('Tag already assigned to this company.');
        }

        return $company->companyTagAssignments()->create([
            'tag_id' => $tag->id,
            'assigned_by' => Auth::id(),
            'assigned_at' => now()
        ]);
    }

    /**
     * Remove tag from company
     */
    public function removeTag(Company $company, CompanyTag $tag): bool
    {
        $assignment = $company->companyTagAssignments()->where('tag_id', $tag->id)->first();
        
        if (!$assignment) {
            throw new \Exception('Tag not assigned to this company.');
        }

        return $assignment->delete();
    }

    /**
     * Create company relationship
     */
    public function createRelationship(Company $company, array $data): CompanyRelationship
    {
        if ($data['related_company_id'] == $company->id) {
            throw new \Exception('Cannot create relationship with the same company.');
        }

        return $company->relationships()->create([
            'related_company_id' => $data['related_company_id'],
            'relationship_type' => $data['relationship_type'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Update company relationship
     */
    public function updateRelationship(Company $company, CompanyRelationship $relationship, array $data): CompanyRelationship
    {
        if ($relationship->company_id !== $company->id) {
            throw new \Exception('Relationship not found for this company.');
        }

        $relationship->update([
            'relationship_type' => $data['relationship_type'],
            'description' => $data['description'] ?? $relationship->description,
            'start_date' => $data['start_date'] ?? $relationship->start_date,
            'end_date' => $data['end_date'] ?? $relationship->end_date,
            'is_active' => $data['is_active'] ?? $relationship->is_active,
            'updated_by' => Auth::id()
        ]);

        return $relationship;
    }

    /**
     * Delete company relationship
     */
    public function deleteRelationship(Company $company, CompanyRelationship $relationship): bool
    {
        if ($relationship->company_id !== $company->id) {
            throw new \Exception('Relationship not found for this company.');
        }

        return $relationship->delete();
    }

    /**
     * Create company activity
     */
    public function createActivity(Company $company, array $data): CompanyActivity
    {
        return $company->activities()->create([
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'activity_date' => $data['activity_date'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'location' => $data['location'] ?? null,
            'priority' => $data['priority'],
            'is_completed' => false,
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Update company activity
     */
    public function updateActivity(Company $company, CompanyActivity $activity, array $data): CompanyActivity
    {
        if ($activity->company_id !== $company->id) {
            throw new \Exception('Activity not found for this company.');
        }

        $activity->update([
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'activity_date' => $data['activity_date'],
            'duration_minutes' => $data['duration_minutes'] ?? $activity->duration_minutes,
            'location' => $data['location'] ?? $activity->location,
            'priority' => $data['priority'],
            'is_completed' => $data['is_completed'] ?? $activity->is_completed,
            'updated_by' => Auth::id()
        ]);

        return $activity;
    }

    /**
     * Delete company activity
     */
    public function deleteActivity(Company $company, CompanyActivity $activity): bool
    {
        if ($activity->company_id !== $company->id) {
            throw new \Exception('Activity not found for this company.');
        }

        return $activity->delete();
    }

    /**
     * Create company communication
     */
    public function createCommunication(Company $company, array $data): CompanyCommunication
    {
        return $company->communications()->create([
            'communication_type' => $data['communication_type'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'communication_date' => $data['communication_date'],
            'direction' => $data['direction'],
            'priority' => $data['priority'],
            'status' => 'unread',
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Update company communication
     */
    public function updateCommunication(Company $company, CompanyCommunication $communication, array $data): CompanyCommunication
    {
        if ($communication->company_id !== $company->id) {
            throw new \Exception('Communication not found for this company.');
        }

        $communication->update([
            'communication_type' => $data['communication_type'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'communication_date' => $data['communication_date'],
            'direction' => $data['direction'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? $communication->status,
            'updated_by' => Auth::id()
        ]);

        return $communication;
    }

    /**
     * Delete company communication
     */
    public function deleteCommunication(Company $company, CompanyCommunication $communication): bool
    {
        if ($communication->company_id !== $company->id) {
            throw new \Exception('Communication not found for this company.');
        }

        return $communication->delete();
    }

    /**
     * Get company dashboard statistics
     */
    public function getDashboardStatistics(Company $company): array
    {
        return [
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
    }

    /**
     * Bulk delete companies
     */
    public function bulkDelete(array $companyIds): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($companyIds as $companyId) {
            try {
                $company = Company::find($companyId);
                
                if ($company) {
                    $this->deleteCompany($company);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Company ID {$companyId}: " . $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Bulk update company status
     */
    public function bulkUpdateStatus(array $companyIds, string $status): int
    {
        return Company::whereIn('id', $companyIds)
            ->update(['status' => $status]);
    }

    /**
     * Toggle company status
     */
    public function toggleStatus(Company $company): string
    {
        $newStatus = $company->status === 'active' ? 'inactive' : 'active';
        $company->update(['status' => $newStatus]);
        
        return $newStatus;
    }

    /**
     * Validate if company can be deleted
     */
    protected function validateCompanyDeletion(Company $company): void
    {
        $hasBranches = $company->branches()->exists();
        $hasCustomers = $company->customers()->exists();
        $hasSuppliers = $company->suppliers()->exists();

        if ($hasBranches) {
            throw new \Exception('Cannot delete company that still has branches.');
        }

        if ($hasCustomers) {
            throw new \Exception('Cannot delete company that still has customers.');
        }

        if ($hasSuppliers) {
            throw new \Exception('Cannot delete company that still has suppliers.');
        }
    }

    /**
     * Get company statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('status', 'active')->count(),
            'companies_with_branches' => Company::has('branches')->count(),
            'companies_with_customers' => Company::has('customers')->count(),
            'companies_with_suppliers' => Company::has('suppliers')->count(),
            'companies_by_type' => Company::selectRaw('company_type, COUNT(*) as count')
                ->groupBy('company_type')
                ->pluck('count', 'company_type')
                ->toArray(),
            'companies_by_industry' => Company::selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray()
        ];
    }

    /**
     * Search companies
     */
    public function searchCompanies(string $search, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get companies by province
     */
    public function getCompaniesByProvince(int $provinceId): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }
}
