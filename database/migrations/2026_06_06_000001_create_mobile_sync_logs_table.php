<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('job_schedule_id')->nullable()->constrained('job_schedules')->nullOnDelete();
            $table->foreignId('job_schedule_room_id')->nullable()->constrained('job_schedule_rooms')->nullOnDelete();
            $table->string('action', 80);
            $table->string('idempotency_key', 160)->nullable()->unique();
            $table->timestamp('client_clicked_at')->nullable();
            $table->string('client_delivery_mode', 40)->nullable();
            $table->timestamp('client_queued_at')->nullable();
            $table->timestamp('client_synced_at')->nullable();
            $table->timestamp('server_received_at')->nullable();
            $table->string('sync_status', 40)->default('synced');
            $table->string('payload_hash', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['job_schedule_id', 'action']);
            $table->index(['user_id', 'server_received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_logs');
    }
};
