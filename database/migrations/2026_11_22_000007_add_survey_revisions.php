<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban klien 22 Nov 2026 (#17) / MOM 18 Nov: survey yang ditolak menjadi REVISI, bukan
 * survey baru. Survey yang sama dibuka lagi sebagai draft dengan revision_number + 1; isi versi
 * yang ditolak (data survey, ruangan, alasan tolak) diarsipkan di survey_revisions. Seperti SQ,
 * yang dipakai hanya revisi terakhir; revisi lama sekadar arsip.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surveys') && ! Schema::hasColumn('surveys', 'revision_number')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->unsignedInteger('revision_number')->default(0)->after('status');
            });
        }

        if (! Schema::hasTable('survey_revisions')) {
            Schema::create('survey_revisions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_id');
                $table->unsignedInteger('revision_number')->comment('Nomor revisi versi yang diarsipkan');
                $table->string('status_before', 30)->nullable();
                $table->text('rejection_reason')->nullable();
                $table->longText('snapshot')->nullable()->comment('JSON survey + detail saat ditolak');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['survey_id', 'revision_number'], 'survey_revisions_survey_rev_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_revisions');

        if (Schema::hasTable('surveys') && Schema::hasColumn('surveys', 'revision_number')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->dropColumn('revision_number');
            });
        }
    }
};
