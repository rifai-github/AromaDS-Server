<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Services\Operational\ContractOnWallCsrService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (BLP-CA/26-11/0004 Ruang-1): trial Unit Only dipasang lewat BLP-SQ/26-11/0004 rev 1,
 * kontrak dari rev 2 → Job Check otomatis tak pernah dibuat (log generated_unit_only_checks: 0).
 */
class UnitOnlyCheckAcrossQuotationRevisionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotations', 'contracts'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->integer('revision_number')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('quotations')->insert([
            ['id' => 29183, 'quotation_number' => 'BLP-SQ/26-11/0004', 'revision_number' => 0],
            ['id' => 29184, 'quotation_number' => 'BLP-SQ/26-11/0004', 'revision_number' => 1],
            ['id' => 29185, 'quotation_number' => 'BLP-SQ/26-11/0004', 'revision_number' => 2],
            ['id' => 29180, 'quotation_number' => 'BLP-SQ/26-11/0003', 'revision_number' => 0],
        ]);
        DB::table('contracts')->insert(['id' => 21134, 'quotation_id' => 29185]);
    }

    public function test_job_advices_of_every_revision_of_the_same_sq_are_considered(): void
    {
        $method = new \ReflectionMethod(ContractOnWallCsrService::class, 'sameQuotationRevisionIds');
        $method->setAccessible(true);

        $ids = $method->invoke(app(ContractOnWallCsrService::class), Contract::with('quotation')->findOrFail(21134));
        sort($ids);

        $this->assertSame([29183, 29184, 29185], $ids);
    }

    public function test_rooms_from_another_revision_are_limited_to_contract_rooms(): void
    {
        $service = file_get_contents(app_path('Services/Operational/ContractOnWallCsrService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('generateUnitOnlyCheckSchedulesAfterInstall($completedInstall, $jobAdvice, $allowedRoomIds)', $service);
        $this->assertStringContainsString('use ($completedRoomIds, $allowedPhysicalRoomIds)', $controller);
    }
}
