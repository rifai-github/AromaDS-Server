<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (JA 0007, Customer Testing Indo): Job Check Unit Only Ruang-1 (59866) dan CSR
 * Ruang-2 (59868) sama-sama service_first periode 1 di JA & gedung yang sama → tampil satu baris
 * "Job Check", dan Material Assign untuk CSR Ruang-2 ditolak "Aksi Tidak Sesuai".
 */
class JobViewUnitOnlyCheckSeparateFromCsrTest extends TestCase
{
    private function invoke(string $method, JobSchedule $job): string
    {
        $reflection = new \ReflectionMethod(JobScheduleController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(app(JobScheduleController::class), $job);
    }

    private function job(array $attributes): JobSchedule
    {
        $job = new JobSchedule();
        $job->forceFill(array_merge([
            'job_advice_id' => 7,
            'building_id' => 20024,
            'type' => 'service_first',
            'period' => '1',
            'job_number' => null,
            'material_checked' => false,
        ], $attributes));

        return $job;
    }

    public function test_unassigned_unit_only_check_and_csr_get_different_keys(): void
    {
        $check = $this->job(['id' => 59866, 'material_checked' => true]);
        $csr = $this->job(['id' => 59868]);

        $this->assertNotSame($this->invoke('buildJobGroupingKey', $check), $this->invoke('buildJobGroupingKey', $csr));
        $this->assertNotSame($this->invoke('materialAssignBatchKey', $check), $this->invoke('materialAssignBatchKey', $csr));
    }

    public function test_numbered_jobs_still_group_by_their_job_number_only(): void
    {
        $a = $this->job(['id' => 1, 'job_number' => 'BLP-CSR/26-11/0001', 'material_checked' => true]);
        $b = $this->job(['id' => 2, 'job_number' => 'BLP-CSR/26-11/0001']);

        $this->assertSame($this->invoke('buildJobGroupingKey', $a), $this->invoke('buildJobGroupingKey', $b));
    }

    public function test_job_view_sql_splits_the_same_way(): void
    {
        Schema::dropIfExists('js_grouping_probe');
        Schema::create('js_grouping_probe', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->string('type')->nullable();
            $t->string('period')->nullable();
            $t->string('job_number')->nullable();
            $t->boolean('material_checked')->default(false);
        });
        DB::table('js_grouping_probe')->insert([
            ['id' => 59866, 'job_advice_id' => 7, 'building_id' => 1, 'type' => 'service_first', 'period' => '1', 'job_number' => null, 'material_checked' => true],
            ['id' => 59868, 'job_advice_id' => 7, 'building_id' => 1, 'type' => 'service_first', 'period' => '1', 'job_number' => null, 'material_checked' => false],
            ['id' => 59869, 'job_advice_id' => 7, 'building_id' => 1, 'type' => 'service_first', 'period' => '1', 'job_number' => null, 'material_checked' => false],
        ]);

        $source = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));
        preg_match('/->groupByRaw\("(CASE WHEN \(job_number IS NULL[^"]+)"\)/', $source, $match);
        $this->assertNotEmpty($match, 'groupByRaw Job View tidak ditemukan');

        $representatives = DB::table('js_grouping_probe')
            ->selectRaw('MIN(id) as id')
            ->groupBy('job_advice_id', 'building_id', 'type', 'period', 'job_number')
            ->groupByRaw($match[1])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertSame([59866, 59868], $representatives);

        Schema::dropIfExists('js_grouping_probe');
    }
}
