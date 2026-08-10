<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legacy Catalyst import created product categories but never populated
     * `is_unit` (it only ever wrote that flag onto product_types), and there is no
     * seeder for it either — the column is otherwise only set by hand via
     * Warehouse > Product Structure > Categories. Two dispenser categories were
     * therefore left at the `is_unit = 0` default even though they hold physical,
     * serial-number-tracked hardware.
     *
     * Effect of the bad flag: unit classification treated these dispensers as
     * consumables, so autoCreateMaterialIssue()'s install-job filter skipped them
     * and they could never be issued on an Install (IR) job. Reported by QA on
     * contract SMG-AG/25-04/0013 (rental #3, "Dispenser Hand Sanitizer 7600S--A").
     *
     * `has_serial_number` is set alongside it: without it these units would be
     * issued on an IR job with no serial-number capture
     * (ProductCategory::requiresSerialNumber()).
     *
     * Matched by NAME, never by id: the ids differ per environment because they
     * come from the Catalyst import (e.g. "Hand Sanitizer Disp" is id 34 on QA but
     * id 28 on staging).
     *
     * Deliberately NOT included: "PURE Hand Sanitizer W/DISP Svc". Despite the
     * similar name it holds ~62 liquid refill products ("Hand Sanitizer 1000ml",
     * ...), not dispensers, so it must stay is_unit = 0.
     *
     * Contents verified on QA and staging: both categories contain only
     * "Dispenser ..." products.
     */
    private const DISPENSER_CATEGORY_NAMES = [
        'Hand Sanitizer Disp',
        'Hand Soap Disp',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        $columns = ['is_unit' => 1];

        if (Schema::hasColumn('product_categories', 'has_serial_number')) {
            $columns['has_serial_number'] = 1;
        }

        DB::table('product_categories')
            ->whereIn('name', self::DISPENSER_CATEGORY_NAMES)
            ->where('is_unit', 0)
            ->update($columns);
    }

    public function down(): void
    {
        // Not reversible: the prior state was the Catalyst import's unset default
        // rather than a deliberate choice, and restoring is_unit = 0 would
        // reintroduce the bug this migration exists to fix.
    }
};
