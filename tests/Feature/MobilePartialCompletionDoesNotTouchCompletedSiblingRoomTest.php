<?php

namespace Tests\Feature;

use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\User;
use App\Services\Operational\JobWebCompletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bug #28 (reported BEFORE the bug #9 fix landed): "ruang ballroom sudah
 * menyelesaikan pekerjaan, unitnya juga sudah IN USE, trs ruang meeting besar
 * outstanding tapi kenapa ballroom ikut2an ke bawa Outstanding dan muncul new
 * job untuk ballroom dan ruang meeting besar." — i.e. a completed room got
 * incorrectly dragged into the partial-completion/outstanding follow-up flow
 * just because a SIBLING room on the same job_schedule was left unfinished.
 *
 * This locks in that handlePartialCompletion() (the fixed/extracted version
 * used by both mobile and web after the bug #9 fix) only ever moves the
 * actually-unfinished room into a follow-up job — a sibling room that is
 * already 'completed' must never get a duplicate follow-up job/room created
 * for it, and its own status/room rows must stay untouched.
 */
class MobilePartialCompletionDoesNotTouchCompletedSiblingRoomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('building_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->integer('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->string('service_period_type')->nullable();
            $table->integer('service_interval_days')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('job_reference_number')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->foreignId('material_return_id')->nullable();
            $table->timestamp('material_return_at')->nullable();
            $table->foreignId('material_return_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('received_from')->nullable();
            $table->foreignId('received_by_old')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Teknisi Test',
            'email' => 'teknisi@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_receivings',
            'material_returns',
            'material_issue_items',
            'job_assign_material_issues',
            'material_issues',
            'job_assign_schedules',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'warehouses',
            'buildings',
            'branches',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_partial_completion_does_not_create_a_follow_up_for_an_already_completed_sibling_room(): void
    {
        DB::table('branches')->insert(['id' => 1, 'code' => 'SBY', 'name' => 'Surabaya', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('buildings')->insert(['id' => 10, 'branch_id' => 1, 'name' => 'Hotel Test', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_advices')->insert(['id' => 70, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()], // Ballroom
            ['id' => 81, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()], // Meeting Besar
        ]);

        // Single job_schedule covering BOTH rooms (one job_number, 2 rentals/rooms),
        // mirroring bug #28's "2 room dan 2 rental" under the same job number.
        DB::table('job_schedules')->insert([
            'id' => 50,
            'job_advice_id' => 70,
            'job_number' => 'SBY-IR/26-06/0099',
            'type' => 'install',
            'status' => 'in_progress',
            'building_id' => 10,
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 60, 'job_schedule_id' => 50, 'job_advice_room_id' => 80, 'room_name' => 'Ballroom', 'room_id' => 800, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 61, 'job_schedule_id' => 50, 'job_advice_room_id' => 81, 'room_name' => 'Meeting Besar', 'room_id' => 801, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $job = JobSchedule::findOrFail(50);

        app(JobWebCompletionService::class)->handlePartialCompletion($job, now(), 1);

        // The Ballroom room itself must stay untouched — still 'completed', not
        // flipped to 'cancelled' and not given a material_return.
        $ballroom = JobScheduleRoom::find(60);
        $this->assertSame('completed', $ballroom->status, 'Ballroom must stay completed — it must not be treated as unfinished work.');
        $this->assertNull($ballroom->material_return_id, 'Ballroom must not get a material return queued — its material was actually used, not left over.');

        // The Meeting Besar room (the genuinely unfinished one) must be moved.
        $meeting = JobScheduleRoom::find(61);
        $this->assertSame('cancelled', $meeting->status);

        // Exactly ONE follow-up job must exist (for Meeting Besar), not two, and it
        // must not contain a duplicate Ballroom room.
        $followUps = JobSchedule::where('internal_notes', 'like', 'Lanjutan dari Job SBY-IR/26-06/0099%')->get();
        $this->assertCount(1, $followUps, 'Exactly one follow-up job should be created (for Meeting Besar) — Ballroom must not get its own duplicate follow-up.');

        $followUpRooms = $followUps->first()->jobScheduleRooms;
        $this->assertCount(1, $followUpRooms);
        $this->assertSame('Meeting Besar', $followUpRooms->first()->room_name);

        // No second job_schedule_room row was ever created for Ballroom anywhere.
        $this->assertSame(1, JobScheduleRoom::where('room_name', 'Ballroom')->count(), 'Ballroom must end up with exactly one room row total — no duplicate IR/room was created for it.');
    }
}
