<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Feeds the "Start Job" column on the Rental & Team tab.
 *
 * A visit is split across sibling job_schedules rows sharing one job number, and only the
 * schedule the app had open when the technician tapped "Mulai Kerja" gets started_at. Rooms
 * on the other siblings rendered a blank cell even after the whole job was done - QA
 * SBY-CSR/26-09/0028, where Ruang Lobby (schedule 825) showed "-" next to a finish time.
 */
class JobScheduleVisitStartFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    private function resolve(array $siblingJobIds, $teamLocations): array
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'resolveVisitStart');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), $siblingJobIds, $teamLocations);
    }

    private function location(int $jobScheduleId, string $action, ?string $lat, ?string $lng, string $recordedAt): object
    {
        return (object) [
            'job_schedule_id' => $jobScheduleId,
            'action' => $action,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => $recordedAt,
        ];
    }

    public function test_it_takes_the_earliest_start_among_the_siblings_of_one_visit(): void
    {
        DB::table('job_schedules')->insert([
            ['id' => 825, 'job_number' => 'SBY-CSR/26-09/0028', 'started_at' => null],
            ['id' => 826, 'job_number' => 'SBY-CSR/26-09/0028', 'started_at' => '2026-09-08 16:18:17'],
        ]);

        $visitStart = $this->resolve([825, 826], collect());

        $this->assertSame('2026-09-08 16:18:17', (string) $visitStart['started_at']);
    }

    public function test_a_visit_nobody_ever_started_reports_no_start_rather_than_inventing_one(): void
    {
        DB::table('job_schedules')->insert([
            ['id' => 178, 'job_number' => 'SBY-RV/26-06/0012', 'started_at' => null],
            ['id' => 179, 'job_number' => 'SBY-RV/26-06/0012', 'started_at' => null],
        ]);

        $this->assertNull($this->resolve([178, 179], collect())['started_at']);
    }

    public function test_it_falls_back_to_the_first_arrival_pin_when_the_schedule_has_no_coordinates(): void
    {
        // The whole fleet currently posts one hardcoded coordinate, so the pin is only ever
        // as good as that - but the column should still find it instead of showing nothing.
        DB::table('job_schedules')->insert([
            ['id' => 826, 'job_number' => 'SBY-CSR/26-09/0028', 'started_at' => '2026-09-08 16:18:17'],
        ]);

        $visitStart = $this->resolve([826], collect([
            $this->location(826, 'left', '-6.3000000', '106.9000000', '2026-09-08 16:31:00'),
            $this->location(826, 'arrived', '-6.2088000', '106.8456000', '2026-09-08 16:17:55'),
        ]));

        $this->assertSame('-6.2088000', $visitStart['latitude']);
        $this->assertSame('106.8456000', $visitStart['longitude']);
    }

    public function test_a_location_row_without_coordinates_is_skipped(): void
    {
        DB::table('job_schedules')->insert([
            ['id' => 826, 'job_number' => 'SBY-CSR/26-09/0028', 'started_at' => '2026-09-08 16:18:17'],
        ]);

        $visitStart = $this->resolve([826], collect([
            $this->location(826, 'arrived', null, null, '2026-09-08 16:17:00'),
            $this->location(826, 'arrived', '-6.2088000', '106.8456000', '2026-09-08 16:17:55'),
        ]));

        $this->assertSame('-6.2088000', $visitStart['latitude']);
    }

    public function test_the_schedules_own_coordinates_win_over_the_location_history(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 826,
                'job_number' => 'SBY-CSR/26-09/0028',
                'started_at' => '2026-09-08 16:18:17',
                'latitude' => '-6.1111111',
                'longitude' => '106.1111111',
            ],
        ]);

        $visitStart = $this->resolve([826], collect([
            $this->location(826, 'arrived', '-6.2088000', '106.8456000', '2026-09-08 16:17:55'),
        ]));

        $this->assertEqualsWithDelta(-6.1111111, (float) $visitStart['latitude'], 0.0000001);
    }
}
