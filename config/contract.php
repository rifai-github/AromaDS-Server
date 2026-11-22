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

];
