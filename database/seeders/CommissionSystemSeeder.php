<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\Achievement;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\CommissionPayment;
use App\Models\User;

class CommissionSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for created_by
        $firstUser = User::first();
        if (!$firstUser) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        // Create achievement periods
        $periods = [
            [
                'period_name' => 'Q4 2025 Achievement Period',
                'start_date' => '2025-10-01',
                'end_date' => '2025-12-31',
                'status' => 'active',
                'description' => 'Q4 2025 achievement tracking period',
                'created_by' => $firstUser->id
            ],
            [
                'period_name' => 'Q1 2026 Achievement Period',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'status' => 'inactive',
                'description' => 'Q1 2026 achievement tracking period',
                'created_by' => $firstUser->id
            ]
        ];

        foreach ($periods as $periodData) {
            AchievementPeriod::create($periodData);
        }

        // Get users for achievements
        $users = User::take(3)->get();
        $period = AchievementPeriod::first();

        if ($users->count() > 0 && $period) {
            // Create achievements for each user
            $achievementTypes = ['sales', 'service', 'installation'];
            $statuses = ['achieved', 'exceeded', 'failed', 'pending'];

            foreach ($users as $index => $user) {
                $achievementType = $achievementTypes[$index % count($achievementTypes)];
                $status = $statuses[$index % count($statuses)];
                
                $targetAmount = rand(5000000, 20000000);
                $achievedAmount = $status === 'failed' ? rand(1000000, $targetAmount - 1000000) : 
                                 ($status === 'exceeded' ? $targetAmount + rand(1000000, 5000000) : $targetAmount);
                
                $commissionRate = rand(3, 8);
                $commissionAmount = $achievedAmount * ($commissionRate / 100);

                Achievement::create([
                    'user_id' => $user->id,
                    'achievement_period_id' => $period->id,
                    'achievement_type' => $achievementType,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedAmount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'status' => $status,
                    'achievement_date' => now()->subDays(rand(1, 30)),
                    'notes' => "Sample achievement for {$achievementType}",
                    'created_by' => $firstUser->id
                ]);

                // Create commission calculation
                $bonusAmount = $status === 'exceeded' ? rand(50000, 200000) : 0;
                $penaltyAmount = $status === 'failed' ? rand(10000, 50000) : 0;
                $finalAmount = $commissionAmount + $bonusAmount - $penaltyAmount;

                $commission = CommissionCalculation::create([
                    'user_id' => $user->id,
                    'achievement_period_id' => $period->id,
                    'calculation_type' => 'automatic',
                    'base_amount' => $achievedAmount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'bonus_amount' => $bonusAmount,
                    'penalty_amount' => $penaltyAmount,
                    'final_amount' => $finalAmount,
                    'status' => $status === 'failed' ? 'cancelled' : 'calculated',
                    'calculation_date' => now()->subDays(rand(1, 30)),
                    'calculation_notes' => "Automatic calculation based on {$achievementType} achievement",
                    'created_by' => $firstUser->id
                ]);

                // Create commission payment for some calculations
                if ($status !== 'failed') {
                    $paymentMethods = ['bank_transfer', 'cash', 'check'];
                    $paymentStatuses = ['completed', 'pending', 'processing'];
                    
                    CommissionPayment::create([
                        'commission_calculation_id' => $commission->id,
                        'user_id' => $user->id,
                        'amount' => $finalAmount,
                        'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                        'payment_reference' => 'PAY-' . str_pad($commission->id, 6, '0', STR_PAD_LEFT),
                        'payment_date' => now()->subDays(rand(1, 15)),
                        'status' => $paymentStatuses[array_rand($paymentStatuses)],
                        'payment_notes' => "Payment for {$achievementType} commission",
                        'bank_account' => '1234567890',
                        'bank_name' => 'Bank Mandiri',
                        'created_by' => $firstUser->id
                    ]);
                }
            }
        }

        $this->command->info('Commission system sample data created successfully!');
    }
}
