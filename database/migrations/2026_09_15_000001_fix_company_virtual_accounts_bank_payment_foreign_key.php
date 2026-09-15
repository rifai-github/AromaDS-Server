<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * `company_virtual_accounts.bank_payment_id` dulu bernama `bank_id` dan menunjuk `banks`.
 * Kolomnya sudah di-rename, FK-nya tidak — namanya masih
 * `company_virtual_accounts_bank_id_foreign` dan targetnya masih `banks(id)`.
 *
 * Seluruh aplikasi memperlakukan kolom ini sebagai FK ke `bank_payments`
 * (`'bank_payment_id' => 'required|exists:bank_payments,id'` di CompanyVirtualAccountController,
 * VirtualAccountController, dan ContractController). Satu bank punya banyak rekening, jadi
 * `bank_payments.id` cepat melewati `banks.id` — begitu itu terjadi, pembuatan VA gagal dengan
 * "Integrity constraint violation: 1452" walau validasi Laravel-nya lolos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_virtual_accounts') || !Schema::hasTable('bank_payments')) {
            return;
        }

        $this->dropLegacyForeignKey();

        // Kolom dibuat nullable supaya baris warisan yang tidak bisa dipetakan tetap
        // tersimpan (di-NULL-kan) alih-alih menggagalkan pembuatan FK saat deploy.
        DB::statement('ALTER TABLE `company_virtual_accounts` MODIFY `bank_payment_id` BIGINT UNSIGNED NULL');

        $this->remapLegacyBankIds();

        Schema::table('company_virtual_accounts', function (Blueprint $table) {
            $table->foreign('bank_payment_id', 'company_virtual_accounts_bank_payment_id_foreign')
                ->references('id')->on('bank_payments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_virtual_accounts')) {
            return;
        }

        $this->dropForeignKeyIfExists('company_virtual_accounts_bank_payment_id_foreign');

        // Baris yang bank_payment_id-nya NULL tidak bisa dikembalikan ke kolom NOT NULL,
        // jadi struktur lama hanya dipulihkan kalau tidak ada NULL yang tersisa.
        if (DB::table('company_virtual_accounts')->whereNull('bank_payment_id')->exists()) {
            return;
        }

        DB::statement('ALTER TABLE `company_virtual_accounts` MODIFY `bank_payment_id` BIGINT UNSIGNED NOT NULL');

        if (Schema::hasTable('banks')) {
            Schema::table('company_virtual_accounts', function (Blueprint $table) {
                $table->foreign('bank_payment_id', 'company_virtual_accounts_bank_id_foreign')
                    ->references('id')->on('banks')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Baris warisan (hasil import Catalyst) menyimpan `banks.id` di kolom ini.
     * Dipetakan ke rekening default bank tersebut; kalau bank itu tidak punya
     * rekening sama sekali, nilainya di-NULL-kan supaya FK baru bisa dipasang.
     */
    private function remapLegacyBankIds(): void
    {
        $orphanIds = DB::table('company_virtual_accounts')
            ->whereNotNull('bank_payment_id')
            ->whereNotIn('bank_payment_id', fn ($query) => $query->select('id')->from('bank_payments'))
            ->distinct()
            ->pluck('bank_payment_id');

        if ($orphanIds->isEmpty()) {
            return;
        }

        foreach ($orphanIds as $legacyBankId) {
            $bankPaymentId = DB::table('bank_payments')
                ->where('bank_id', $legacyBankId)
                ->whereNull('deleted_at')
                ->orderByDesc('is_default_va')
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->value('id');

            $affected = DB::table('company_virtual_accounts')
                ->where('bank_payment_id', $legacyBankId)
                ->update(['bank_payment_id' => $bankPaymentId]);

            Log::info('company_virtual_accounts.bank_payment_id dipetakan ulang', [
                'legacy_bank_id' => $legacyBankId,
                'bank_payment_id' => $bankPaymentId,
                'rows' => $affected,
            ]);
        }
    }

    private function dropLegacyForeignKey(): void
    {
        // Nama constraint berbeda antar lingkungan: dump bootstrap memakai nama warisan
        // `..._bank_id_foreign`, instalasi yang lebih baru bisa memakai nama konvensi Laravel.
        $this->dropForeignKeyIfExists('company_virtual_accounts_bank_id_foreign');
        $this->dropForeignKeyIfExists('company_virtual_accounts_bank_payment_id_foreign');
    }

    private function dropForeignKeyIfExists(string $constraint): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'company_virtual_accounts')
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `company_virtual_accounts` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
