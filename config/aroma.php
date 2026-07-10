<?php

return [

    /*
    |--------------------------------------------------------------------------
    | XFreqService per-service material interval filter
    |--------------------------------------------------------------------------
    |
    | When enabled, recurring service jobs (service / service_routine) only
    | include a rental material on the services where it is actually due,
    | based on rental_details.service_frequency_multiplier (legacy Catalyst
    | "XFreqService"). CONFIRMED client rule (10 Jul 2026): the multiplier is
    | an interval measured in SERVICE COUNT (not time). Material is due at
    | service #n when (n - 1) % multiplier == 0; multiplier 0 = permanent unit
    | (install only). Service #1 = install, where all materials are fresh.
    |
    | Kill-switch / staged rollout: defaults OFF so production behaviour is
    | unchanged until the flow is verified on staging. Only rentals that have
    | XFreqService actually configured (a value >= 1 on any detail) are ever
    | affected — un-configured rentals keep the current full-BOM behaviour.
    |
    */
    'xfreq_service_material_filter' => env('XFREQ_SERVICE_MATERIAL_FILTER', false),

];
