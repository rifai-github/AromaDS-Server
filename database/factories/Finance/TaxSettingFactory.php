<?php

namespace Database\Factories\Finance;

use App\Models\Finance\TaxSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TaxSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TaxSetting::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $taxTypes = ['income', 'sales', 'vat', 'withholding', 'other'];
        $calculationMethods = ['percentage', 'fixed', 'tiered'];
        $roundingMethods = ['nearest', 'up', 'down', 'none'];
        $statuses = ['active', 'inactive'];
        
        $taxType = $this->faker->randomElement($taxTypes);
        $calculationMethod = $this->faker->randomElement($calculationMethods);
        
        // Generate appropriate tax rate based on type and calculation method
        $taxRate = $this->generateTaxRate($taxType, $calculationMethod);
        
        return [
            'name' => $this->faker->unique()->words(3, true),
            'tax_code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'tax_type' => $taxType,
            'tax_rate' => $taxRate,
            'description' => $this->faker->optional(0.7)->sentence(),
            'effective_date' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', '+1 year'),
            'end_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+2 years'),
            'status' => $this->faker->randomElement($statuses),
            'is_compound' => $this->faker->boolean(20),
            'calculation_method' => $calculationMethod,
            'rounding_method' => $this->faker->randomElement($roundingMethods),
            'decimal_places' => $this->faker->randomElement([0, 2, 4]),
            'minimum_amount' => $this->faker->optional(0.4)->randomFloat(2, 0, 1000000),
            'maximum_amount' => $this->faker->optional(0.3)->randomFloat(2, 1000000, 10000000),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Generate appropriate tax rate based on tax type and calculation method
     */
    private function generateTaxRate(string $taxType, string $calculationMethod): float
    {
        if ($calculationMethod === 'fixed') {
            return $this->faker->randomFloat(2, 1000, 100000);
        }

        switch ($taxType) {
            case 'income':
                return $this->faker->randomFloat(2, 5, 35);
            case 'sales':
                return $this->faker->randomFloat(2, 5, 20);
            case 'vat':
                return $this->faker->randomFloat(2, 0, 20);
            case 'withholding':
                return $this->faker->randomFloat(2, 1, 15);
            case 'other':
                return $this->faker->randomFloat(2, 0, 25);
            default:
                return $this->faker->randomFloat(2, 0, 20);
        }
    }

    /**
     * Indicate that the tax setting is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the tax setting is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tax setting is for income tax.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => 'income',
            'tax_rate' => $this->faker->randomFloat(2, 5, 35),
            'calculation_method' => 'percentage',
        ]);
    }

    /**
     * Indicate that the tax setting is for sales tax.
     */
    public function sales(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => 'sales',
            'tax_rate' => $this->faker->randomFloat(2, 5, 20),
            'calculation_method' => 'percentage',
        ]);
    }

    /**
     * Indicate that the tax setting is for VAT.
     */
    public function vat(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => 'vat',
            'tax_rate' => $this->faker->randomFloat(2, 0, 20),
            'calculation_method' => 'percentage',
        ]);
    }

    /**
     * Indicate that the tax setting is for withholding tax.
     */
    public function withholding(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => 'withholding',
            'tax_rate' => $this->faker->randomFloat(2, 1, 15),
            'calculation_method' => 'percentage',
        ]);
    }

    /**
     * Indicate that the tax setting is compound.
     */
    public function compound(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_compound' => true,
        ]);
    }

    /**
     * Indicate that the tax setting uses fixed calculation method.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_method' => 'fixed',
            'tax_rate' => $this->faker->randomFloat(2, 1000, 100000),
        ]);
    }

    /**
     * Indicate that the tax setting uses tiered calculation method.
     */
    public function tiered(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_method' => 'tiered',
            'tax_rate' => $this->faker->randomFloat(2, 5, 25),
        ]);
    }

    /**
     * Indicate that the tax setting is effective now.
     */
    public function effective(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => Carbon::now()->subDays($this->faker->numberBetween(1, 365)),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the tax setting is future dated.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => Carbon::now()->addDays($this->faker->numberBetween(1, 365)),
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tax setting is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => Carbon::now()->subDays($this->faker->numberBetween(366, 730)),
            'end_date' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tax setting has no end date.
     */
    public function noEndDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the tax setting has minimum and maximum amounts.
     */
    public function withThresholds(): static
    {
        $minAmount = $this->faker->randomFloat(2, 0, 1000000);
        $maxAmount = $minAmount + $this->faker->randomFloat(2, 100000, 5000000);
        
        return $this->state(fn (array $attributes) => [
            'minimum_amount' => $minAmount,
            'maximum_amount' => $maxAmount,
        ]);
    }

    /**
     * Indicate that the tax setting has no thresholds.
     */
    public function noThresholds(): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_amount' => null,
            'maximum_amount' => null,
        ]);
    }

    /**
     * Indicate that the tax setting has description.
     */
    public function withDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the tax setting has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the tax setting was created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Indicate that the tax setting uses zero decimal places.
     */
    public function zeroDecimals(): static
    {
        return $this->state(fn (array $attributes) => [
            'decimal_places' => 0,
        ]);
    }

    /**
     * Indicate that the tax setting uses two decimal places.
     */
    public function twoDecimals(): static
    {
        return $this->state(fn (array $attributes) => [
            'decimal_places' => 2,
        ]);
    }

    /**
     * Indicate that the tax setting uses four decimal places.
     */
    public function fourDecimals(): static
    {
        return $this->state(fn (array $attributes) => [
            'decimal_places' => 4,
        ]);
    }
}
