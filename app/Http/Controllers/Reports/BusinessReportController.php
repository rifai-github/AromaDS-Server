<?php

namespace App\Http\Controllers\Reports;

use App\Exports\BusinessReportExport;
use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Branch;
use App\Reports\BusinessReport;
use App\Reports\ReportFilters;
use App\Reports\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Halaman menu Report (spec Halaman Report 24 Nov 2026): satu controller untuk semua laporan di
 * ReportRegistry. Izin per laporan dicek di route (middleware permission:reports.{key}).
 */
class BusinessReportController extends Controller
{
    /** PDF dibuat dompdf di memori; di atas ini user diminta mempersempit filter. */
    private const PDF_MAX_ROWS = 3000;

    public function show(Request $request, string $report)
    {
        $definition = $this->resolve($report);
        $filters = ReportFilters::fromRequest($request, $definition);
        $rows = $this->rowsFor($definition, $filters);

        $perPage = (int) $request->query('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
        $page = max(1, (int) $request->query('page', 1));

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('reports.business.show', [
            'report' => $definition,
            'filters' => $filters,
            'rows' => $paginator,
            'branches' => $this->branches(),
            'exportQuery' => $filters->toQuery(),
        ]);
    }

    public function export(Request $request, string $report, string $format)
    {
        $definition = $this->resolve($report);
        $filters = ReportFilters::fromRequest($request, $definition);
        $rows = $this->rowsFor($definition, $filters);
        $filename = Str::slug($definition->title()).'_'.$filters->dateFrom->format('Ymd').'-'.$filters->dateTo->format('Ymd');

        if ($format === 'xlsx') {
            $columns = $definition->columns();
            $data = $rows->map(fn (array $row) => array_map(fn ($key) => $row[$key] ?? '', array_keys($columns)))->all();

            return Excel::download(new BusinessReportExport(array_values($columns), $data), $filename.'.xlsx');
        }

        if ($rows->count() > self::PDF_MAX_ROWS) {
            return back()->with('error', 'Data terlalu banyak untuk PDF ('.number_format($rows->count(), 0, ',', '.')
                .' baris, maks. '.number_format(self::PDF_MAX_ROWS, 0, ',', '.').'). Persempit filter atau gunakan Export Excel.');
        }

        $branchName = $filters->branchId ? Branch::whereKey($filters->branchId)->value('name') : null;

        return Pdf::loadView('reports.business.pdf', [
            'report' => $definition,
            'filters' => $filters,
            'rows' => $rows,
            'branchName' => $branchName,
        ])->setPaper('a4', 'landscape')->download($filename.'.pdf');
    }

    private function resolve(string $key): BusinessReport
    {
        $definition = ReportRegistry::find($key);
        abort_unless($definition, 404);

        // Izin per laporan (reports.{key}) dengan aturan yang sama seperti middleware
        // permission: Admin/Management lolos, role lain lewat System Role.
        $response = app(CheckPermission::class)->handle(request(), fn () => response('ok'), $definition->permission());
        abort_if($response->getStatusCode() === 403, 403);

        return $definition;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rowsFor(BusinessReport $definition, ReportFilters $filters): Collection
    {
        $rows = $definition->rows($filters)->values();

        if ($filters->search !== null) {
            $needle = Str::lower($filters->search);
            $rows = $rows->filter(function (array $row) use ($needle) {
                foreach ($row as $key => $value) {
                    if (! str_starts_with((string) $key, '_') && is_scalar($value) && str_contains(Str::lower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        return $rows;
    }

    private function branches(): Collection
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
