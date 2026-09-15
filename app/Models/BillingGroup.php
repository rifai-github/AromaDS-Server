<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;

class BillingGroup extends Model
{
    use AutoFilterable;

    /**
     * Nomor pajak milik billing group ini, bukan milik customer-nya.
     *
     * Satu customer bisa punya beberapa billing group dengan NPWP sama tetapi
     * NITKU berbeda (satu NITKU per cabang/gedung). Mengambil nomor pajak dari
     * customer membuat semua invoice-nya memakai identitas yang sama, sehingga
     * invoice Billing Group 1 tercetak dengan NITKU milik Billing Group 2.
     *
     * Kolom `npwp_number` adalah kolom warisan dan tidak lagi diisi form Billing
     * Group — yang diisi sekarang `npwp` + `nitku`. Keduanya tetap didukung.
     */
    public function getTaxIdentityNumberAttribute(): ?string
    {
        $legacy = trim((string) ($this->attributes['npwp_number'] ?? ''));
        if ($legacy !== '') {
            return $legacy;
        }

        $npwp = preg_replace('/\s+/', '', (string) ($this->attributes['npwp'] ?? ''));
        $nitku = preg_replace('/\s+/', '', (string) ($this->attributes['nitku'] ?? ''));

        if ($npwp === '' && $nitku === '') {
            return null;
        }

        return $npwp.$nitku;
    }

    /**
     * Alamat yang tercetak sebagai alamat pajak untuk billing group ini.
     */
    public function getTaxIdentityAddressAttribute(): ?string
    {
        $address = trim((string) ($this->attributes['npwp_address'] ?? ''));

        return $address !== '' ? $address : null;
    }
}
