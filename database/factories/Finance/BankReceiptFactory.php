<?php

namespace Database\Factories\Finance;

use App\Models\Finance\BankReceipt;
use App\Models\Company\Customer;
use App\Models\Finance\Bank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\BankReceipt>
 */
class BankReceiptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankReceipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $receiptDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $paymentDate = Carbon::parse($receiptDate)->addDays($this->faker->numberBetween(0, 7));
        
        return [
            'receipt_number' => 'BR' . $receiptDate->format('Ymd') . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'receipt_date' => $receiptDate,
            'customer_id' => Customer::factory(),
            'invoice_reference' => $this->faker->optional(0.7)->regexify('INV-[0-9]{4}-[0-9]{4}'),
            'bank_id' => Bank::factory(),
            'account_number' => $this->faker->numerify('##########'),
            'account_holder_name' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(2, 100000, 50000000),
            'payment_date' => $paymentDate,
            'payment_method' => $this->faker->randomElement(['transfer', 'cash', 'check', 'giro']),
            'status' => $this->faker->randomElement(['pending', 'verified', 'rejected', 'processed']),
            'receipt_image' => $this->faker->optional(0.6)->filePath(),
            'notes' => $this->faker->optional(0.4)->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $receiptDate,
            'updated_at' => $receiptDate,
        ];
    }

    /**
     * Indicate that the bank receipt is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the bank receipt is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
        ]);
    }

    /**
     * Indicate that the bank receipt is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the bank receipt is processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    /**
     * Indicate that the bank receipt uses bank transfer payment method.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
        ]);
    }

    /**
     * Indicate that the bank receipt uses cash payment method.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Indicate that the bank receipt uses check payment method.
     */
    public function check(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'check',
        ]);
    }

    /**
     * Indicate that the bank receipt uses giro payment method.
     */
    public function giro(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'giro',
        ]);
    }

    /**
     * Indicate that the bank receipt has a receipt image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_image' => 'bank-receipts/' . $this->faker->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the bank receipt has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the bank receipt has an invoice reference.
     */
    public function withInvoiceReference(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_reference' => 'INV-' . $this->faker->numberBetween(1000, 9999) . '-' . date('Y'),
        ]);
    }

    /**
     * Indicate that the bank receipt is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_date' => $this->faker->dateTimeBetween('first day of this month', 'last day of this month'),
            'payment_date' => $this->faker->dateTimeBetween('first day of this month', 'last day of this month'),
        ]);
    }

    /**
     * Indicate that the bank receipt is from last month.
     */
    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_date' => $this->faker->dateTimeBetween('first day of last month', 'last day of last month'),
            'payment_date' => $this->faker->dateTimeBetween('first day of last month', 'last day of last month'),
        ]);
    }

    /**
     * Indicate that the bank receipt is from this year.
     */
    public function thisYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_date' => $this->faker->dateTimeBetween('first day of january this year', 'last day of december this year'),
            'payment_date' => $this->faker->dateTimeBetween('first day of january this year', 'last day of december this year'),
        ]);
    }

    /**
     * Indicate that the bank receipt has a high amount.
     */
    public function highAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 10000000, 50000000),
        ]);
    }

    /**
     * Indicate that the bank receipt has a low amount.
     */
    public function lowAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 100000, 1000000),
        ]);
    }

    /**
     * Indicate that the bank receipt is for a specific customer.
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Indicate that the bank receipt is for a specific bank.
     */
    public function forBank(Bank $bank): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_id' => $bank->id,
        ]);
    }

    /**
     * Indicate that the bank receipt was created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
