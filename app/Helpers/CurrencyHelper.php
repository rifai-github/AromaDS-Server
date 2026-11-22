<?php

use App\Support\AppCurrency;

if (! function_exists('currency_symbol')) {
    /**
     * Simbol mata uang sistem dari Master Company (default "Rp").
     */
    function currency_symbol(): string
    {
        return AppCurrency::symbol();
    }
}

if (! function_exists('currency_code')) {
    /**
     * Kode ISO mata uang sistem dari Master Company (default "IDR").
     */
    function currency_code(): string
    {
        return AppCurrency::code();
    }
}
