<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 50);
            $table->string('source_database')->nullable();
            $table->string('batch_name');
            $table->string('mode', 20);
            $table->string('status', 20);
            $table->json('steps');
            $table->json('options')->nullable();
            $table->longText('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_system', 'id']);
            $table->index(['source_system', 'status']);
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 50);
            $table->string('source_table', 100);
            $table->string('source_key');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id');
            $table->string('source_hash', 40)->nullable();
            $table->foreignId('last_batch_id')->nullable()->constrained('source_import_batches')->nullOnDelete();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_table', 'source_key', 'target_table'],
                'source_import_maps_source_target_unique'
            );
            $table->index(
                ['target_table', 'target_id'],
                'source_import_maps_target_table_target_id_index'
            );
        });

        Schema::create('source_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('source_import_batches')->cascadeOnDelete();
            $table->string('source_system', 50);
            $table->string('step', 100);
            $table->string('level', 20);
            $table->string('source_table', 100)->nullable();
            $table->string('source_key')->nullable();
            $table->string('target_table', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('message');
            $table->longText('context')->nullable();
            $table->timestamps();

            $table->index(['source_system', 'level', 'id']);
            $table->index(['batch_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_import_logs');
        Schema::dropIfExists('source_import_maps');
        Schema::dropIfExists('source_import_batches');
    }
};
