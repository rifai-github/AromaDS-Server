<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Str;

/**
 * UAT 19 Nov 2026 (Database Mapping, master option): MsCustContact.ContactType berisi
 * Mr / Mrs / Miss. Klien minta setelah diimport Mr jadi "Bapak", Mrs dan Miss jadi "Ibu",
 * dan master option "Salutation" wajib punya kedua pilihan itu.
 *
 * Dipakai bersama oleh step customer_contacts (CatalystMasterDataImporter), import CSV
 * catalyst:import-customer-completeness-export, dan catalyst:normalize-contact-salutations,
 * supaya ketiganya tidak pernah beda aturan.
 */
class CatalystSalutation
{
    public const MASTER_OPTION = 'Salutation';

    public const BAPAK = 'Bapak';

    public const IBU = 'Ibu';

    /** Pilihan yang dijamin ada di master option Salutation. */
    public const DETAILS = [self::BAPAK, self::IBU];

    /** Nilai mentah (huruf kecil, tanpa titik) -> sapaan baru. */
    private const MAP = [
        'mr' => self::BAPAK,
        'mrs' => self::IBU,
        'miss' => self::IBU,
        'ms' => self::IBU,
    ];

    /**
     * Nilai mentah yang dikenali, beserta varian titik di belakang. Dipakai command
     * normalisasi untuk menyaring baris yang perlu ditulis ulang.
     *
     * @return list<string>
     */
    public static function legacyValues(): array
    {
        $values = [];
        foreach (array_keys(self::MAP) as $key) {
            $values[] = $key;
            $values[] = $key.'.';
        }

        return $values;
    }

    /**
     * Mr/Mr./MR -> Bapak, Mrs/Miss/Ms (dengan/tanpa titik) -> Ibu. Nilai lain dikembalikan
     * apa adanya (sudah di-trim) supaya sapaan seperti "dr." tidak hilang.
     */
    public static function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || in_array(Str::lower($value), ['null', '\\n'], true)) {
            return null;
        }

        $key = Str::lower(rtrim($value, '. '));

        return self::MAP[$key] ?? $value;
    }
}
