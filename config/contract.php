<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Suspend otomatis kontrak yang invoice-nya lewat grace period
    |--------------------------------------------------------------------------
    |
    | MOM 19 Nov 2026 p3. Bila true, scheduler menjalankan
    | `contracts:suspend-past-grace --apply` setiap hari pukul 02:00. Default false: jalankan
    | dulu tanpa --apply untuk melihat daftarnya, karena invoice lama yang belum ditandai lunas
    | akan ikut men-suspend kontraknya.
    |
    */

    'grace_auto_suspend' => (bool) env('GRACE_AUTO_SUSPEND', false),

    /*
    |--------------------------------------------------------------------------
    | Aturan grace period (jawaban klien 24 Nov 2026)
    |--------------------------------------------------------------------------
    |
    | Grace dihitung dari tanggal invoice terbit. Customer yang grace_period_days-nya kosong/0
    | memakai grace_default_days. Suspend otomatis hanya untuk invoice yang terbit mulai
    | grace_auto_suspend_from (kosongkan env untuk tanpa batas).
    |
    */

    'grace_default_days' => (int) env('GRACE_DEFAULT_DAYS', 60),

    'grace_auto_suspend_from' => env('GRACE_AUTO_SUSPEND_FROM', '2027-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Masa berlaku link tanda tangan digital kontrak (hari)
    |--------------------------------------------------------------------------
    |
    | MOM 19 Nov 2026 p4 no 8. Link publik untuk penandatangan Pink & customer. Setelah lewat,
    | admin membuat link baru (selama belum ada yang tanda tangan).
    |
    */

    'signature_link_days' => (int) env('CONTRACT_SIGNATURE_LINK_DAYS', 14),

];
