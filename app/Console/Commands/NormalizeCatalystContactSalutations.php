<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterOptionWriter;
use App\Services\Imports\Catalyst\CatalystSalutation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 19 Nov 2026 (Database Mapping, master option Salutation): kontak hasil import Catalyst
 * lama masih menyimpan ContactType mentah (Mr / Mrs / Miss). Klien minta Mr -> "Bapak",
 * Mrs / Miss -> "Ibu". Import baru sudah menormalisasi sendiri (CatalystSalutation); command
 * ini untuk merapikan data yang SUDAH ada.
 *
 * Default dry-run. Idempoten: baris yang sudah Bapak/Ibu tidak tersentuh, jadi aman diulang.
 */
class NormalizeCatalystContactSalutations extends Command
{
    protected $signature = 'catalyst:normalize-contact-salutations
                            {--apply : Tulis perubahan ke customer_contacts dan master option Salutation}';

    protected $description = 'Rewrite customer_contacts.salutation Mr/Mrs/Miss/Ms (with/without dot) to Bapak/Ibu and ensure Salutation master option has both';

    public function handle(): int
    {
        if (!Schema::hasTable('customer_contacts') || !Schema::hasColumn('customer_contacts', 'salutation')) {
            $this->error('Tabel customer_contacts / kolom salutation tidak ada.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info('Normalisasi salutation kontak customer - mode '.($apply ? 'apply' : 'dry-run').'.');

        $writer = new CatalystMasterOptionWriter();
        $optionRows = [];
        foreach (CatalystSalutation::DETAILS as $detail) {
            $result = $writer->upsert(CatalystSalutation::MASTER_OPTION, $detail, $apply);
            $optionRows[] = [$detail, match ($result['action']) {
                'inserted' => $apply ? 'ditambahkan' : 'akan ditambahkan',
                'exists' => 'sudah ada',
                'missing_master' => 'master option Salutation TIDAK ADA',
                default => $result['action'],
            }];
        }
        $this->table(['Detail Salutation', 'Status'], $optionRows);

        $legacy = CatalystSalutation::legacyValues();
        $placeholders = implode(',', array_fill(0, count($legacy), '?'));

        $counts = DB::table('customer_contacts')
            ->whereNotNull('salutation')
            ->whereRaw("LOWER(TRIM(salutation)) IN ($placeholders)", $legacy)
            ->selectRaw('TRIM(salutation) as salutation, COUNT(*) as total')
            ->groupByRaw('TRIM(salutation)')
            ->orderByRaw('TRIM(salutation)')
            ->get();

        $rows = [];
        $total = 0;
        foreach ($counts as $row) {
            $rows[] = [$row->salutation, CatalystSalutation::normalize($row->salutation), (int) $row->total];
            $total += (int) $row->total;
        }

        if ($rows === []) {
            $this->info('Tidak ada salutation Mr/Mrs/Miss/Ms yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->table(['Nilai lama', 'Nilai baru', 'Jumlah kontak'], $rows);

        if (!$apply) {
            $this->warn("Dry-run: {$total} kontak akan diubah. Jalankan ulang dengan --apply untuk menyimpan.");

            return self::SUCCESS;
        }

        $updated = 0;
        DB::transaction(function () use (&$updated) {
            $groups = [
                CatalystSalutation::BAPAK => ['mr', 'mr.'],
                CatalystSalutation::IBU => ['mrs', 'mrs.', 'miss', 'miss.', 'ms', 'ms.'],
            ];

            foreach ($groups as $target => $values) {
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $payload = ['salutation' => $target];
                if (Schema::hasColumn('customer_contacts', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                $updated += DB::table('customer_contacts')
                    ->whereNotNull('salutation')
                    ->whereRaw("LOWER(TRIM(salutation)) IN ($placeholders)", $values)
                    ->update($payload);
            }
        });

        $this->info("Selesai: {$updated} kontak diubah.");

        return self::SUCCESS;
    }
}
