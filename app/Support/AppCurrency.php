<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Mata uang sistem (jawaban klien #12, UAT 17-19 Nov 2026): diatur di Master Company, default
 * Rupiah, berlaku untuk semua customer. Hanya label/simbol yang berubah; nominal tidak dikonversi.
 *
 * Pajak (PPN, faktur, CoreTax) dan e-Materai tetap Rupiah karena kewajiban ke DJP selalu IDR.
 */
final class AppCurrency
{
    public const DEFAULT_CODE = 'IDR';

    private const CONTAINER_KEY = 'app.currency.code';

    private const OPTIONS_KEY = 'app.currency.options';

    /** Daftar bawaan bila Master Currency (tabel currencies) kosong. */
    private const BUILT_IN = [
        'IDR' => ['name' => 'Rupiah Indonesia', 'symbol' => 'Rp', 'word' => 'RUPIAH'],
        'MYR' => ['name' => 'Ringgit Malaysia', 'symbol' => 'RM', 'word' => 'RINGGIT'],
        'SGD' => ['name' => 'Dolar Singapura', 'symbol' => 'S$', 'word' => 'DOLAR SINGAPURA'],
        'USD' => ['name' => 'Dolar Amerika Serikat', 'symbol' => 'US$', 'word' => 'DOLAR AMERIKA SERIKAT'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'word' => 'EURO'],
    ];

    public static function code(): string
    {
        $app = app();
        if ($app->bound(self::CONTAINER_KEY)) {
            return $app->make(self::CONTAINER_KEY);
        }

        $code = self::DEFAULT_CODE;
        try {
            $stored = DB::table('companies')
                ->whereNull('deleted_at')
                ->whereNotNull('currency_code')
                ->orderBy('id')
                ->value('currency_code');
            if (is_string($stored) && trim($stored) !== '') {
                $code = strtoupper(trim($stored));
            }
        } catch (\Throwable) {
            // Tabel/kolom belum ada (sebelum migrate atau di test) -> default Rupiah.
        }

        $app->instance(self::CONTAINER_KEY, $code);

        return $code;
    }

    public static function symbol(): string
    {
        return self::options()[self::code()]['symbol'] ?? self::code();
    }

    /**
     * Nama mata uang untuk "terbilang" di printout (mis. "SERATUS RIBU  RUPIAH").
     */
    public static function word(): string
    {
        return self::options()[self::code()]['word'] ?? self::code();
    }

    /**
     * @return array<string, array{name: string, symbol: string, word: string}>
     */
    public static function options(): array
    {
        $app = app();
        if ($app->bound(self::OPTIONS_KEY)) {
            return $app->make(self::OPTIONS_KEY);
        }

        $options = self::BUILT_IN;

        try {
            $rows = DB::table('currencies')->where('is_active', true)->orderBy('code')->get(['code', 'name', 'symbol']);
            foreach ($rows as $row) {
                $code = strtoupper(trim((string) $row->code));
                if ($code === '') {
                    continue;
                }
                $name = (string) ($row->name ?: $code);
                $options[$code] = [
                    'name' => $name,
                    'symbol' => (string) ($row->symbol ?: $code),
                    'word' => self::BUILT_IN[$code]['word'] ?? mb_strtoupper($name),
                ];
            }
        } catch (\Throwable) {
            // Master Currency tak tersedia -> daftar bawaan saja.
        }

        $app->instance(self::OPTIONS_KEY, $options);

        return $options;
    }

    public static function forget(): void
    {
        app()->forgetInstance(self::CONTAINER_KEY);
        app()->forgetInstance(self::OPTIONS_KEY);
    }
}
