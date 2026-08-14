<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('job_schedule_unit_id')->nullable()->after('job_schedule_room_id');
            $table->index(['job_schedule_room_id', 'job_schedule_unit_id', 'photo_type'], 'job_photos_room_unit_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('job_photos', function (Blueprint $table) {
            $table->dropIndex('job_photos_room_unit_type_idx');
            $table->dropColumn('job_schedule_unit_id');
        });
    }
};
