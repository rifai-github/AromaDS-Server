<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Extra / Change Unit job schedules must carry the EXT document code and
 * Complain job schedules the NR code. Both codes exist in DocumentNumberService
 * but became unreachable when job-number generation moved from Job Advice
 * approval to the assignment stage, so every Extra/Complain job fell through to
 * the generic JS prefix.
 */
class JobScheduleExtraComplainNumberPrefixTest extends TestCase
{
    private function documentTypeFor(string $type): string
    {
        $job = new JobSchedule(['type' => $type]);
        $job->setRelation('jobAdvice', null);

        $method = new ReflectionMethod(JobScheduleController::class, 'documentTypeForJobSchedule');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController, $job);
    }

    private function generatedDocumentTypeFor(string $type): string
    {
        $captured = null;

        $service = Mockery::mock(DocumentNumberService::class);
        $service->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function ($documentType) use (&$captured) {
                $captured = $documentType;

                return 'SBY-XX/26-08/0001';
            });

        $this->instance(DocumentNumberService::class, $service);

        $method = new ReflectionMethod(JobScheduleController::class, 'generateJobNumber');
        $method->setAccessible(true);
        $method->invoke(new JobScheduleController, $type, null, null);

        return $captured;
    }

    public function test_extra_job_schedule_uses_ext_document_type(): void
    {
        $this->assertSame('job_schedule_extra', $this->documentTypeFor('extra'));
        $this->assertSame('job_schedule_extra', $this->generatedDocumentTypeFor('extra'));
    }

    public function test_complain_job_schedule_uses_nr_document_type(): void
    {
        $this->assertSame('job_schedule_complain', $this->documentTypeFor('complain'));
        $this->assertSame('job_schedule_complain', $this->generatedDocumentTypeFor('complain'));
    }

    public function test_change_unit_job_schedule_shares_the_ext_document_type(): void
    {
        $this->assertSame('job_schedule_extra', $this->documentTypeFor('change'));
        $this->assertSame('job_schedule_extra', $this->generatedDocumentTypeFor('change'));
    }

    public function test_ext_and_nr_resolve_to_the_expected_prefix_codes(): void
    {
        $typeCodes = (new ReflectionMethod(DocumentNumberService::class, 'generate'))
            ->getDeclaringClass()
            ->getConstant('TYPE_CODES');

        $this->assertSame('EXT', $typeCodes['job_schedule_extra']);
        $this->assertSame('NR', $typeCodes['job_schedule_complain']);
    }

    public function test_unmapped_types_still_fall_back_to_generic_job_schedule(): void
    {
        $this->assertSame('job_schedule', $this->documentTypeFor('maintenance'));
    }
}
