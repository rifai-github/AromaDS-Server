<?php

return [
    'connection_name' => env('CATALYST_IMPORT_CONNECTION', 'catalyst_import'),
    'php_binary' => env('CATALYST_IMPORT_PHP_BINARY', ''),

    'source' => [
        'driver' => env('CATALYST_SOURCE_DRIVER', 'sqlsrv'),
        'host' => env('CATALYST_SOURCE_HOST', '127.0.0.1'),
        'port' => (int) env('CATALYST_SOURCE_PORT', 1433),
        'database' => env('CATALYST_SOURCE_DATABASE', 'PinkAds'),
        'username' => env('CATALYST_SOURCE_USERNAME', ''),
        'password' => env('CATALYST_SOURCE_PASSWORD', ''),
        'trusted_connection' => filter_var(env('CATALYST_SOURCE_TRUSTED_CONNECTION', false), FILTER_VALIDATE_BOOLEAN),
        'charset' => env('CATALYST_SOURCE_CHARSET', 'utf8'),
        'prefix' => env('CATALYST_SOURCE_PREFIX', ''),
        'encrypt' => env('CATALYST_SOURCE_ENCRYPT', 'no'),
        'trust_server_certificate' => env('CATALYST_SOURCE_TRUST_SERVER_CERTIFICATE', 'yes'),
    ],

    'chunk_size' => (int) env('CATALYST_IMPORT_CHUNK_SIZE', 250),

    // UAT 19 Nov 2026: hanya baris FgActive <> 'N' yang diimport untuk step di
    // CatalystMasterDataImporter::ACTIVE_ONLY_STEPS (employee tetap semua).
    'active_only' => filter_var(env('CATALYST_IMPORT_ACTIVE_ONLY', true), FILTER_VALIDATE_BOOLEAN),

    // Jawaban klien no. 3/14 (spreadsheet struktur Catalyst, 26 Nov 2026): kontrak yang
    // diimport hanya Status P, berakhir (TransDate + ContractPeriod - 1 hari) >= tanggal ini,
    // belum di-renew kontrak lain, dan customer-nya bukan TEST. Lihat
    // CatalystMasterDataImporter::eligibleContractNumbers().
    'contract_filter' => filter_var(env('CATALYST_IMPORT_CONTRACT_FILTER', true), FILTER_VALIDATE_BOOLEAN),
    'contract_active_until_min' => env('CATALYST_IMPORT_CONTRACT_ACTIVE_UNTIL_MIN', '2026-12-01'),
];
