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
];
