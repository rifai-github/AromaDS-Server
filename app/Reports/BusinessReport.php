<?php

namespace App\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Satu laporan di menu Report (spec Halaman Report 24 Nov 2026). Setiap laporan menampilkan
 * tabel dengan filter range tanggal + branch + pencarian, dan bisa di-export ke Excel dan PDF
 * lewat BusinessReportController. Baris = array asosiatif dengan kunci sesuai columns(), nilai
 * sudah siap tampil (string/angka).
 */
abstract class BusinessReport
{
    /** Slug URL dan akhiran nama permission (reports.{key}). */
    abstract public function key(): string;

    abstract public function title(): string;

    /** Penjelasan singkat fungsi laporan, tampil di atas tabel. */
    abstract public function description(): string;

    /** Tanggal apa yang disaring oleh filter range tanggal. */
    abstract public function dateLabel(): string;

    /** @return array<string, string> kunci kolom => judul kolom */
    abstract public function columns(): array;

    /** @return Collection<int, array<string, mixed>> */
    abstract public function rows(ReportFilters $filters): Collection;

    public function permission(): string
    {
        return 'reports.'.$this->key();
    }

    /** Default range: 30 hari ke belakang s/d hari ini. */
    public function defaultRange(): array
    {
        return [now()->subDays(30)->startOfDay(), now()->startOfDay()];
    }

    /**
     * Filter tambahan khusus laporan ini.
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public function extraFilters(): array
    {
        return [];
    }

    /** Kolom yang dirata kanan (angka). */
    public function numericColumns(): array
    {
        return [];
    }

    /** Baris yang perlu disorot (mis. job selesai tanpa invoice). */
    public function highlightRow(array $row): bool
    {
        return false;
    }

    protected function date(mixed $value, string $format = 'd/m/Y'): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '-';
        }
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'Sudah' : 'Belum';
    }
}
