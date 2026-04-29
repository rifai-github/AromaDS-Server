<?php

namespace Database\Factories\Finance;

use App\Models\Finance\VirtualAccountImport;
use App\Models\Finance\Bank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class VirtualAccountImportFactory extends Factory
{
    protected $model = VirtualAccountImport::class;

    public function definition(): array
    {
        $importDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $fileType = $this->faker->randomElement(['csv', 'xlsx', 'txt']);
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']);
        
        // Generate realistic statistics based on status
        $totalRecords = $this->faker->numberBetween(100, 5000);
        $successCount = 0;
        $failedCount = 0;
        
        if ($status === 'completed') {
            $successCount = $totalRecords;
            $failedCount = 0;
        } elseif ($status === 'failed') {
            $successCount = 0;
            $failedCount = $totalRecords;
        } elseif ($status === 'processing') {
            $successCount = $this->faker->numberBetween(0, $totalRecords);
            $failedCount = $this->faker->numberBetween(0, $totalRecords - $successCount);
        }
        
        return [
            'import_number' => 'VA-IMP-' . $importDate->format('Ymd') . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'import_date' => $importDate,
            'bank_id' => Bank::factory(),
            'file_name' => 'virtual_accounts_' . $importDate->format('Y-m-d') . '.' . $fileType,
            'file_path' => 'virtual-account-imports/virtual_accounts_' . $importDate->format('Y-m-d') . '.' . $fileType,
            'file_size' => $this->faker->numberBetween(1024 * 100, 1024 * 1024 * 10), // 100KB to 10MB
            'file_type' => $fileType,
            'skip_header' => $this->faker->boolean(),
            'delimiter' => $this->faker->randomElement([',', ';', '|', '\t']),
            'encoding' => $this->faker->randomElement(['UTF-8', 'ISO-8859-1', 'Windows-1252']),
            'va_number_column' => 'va_number',
            'customer_name_column' => 'customer_name',
            'amount_column' => 'amount',
            'due_date_column' => 'due_date',
            'total_records' => $totalRecords,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'status' => $status,
            'auto_process' => $this->faker->boolean(),
            'notes' => $this->faker->optional(0.4)->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $importDate,
            'updated_at' => $importDate->copy()->addHours($this->faker->numberBetween(1, 24)),
        ];
    }

    /**
     * Indicate that the import is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'total_records' => 0,
            'success_count' => 0,
            'failed_count' => 0,
        ]);
    }

    /**
     * Indicate that the import is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'total_records' => $this->faker->numberBetween(100, 5000),
            'success_count' => $this->faker->numberBetween(0, 1000),
            'failed_count' => $this->faker->numberBetween(0, 100),
        ]);
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'total_records' => $this->faker->numberBetween(100, 5000),
            'success_count' => fn (array $attributes) => $attributes['total_records'],
            'failed_count' => 0,
        ]);
    }

    /**
     * Indicate that the import is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'total_records' => $this->faker->numberBetween(100, 5000),
            'success_count' => 0,
            'failed_count' => fn (array $attributes) => $attributes['total_records'],
        ]);
    }

    /**
     * Indicate that the import is for CSV files.
     */
    public function csv(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'csv',
            'file_name' => str_replace(['.xlsx', '.txt'], '.csv', $attributes['file_name']),
            'file_path' => str_replace(['.xlsx', '.txt'], '.csv', $attributes['file_path']),
        ]);
    }

    /**
     * Indicate that the import is for Excel files.
     */
    public function xlsx(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'xlsx',
            'file_name' => str_replace(['.csv', '.txt'], '.xlsx', $attributes['file_name']),
            'file_path' => str_replace(['.csv', '.txt'], '.xlsx', $attributes['file_path']),
        ]);
    }

    /**
     * Indicate that the import is for text files.
     */
    public function txt(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'txt',
            'file_name' => str_replace(['.csv', '.xlsx'], '.txt', $attributes['file_name']),
            'file_path' => str_replace(['.csv', '.xlsx'], '.txt', $attributes['file_path']),
        ]);
    }

    /**
     * Indicate that the import has header row.
     */
    public function withHeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'skip_header' => true,
        ]);
    }

    /**
     * Indicate that the import has no header row.
     */
    public function withoutHeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'skip_header' => false,
        ]);
    }

    /**
     * Indicate that the import has auto process enabled.
     */
    public function autoProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_process' => true,
        ]);
    }

    /**
     * Indicate that the import has auto process disabled.
     */
    public function manualProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_process' => false,
        ]);
    }

    /**
     * Indicate that the import has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the import is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'import_date' => $this->faker->dateTimeBetween(Carbon::now()->startOfMonth(), Carbon::now()),
            'created_at' => $this->faker->dateTimeBetween(Carbon::now()->startOfMonth(), Carbon::now()),
        ]);
    }

    /**
     * Indicate that the import is from last month.
     */
    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'import_date' => $this->faker->dateTimeBetween(Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()),
            'created_at' => $this->faker->dateTimeBetween(Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()),
        ]);
    }

    /**
     * Indicate that the import is from this year.
     */
    public function thisYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'import_date' => $this->faker->dateTimeBetween(Carbon::now()->startOfYear(), Carbon::now()),
            'created_at' => $this->faker->dateTimeBetween(Carbon::now()->startOfYear(), Carbon::now()),
        ]);
    }

    /**
     * Indicate that the import has large file size.
     */
    public function largeFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(1024 * 1024 * 5, 1024 * 1024 * 10), // 5MB to 10MB
        ]);
    }

    /**
     * Indicate that the import has small file size.
     */
    public function smallFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(1024 * 100, 1024 * 1024), // 100KB to 1MB
        ]);
    }

    /**
     * Indicate that the import has many records.
     */
    public function manyRecords(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_records' => $this->faker->numberBetween(1000, 5000),
        ]);
    }

    /**
     * Indicate that the import has few records.
     */
    public function fewRecords(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_records' => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * Indicate that the import is for a specific bank.
     */
    public function forBank(Bank $bank): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_id' => $bank->id,
        ]);
    }

    /**
     * Indicate that the import is created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
