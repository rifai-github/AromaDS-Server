<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tambah Term of Payment 5x/6x/8x per periode contract.
 *
 * Master Catalyst punya "5 x In Advance", "6 x In Advance", dan "8 x In Advance"
 * (kode 5xA / 6x / 8x), tapi master TOP ADS berhenti di 4x — jadi quotation hasil
 * import tidak punya nilai yang bisa dipilih di dropdown SQ.
 *
 * Label mengikuti konvensi yang sudah dipakai klien di data live untuk 2x/3x/4x
 * ("2x in advance"), bukan default seeder ("2x dalam Periode Contract").
 *
 * Lihat CatalystMasterDataImporter::CATALYST_TERM_OF_PAYMENT_MAP.
 */
return new class extends Migration
{
    private const PAYMENT_COUNTS = [5, 6, 8];

    public function up(): void
    {
        $masterOptionId = $this->termOfPaymentMasterOptionId();

        if (! $masterOptionId) {
            return;
        }

        foreach (self::PAYMENT_COUNTS as $count) {
            $optionName = "{$count}x per periode contract";

            $exists = DB::table('option_details')
                ->where('master_option_id', $masterOptionId)
                ->where('option_name', $optionName)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('option_details')->insert([
                'master_option_id' => $masterOptionId,
                'option_name' => $optionName,
                'label' => "{$count}x in advance",
                'code' => 'installments',
                'option_description' => json_encode([
                    'description' => "Pembayaran {$count}x dibagi rata dalam satu periode kontrak",
                    'billing_mode' => 'per_contract_period',
                    'months' => null,
                    'payment_count' => (string) $count,
                ]),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->forgetQuotationTermCache();
    }

    public function down(): void
    {
        $masterOptionId = $this->termOfPaymentMasterOptionId();

        if (! $masterOptionId) {
            return;
        }

        $optionNames = array_map(fn ($count) => "{$count}x per periode contract", self::PAYMENT_COUNTS);

        // Jangan hapus kalau sudah dipakai quotation/contract - biarkan datanya utuh.
        $inUse = DB::table('quotations')->whereIn('terms_of_payment', $optionNames)->exists()
            || DB::table('contracts')->whereIn('term_of_payment', $optionNames)->exists();

        if ($inUse) {
            return;
        }

        DB::table('option_details')
            ->where('master_option_id', $masterOptionId)
            ->whereIn('option_name', $optionNames)
            ->delete();

        $this->forgetQuotationTermCache();
    }

    /**
     * Dropdown TOP di wizard SQ di-cache 10 menit
     * (QuotationWizardController::getTermOfPaymentOptions). Tanpa ini opsi baru
     * baru muncul setelah cache-nya kedaluwarsa sendiri.
     */
    private function forgetQuotationTermCache(): void
    {
        Cache::forget('quotation-wizard:term-of-payment-options');
    }

    private function termOfPaymentMasterOptionId(): ?int
    {
        $id = DB::table('master_options')->where('name', 'Term of Payment')->value('id');

        return $id ? (int) $id : null;
    }
};
