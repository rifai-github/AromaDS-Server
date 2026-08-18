<?php

namespace App\Services\Finance;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;

/**
 * Reads the fields out of a CoreTax "Output Tax Invoice" PDF — the actual
 * signed faktur pajak DJP hands back for one invoice, as opposed to the
 * batched CSV/XLSX result file the export flow produces.
 *
 * The labels this matches against ("Kode dan Nomor Seri Faktur Pajak", "NPWP",
 * "Dasar Pengenaan Pajak", "Jumlah PPN") come from DJP's fixed, government
 * mandated Faktur Pajak layout, not from CoreTax specifically, so they hold
 * across companies and tax periods. Only the faktur number and the
 * "(Referensi: ...)" line — the invoice number WE wrote into the export that
 * CoreTax echoes back verbatim — are load-bearing; everything else is
 * best-effort supplementary detail that degrades to null rather than failing
 * the whole file.
 */
class CoreTaxFakturPdfParser
{
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * @return array{faktur_number: ?string, reference: ?string, seller_npwp: ?string,
     *   buyer_npwp: ?string, faktur_date: ?Carbon, dpp: ?float, ppn: ?float}
     */
    public function parse(string $fullPath): array
    {
        $text = (new Parser)->parseFile($fullPath)->getText();

        preg_match_all('/NPWP\s*:\s*(\d+)/u', $text, $npwpMatches);
        $npwps = $npwpMatches[1] ?? [];

        return [
            'faktur_number' => $this->find('/Kode dan Nomor Seri Faktur Pajak\s*:?\s*(\d+)/u', $text),
            'reference' => $this->find('/\(Referensi\s*:\s*([^)]+)\)/u', $text),
            'seller_npwp' => $npwps[0] ?? null,
            'buyer_npwp' => $npwps[1] ?? null,
            'faktur_date' => $this->findDate($text),
            'dpp' => $this->findAmount('/Dasar Pengenaan Pajak\s+([\d.,]+)/u', $text),
            'ppn' => $this->findAmount('/Jumlah PPN[^\n]*\s+([\d.,]+)/u', $text),
        ];
    }

    private function find(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) ? trim($m[1]) : null;
    }

    /**
     * DJP prints the issue date in Indonesian ("12 Agustus 2026") right before
     * the electronic-signature notice, so we can't rely on PHP's/Carbon's
     * locale-agnostic date parsing — the month name has to be mapped by hand.
     */
    private function findDate(string $text): ?Carbon
    {
        // A bare \w+ for the month name is too loose here: it also matches
        // numeric noise inside the item pricing table, producing a false date.
        // Anchoring on the actual month names rules that out.
        $months = implode('|', array_keys(self::MONTHS));
        $pattern = '/,\s*(\d{1,2})\s+('.$months.')\s+(\d{4})/iu';

        if (! preg_match($pattern, $text, $m)) {
            return null;
        }

        $month = self::MONTHS[strtolower($m[2])] ?? null;

        if (! $month) {
            return null;
        }

        try {
            return Carbon::create((int) $m[3], $month, (int) $m[1]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function findAmount(string $pattern, string $text): ?float
    {
        $raw = $this->find($pattern, $text);

        if ($raw === null) {
            return null;
        }

        // Indonesian number format: "." thousands separator, "," decimal separator.
        $normalized = str_replace(['.', ','], ['', '.'], $raw);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
