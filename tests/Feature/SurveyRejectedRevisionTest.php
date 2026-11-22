<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\SurveyController;
use App\Models\Survey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien 22 Nov 2026 (#17) / MOM 18 Nov: survey yang ditolak dijadikan REVISI (nomor
 * sama, revisi ke-N), bukan survey baru. Versi yang ditolak tersimpan sebagai arsip.
 */
class SurveyRejectedRevisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['survey_revisions', 'survey_details', 'surveys', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $t->id();
            $t->string('survey_number')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->text('recommendations')->nullable();
            $t->string('status')->default('draft');
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('survey_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('survey_id');
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_11_22_000007_add_survey_revisions.php'))->up();

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin']);
        DB::table('surveys')->insert([
            'id' => 1, 'survey_number' => 'JKT-SR/26-11/0001', 'status' => 'rejected',
            'recommendations' => 'Lokasi detail salah',
        ]);
        DB::table('survey_details')->insert(['survey_id' => 1, 'room_name' => 'Lobby']);

        $user = new class extends \App\Models\User
        {
            protected $table = 'users';

            public function hasRole($role)
            {
                return false;
            }

            public function hasRoleStartingWith($prefix)
            {
                return false;
            }

            public function hasPermission($permission)
            {
                return $permission === 'marketing.surveys.edit';
            }
        };
        $this->actingAs($user->newQuery()->findOrFail(1));
    }

    public function test_rejected_survey_becomes_the_next_revision_and_archives_the_rejected_version(): void
    {
        $response = app(SurveyController::class)->revise(Survey::findOrFail(1));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $survey = Survey::findOrFail(1);
        $this->assertSame('draft', $survey->status);
        $this->assertSame(1, (int) $survey->revision_number);
        $this->assertSame('JKT-SR/26-11/0001', $survey->survey_number);
        $this->assertSame('JKT-SR/26-11/0001 R.1', $survey->survey_number_with_revision);
        $this->assertSame(1, DB::table('surveys')->count(), 'Tidak boleh membuat survey baru');

        $archive = DB::table('survey_revisions')->sole();
        $this->assertSame(0, (int) $archive->revision_number);
        $this->assertSame('Lokasi detail salah', $archive->rejection_reason);
        $snapshot = json_decode($archive->snapshot, true);
        $this->assertSame('rejected', $snapshot['survey']['status']);
        $this->assertSame('Lobby', $snapshot['details'][0]['room_name']);
    }

    public function test_only_rejected_surveys_can_be_revised(): void
    {
        DB::table('surveys')->where('id', 1)->update(['status' => 'approved']);

        $response = app(SurveyController::class)->revise(Survey::findOrFail(1));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('survey_revisions')->count());
    }
}
