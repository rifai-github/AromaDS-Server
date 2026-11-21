<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * UAT 19 Nov 2026 (Database Mapping #5): data pribadi employee dari MsEmployee /
 * MsEmployeeDt / MsEmpStatus dipetakan ke kolom users (gender, marital_status, religion,
 * identity_type, blood_type, rhesus, employee_status) sebagai TEKS option_name Master
 * Option - halaman My Profile membaca option_name, bukan id.
 *
 * Nama kolom sumber BELUM pernah dikonfirmasi dari skema Catalyst (repo tidak punya
 * dump-nya), jadi kolom dibaca secara defensif: kandidat di SOURCE_COLUMNS dicoba
 * berurutan, case-insensitive, dan kolom yang tidak ada di baris dilewati. Daftar yang
 * sama dipakai scripts/export_catalyst_users.ps1 - ubah keduanya bersamaan.
 *
 * Nilai yang tidak bisa dicocokkan ke detail Master Option dikembalikan null (tidak
 * ditulis), supaya dropdown My Profile tidak terisi teks yang tidak ada pilihannya.
 */
class CatalystEmployeeProfileMapper
{
    public const FIELD_OPTIONS = [
        'gender' => 'Gender',
        'marital_status' => 'Marital Status',
        'religion' => 'Religion',
        'identity_type' => 'Identity Type',
        'blood_type' => 'Blood Type',
        'rhesus' => 'Rhesus',
        'employee_status' => 'Employee Status',
    ];

    /** Kandidat nama kolom Catalyst per field (asumsi, lihat docblock kelas). */
    public const SOURCE_COLUMNS = [
        'gender' => ['Sex', 'Gender', 'JenisKelamin', 'EmpSex'],
        'marital_status' => ['MaritalStatus', 'Marital', 'MaritalStat', 'StatusMarital', 'StatusNikah'],
        'religion' => ['Religion', 'Agama'],
        'identity_type' => ['IDType', 'IDCardType', 'IdentityType'],
        'blood_type' => ['BloodType', 'Blood', 'BloodGroup', 'GolDarah'],
        'rhesus' => ['Rhesus', 'BloodRhesus', 'RhesusType'],
        'employee_status' => ['EmpStatusName', 'EmpStatus', 'EmployeeStatus', 'StatusEmp', 'EmpStatusCode'],
    ];

    /** Kolom kode & nama di tabel MsEmpStatus (asumsi). */
    public const EMP_STATUS_CODE_COLUMNS = ['EmpStatus', 'EmpStatusCode', 'StatusCode', 'Code'];

    public const EMP_STATUS_NAME_COLUMNS = ['EmpStatusName', 'StatusName', 'EmpStatusDesc', 'Description', 'Name'];

    /** Sinonim (sudah dinormalisasi) -> option_name bawaan seed Master Option. */
    private const SYNONYMS = [
        'gender' => [
            'l' => 'Male', 'lk' => 'Male', 'm' => 'Male', 'male' => 'Male', 'pria' => 'Male', 'laki' => 'Male', 'laki laki' => 'Male',
            'p' => 'Female', 'pr' => 'Female', 'f' => 'Female', 'w' => 'Female', 'female' => 'Female', 'perempuan' => 'Female', 'wanita' => 'Female',
        ],
        'marital_status' => [
            'k' => 'Married', 'kawin' => 'Married', 'menikah' => 'Married', 'sudah menikah' => 'Married', 'nikah' => 'Married', 'married' => 'Married', 'm' => 'Married',
            'tk' => 'Single', 'belum kawin' => 'Single', 'tidak kawin' => 'Single', 'belum menikah' => 'Single', 'lajang' => 'Single', 'single' => 'Single', 's' => 'Single',
            'cerai' => 'Divorced', 'cerai hidup' => 'Divorced', 'divorced' => 'Divorced', 'd' => 'Divorced',
            'cerai mati' => 'Widowed', 'janda' => 'Widowed', 'duda' => 'Widowed', 'janda duda' => 'Widowed', 'widowed' => 'Widowed', 'w' => 'Widowed',
        ],
        'religion' => [
            'islam' => 'Islam', 'muslim' => 'Islam', 'moslem' => 'Islam',
            'kristen' => 'Christian', 'protestan' => 'Christian', 'kristen protestan' => 'Christian', 'christian' => 'Christian', 'protestant' => 'Christian',
            'katolik' => 'Catholic', 'katholik' => 'Catholic', 'kristen katolik' => 'Catholic', 'catholic' => 'Catholic',
            'hindu' => 'Hindu',
            'budha' => 'Buddhist', 'buddha' => 'Buddhist', 'buddhist' => 'Buddhist',
            'konghucu' => 'Other', 'khonghucu' => 'Other', 'kong hu cu' => 'Other', 'lainnya' => 'Other', 'lain lain' => 'Other', 'other' => 'Other',
        ],
        'identity_type' => [
            'ktp' => 'KTP', 'e ktp' => 'KTP', 'ektp' => 'KTP', 'kartu tanda penduduk' => 'KTP',
            'sim' => 'SIM',
            'passport' => 'Passport', 'paspor' => 'Passport', 'pasport' => 'Passport',
        ],
        'blood_type' => [
            'a' => 'A', 'b' => 'B', 'ab' => 'AB', 'o' => 'O', '0' => 'O',
        ],
        'rhesus' => [
            'positive' => 'Positive', 'positif' => 'Positive', 'pos' => 'Positive', 'rh positif' => 'Positive', 'rh positive' => 'Positive',
            'negative' => 'Negative', 'negatif' => 'Negative', 'neg' => 'Negative', 'rh negatif' => 'Negative', 'rh negative' => 'Negative',
        ],
        'employee_status' => [
            'tetap' => 'Permanent', 'karyawan tetap' => 'Permanent', 'permanent' => 'Permanent', 'pkwtt' => 'Permanent',
            'kontrak' => 'Contract', 'karyawan kontrak' => 'Contract', 'contract' => 'Contract', 'pkwt' => 'Contract',
            'percobaan' => 'Probation', 'masa percobaan' => 'Probation', 'probation' => 'Probation',
            'magang' => 'Intern', 'intern' => 'Intern', 'internship' => 'Intern',
        ],
    ];

    /** @var array<string, array<string, string>>|null field -> [normalized key => option_name] */
    private ?array $optionLookup;

    /**
     * @param  array<string, list<array{option_name: string, label?: string|null, option_description?: string|null}>>|null  $options
     *         Opsional untuk test; null = baca option_details dari DB target.
     */
    public function __construct(?array $options = null)
    {
        $this->optionLookup = $options === null ? null : $this->buildLookup($options);
    }

    /**
     * @param  array<string, mixed>  $row  Baris MsEmployee (+ MsEmployeeDt), kunci case-insensitive.
     * @param  array<string, string>  $empStatusNames  Kode MsEmpStatus (huruf kecil) -> nama.
     * @return array<string, string>  Hanya field yang berhasil dipetakan.
     */
    public function map(array $row, array $empStatusNames = []): array
    {
        $row = array_change_key_case($row, CASE_LOWER);
        $result = [];

        foreach (array_keys(self::FIELD_OPTIONS) as $field) {
            $raw = $this->firstValue($row, self::SOURCE_COLUMNS[$field]);
            if ($raw === null) {
                continue;
            }

            if ($field === 'employee_status') {
                $raw = $empStatusNames[Str::lower($raw)] ?? $raw;
            }

            if ($field === 'blood_type' && preg_match('/^\s*(AB|A|B|O|0)\s*(?:RH)?\s*([+-])\s*$/i', $raw, $match)) {
                // "A+" / "O-": golongan darah dan rhesus dalam satu kolom.
                $raw = $match[1];
                if (!isset($result['rhesus']) && $this->firstValue($row, self::SOURCE_COLUMNS['rhesus']) === null) {
                    $rhesus = $this->resolve('rhesus', $match[2] === '+' ? 'positive' : 'negative');
                    if ($rhesus !== null) {
                        $result['rhesus'] = $rhesus;
                    }
                }
            }

            if ($field === 'rhesus' && in_array(trim($raw), ['+', '-', 'Rh+', 'Rh-', 'RH+', 'RH-'], true)) {
                $raw = str_ends_with(trim($raw), '+') ? 'positive' : 'negative';
            }

            $value = $this->resolve($field, $raw);
            if ($value !== null) {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * Bangun lookup kode -> nama dari baris MsEmpStatus (kolom dibaca defensif).
     *
     * @param  iterable<array<string, mixed>|object>  $rows
     * @return array<string, string>
     */
    public static function empStatusLookup(iterable $rows): array
    {
        $lookup = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array) $row, CASE_LOWER);
            $code = self::firstValueStatic($row, self::EMP_STATUS_CODE_COLUMNS);
            $name = self::firstValueStatic($row, self::EMP_STATUS_NAME_COLUMNS);
            if ($code !== null && $name !== null) {
                $lookup[Str::lower($code)] = $name;
            }
        }

        return $lookup;
    }

    private function resolve(string $field, string $raw): ?string
    {
        $lookup = $this->lookup()[$field] ?? [];
        $key = $this->normalize($raw);

        if ($key === null) {
            return null;
        }

        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        // Status nikah gaya PTKP: K/0, TK/1, K3 ...
        if ($field === 'marital_status' && preg_match('/^(tk|k)\s*\d*$/', $key, $match)) {
            $key = $match[1];
        }

        $canonical = self::SYNONYMS[$field][$key] ?? null;
        if ($canonical === null) {
            return null;
        }

        if ($lookup === []) {
            // Master option kosong / tidak ada: pakai nama bawaan seed.
            return $canonical;
        }

        return $lookup[$this->normalize($canonical)] ?? null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function lookup(): array
    {
        if ($this->optionLookup !== null) {
            return $this->optionLookup;
        }

        $options = [];
        if (Schema::hasTable('master_options') && Schema::hasTable('option_details')) {
            foreach (self::FIELD_OPTIONS as $field => $masterName) {
                $query = DB::table('option_details')
                    ->join('master_options', 'master_options.id', '=', 'option_details.master_option_id')
                    ->whereRaw('LOWER(master_options.name) = ?', [Str::lower($masterName)])
                    ->orderBy('option_details.id');

                if (Schema::hasColumn('option_details', 'deleted_at')) {
                    $query->whereNull('option_details.deleted_at');
                }
                if (Schema::hasColumn('master_options', 'deleted_at')) {
                    $query->whereNull('master_options.deleted_at');
                }

                $options[$field] = $query
                    ->get(['option_details.option_name', 'option_details.label', 'option_details.option_description'])
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }
        }

        return $this->optionLookup = $this->buildLookup($options);
    }

    private function buildLookup(array $options): array
    {
        $lookup = [];
        foreach ($options as $field => $rows) {
            $lookup[$field] = [];
            foreach ($rows as $row) {
                $name = trim((string) ($row['option_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                // option_name menang atas label/deskripsi kalau bentrok.
                foreach (['option_name', 'label', 'option_description'] as $column) {
                    $key = $this->normalize($row[$column] ?? null);
                    if ($key !== null && ($column === 'option_name' || !isset($lookup[$field][$key]))) {
                        $lookup[$field][$key] = $name;
                    }
                }
            }
        }

        return $lookup;
    }

    private function normalize($value): ?string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));

        return $value === '' || $value === 'null' ? null : $value;
    }

    private function firstValue(array $row, array $columns): ?string
    {
        return self::firstValueStatic($row, $columns);
    }

    private static function firstValueStatic(array $row, array $columns): ?string
    {
        foreach ($columns as $column) {
            $value = $row[Str::lower($column)] ?? null;
            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '' && Str::lower($value) !== 'null') {
                return $value;
            }
        }

        return null;
    }
}
