<?php

namespace Database\Factories\Finance;

use App\Models\Finance\VirtualAccountExport;
use App\Models\Finance\Bank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\VirtualAccountExport>
 */
class VirtualAccountExportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VirtualAccountExport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exportDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $fileType = $this->faker->randomElement(['csv', 'xlsx', 'txt']);
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']);
        
        // Generate file information for completed exports
        $fileName = null;
        $filePath = null;
        $fileSize = null;
        
        if ($status === 'completed') {
            $fileName = 'virtual_accounts_export_' . Carbon::parse($exportDate)->format('Y-m-d') . '.' . $fileType;
            $filePath = 'virtual-account-exports/' . $fileName;
            $fileSize = $this->faker->numberBetween(1024 * 100, 1024 * 1024 * 10); // 100KB to 10MB
        }

        // Generate date range for export settings
        $dateFrom = null;
        $dateTo = null;
        if ($this->faker->boolean()) {
            $dateFrom = Carbon::parse($exportDate)->subDays($this->faker->numberBetween(7, 30));
            $dateTo = Carbon::parse($exportDate)->subDays($this->faker->numberBetween(1, 6));
        }

        // Generate include columns
        $defaultColumns = ['va_number', 'customer_name', 'amount', 'due_date', 'status'];
        $includeColumns = $defaultColumns;
        
        if ($this->faker->boolean()) {
            $includeColumns[] = 'created_at';
        }
        if ($this->faker->boolean()) {
            $includeColumns[] = 'updated_at';
        }
        if ($this->faker->boolean()) {
            $includeColumns[] = 'notes';
        }

        return [
            'export_number' => 'VA-EXP-' . Carbon::parse($exportDate)->format('Ymd') . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'export_date' => $exportDate,
            'bank_id' => Bank::factory(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_filter' => $this->faker->optional()->randomElement(['active', 'inactive', 'expired']),
            'limit_records' => $this->faker->numberBetween(500, 5000),
            'include_header' => $this->faker->boolean(80), // 80% chance to include header
            'delimiter' => $this->faker->randomElement([',', ';', '|', '\t']),
            'include_columns' => $includeColumns,
            'total_records' => $status === 'completed' ? $this->faker->numberBetween(100, 5000) : 0,
            'status' => $status,
            'auto_process' => $this->faker->boolean(30), // 30% chance to auto process
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the export is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'file_name' => null,
            'file_path' => null,
            'file_size' => null,
            'total_records' => 0,
        ]);
    }

    /**
     * Indicate that the export is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'file_name' => null,
            'file_path' => null,
            'file_size' => null,
            'total_records' => 0,
        ]);
    }

    /**
     * Indicate that the export is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $exportDate = $attributes['export_date'] ?? $this->faker->dateTimeBetween('-1 year', 'now');
            $fileType = $attributes['file_type'] ?? $this->faker->randomElement(['csv', 'xlsx', 'txt']);
            $fileName = 'virtual_accounts_export_' . Carbon::parse($exportDate)->format('Y-m-d') . '.' . $fileType;
            $filePath = 'virtual-account-exports/' . $fileName;
            
            return [
                'status' => 'completed',
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $this->faker->numberBetween(1024 * 100, 1024 * 1024 * 10),
                'total_records' => $this->faker->numberBetween(100, 5000),
            ];
        });
    }

    /**
     * Indicate that the export failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'file_name' => null,
            'file_path' => null,
            'file_size' => null,
            'total_records' => 0,
        ]);
    }

    /**
     * Indicate that the export has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the export is for CSV format.
     */
    public function csv(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'csv',
            'delimiter' => ',',
        ]);
    }

    /**
     * Indicate that the export is for Excel format.
     */
    public function excel(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'xlsx',
        ]);
    }

    /**
     * Indicate that the export is for text format.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'txt',
        ]);
    }

    /**
     * Indicate that the export has auto process enabled.
     */
    public function autoProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_process' => true,
        ]);
    }

    /**
     * Indicate that the export has a specific bank.
     */
    public function forBank(Bank $bank): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_id' => $bank->id,
        ]);
    }

    /**
     * Indicate that the export has a specific user as creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Indicate that the export has a date range filter.
     */
    public function withDateRange(): static
    {
        return $this->state(function (array $attributes) {
            $exportDate = $attributes['export_date'] ?? $this->faker->dateTimeBetween('-1 year', 'now');
            $dateFrom = Carbon::parse($exportDate)->subDays($this->faker->numberBetween(7, 30));
            $dateTo = Carbon::parse($exportDate)->subDays($this->faker->numberBetween(1, 6));
            
            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ];
        });
    }

    /**
     * Indicate that the export has a status filter.
     */
    public function withStatusFilter(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_filter' => $this->faker->randomElement(['active', 'inactive', 'expired']),
        ]);
    }

    /**
     * Indicate that the export has minimal columns.
     */
    public function minimalColumns(): static
    {
        return $this->state(fn (array $attributes) => [
            'include_columns' => ['va_number', 'customer_name', 'amount'],
        ]);
    }

    /**
     * Indicate that the export has all columns.
     */
    public function allColumns(): static
    {
        return $this->state(fn (array $attributes) => [
            'include_columns' => ['va_number', 'customer_name', 'amount', 'due_date', 'status', 'created_at', 'updated_at', 'notes'],
        ]);
    }
}
