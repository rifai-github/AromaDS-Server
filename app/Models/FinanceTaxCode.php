<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceTaxCode extends Model
{
    public const PPN_STATUS_OPTIONS = [
        'PPN dapat di kreditkan',
        "PPN dipungut oleh pembeli\n( PPN dipungut oleh pihak pemungut, bukan disetorkan oleh penjual )",
        "PPN Tidak Dipungut (pembeli tidak membayar PPN, dan penjual tidak menyetorkannya) atau dibebaskan\nIni khusus untuk transaksi ke kawasan tertentu (Berikat, Bebas, Ekonomi Khusus)",
        "PPN Tidak Dipungut atau dibebaskan\nDigunakan khusus untuk transaksi yang dibebaskan dari pengenaan PPN\npembeli tetap wajib melaporkannya dalam SPT Masa PPN, tetapi statusnya harus diubah menjadi \"tidak dikreditkan\"",
    ];

    public const PRINT_STATUS_OPTIONS = [
        'Tercetak',
        'Tercetak (nol)',
    ];

    public const CUSTOMER_STATUS_OPTIONS = [
        'Bayar & setor oleh penjual',
        'Bayar & setor oleh customer',
        'customer dan penjual tidak dapat mengkreditkan',
    ];

    protected $fillable = [
        'code',
        'description',
        'ppn_status',
        'invoice_status',
        'faktur_pajak_status',
        'customer_status',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function isCollectedBySeller(): bool
    {
        return $this->customer_status === 'Bayar & setor oleh penjual';
    }

    public function isCollectedByCustomer(): bool
    {
        return $this->customer_status === 'Bayar & setor oleh customer';
    }

    public function hasZeroTaxPrint(): bool
    {
        return $this->invoice_status === 'Tercetak (nol)'
            || $this->faktur_pajak_status === 'Tercetak (nol)';
    }

    public function appliesPpnToInvoice(): bool
    {
        return $this->isCollectedBySeller() && !$this->hasZeroTaxPrint();
    }

    public function printModeLabel(): string
    {
        return $this->hasZeroTaxPrint() ? 'zero' : 'normal';
    }
}
