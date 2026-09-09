<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_schedule_rooms', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('status');
            $table->unsignedBigInteger('started_by')->nullable()->after('started_at');
            $table->decimal('start_latitude', 10, 8)->nullable()->after('started_by');
            $table->decimal('start_longitude', 11, 8)->nullable()->after('start_latitude');

            $table->index('started_by', 'job_schedule_rooms_started_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('job_schedule_rooms', function (Blueprint $table) {
            $table->dropIndex('job_schedule_rooms_started_by_idx');
            $table->dropColumn(['started_at', 'started_by', 'start_latitude', 'start_longitude']);
        });
    }
};
