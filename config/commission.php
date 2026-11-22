<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notifikasi pencairan komisi ke Finance
    |--------------------------------------------------------------------------
    |
    | MOM 17 Nov 2026 p1: setelah admin memproses pencairan komisi di Commission Payment,
    | bagian Finance (Bu Tiff) menerima email berisi nama admin, total nominal komisi yang
    | dicentang, dan jumlah marketing. Isi dengan satu atau beberapa alamat dipisah koma.
    | Kosong = email tidak dikirim (pencairan tetap diproses, admin diberi peringatan).
    |
    */

    'finance_notification_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('COMMISSION_FINANCE_EMAILS', ''))
    ))),

];
