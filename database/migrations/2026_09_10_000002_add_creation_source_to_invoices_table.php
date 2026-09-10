<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records whether an invoice was raised by a person or produced by the system.
 *
 * Every creation path - rental period, billing group, Extra job, Lost Unit and
 * the manual form - stamps created_by with whoever happened to be authenticated
 * at the time. For a system-generated invoice that is the technician who closed
 * the job, so the Invoice Detail screen credited them with creating an invoice
 * they never touched. There was no field to tell the two apart.
 *
 * The default is 'auto' on purpose: it backfills every existing row, which is
 * correct here because manual invoice creation is currently disabled in the UI,
 * so nothing already in the table was hand-made. Only the manual form sets
 * 'manual' explicitly - a new automatic path therefore needs no change, while a
 * new hand-made one has to say so where the record is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'creation_source')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('creation_source', 20)->default('auto')->after('created_by');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'creation_source')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('creation_source');
        });
    }
};
