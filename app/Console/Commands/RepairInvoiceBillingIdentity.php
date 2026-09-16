<?php

namespace App\Console\Commands;

use App\Models\Finance\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menyegarkan identitas penagihan invoice dari billing group-nya sendiri.
 *
 * invoices menyimpan npwp_number, tax_address, dan billing_address sebagai
 * stempel saat invoice dibuat, bukan sebagai nilai hidup. Jadi invoice yang
 * terlanjur dibuat sebelum dua perbaikan berikut tetap salah walaupun kodenya
 * sudah benar:
 *
 * - identitas pajak dulu di-resolve dari customer, padahal satu customer bisa
 *   punya beberapa billing group dengan NITKU berbeda (QA 15 Sep 2026 part 2:
 *   invoice Billing Group 1 tercetak dengan NITKU dan alamat pajak milik
 *   Billing Group 2);
 * - billing_address diisi dari billing_groups.pic_address, kolom yang tidak
 *   pernah punya input di form sehingga selalu NULL dan jatuh ke alamat
 *   customer.
 *
 * Tombol "Reload Tax" di layar invoice sudah melakukan hal yang sama, tetapi
 * hanya untuk invoice berstatus draft. Kedua invoice yang dilaporkan QA sudah
 * lewat draft, jadi tidak ada jalan sama sekali lewat UI.
 *
 * Yang sudah terbit faktur pajaknya TIDAK disentuh: identitas di faktur yang
 * sudah dilaporkan ke CoreTax tidak boleh berubah diam-diam.
 */
class RepairInvoiceBillingIdentity extends Command
{
    protected $signature = 'finance:repair-invoice-billing-identity
                            {--apply : Terapkan perubahan (default dry-run)}
                            {--invoice= : Batasi ke satu nomor invoice}
                            {--contract= : Batasi ke satu nomor kontrak}
                            {--include-faktur : Ikut sertakan invoice yang faktur pajaknya sudah terbit (JANGAN dipakai kecuali diminta)}';

    protected $description = 'Stempel ulang npwp_number/tax_address/billing_address invoice dari billing group-nya, untuk invoice yang dibuat sebelum perbaikan identitas per billing group';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->info('DRY RUN. Tidak ada perubahan database. Tambahkan --apply untuk menerapkan.');
        }

        $query = Invoice::query()
            ->whereNotNull('billing_group_id')
            ->with(['billingGroup', 'contract.customer']);

        if (! $this->option('include-faktur')) {
            $query->where(function ($q) {
                $q->whereNull('faktur_pajak')->orWhere('faktur_pajak', '');
            });
        } else {
            $this->warn('--include-faktur aktif: invoice yang fakturnya sudah terbit ikut disentuh.');
        }

        if ($number = $this->option('invoice')) {
            $query->where('invoice_number', $number);
        }

        if ($contract = $this->option('contract')) {
            $query->where('contract_number', $contract);
        }

        $invoices = $query->orderBy('invoice_number')->get();

        if ($invoices->isEmpty()) {
            $this->warn('Tidak ada invoice yang cocok dengan filternya.');

            return self::SUCCESS;
        }

        $changes = [];

        foreach ($invoices as $invoice) {
            $group = $invoice->billingGroup;

            if (! $group) {
                continue;
            }

            $target = [
                'npwp_number' => $group->tax_identity_number,
                'tax_number' => $group->tax_identity_number,
                'tax_address' => $group->tax_identity_address,
                'billing_address' => $group->pic_address ?: ($invoice->contract?->customer?->address ?: null),
            ];

            // Billing group yang kolomnya masih kosong tidak boleh menghapus
            // nilai yang sudah ada di invoice -- lebih baik dibiarkan apa adanya
            // daripada dikosongkan.
            $diff = [];
            foreach ($target as $column => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if ((string) $invoice->{$column} !== (string) $value) {
                    $diff[$column] = [$invoice->{$column}, $value];
                }
            }

            if ($diff) {
                $changes[] = [$invoice, $diff];
            }
        }

        $this->line('Invoice diperiksa: '.$invoices->count().', perlu diperbaiki: '.count($changes));
        $this->newLine();

        foreach ($changes as [$invoice, $diff]) {
            $this->line("  {$invoice->invoice_number}  (".($invoice->billingGroup->billing_group_name ?? 'billing group #'.$invoice->billing_group_id).')');
            foreach ($diff as $column => [$from, $to]) {
                $this->line(sprintf('     %-16s %s  ->  %s', $column, $this->show($from), $this->show($to)));
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Dry-run selesai. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as [$invoice, $diff]) {
                $invoice->forceFill(array_map(fn ($pair) => $pair[1], $diff))->save();
            }
        });

        $this->newLine();
        $this->info('Selesai. '.count($changes).' invoice diperbarui.');

        return self::SUCCESS;
    }

    private function show($value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '(kosong)' : $value;
    }
}
