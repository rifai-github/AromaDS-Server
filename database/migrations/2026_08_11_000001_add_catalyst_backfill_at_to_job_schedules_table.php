<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_schedules', function (Blueprint $table) {
            $table->timestamp('catalyst_backfill_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_schedules', function (Blueprint $table) {
            $table->dropColumn('catalyst_backfill_at');
        });
    }
};
