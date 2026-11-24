<?php

namespace App\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filter umum semua laporan menu Report (spec 24 Nov 2026): range tanggal wajib, branch, dan
 * pencarian teks. Laporan boleh punya filter tambahan (extra) sendiri.
 */
class ReportFilters
{
    /**
     * @param  array<string, string|null>  $extra
     */
    public function __construct(
        public readonly Carbon $dateFrom,
        public readonly Carbon $dateTo,
        public readonly ?int $branchId = null,
        public readonly ?string $search = null,
        public readonly array $extra = [],
    ) {}

    public static function fromRequest(Request $request, BusinessReport $report): self
    {
        [$defaultFrom, $defaultTo] = $report->defaultRange();

        $from = self::parseDate($request->query('date_from')) ?? $defaultFrom->copy();
        $to = self::parseDate($request->query('date_to')) ?? $defaultTo->copy();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $extra = [];
        foreach (array_keys($report->extraFilters()) as $key) {
            $value = $request->query($key);
            $extra[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        $branchId = $request->query('branch_id');
        $search = trim((string) $request->query('search', ''));

        return new self(
            $from->startOfDay(),
            $to->endOfDay(),
            is_numeric($branchId) ? (int) $branchId : null,
            $search !== '' ? $search : null,
            $extra,
        );
    }

    public function extra(string $key): ?string
    {
        return $this->extra[$key] ?? null;
    }

    /** @return array<string, string> Query string untuk link export dengan filter yang sama. */
    public function toQuery(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom->toDateString(),
            'date_to' => $this->dateTo->toDateString(),
            'branch_id' => $this->branchId ? (string) $this->branchId : null,
            'search' => $this->search,
        ] + $this->extra, fn ($value) => $value !== null && $value !== '');
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
