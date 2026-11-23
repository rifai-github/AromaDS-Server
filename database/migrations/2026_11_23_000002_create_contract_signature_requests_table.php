<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 19 Nov 2026 p4 no 8 — Digital Sign kontrak (jawaban klien 23 Nov 2026):
 * setelah kontrak di-post, sistem membuat link publik (tanpa login) untuk penandatangan
 * internal (Pink) dan customer yang dipilih di kontrak. Tanda tangan digoreskan di layar
 * (tidak tersertifikasi, sama dengan Catalyst). Satu "batch" = satu snapshot PDF yang
 * ditandatangani kedua pihak; setelah ada tanda tangan, kontrak dinyatakan final.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_signature_requests')) {
            return;
        }

        Schema::create('contract_signature_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->string('batch', 36)->index();
            $table->string('signer_role', 20); // internal | customer
            $table->string('signer_name');
            $table->string('signer_position')->nullable();
            $table->unsignedBigInteger('signer_user_id')->nullable();
            $table->unsignedBigInteger('signer_contact_id')->nullable();
            $table->string('token', 64)->unique();
            $table->string('html_path');
            $table->string('document_path');
            $table->string('document_hash', 64);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signer_ip', 45)->nullable();
            $table->text('signer_user_agent')->nullable();
            $table->string('final_document_path')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signature_requests');
    }
};
