<?php

namespace App\Http\Traits;

/**
 * Identitas pajak milik satu billing group, bukan milik customer-nya.
 *
 * Ada DUA model billing group di repo ini: App\Models\BillingGroup dan
 * App\Models\Finance\BillingGroup. Keduanya memetakan tabel yang sama. Yang
 * dipakai jalur invoice adalah yang di namespace Finance — App\Models\Finance\Invoice
 * menyebut `BillingGroup::class` tanpa `use`, jadi resolusinya jatuh ke
 * App\Models\Finance\BillingGroup, dan InvoiceGenerationService meng-import
 * kelas yang sama.
 *
 * Accessor ini semula hanya dipasang di App\Models\BillingGroup, sehingga
 * $invoice->billingGroup->tax_identity_number SELALU null di runtime dan
 * invoice diam-diam kembali memakai identitas customer -- persis bug yang
 * dilaporkan QA dan yang dikira sudah beres. Unit test-nya lolos karena
 * meng-instantiate kelas yang salah. Karena itu aturannya tinggal di trait:
 * satu definisi, dipakai kedua model.
 */
trait HasBillingTaxIdentity
{
    /**
     * Nomor pajak billing group ini.
     *
     * Satu customer bisa punya beberapa billing group dengan NPWP sama tetapi
     * NITKU berbeda (satu NITKU per cabang/gedung). Kolom `npwp_number` adalah
     * kolom warisan dan tidak lagi diisi form Billing Group -- yang diisi
     * sekarang `npwp` + `nitku`. Keduanya tetap didukung.
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
