<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Klien 25 Nov 2026: Frequency di Master Rental tampil 1x untuk komponen yang di Excel Master
 * Product Pak Hengky tertulis 0x (hanya saat install, mis. Difusser). Penyebab: generator
 * aroma_fresh_bootstrap.sql (24 Agu) menulis kolom xFS = 0 sebagai
 * rental_details.service_frequency_multiplier = 1 di 435 baris, sementara pasangannya di
 * rental_components.replacement_frequency_months tetap 0 (nilai Excel). Baris BOM dan
 * komponennya dibuat berpasangan dengan id yang sama.
 *
 * Perintah ini mengembalikan multiplier ke 0 untuk baris yang komponen pasangannya (id dan
 * master rental sama) bernilai 0 dan multiplier-nya masih 1. Baris yang sudah diedit ke nilai
 * lain, atau tanpa pasangan (rental/komponen yang dibuat belakangan), tidak disentuh.
 */
class RestoreInstallOnlyRentalFrequency extends Command
{
    protected $signature = 'master-rentals:restore-install-only-frequency
        {--rental= : Hanya satu rental_code}
        {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Kembalikan Frequency 0x (hanya install) yang tertulis 1x akibat seed bootstrap (dry-run default)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $rows = DB::table('rental_details as d')
            ->join('rental_components as c', function ($join) {
                $join->on('c.id', '=', 'd.id')->on('c.master_rental_id', '=', 'd.master_rental_id');
            })
            ->join('master_rentals as r', 'r.id', '=', 'd.master_rental_id')
            ->leftJoin('product_categories as pc', 'pc.id', '=', 'd.product_category_id')
            ->whereNull('d.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.replacement_frequency_months', 0)
            ->where('d.service_frequency_multiplier', 1)
            ->when($this->option('rental'), fn ($query, $code) => $query->where('r.rental_code', trim($code)))
            ->orderBy('r.rental_code')
            ->orderBy('d.id')
            ->get([
                'd.id', 'r.rental_code', 'r.rental_name', 'c.component_name', 'c.description',
                'pc.name as category', 'pc.is_unit',
            ]);

        $units = $rows->where('is_unit', 1)->count();
        $this->info(($apply ? 'APPLY' : 'DRY-RUN').": {$rows->count()} baris BOM di "
            .$rows->pluck('rental_code')->unique()->count()." rental: Frequency 1x -> 0x "
            ."({$units} unit, ".($rows->count() - $units).' non-unit).');

        $this->table(
            ['Rental', 'Material Type', 'Material', 'Unit?'],
            $rows->take(25)->map(fn ($row) => [
                $row->rental_code,
                $row->component_name,
                $row->category ?? preg_replace('/^Material:\s*/', '', (string) $row->description),
                $row->is_unit ? 'ya' : 'tidak',
            ])->all()
        );
        if ($rows->count() > 25) {
            $this->line('... dan '.($rows->count() - 25).' baris lain.');
        }

        if (! $apply) {
            if ($rows->isNotEmpty()) {
                $this->comment('Tidak ada yang ditulis. Jalankan ulang dengan --apply untuk menerapkan.');
            }

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($rows->pluck('id')->chunk(500) as $ids) {
            $updated += DB::table('rental_details')
                ->whereIn('id', $ids->all())
                ->where('service_frequency_multiplier', 1)
                ->update(['service_frequency_multiplier' => 0, 'updated_at' => now()]);
        }

        $this->info("Selesai: {$updated} baris diubah ke 0x.");

        return self::SUCCESS;
    }
}
