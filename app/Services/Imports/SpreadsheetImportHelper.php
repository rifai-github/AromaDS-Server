<?php

namespace App\Services\Imports;

use App\Exports\ArrayTemplateExport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelReader;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared helper for the dashboard "Import Excel/CSV" feature across master-data
 * modules (Serial Numbers, Master Rentals, Customers).
 *
 * Mirrors the existing import flow in MasterProductImportController but adds
 * .xlsx support (via maatwebsite/excel) and reusable header-keyed parsing +
 * downloadable templates, so each module's import controller stays thin.
 */
class SpreadsheetImportHelper
{
    /** Max upload size in KB (10 MB), matching the existing import controllers. */
    public const MAX_FILE_KB = 10240;

    /**
     * Validation rules for the uploaded file. Accepts CSV and Excel.
     */
    public static function validationRules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:'.self::MAX_FILE_KB,
        ];
    }

    /**
     * Parse an uploaded CSV/XLSX file into header-keyed associative rows.
     *
     * Row 1 is treated as the header. Each returned element is
     * [headerName => cellValue]. Rows whose column count does not match the
     * header are skipped (same tolerance as the legacy CSV parser).
     *
     * @return array<int, array<string, string>>
     */
    public static function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $matrix = Excel::toArray([], $file->getRealPath(), null, self::readerTypeForExtension($extension));
            $matrix = $matrix[0] ?? [];
        } else {
            $matrix = self::readCsv($file->getRealPath());
        }

        return self::matrixToHeaderedRows($matrix);
    }

    private static function readerTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'xls' => ExcelReader::XLS,
            default => ExcelReader::XLSX,
        };
    }

    /**
     * Read a CSV file into a raw 2D array (no header handling).
     *
     * @return array<int, array<int, string>>
     */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Gagal membuka file CSV.');
        }

        $matrix = [];
        while (($row = fgetcsv($handle)) !== false) {
            $matrix[] = $row;
        }
        fclose($handle);

        return $matrix;
    }

    /**
     * Turn a raw 2D matrix into header-keyed rows, trimming header names and
     * dropping a UTF-8 BOM on the first header cell if present.
     *
     * @param  array<int, array<int, mixed>>  $matrix
     * @return array<int, array<string, string>>
     */
    private static function matrixToHeaderedRows(array $matrix): array
    {
        if (empty($matrix)) {
            throw new \RuntimeException('File kosong atau tidak terbaca.');
        }

        $header = array_map(static function ($h) {
            return self::normalizeHeaderKey($h);
        }, $matrix[0]);

        $columnCount = count($header);
        $rows = [];

        foreach (array_slice($matrix, 1) as $raw) {
            // Pad short rows / trim long rows so array_combine never fails on
            // trailing-empty-cell quirks from Excel exports.
            $raw = array_map(static fn ($v) => (string) $v, $raw);
            if (count($raw) < $columnCount) {
                $raw = array_pad($raw, $columnCount, '');
            } elseif (count($raw) > $columnCount) {
                $raw = array_slice($raw, 0, $columnCount);
            }

            $combined = array_combine($header, $raw);

            // Skip fully-empty rows (common trailing rows in spreadsheets).
            if (count(array_filter($combined, static fn ($v) => trim($v) !== '')) === 0) {
                continue;
            }

            $rows[] = $combined;
        }

        return $rows;
    }

    private static function normalizeHeaderKey(mixed $header): string
    {
        $key = trim((string) $header);
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?? $key;
        $key = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $key) ?? $key;
        $key = mb_strtolower(trim($key));
        $key = preg_replace('/[\s\-]+/', '_', $key) ?? $key;

        return trim($key, '_');
    }

    /**
     * Build a downloadable template containing the header row plus sample rows.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $sampleRows
     * @param  string  $format  'xlsx' (default) or 'csv'
     */
    public static function downloadTemplate(array $headers, array $sampleRows, string $baseFilename, string $format = 'xlsx')
    {
        $format = strtolower($format) === 'csv' ? 'csv' : 'xlsx';

        if ($format === 'csv') {
            return self::streamCsvTemplate($headers, $sampleRows, $baseFilename.'.csv');
        }

        $export = new ArrayTemplateExport($headers, $sampleRows);

        return Excel::download($export, $baseFilename.'.xlsx');
    }

    /**
     * Stream a CSV template without writing a temp file.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $sampleRows
     */
    private static function streamCsvTemplate(array $headers, array $sampleRows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $sampleRows) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
