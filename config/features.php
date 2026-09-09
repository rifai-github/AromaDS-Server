<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Serial Number bypass (trial mode)
    |--------------------------------------------------------------------------
    |
    | Temporary switch for the first months of SN rollout: when enabled,
    | mobile SN validation (install scan, swap unit, material pickup) accepts
    | serial numbers that were never pre-registered/linked by the warehouse,
    | and auto-registers them into `serial_numbers` on scan so the system
    | builds up known SNs per product over time. Turn back to false once the
    | trial period ends. See App\Services\SerialNumberBypassService.
    |
    */
    'sn_bypass_enabled' => env('SN_BYPASS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Hostname access restriction
    |--------------------------------------------------------------------------
    |
    | The ERP is meant to be reached by server IP only. A third party's domain
    | ("www.wappin.id") has an A record pointing at the production IP, so the
    | app was being served -- and indexed by search engines -- under a domain
    | the team never registered. With this on, any request whose Host is a
    | hostname rather than an IP gets a bare 404 + noindex.
    |
    | Bare IPs, localhost and *.test always pass. List extra hostnames in
    | APP_ALLOWED_HOSTS (comma separated) once the ERP gets a real domain.
    | See App\Http\Middleware\RestrictHostAccess.
    |
    */
    'block_hostname_access' => env('BLOCK_HOSTNAME_ACCESS', true),

    'allowed_hosts' => env('APP_ALLOWED_HOSTS', ''),
];
