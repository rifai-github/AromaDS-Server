<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 24 Nov 2026: data pajak/identitas customer di invoice harus berupa snapshot saat invoice
 * terbit - tidak boleh ikut berubah bila master Customer Tax diganti di tengah penagihan.
 * NPWP, alamat pajak dan alamat tagih sudah distempel; NIK dan nama Bill To masih dibaca
 * langsung dari master, dan NPWP/alamat pajak kosong dulu jatuh ke master customer.
 *
 * Kolom baru + pengisian sekali untuk invoice yang sudah ada, dari master saat ini (hanya
 * mengisi yang kosong - stempel yang sudah ada tidak disentuh).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'bill_to_name')) {
                $table->string('bill_to_name')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('invoices', 'nik_number')) {
                $table->string('nik_number', 50)->nullable()->after('npwp_number');
            }
        });

        if (! Schema::hasTable('customers')) {
            return;
        }

        $hasTaxSettings = Schema::hasTable('customer_tax_settings');
        $hasBillingGroups = Schema::hasTable('billing_groups') && Schema::hasColumn('billing_groups', 'nik');

        DB::table('invoices')
            ->whereNotNull('customer_id')
            ->where(function ($query) {
                $query->whereNull('bill_to_name')
                    ->orWhereNull('nik_number')
                    ->orWhereNull('npwp_number')
                    ->orWhereNull('tax_address');
            })
            ->select(['id', 'customer_id', 'billing_group_id', 'bill_to_name', 'nik_number', 'npwp_number', 'tax_address'])
            ->orderBy('id')
            ->chunkById(500, function ($invoices) use ($hasTaxSettings, $hasBillingGroups) {
                $customers = DB::table('customers')->whereIn('id', $invoices->pluck('customer_id')->unique())
                    ->get(['id', 'name', 'nik', 'npwp', 'npwp_address'])->keyBy('id');
                $taxes = $hasTaxSettings
                    ? DB::table('customer_tax_settings')->whereIn('customer_id', $customers->keys())->whereNull('deleted_at')
                        ->orderBy('id')->get(['customer_id', 'tax_number', 'tax_address'])->groupBy('customer_id')->map->first()
                    : collect();
                $groups = $hasBillingGroups
                    ? DB::table('billing_groups')->whereIn('id', $invoices->pluck('billing_group_id')->filter()->unique())->pluck('nik', 'id')
                    : collect();

                foreach ($invoices as $invoice) {
                    $customer = $customers[$invoice->customer_id] ?? null;
                    if (! $customer) {
                        continue;
                    }
                    $tax = $taxes[$invoice->customer_id] ?? null;
                    $values = array_filter([
                        'bill_to_name' => $invoice->bill_to_name === null ? $customer->name : null,
                        'nik_number' => $invoice->nik_number === null ? (($groups[$invoice->billing_group_id] ?? null) ?: $customer->nik) : null,
                        'npwp_number' => $invoice->npwp_number === null ? ($tax->tax_number ?? null ?: $customer->npwp) : null,
                        'tax_address' => $invoice->tax_address === null ? ($tax->tax_address ?? null ?: $customer->npwp_address) : null,
                    ], fn ($value) => $value !== null && $value !== '');

                    if ($values) {
                        DB::table('invoices')->where('id', $invoice->id)->update($values);
                    }
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['bill_to_name', 'nik_number'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
