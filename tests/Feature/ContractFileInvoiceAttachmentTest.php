<?php

namespace Tests\Feature;

use App\Models\ContractFile;
use App\Services\Marketing\ContractFileInvoiceAttachmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Spec Halaman Report 24 Nov 2026 poin 1: file kontrak verified hanya menjadi pelengkap invoice
 * bila dipilih "Ya". Salinan di invoice_files ditambah/dicabut mengikuti pilihan itu, dan hanya
 * memakai kolom yang benar-benar ada di tabel invoice_files.
 */
class ContractFileInvoiceAttachmentTest extends TestCase
{
    private const TABLES = ['invoice_files', 'invoices', 'contract_files', 'contracts'];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_files', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->string('file_type')->nullable();
            $t->string('file_name')->nullable();
            $t->string('file_path')->nullable();
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->string('verification_status')->nullable();
            $t->boolean('is_invoice_attachment')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->string('contract_number')->nullable();
            $t->string('invoice_status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        // Sama dengan skema produksi: tanpa file_size / mime_type / uploaded_by / uploaded_at.
        Schema::create('invoice_files', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->string('file_name');
            $t->string('file_path');
            $t->string('file_type');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        DB::table('contracts')->insert(['id' => 1, 'contract_number' => 'JKT-CA/26-11/0001']);
        DB::table('invoices')->insert([
            ['id' => 10, 'contract_id' => 1, 'contract_number' => 'JKT-CA/26-11/0001', 'invoice_status' => 'draft'],
            ['id' => 11, 'contract_id' => null, 'contract_number' => 'JKT-CA/26-11/0001', 'invoice_status' => 'sent'],
            ['id' => 12, 'contract_id' => 1, 'contract_number' => 'JKT-CA/26-11/0001', 'invoice_status' => 'cancelled'],
        ]);
        DB::table('contract_files')->insert([
            ['id' => 1, 'contract_id' => 1, 'file_type' => 'contract_balik', 'file_name' => 'ttd.pdf', 'file_path' => 'uploads/contracts/ttd.pdf', 'verification_status' => 'verified', 'is_invoice_attachment' => null],
            ['id' => 2, 'contract_id' => 1, 'file_type' => 'npwp_scan', 'file_name' => 'npwp.pdf', 'file_path' => 'uploads/contracts/npwp.pdf', 'verification_status' => 'pending', 'is_invoice_attachment' => null],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function service(): ContractFileInvoiceAttachmentService
    {
        return $this->app->make(ContractFileInvoiceAttachmentService::class);
    }

    public function test_yes_copies_the_file_to_every_non_cancelled_invoice_of_the_contract(): void
    {
        $this->service()->setAttachment(ContractFile::find(1), true);

        $this->assertTrue(ContractFile::find(1)->is_invoice_attachment);
        $this->assertEqualsCanonicalizing([10, 11], DB::table('invoice_files')->pluck('invoice_id')->all());
        $this->assertSame(['contracts/ttd.pdf'], DB::table('invoice_files')->distinct()->pluck('file_path')->all());

        // Dipanggil ulang tidak menggandakan lampiran.
        $this->service()->setAttachment(ContractFile::find(1), true);
        $this->assertSame(2, DB::table('invoice_files')->count());
    }

    public function test_no_keeps_the_file_out_and_removes_earlier_copies_only(): void
    {
        $this->service()->setAttachment(ContractFile::find(1), true);
        DB::table('invoice_files')->insert([
            'invoice_id' => 10, 'file_name' => 'BA manual', 'file_path' => 'contracts/ttd.pdf',
            'file_type' => 'attachment', 'description' => 'diunggah manual oleh finance',
        ]);

        $this->service()->setAttachment(ContractFile::find(1), false);

        $this->assertFalse(ContractFile::find(1)->is_invoice_attachment);
        $this->assertSame(['diunggah manual oleh finance'], DB::table('invoice_files')->pluck('description')->all());
    }

    public function test_unverified_file_is_never_attached(): void
    {
        $this->service()->setAttachment(ContractFile::find(2), true);

        $this->assertSame(0, DB::table('invoice_files')->count());
    }
}
