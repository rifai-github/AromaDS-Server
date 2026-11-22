<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy approver fallback
    |--------------------------------------------------------------------------
    |
    | Before tiered approval existed, anyone holding `marketing.quotations.approve`
    | could approve any quotation regardless of how deep the discount was.
    |
    | While true, a user who holds that legacy permission but has not yet been
    | granted ANY approval-level permission is treated as the most senior level,
    | so nobody loses the ability to approve on the day this ships.
    |
    | Turn this off once every approver role has been assigned a proper level in
    | Master > Level Approval Quotation.
    |
    | TIDAK DIPAKAI LAGI sejak MOM 18 Nov 2026: wewenang approval = user yang ditunjuk
    | per level, dan tanpa level/penunjukan toleransinya 0%. Dibiarkan hanya agar env
    | lama tak error.
    |
    */

    'legacy_approve_is_highest' => env('QUOTATION_LEGACY_APPROVE_IS_HIGHEST', true),

];
