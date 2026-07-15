<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalyst_migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('action_key', 80);
            $table->string('label', 160);
            $table->string('execution', 20)->default('background');
            $table->string('status', 20)->default('pending');
            $table->string('mode', 20)->nullable();
            $table->string('current_step', 120)->nullable();
            $table->text('progress_message')->nullable();
            $table->unsignedInteger('pid')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('source_import_batches')->nullOnDelete();
            $table->string('log_path')->nullable();
            $table->string('backup_path')->nullable();
            $table->string('backup_sha256', 64)->nullable();
            $table->unsignedBigInteger('backup_size')->nullable();
            $table->longText('summary')->nullable();
            $table->longText('output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'id']);
            $table->index(['action_key', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalyst_migration_runs');
    }
};
