<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\WarehouseController;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Multi-warehouse per branch (docs/warehouse-opsi3-implementation-plan.md).
 *
 * Client report 17 Nov 2026: adding a 2nd warehouse to a branch was rejected
 * with "Branch ini sudah memiliki warehouse aktif" (single-warehouse rule from
 * 584fda62). Branches really do run several equivalent ready-stock warehouses,
 * so the rule becomes: any number of active warehouses per branch, exactly one
 * of them flagged `is_default` (used when the system picks a warehouse itself).
 */
class WarehouseDefaultRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_type_id')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['warehouse_products', 'serial_numbers', 'inventory_issuings', 'inventory_requests'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->decimal('quantity', 12, 2)->default(0);
                $table->string('status')->nullable();
                $table->string('location_type')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issuing_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'Jakarta'],
            ['id' => 2, 'name' => 'Surabaya'],
        ]);
    }

    private function controller(): WarehouseController
    {
        return app(WarehouseController::class);
    }

    private function jsonRequest(string $method, array $data): Request
    {
        $request = Request::create('/warehouse/warehouses', $method, $data);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        return $request;
    }

    private function payload(array $overrides = []): array
    {
        static $sequence = 0;
        $sequence++;

        return array_merge([
            'warehouse_code' => 'WH-T' . $sequence,
            'name' => 'Gudang ' . $sequence,
            'branch_id' => 1,
            'is_active' => true,
            'is_center' => false,
        ], $overrides);
    }

    private function store(array $overrides = []): JsonResponse
    {
        return $this->controller()->store($this->jsonRequest('POST', $this->payload($overrides)));
    }

    private function update(Warehouse $warehouse, array $overrides = []): JsonResponse
    {
        return $this->controller()->update($this->jsonRequest('PUT', $this->payload($overrides)), $warehouse);
    }

    private function makeWarehouse(bool $isDefault, int $branchId = 1, bool $active = true): Warehouse
    {
        return Warehouse::create([
            'warehouse_code' => 'WH-' . uniqid(),
            'name' => $isDefault ? 'Gudang default' : 'Gudang lain',
            'branch_id' => $branchId,
            'is_default' => $isDefault,
            'is_active' => $active,
            'is_center' => false,
        ]);
    }

    private function defaultIds(int $branchId): array
    {
        return Warehouse::where('branch_id', $branchId)->where('is_active', true)->where('is_default', true)->pluck('id')->all();
    }

    private function errorKeys(JsonResponse $response): array
    {
        return array_keys($response->getData(true)['errors'] ?? []);
    }

    public function test_branch_can_have_several_active_warehouses(): void
    {
        $default = $this->makeWarehouse(true);

        $second = $this->store();
        $third = $this->store();

        $this->assertSame(200, $second->getStatusCode(), $second->getContent());
        $this->assertSame(200, $third->getStatusCode(), $third->getContent());
        $this->assertSame(3, Warehouse::where('branch_id', 1)->where('is_active', true)->count());
        $this->assertSame([$default->id], $this->defaultIds(1));
    }

    public function test_first_active_warehouse_of_a_branch_becomes_default(): void
    {
        $response = $this->store(['is_default' => false]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->getData(true)['data']['is_default']);
    }

    public function test_new_default_moves_the_flag_from_the_old_one(): void
    {
        $old = $this->makeWarehouse(true);

        $response = $this->store(['is_default' => true]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertFalse($old->fresh()->is_default);
        $this->assertSame([$response->getData(true)['data']['id']], $this->defaultIds(1));
    }

    public function test_default_in_another_branch_is_untouched(): void
    {
        $otherBranchDefault = $this->makeWarehouse(true, 2);

        $this->store(['is_default' => true, 'branch_id' => 1]);

        $this->assertTrue($otherBranchDefault->fresh()->is_default);
    }

    public function test_inactive_warehouse_cannot_be_default(): void
    {
        $this->makeWarehouse(true);

        $response = $this->store(['is_default' => true, 'is_active' => false]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['is_default'], $this->errorKeys($response));
    }

    public function test_update_cannot_unset_the_default_while_siblings_are_active(): void
    {
        $default = $this->makeWarehouse(true);
        $this->makeWarehouse(false);

        $response = $this->update($default, ['is_default' => false]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['is_default'], $this->errorKeys($response));
        $this->assertTrue($default->fresh()->is_default);
    }

    public function test_only_warehouse_of_a_branch_stays_default_when_unticked(): void
    {
        $only = $this->makeWarehouse(true);

        $response = $this->update($only, ['is_default' => false]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($only->fresh()->is_default);
    }

    public function test_update_cannot_deactivate_the_default_while_siblings_are_active(): void
    {
        $default = $this->makeWarehouse(true);
        $this->makeWarehouse(false);

        $response = $this->update($default, ['is_active' => false, 'is_default' => false]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertTrue($default->fresh()->is_active);
    }

    public function test_update_setting_default_moves_the_flag(): void
    {
        $old = $this->makeWarehouse(true);
        $other = $this->makeWarehouse(false);

        $response = $this->update($other, ['is_default' => true]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame([$other->id], $this->defaultIds(1));
        $this->assertFalse($old->fresh()->is_default);
    }

    public function test_update_without_is_default_keeps_the_current_flag(): void
    {
        $default = $this->makeWarehouse(true);
        $other = $this->makeWarehouse(false);

        $this->assertSame(200, $this->update($default, ['name' => 'Gudang Jakarta'])->getStatusCode());
        $this->assertSame(200, $this->update($other, ['name' => 'Gudang BSD'])->getStatusCode());
        $this->assertSame([$default->id], $this->defaultIds(1));
    }

    public function test_edit_modal_json_booleans_are_accepted(): void
    {
        $default = $this->makeWarehouse(true);

        $response = $this->update($default, ['is_center' => false, 'is_active' => true, 'is_default' => true]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    public function test_moving_the_last_warehouse_to_an_empty_branch_makes_it_default_there(): void
    {
        $warehouse = $this->makeWarehouse(false, 1);
        Warehouse::whereKey($warehouse->id)->update(['is_default' => true]);

        $response = $this->update($warehouse->fresh(), ['branch_id' => 2]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame([$warehouse->id], $this->defaultIds(2));
    }

    public function test_set_default_moves_the_flag(): void
    {
        $old = $this->makeWarehouse(true);
        $other = $this->makeWarehouse(false);

        $this->jsonRequest('POST', []);
        $response = $this->controller()->setDefault($other);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame([$other->id], $this->defaultIds(1));
        $this->assertFalse($old->fresh()->is_default);
    }

    public function test_set_default_rejects_inactive_warehouse(): void
    {
        $this->makeWarehouse(true);
        $inactive = $this->makeWarehouse(false, 1, false);

        $this->jsonRequest('POST', []);
        $this->assertSame(422, $this->controller()->setDefault($inactive)->getStatusCode());
        $this->assertFalse($inactive->fresh()->is_default);
    }

    public function test_destroy_blocks_default_with_active_siblings_but_allows_others(): void
    {
        $default = $this->makeWarehouse(true);
        $other = $this->makeWarehouse(false);

        $this->jsonRequest('DELETE', []);
        $this->assertSame(422, $this->controller()->destroy($default)->getStatusCode());
        $this->assertTrue($default->fresh()->is_active);

        $this->jsonRequest('DELETE', []);
        $this->assertSame(200, $this->controller()->destroy($other)->getStatusCode());
        $this->assertFalse($other->fresh()->is_active);
    }

    public function test_bulk_delete_allows_default_when_the_whole_branch_goes(): void
    {
        $default = $this->makeWarehouse(true);
        $other = $this->makeWarehouse(false);

        $response = $this->controller()->bulkDelete($this->jsonRequest('POST', ['ids' => [$default->id, $other->id]]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(0, Warehouse::where('is_active', true)->count());
    }

    public function test_bulk_delete_skips_default_when_a_sibling_stays_active(): void
    {
        $default = $this->makeWarehouse(true);
        $this->makeWarehouse(false);

        $response = $this->controller()->bulkDelete($this->jsonRequest('POST', ['ids' => [$default->id]]));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertTrue($default->fresh()->is_active);
    }

    public function test_migration_marks_lowest_active_warehouse_per_branch_as_default(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });

        DB::table('warehouses')->insert([
            ['id' => 10, 'branch_id' => 1, 'name' => 'Jakarta lama', 'is_active' => false],
            ['id' => 11, 'branch_id' => 1, 'name' => 'Jakarta A', 'is_active' => true],
            ['id' => 12, 'branch_id' => 1, 'name' => 'Jakarta B', 'is_active' => true],
            ['id' => 20, 'branch_id' => 2, 'name' => 'Surabaya', 'is_active' => true],
        ]);

        $migration = require database_path('migrations/2026_11_17_000001_add_is_default_to_warehouses_table.php');
        $migration->up();

        $flags = DB::table('warehouses')->orderBy('id')->pluck('is_default', 'id')->map(fn ($v) => (bool) $v)->all();

        $this->assertSame([10 => false, 11 => true, 12 => false, 20 => true], $flags);
    }
}
