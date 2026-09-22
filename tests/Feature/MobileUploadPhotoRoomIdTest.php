<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * APK mengirim `job_schedule_room_id` yang isinya id **JobAdviceRoom** (RoomModel.id),
 * sementara kolomnya ber-FK ke `job_schedule_rooms`. Nilainya dulu ditulis apa adanya,
 * jadi setiap unggahan foto gagal dengan "1452 Cannot add or update a child row".
 *
 * Karena unggahan foto bukti scan sengaja fire-and-forget, kegagalannya senyap: di
 * produksi tercatat 3.217 percobaan gagal (15-22 Sep 2026) dan nol baris `sn_scan` —
 * persis keluhan QA "foto scan QR tidak ada" (Revisi 1, 21 Sep 2026).
 */
class MobileUploadPhotoRoomIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
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
    }

    protected function tearDown(): void
    {
        foreach (['job_schedule_room_rentals', 'job_schedule_rooms'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function resolve(int $jobScheduleId, $rawRoomId)
    {
        $method = new \ReflectionMethod(JobController::class, 'resolveJobPhotoRoomId');
        $method->setAccessible(true);

        return $method->invoke(app(JobController::class), $jobScheduleId, $rawRoomId);
    }

    public function test_job_advice_room_id_is_translated_to_its_job_schedule_room(): void
    {
        // Baris nyata dari produksi: job 108, JobAdviceRoom 75839, JobScheduleRoom 116.
        DB::table("job_schedule_rooms")->insert([
            'id' => 116,
            'job_schedule_id' => 108,
            'job_advice_room_id' => 75839,
            'room_name' => 'Ruang Periksa Simo',
        ]);

        $this->assertSame(116, $this->resolve(108, 75839));
        $this->assertSame(116, $this->resolve(108, '75839'));
    }

    public function test_a_real_job_schedule_room_id_is_kept_as_is(): void
    {
        DB::table("job_schedule_rooms")->insert([
            'id' => 116,
            'job_schedule_id' => 108,
            'job_advice_room_id' => 75839,
            'room_name' => 'Ruang Periksa Simo',
        ]);

        $this->assertSame(116, $this->resolve(108, 116));
    }

    public function test_multi_rental_rooms_resolve_through_the_pivot(): void
    {
        DB::table("job_schedule_rooms")->insert([
            'id' => 200,
            'job_schedule_id' => 108,
            'job_advice_room_id' => null,
            'room_name' => 'LOBBY',
        ]);

        DB::table("job_schedule_room_rentals")->insert([
            'job_schedule_room_id' => 200,
            'job_advice_room_id' => 90001,
            'is_primary' => true,
        ]);

        $this->assertSame(200, $this->resolve(108, 90001));
    }

    public function test_the_job_owning_the_room_wins_when_an_advice_room_maps_to_several(): void
    {
        DB::table("job_schedule_rooms")->insert([
            'id' => 300,
            'job_schedule_id' => 500,
            'job_advice_room_id' => 75839,
            'room_name' => 'Ruang Periksa Simo',
        ]);
        DB::table("job_schedule_rooms")->insert([
            'id' => 301,
            'job_schedule_id' => 108,
            'job_advice_room_id' => 75839,
            'room_name' => 'Ruang Periksa Simo',
        ]);

        $this->assertSame(301, $this->resolve(108, 75839));
    }

    /**
     * Id yang sudah diterjemahkan harus dipakai di SELURUH tulisan request itu.
     *
     * Perbaikan pertama hanya menerjemahkan id untuk job_photos dan meninggalkan id
     * mentah di recordMobileSync(). mobile_sync_logs punya FK yang sama, jadi request-nya
     * tetap berakhir 1452 -> 500: fotonya tersimpan, tapi klien menganggap gagal dan
     * antrean sync mengulang unggahan yang sama tiap menit. Satu scan jadi 5 baris foto
     * kembar berjarak 60 detik (22 Sep 2026, job 143).
     */
    public function test_the_resolved_room_id_is_used_for_the_sync_log_too(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/Api/Mobile/JobController.php')
        );

        $start = strpos($controller, 'public function uploadPhoto(');
        $this->assertNotFalse($start, 'uploadPhoto() tidak ditemukan.');

        $end = strpos($controller, 'private function resolveJobPhotoRoomId', $start);
        $this->assertNotFalse($end, 'Batas akhir uploadPhoto() tidak ditemukan.');

        $body = substr($controller, $start, $end - $start);

        $this->assertStringContainsString(
            "\$this->recordMobileSync(\$request, 'upload_photo', (int) \$jobScheduleId, \$jobScheduleRoomId)",
            $body,
            'recordMobileSync harus memakai id yang sudah diterjemahkan.'
        );

        // Id mentah dari request hanya boleh muncul sekali: sebagai masukan penerjemah.
        $this->assertSame(
            1,
            substr_count($body, '$request->job_schedule_room_id ?? $request->room_id'),
            'Id mentah dari request tidak boleh dipakai lagi setelah diterjemahkan.'
        );
    }

    public function test_an_unmappable_id_becomes_null_instead_of_losing_the_photo(): void
    {
        $this->assertNull($this->resolve(108, 75839));
        $this->assertNull($this->resolve(108, 0));
        $this->assertNull($this->resolve(108, null));
        $this->assertNull($this->resolve(108, 'bukan angka'));
    }
}
