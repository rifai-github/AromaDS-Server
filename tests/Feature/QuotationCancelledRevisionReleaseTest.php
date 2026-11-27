<?php

namespace Tests\Feature;

use App\Models\Quotation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (BLP-SQ/26-11/0003): revisi 2 (draft) dihapus → statusnya cancelled tetapi tetap
 * "revisi terakhir". Revisi 1 (approved) kehilangan tombol Revisi dan tak bisa dipakai lagi.
 */
class QuotationCancelledRevisionReleaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotations', 'quotation_details', 'quotation_rooms', 'quotation_surveys', 'surveys'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number');
            $t->integer('revision_number')->default(0);
            $t->boolean('is_latest_revision')->default(true);
            $t->string('status')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->dateTime('date_approved')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['quotation_number', 'revision_number']);
        });
        Schema::create('quotation_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotation_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotation_surveys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id');
            $t->unsignedBigInteger('survey_id');
            $t->dateTime('added_at')->nullable();
            $t->unsignedBigInteger('added_by')->nullable();
            $t->integer('sort_order')->nullable();
            $t->timestamps();
        });

        DB::table('quotations')->insert([
            ['id' => 29180, 'quotation_number' => 'BLP-SQ/26-11/0003', 'revision_number' => 0, 'status' => 'approved', 'is_latest_revision' => false],
            ['id' => 29181, 'quotation_number' => 'BLP-SQ/26-11/0003', 'revision_number' => 1, 'status' => 'approved', 'is_latest_revision' => false],
            ['id' => 29182, 'quotation_number' => 'BLP-SQ/26-11/0003', 'revision_number' => 2, 'status' => 'draft', 'is_latest_revision' => true],
        ]);
    }

    private function cancelRevisionTwo(): void
    {
        $rev2 = Quotation::findOrFail(29182);
        $rev2->update(['status' => 'cancelled']);
        $rev2->releaseLatestRevisionAfterCancel();
    }

    public function test_cancelling_the_latest_draft_hands_latest_back_to_the_previous_revision(): void
    {
        $this->cancelRevisionTwo();

        $this->assertFalse((bool) DB::table('quotations')->where('id', 29182)->value('is_latest_revision'));
        $this->assertTrue((bool) DB::table('quotations')->where('id', 29181)->value('is_latest_revision'));
        $this->assertSame([29181], Quotation::usable()->pluck('id')->all());
    }

    public function test_cancelled_revision_does_not_block_the_previous_one_in_usable_scope(): void
    {
        // Data lama (sebelum perbaikan): tanda latest sudah dipindah manual tapi rev 2 masih cancelled.
        DB::table('quotations')->where('id', 29182)->update(['status' => 'cancelled', 'is_latest_revision' => false]);
        DB::table('quotations')->where('id', 29181)->update(['is_latest_revision' => true]);

        $this->assertSame([29181], Quotation::usable()->pluck('id')->all());
    }

    public function test_new_revision_after_a_cancelled_one_skips_its_number(): void
    {
        $this->cancelRevisionTwo();

        $rev3 = Quotation::findOrFail(29181)->copyAsRevision();

        $this->assertSame(3, (int) $rev3->revision_number);
        $this->assertSame('draft', $rev3->status);
        $this->assertSame(1, DB::table('quotations')->where('is_latest_revision', true)->count());
        $this->assertTrue((bool) DB::table('quotations')->where('id', $rev3->id)->value('is_latest_revision'));
    }

    public function test_cancelling_an_older_draft_leaves_flags_alone(): void
    {
        DB::table('quotations')->insert(['id' => 1, 'quotation_number' => 'X/1', 'revision_number' => 0, 'status' => 'draft', 'is_latest_revision' => false]);
        $q = Quotation::findOrFail(1);
        $q->update(['status' => 'cancelled']);
        $q->releaseLatestRevisionAfterCancel();

        $this->assertTrue((bool) DB::table('quotations')->where('id', 29182)->value('is_latest_revision'));
    }
}
