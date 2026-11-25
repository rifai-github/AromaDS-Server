<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 24 Nov 2026: tab Rental & Team di detail job butuh kolom "Done" per room -
 * "done by web" bila diselesaikan dari web, "done by app" bila dari aplikasi teknisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_schedule_rooms') && ! Schema::hasColumn('job_schedule_rooms', 'completion_source')) {
            Schema::table('job_schedule_rooms', function (Blueprint $table) {
                $table->string('completion_source', 10)->nullable()->after('completed_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_schedule_rooms') && Schema::hasColumn('job_schedule_rooms', 'completion_source')) {
            Schema::table('job_schedule_rooms', function (Blueprint $table) {
                $table->dropColumn('completion_source');
            });
        }
    }
};
