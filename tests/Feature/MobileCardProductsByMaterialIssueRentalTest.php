<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 25-26 Nov 2026, JKT-IF/26-11/0001: satu ruangan "Test Ruang 261116-2" dengan rental 301
 * (Dispenser A301C, Alluring Floral, Cleaner) dan rental 103 (Dispenser AE103C, Bakers Life,
 * Cleaner). Kedua kartu APK menampilkan keenam komponen.
 */
class MobileCardProductsByMaterialIssueRentalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job_assign_schedules', 'material_issue_items', 'rental_details'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('job_assign_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('material_issue_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('room_name')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('master_rental_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('job_assign_schedules')->insert(['id' => 5, 'job_schedule_id' => 366]);
        DB::table('rental_details')->insert([
            ['id' => 1, 'master_rental_id' => 301], ['id' => 2, 'master_rental_id' => 301], ['id' => 3, 'master_rental_id' => 301],
            ['id' => 11, 'master_rental_id' => 103], ['id' => 12, 'master_rental_id' => 103], ['id' => 13, 'master_rental_id' => 103],
        ]);
        $room = 'Test Ruang 261116-2';
        DB::table('material_issue_items')->insert([
            ['job_assign_schedule_id' => 5, 'product_id' => 561, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 301, RentalDetailID: 1"],
            ['job_assign_schedule_id' => 5, 'product_id' => 590, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 301, RentalDetailID: 2"],
            ['job_assign_schedule_id' => 5, 'product_id' => 12, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 301, RentalDetailID: 3"],
            ['job_assign_schedule_id' => 5, 'product_id' => 561, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 103, RentalDetailID: 11"],
            ['job_assign_schedule_id' => 5, 'product_id' => 591, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 103, RentalDetailID: 12"],
            ['job_assign_schedule_id' => 5, 'product_id' => 39, 'room_name' => $room, 'notes' => "Room: {$room}, Rental: ADS 103, RentalDetailID: 13 (Substituted from X)"],
        ]);
    }

    private function products(): array
    {
        return array_map(fn ($id) => ['product_id' => $id, 'source' => 'inventory_issuing'], [561, 590, 12, 591, 39]);
    }

    private function narrow(int $rentalId, ?array $products = null): array
    {
        $method = new \ReflectionMethod(JobController::class, 'narrowProductsToCardRentalByMaterialIssue');
        $method->setAccessible(true);

        $result = $method->invoke(app(JobController::class), $products ?? $this->products(), 366, (object) ['rental_product_id' => $rentalId], 'Test Ruang 261116-2');

        return array_column($result, 'product_id');
    }

    public function test_each_card_shows_only_its_own_rentals_components(): void
    {
        $this->assertSame([561, 590, 12], $this->narrow(301));
        $this->assertSame([561, 591, 39], $this->narrow(103));
    }

    public function test_unit_on_wall_entries_and_products_outside_material_issue_are_kept(): void
    {
        $products = array_merge($this->products(), [
            ['product_id' => 999, 'source' => 'inventory_issuing'],
            ['product_id' => 591, 'source' => 'unit_on_wall'],
        ]);

        $this->assertSame([561, 590, 12, 999, 591], $this->narrow(301, $products));
    }

    public function test_untagged_material_issue_rows_leave_the_list_untouched(): void
    {
        DB::table('material_issue_items')->insert(['job_assign_schedule_id' => 5, 'product_id' => 77, 'room_name' => 'Test Ruang 261116-2', 'notes' => 'Room: Test Ruang 261116-2, ComponentID: 4']);

        $this->assertSame([561, 590, 12, 591, 39], $this->narrow(301));
    }

    public function test_a_rental_not_in_the_material_issue_leaves_the_list_untouched(): void
    {
        $this->assertSame([561, 590, 12, 591, 39], $this->narrow(555));
    }
}
