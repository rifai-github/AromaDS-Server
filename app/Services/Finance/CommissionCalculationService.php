<?php

namespace App\Services\Finance;

use App\Models\Finance\MarketingTarget;
use App\Models\Finance\CommissionLevel;
use App\Models\Finance\MarketingLevel;
use App\Models\Finance\CrVariable;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\Achievement;
use App\Models\Finance\RenewalContractAssignment;
use App\Models\Contract;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CommissionCalculationService
{
    /**
     * Calculate commission for a contract based on target achievement
     * 
     * Rules:
     * 1. Only calculate for installed and billable contracts (is_installed = true)
     * 2. Based on target achievement percentage (multi-level)
     * 3. Based on CR variable (default 90 days, configurable)
     * 4. Based on net value (if updated, use net_value; else use contract_value)
     * 5. Not accumulated - only count current period
     * 6. Different for new contract vs renewal contract
     */
    public function calculateCommissionForContract(Contract $contract, $cashReceiptDate = null): array
    {
        try {
            DB::beginTransaction();

            // Get contract data
            $marketingUser = $contract->marketing;
            if (!$marketingUser) {
                throw new \Exception("Contract does not have marketing user");
            }

            // Check if contract is installed
            if (!$contract->is_installed) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Contract is not installed yet. Commission can only be calculated for installed contracts.',
                    'commission' => null
                ];
            }

            // Satu kontrak hanya boleh punya satu komisi otomatis. Tanpa guard ini, mengganti
            // install_date (ContractController::updateAdditionalInfo) membuat record kedua dan
            // menambahkan nilai kontrak ke target marketing sekali lagi. Kunci baris kontrak
            // supaya webhook pembayaran dan update install tidak lolos bersamaan.
            Contract::whereKey($contract->id)->lockForUpdate()->first();
            $existingCalculation = $this->automaticCalculationsFor($contract)->first();
            if ($existingCalculation) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => "Commission already calculated for contract {$contract->contract_number} (status: {$existingCalculation->status}).",
                    'commission' => $existingCalculation,
                    'already_calculated' => true
                ];
            }

            // Determine contract type (new or renewal)
            $isRenewal = $this->isRenewalContract($contract);
            $targetType = $isRenewal ? 'renewal' : 'new';

            // Get current achievement period
            $achievementPeriod = $this->getCurrentAchievementPeriod();
            if (!$achievementPeriod) {
                throw new \Exception("No active achievement period found");
            }

            // Get marketing target for this user and period
            $marketingTarget = $this->findMarketingTarget($marketingUser->id, $achievementPeriod->id, $targetType, $contract);

            if (!$marketingTarget) {
                DB::rollBack();

                $hint = $targetType === 'renewal'
                    ? ' Buat Marketing Target tipe Renewal atau Renewal Contract Assignment untuk periode ini.'
                    : ' Buat Marketing Target tipe New untuk periode ini.';

                return [
                    'success' => false,
                    'message' => "No active marketing target found for user {$marketingUser->name} for {$targetType} contracts ({$achievementPeriod->period_name}).{$hint}",
                    'commission' => null
                ];
            }

            // Get net value (use net_value if updated, else use contract_value)
            $contractValue = $contract->net_value ?? $contract->contract_value;
            
            // Update marketing target achieved amount
            $marketingTarget->achieved_amount += $contractValue;
            $marketingTarget->save();

            // Calculate achievement percentage
            $achievementPercentage = ($marketingTarget->achieved_amount / $marketingTarget->target_amount) * 100;

            // Get commission level based on achievement percentage
            $commissionLevel = CommissionLevel::getLevelByPercentage($achievementPercentage, $targetType);
            if (!$commissionLevel) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => "No commission level found for achievement percentage: {$achievementPercentage}% ({$targetType}). Buat Commission Level tipe ".ucfirst($targetType).' atau Both yang mencakup persentase ini.',
                    'commission' => null
                ];
            }

            // Calculate commission amount
            $commissionRate = $commissionLevel->commission_rate; // e.g., 1.00% = 1.00
            $commissionAmount = $contractValue * ($commissionRate / 100);

            // Check CR variable (Cash Receipt period)
            $crVariable = CrVariable::getDefault();
            $crDays = $crVariable ? $crVariable->cr_days : 90; // Default 90 days
            
            // Calculate CR due date (if cash receipt date provided)
            $crDueDate = null;
            $isCrExpired = false;
            if ($cashReceiptDate) {
                $crDueDate = Carbon::parse($cashReceiptDate)->addDays($crDays);
                $isCrExpired = Carbon::now()->gt($crDueDate);
            }

            // If CR expired, commission is void
            if ($isCrExpired) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => "Cash Receipt period expired. Commission void due to payment received after {$crDays} days.",
                    'commission' => null,
                    'is_cr_expired' => true
                ];
            }

            // Check if commission should go to different user (commission transfer)
            $commissionRecipient = $this->getCommissionRecipient($contract);
            $finalUserId = $commissionRecipient ? $commissionRecipient->id : $marketingUser->id;

            // Get marketing level for the user
            $marketingLevel = $this->getMarketingLevel($finalUserId);

            // Create commission calculation
            $commissionCalculation = CommissionCalculation::create([
                'user_id' => $finalUserId,
                'achievement_period_id' => $achievementPeriod->id,
                'marketing_target_id' => $marketingTarget->id,
                'contract_id' => $contract->id,
                'calculation_type' => $isRenewal ? 'renewal' : 'new',
                'base_amount' => $contract->contract_value,
                'net_value' => $contractValue,
                'commission_rate' => $commissionRate,
                'commission_level_id' => $commissionLevel->id,
                'commission_amount' => $commissionAmount,
                'bonus_amount' => 0,
                'penalty_amount' => 0,
                'final_amount' => $commissionAmount,
                // enum DB: calculated|approved|paid|cancelled. Dihitung karena uang sudah masuk
                // (jalur pembayaran) langsung approved, sama seperti cabang update di bawah.
                'status' => $cashReceiptDate ? 'approved' : 'calculated',
                'calculation_date' => now(),
                'calculation_notes' => "Auto-calculated for contract {$contract->contract_number}. Achievement: {$achievementPercentage}%",
                'is_installed' => true,
                'cash_receipt_date' => $cashReceiptDate ? Carbon::parse($cashReceiptDate) : null,
                'cr_variable_id' => $crVariable ? $crVariable->id : null,
                'cr_due_date' => $crDueDate,
                'is_cr_expired' => $isCrExpired,
                'is_commission_void' => $isCrExpired,
                'marketing_level_id' => $marketingLevel ? $marketingLevel->id : null,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1
            ]);

            // Create achievement record
            Achievement::create([
                'user_id' => $finalUserId,
                'achievement_period_id' => $achievementPeriod->id,
                'contract_id' => $contract->id,
                'achievement_type' => $isRenewal ? 'renewal' : 'new',
                'target_amount' => $marketingTarget->target_amount,
                'achieved_amount' => $marketingTarget->achieved_amount,
                'commission_rate' => $commissionRate,
                'commission_level_id' => $commissionLevel->id,
                'commission_amount' => $commissionAmount,
                'status' => self::achievementStatus((float) $marketingTarget->achieved_amount, (float) $marketingTarget->target_amount),
                'achievement_date' => now(),
                'cut_off_start_date' => $achievementPeriod->start_date->day,
                'cut_off_end_date' => $achievementPeriod->end_date->day,
                'cut_off_tolerance_days' => 5, // Default tolerance
                'is_installed' => true,
                'installed_date' => $contract->installed_date ?? now(),
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1
            ]);

            DB::commit();

            Log::info("Commission calculated for contract {$contract->contract_number}: {$commissionAmount} for user {$finalUserId}");

            return [
                'success' => true,
                'message' => 'Commission calculated successfully',
                'commission' => $commissionCalculation,
                'amount' => $commissionAmount,
                'achievement_percentage' => $achievementPercentage,
                'commission_level' => $commissionLevel->level_name
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Commission calculation failed for contract {$contract->contract_number}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to calculate commission: ' . $e->getMessage(),
                'commission' => null
            ];
        }
    }

    /**
     * Calculate commission when cash receipt is received
     */
    // Ada dua model Invoice (App\Models\Invoice dan App\Models\Finance\Invoice) untuk tabel yang sama;
    // BankReceiptService dan InvoiceController memakai yang kedua. Tipe tunggal membuat pembayaran
    // invoice dari layar melempar TypeError (bukan Exception, jadi lolos dari semua catch) tepat
    // di langkah komisi.
    public function calculateCommissionOnCashReceipt(Invoice|\App\Models\Finance\Invoice $invoice, $cashReceiptDate): array
    {
        try {
            $contract = $invoice->contract;
            if (!$contract) {
                throw new \Exception("Invoice does not have a contract");
            }

            // Check if commission already calculated
            // Hanya komisi otomatis (new/renewal). Komisi manual/adjustment yang kebetulan
            // menunjuk kontrak yang sama dulu ikut tertangkap di sini: pembayaran invoice
            // meng-approve komisi manual itu dan komisi otomatisnya tak pernah dibuat.
            // Semua komisi otomatis kontrak ini: Commission Transfer sebagian memecah satu
            // komisi menjadi dua baris (marketing asal + penerima) yang harus ikut approved
            // bersama. Komisi yang sudah Paid tidak disentuh - dulu pembayaran invoice periode
            // berikutnya mengembalikannya ke Approved sehingga bisa dibayar dua kali.
            $openCalculations = $this->automaticCalculationsFor($contract)
                ->whereNotIn('status', ['cancelled', 'paid'])
                ->get();
            $existingCalculation = $openCalculations->first()
                ?? $this->automaticCalculationsFor($contract)->where('status', 'paid')->first();

            if ($existingCalculation && $openCalculations->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "Commission for contract {$contract->contract_number} is already paid.",
                    'commission' => $existingCalculation,
                ];
            }

            if ($existingCalculation) {
                // Update existing calculation with cash receipt date
                $crVariable = CrVariable::getDefault();
                $crDays = $crVariable ? $crVariable->cr_days : 90;
                $crDueDate = Carbon::parse($cashReceiptDate)->addDays($crDays);
                $isCrExpired = Carbon::now()->gt($crDueDate);

                foreach ($openCalculations as $openCalculation) {
                    $openCalculation->update([
                        'cash_receipt_date' => Carbon::parse($cashReceiptDate),
                        'cr_due_date' => $crDueDate,
                        'is_cr_expired' => $isCrExpired,
                        'is_commission_void' => $isCrExpired,
                        'status' => $isCrExpired ? 'cancelled' : 'approved',
                        'updated_by' => auth()->id()
                    ]);
                }

                return [
                    'success' => !$isCrExpired,
                    'message' => $isCrExpired 
                        ? "Commission void: Cash receipt received after {$crDays} days" 
                        : "Commission approved: Cash receipt received within {$crDays} days",
                    'commission' => $existingCalculation,
                    'is_cr_expired' => $isCrExpired
                ];
            } else {
                // Calculate new commission
                return $this->calculateCommissionForContract($contract, $cashReceiptDate);
            }

        } catch (\Exception $e) {
            Log::error("Commission calculation on cash receipt failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to calculate commission: ' . $e->getMessage(),
                'commission' => null
            ];
        }
    }

    /**
     * Apply a Contract Net edit to the contract's automatic commission, if one exists.
     *
     * Returns null when the contract has no automatic commission yet: the net value is
     * then simply picked up when Tanggal Install triggers the first calculation, so an
     * edit before install must not create a commission on its own.
     */
    public function applyNetValueChange(Contract $contract): ?array
    {
        if (! $this->automaticCalculationsFor($contract)->exists()) {
            return null;
        }

        return $this->recalculateCommissionForContract($contract);
    }

    /**
     * Recalculate commission when net value is updated
     */
    public function recalculateCommissionForContract(Contract $contract): array
    {
        $calculation = $this->automaticCalculationsFor($contract)->first();
        if (!$calculation) {
            return $this->calculateCommissionForContract($contract);
        }

        // Komisi yang sudah approved/paid/void tidak dihitung ulang diam-diam.
        if ($calculation->status !== 'calculated') {
            return [
                'success' => false,
                'message' => "Commission for contract {$contract->contract_number} is already {$calculation->status}; net value change was not applied.",
                'commission' => $calculation
            ];
        }

        try {
            DB::beginTransaction();

            // Hitung ulang di record yang sama: periode, target, dan penerima tetap milik
            // perhitungan awal. Target hanya digeser sebesar selisih nilai — dulu record lama
            // dihapus lalu nilai baru ditambahkan tanpa mengurangi nilai lama, sehingga
            // achieved_amount terhitung dobel.
            $calculation = CommissionCalculation::whereKey($calculation->id)->lockForUpdate()->first();
            $oldValue = (float) ($calculation->net_value ?? $calculation->base_amount);
            $newValue = (float) ($contract->net_value ?? $contract->contract_value);
            $delta = $newValue - $oldValue;

            $marketingTarget = $calculation->marketing_target_id
                ? MarketingTarget::whereKey($calculation->marketing_target_id)->lockForUpdate()->first()
                : null;
            if ($marketingTarget) {
                $marketingTarget->achieved_amount += $delta;
                $marketingTarget->save();
            }

            $achievement = Achievement::where('contract_id', $contract->id)
                ->where('achievement_period_id', $calculation->achievement_period_id)
                ->where('achievement_type', $calculation->calculation_type)
                ->latest('id')
                ->first();

            // Tier dievaluasi ulang pada posisi pencapaian saat kontrak ini dihitung
            // (snapshot Achievement), bukan pencapaian hari ini yang sudah memuat kontrak lain.
            $commissionRate = (float) $calculation->commission_rate;
            $commissionLevelId = $calculation->commission_level_id;
            $snapshotAchieved = null;
            if ($achievement && (float) $achievement->target_amount > 0) {
                $snapshotAchieved = (float) $achievement->achieved_amount + $delta;
                $percentage = ($snapshotAchieved / (float) $achievement->target_amount) * 100;
                $commissionLevel = CommissionLevel::getLevelByPercentage($percentage, $calculation->calculation_type);
                if ($commissionLevel) {
                    $commissionRate = (float) $commissionLevel->commission_rate;
                    $commissionLevelId = $commissionLevel->id;
                }
            }

            $commissionAmount = $newValue * ($commissionRate / 100);

            $calculation->update([
                'base_amount' => $contract->contract_value,
                'net_value' => $newValue,
                'commission_rate' => $commissionRate,
                'commission_level_id' => $commissionLevelId,
                'commission_amount' => $commissionAmount,
                'final_amount' => $commissionAmount + (float) $calculation->bonus_amount - (float) $calculation->penalty_amount,
                'calculation_notes' => trim(($calculation->calculation_notes ?? '')."\nRecalculated ".now()->toDateString().": net value {$oldValue} -> {$newValue}"),
                'updated_by' => auth()->id() ?? $calculation->updated_by
            ]);

            if ($achievement) {
                $achievement->update([
                    'status' => self::achievementStatus((float) ($snapshotAchieved ?? $achievement->achieved_amount), (float) $achievement->target_amount),
                    'achieved_amount' => $snapshotAchieved ?? $achievement->achieved_amount,
                    'commission_rate' => $commissionRate,
                    'commission_level_id' => $commissionLevelId,
                    'commission_amount' => $commissionAmount,
                    'updated_by' => auth()->id() ?? $achievement->updated_by
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Commission recalculated successfully',
                'commission' => $calculation->fresh(),
                'amount' => $commissionAmount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Commission recalculation failed for contract {$contract->contract_number}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to recalculate commission: ' . $e->getMessage(),
                'commission' => null
            ];
        }
    }

    /**
     * Komisi otomatis milik kontrak (tipe new/renewal). Komisi manual/adjustment dari
     * CommissionController tidak dihitung, jadi tetap boleh berdampingan.
     */
    /**
     * Status baris Achievement otomatis dari posisi pencapaian saat kontrak dihitung.
     * Di bawah target tetap "pending" (periode masih berjalan), bukan "failed".
     * Dulu selalu "pending" walau pencapaian sudah melewati target (QA 8 Okt).
     */
    public static function achievementStatus(float $achieved, float $target): string
    {
        if ($target <= 0 || $achieved < $target) {
            return 'pending';
        }

        return $achieved > $target ? 'exceeded' : 'achieved';
    }

    private function automaticCalculationsFor(Contract $contract)
    {
        return CommissionCalculation::where('contract_id', $contract->id)
            ->whereIn('calculation_type', ['new', 'renewal'])
            ->orderBy('id');
    }

    /**
     * Check if contract is renewal
     */
    private function isRenewalContract(Contract $contract): bool
    {
        $quotationType = $contract->quotation?->quotation_type;

        if ($quotationType === 'renewal' || $contract->contract_type === 'renewal') {
            return true;
        }

        // Jenis eksplisit dari SQ/kontrak menang. Dulu kontrak "new" ikut dianggap renewal
        // hanya karena customer-nya punya kontrak aktif lain (QA 8 Okt: MDO-AG/26-03/0006),
        // sehingga dicari target Renewal dan komisinya tidak terbentuk.
        if ($quotationType === 'new' || $contract->contract_type === 'new') {
            return false;
        }

        // Check if there's a previous contract for same customer
        $previousContract = Contract::where('customer_id', $contract->customer_id)
            ->where('id', '<', $contract->id)
            ->where('status', 'active')
            ->exists();

        return $previousContract;
    }

    /**
     * Target marketing aktif (belum dikunci) untuk user, periode, dan tipe kontrak.
     *
     * Target renewal juga bisa datang dari Renewal Contract Assignment (user + periode +
     * target, opsional rentang nomor kontrak). Dulu assignment itu tidak dibaca sama sekali,
     * jadi QA yang menyiapkan target renewal lewat menu tersebut tidak pernah mendapat komisi.
     * Bila belum ada Marketing Target Renewal, target dibuat dari assignment supaya
     * pencapaiannya tetap terlihat di Marketing Targets. Target yang sudah ada selalu menang,
     * dan target yang dikunci tidak diganti.
     */
    private function findMarketingTarget(int $userId, int $periodId, string $targetType, Contract $contract): ?MarketingTarget
    {
        $targets = fn () => MarketingTarget::withTrashed()
            ->where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('target_type', $targetType);

        $target = $targets()->whereNull('deleted_at')->where('is_locked', false)->lockForUpdate()->first();
        if ($target || $targetType !== 'renewal' || $targets()->whereNull('deleted_at')->exists()) {
            return $target;
        }

        $assignment = RenewalContractAssignment::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->orderByDesc('id')
            ->get()
            ->first(fn (RenewalContractAssignment $a) => $a->isContractInRange($contract->contract_number));

        if (!$assignment || (float) $assignment->target_amount <= 0) {
            return null;
        }

        $attributes = [
            'target_amount' => $assignment->target_amount,
            'is_locked' => false,
            'notes' => RenewalAssignmentService::generatedTargetNote($assignment),
            'updated_by' => auth()->id() ?? 1,
        ];

        // unique_user_period_type ikut menghitung baris yang di-soft-delete.
        $trashed = $targets()->onlyTrashed()->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->update($attributes);

            return $trashed;
        }

        return MarketingTarget::create($attributes + [
            'user_id' => $userId,
            'achievement_period_id' => $periodId,
            'target_type' => 'renewal',
            'achieved_amount' => 0,
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Get current achievement period
     */
    private function getCurrentAchievementPeriod()
    {
        return AchievementPeriod::current()->first();
    }

    /**
     * Get commission recipient (if transferred)
     */
    private function getCommissionRecipient(Contract $contract): ?User
    {
        // Check if there's a commission transfer for this contract
        $transfer = \App\Models\Finance\CommissionTransfer::where('contract_id', $contract->id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if ($transfer) {
            return $transfer->toUser;
        }

        // Check contract commission_recipient_id
        if ($contract->commission_recipient_id) {
            return User::find($contract->commission_recipient_id);
        }

        return null;
    }

    /**
     * Get marketing level for user
     */
    private function getMarketingLevel(int $userId): ?MarketingLevel
    {
        // Get user's marketing level from pivot table
        $marketingLevel = MarketingLevel::whereHas('users', function($query) use ($userId) {
            $query->where('users.id', $userId);
        })
        ->active()
        ->ordered()
        ->first();

        // If no level assigned, return default level or null
        if (!$marketingLevel) {
            return MarketingLevel::active()->ordered()->first();
        }

        return $marketingLevel;
    }
}

