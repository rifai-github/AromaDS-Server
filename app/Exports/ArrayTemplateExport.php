<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generic Excel export used to generate downloadable import templates:
 * a header row plus zero or more sample data rows.
 */
class ArrayTemplateExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(
        private array $headers,
        private array $rows = [],
    ) {}

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
