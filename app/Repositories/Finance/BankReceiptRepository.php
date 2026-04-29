<?php

namespace App\Repositories\Finance;

use App\Models\Finance\BankReceipt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class BankReceiptRepository
{
    protected $model;

    public function __construct(BankReceipt $model)
    {
        $this->model = $model;
    }

    /**
     * Get all bank receipts with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'bank', 'createdBy', 'updatedBy']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get bank receipt by ID
     */
    public function findById(int $id): ?BankReceipt
    {
        return $this->model->with(['customer', 'bank', 'createdBy', 'updatedBy'])->find($id);
    }

    /**
     * Get bank receipt by receipt number
     */
    public function findByReceiptNumber(string $receiptNumber): ?BankReceipt
    {
        return $this->model->where('receipt_number', $receiptNumber)->first();
    }

    /**
     * Create new bank receipt
     */
    public function create(array $data): BankReceipt
    {
        return $this->model->create($data);
    }

    /**
     * Update bank receipt
     */
    public function update(BankReceipt $bankReceipt, array $data): bool
    {
        return $bankReceipt->update($data);
    }

    /**
     * Delete bank receipt
     */
    public function delete(BankReceipt $bankReceipt): bool
    {
        return $bankReceipt->delete();
    }

    /**
     * Get bank receipts by status
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('status', $status)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by customer
     */
    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('customer_id', $customerId)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by bank
     */
    public function getByBank(int $bankId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('bank_id', $bankId)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by date range
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereBetween('receipt_date', [$startDate, $endDate])
                          ->orderBy('receipt_date', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by payment method
     */
    public function getByPaymentMethod(string $paymentMethod, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('payment_method', $paymentMethod)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by amount range
     */
    public function getByAmountRange(float $minAmount, float $maxAmount, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereBetween('amount', [$minAmount, $maxAmount])
                          ->orderBy('amount', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['customer', 'bank']);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get bank receipt statistics
     */
    public function getStatistics(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        return [
            'total' => $this->model->count(),
            'pending' => $this->model->where('status', 'pending')->count(),
            'verified' => $this->model->where('status', 'verified')->count(),
            'rejected' => $this->model->where('status', 'rejected')->count(),
            'processed' => $this->model->where('status', 'processed')->count(),
            'total_amount' => $this->model->sum('amount'),
            'this_month' => $this->model->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            'this_month_amount' => $this->model->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('amount'),
            'today' => $this->model->whereDate('created_at', $now->toDateString())->count(),
            'today_amount' => $this->model->whereDate('created_at', $now->toDateString())->sum('amount'),
        ];
    }

    /**
     * Get bank receipt trends
     */
    public function getTrends(int $days = 30): array
    {
        $trends = [];
        $startDate = Carbon::now()->subDays($days);

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $this->model->whereDate('created_at', $date)->count(),
                'amount' => $this->model->whereDate('created_at', $date)->sum('amount'),
            ];
        }

        return $trends;
    }

    /**
     * Get summary by status
     */
    public function getSummaryByStatus(): Collection
    {
        return $this->model->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                          ->groupBy('status')
                          ->get();
    }

    /**
     * Get summary by payment method
     */
    public function getSummaryByPaymentMethod(): Collection
    {
        return $this->model->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
                          ->groupBy('payment_method')
                          ->get();
    }

    /**
     * Get summary by customer
     */
    public function getSummaryByCustomer(int $limit = 10): Collection
    {
        return $this->model->with('customer')
                          ->selectRaw('customer_id, COUNT(*) as count, SUM(amount) as total_amount')
                          ->groupBy('customer_id')
                          ->orderBy('total_amount', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get summary by bank
     */
    public function getSummaryByBank(int $limit = 10): Collection
    {
        return $this->model->with('bank')
                          ->selectRaw('bank_id, COUNT(*) as count, SUM(amount) as total_amount')
                          ->groupBy('bank_id')
                          ->orderBy('total_amount', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get bank receipts that need attention (pending for more than 3 days)
     */
    public function getNeedsAttention(): Collection
    {
        $threeDaysAgo = Carbon::now()->subDays(3);

        return $this->model->with(['customer', 'bank'])
                          ->where('status', 'pending')
                          ->where('created_at', '<=', $threeDaysAgo)
                          ->orderBy('created_at', 'asc')
                          ->get();
    }

    /**
     * Get bank receipts by created by user
     */
    public function getByCreatedBy(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('created_by', $userId)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by updated by user
     */
    public function getByUpdatedBy(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where('updated_by', $userId)
                          ->orderBy('updated_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Search bank receipts
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->where(function($query) use ($search) {
                              $query->where('receipt_number', 'like', "%{$search}%")
                                    ->orWhere('invoice_reference', 'like', "%{$search}%")
                                    ->orWhere('account_number', 'like', "%{$search}%")
                                    ->orWhere('account_holder_name', 'like', "%{$search}%")
                                    ->orWhere('notes', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function($customerQuery) use ($search) {
                                        $customerQuery->where('company_name', 'like', "%{$search}%")
                                                     ->orWhere('contact_person', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('bank', function($bankQuery) use ($search) {
                                        $bankQuery->where('bank_name', 'like', "%{$search}%");
                                    });
                          })
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('invoice_reference', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_holder_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('company_name', 'like', "%{$search}%")
                                   ->orWhere('contact_person', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bank', function($bankQuery) use ($search) {
                      $bankQuery->where('bank_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['bank_id'])) {
            $query->where('bank_id', $filters['bank_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('receipt_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('receipt_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (!empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['updated_by'])) {
            $query->where('updated_by', $filters['updated_by']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get bank receipts with images
     */
    public function getWithImages(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereNotNull('receipt_image')
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts without images
     */
    public function getWithoutImages(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereNull('receipt_image')
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by month
     */
    public function getByMonth(int $year, int $month, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereYear('receipt_date', $year)
                          ->whereMonth('receipt_date', $month)
                          ->orderBy('receipt_date', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get bank receipts by year
     */
    public function getByYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer', 'bank'])
                          ->whereYear('receipt_date', $year)
                          ->orderBy('receipt_date', 'desc')
                          ->paginate($perPage);
    }
}
