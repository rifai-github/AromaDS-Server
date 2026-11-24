<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aroma Material Assign untuk SQ tanpa aroma (klien 24 Nov 2026)
    |--------------------------------------------------------------------------
    |
    | SQ hasil import Catalyst tak punya aroma per ruangan. Bila true, baris aroma yang ruangan
    | SQ-nya kosong boleh memilih SEMUA aroma di kategori produk yang sama (lintas brand line),
    | dan aroma pilihan itu otomatis mengisi SQ saat inventory issuing terbit. Ruangan SQ yang
    | sudah punya aroma tetap memakai aturan brand line. Set false (atas arahan Pak Hengky)
    | untuk mengembalikan guard penuh. Lihat App\Services\Operational\QuotationAromaGapService.
    |
    */

    'relax_empty_sq_aroma' => (bool) env('MATERIAL_ASSIGN_RELAX_EMPTY_SQ_AROMA', true),

];
