<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 19 Nov 2026 p6: Suspend/Unsuspend ROOM pada kontrak. Room yang di-suspend terkunci dan
 * job-nya tidak bisa dikerjakan di aplikasi teknisi maupun web sampai di-unsuspend. Setiap
 * suspend/unsuspend dicatat (admin, room, hari & jam).
 *
 * Sengaja tabel tersendiri, BUKAN status job 'suspend' yang sudah ada (itu = job selesai tanpa
 * invoice, terminal; unsuspend menghapus job).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_room_suspensions')) {
            return;
        }

        Schema::create('contract_room_suspensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('contract_room_id');
            $table->unsignedBigInteger('room_id')->nullable()->comment('master_rooms.id');
            $table->date('suspend_date')->comment('Tanggal mulai suspend');
            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('unsuspended_by')->nullable();
            $table->timestamp('unsuspended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'unsuspended_at'], 'crs_contract_active_index');
            $table->index(['contract_room_id', 'unsuspended_at'], 'crs_room_active_index');
            $table->index('room_id', 'crs_master_room_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_room_suspensions');
    }
};
