<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 25 Nov 2026: audit trail harus menunjukkan nomor transaksi (mis. JA yang dihapus,
 * invoice yang dihapus finance), bukan hanya "job advice action delete".
 */
class AuditLogReferenceNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('audit_logs');
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action')->nullable();
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->string('reference_number', 100)->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->json('changed_fields')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });
    }

    public function test_deleted_document_number_is_stored_from_the_old_values(): void
    {
        $log = AuditLog::create([
            'action' => 'deleted',
            'model_type' => 'App\Models\JobAdvice',
            'model_id' => 26037,
            'old_values' => ['id' => 26037, 'job_advice_number' => 'JKT-JA/26-08/0110'],
        ]);

        $this->assertSame('JKT-JA/26-08/0110', $log->fresh()->reference_number);
    }

    public function test_viewed_record_reference_and_legacy_rows_are_displayed(): void
    {
        $viewed = AuditLog::create(['action' => 'page_view', 'new_values' => ['reference' => 'JKT-INV/26-11/0002', 'page' => 'finance/invoices/25']]);
        $this->assertSame('JKT-INV/26-11/0002', $viewed->fresh()->reference_number);

        $legacy = new AuditLog(['old_values' => ['invoice_number' => 'JKT-INV/26-11/0001']]);
        $this->assertSame('JKT-INV/26-11/0001', $legacy->display_reference_number);

        $none = AuditLog::create(['action' => 'page_view', 'new_values' => ['page' => 'dashboard']]);
        $this->assertNull($none->fresh()->reference_number);
    }
}
