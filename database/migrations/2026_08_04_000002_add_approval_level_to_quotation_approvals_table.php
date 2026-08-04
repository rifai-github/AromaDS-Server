<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `quotation_approvals` predates the incremental migration folder, so it may
     * or may not already exist depending on the environment - hence the guards.
     *
     * Two fixes here:
     *  - record which approval level a request demanded;
     *  - add created_by/updated_by, which HasComprehensiveAuditTrail writes on
     *    every save but which the table never had. Without them the first
     *    QuotationApproval::create() would fail with "Unknown column".
     */
    public function up(): void
    {
        if (! Schema::hasTable('quotation_approvals')) {
            Schema::create('quotation_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quotation_id');
                $table->unsignedBigInteger('quotation_revision_id')->nullable();
                $table->string('approval_type')->default('general');
                $table->string('status')->default('pending');
                $table->text('approval_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->json('approval_data')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['quotation_id', 'status']);
            });
        }

        Schema::table('quotation_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_approvals', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('approval_data');
            }

            if (! Schema::hasColumn('quotation_approvals', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn('quotation_approvals', 'required_level_id')) {
                $table->unsignedBigInteger('required_level_id')->nullable()->after('approval_type');
            }

            if (! Schema::hasColumn('quotation_approvals', 'required_level_code')) {
                $table->string('required_level_code', 50)->nullable()->after('required_level_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_approvals')) {
            return;
        }

        Schema::table('quotation_approvals', function (Blueprint $table) {
            foreach (['required_level_code', 'required_level_id'] as $column) {
                if (Schema::hasColumn('quotation_approvals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
