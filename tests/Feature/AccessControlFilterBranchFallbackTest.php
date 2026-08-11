<?php

namespace Tests\Feature;

use App\Http\Traits\AccessControlFilterTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA bug: a "Warehouse Pusat Manager" user (branch_id set on the user row, but
 * no explicit user_access_levels row) could act on an Inventory Transfer's
 * warehouse (InventoryTransfer::userCanActForWarehouse() already treats
 * users.branch_id as authoritative) but the resulting Inventory Receiving
 * never showed up in their list - AccessControlFilterTrait only granted
 * branch-scoped visibility when an explicit access_levels "branch" row
 * existed, silently dropping the users.branch_id fallback the rest of the
 * warehouse module already relies on.
 */
class AccessControlFilterBranchFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('data_restriction')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('access_type');
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Deliberately no 'roles' table: hasUnrestrictedAccessControlData() skips
        // its hasRole()/hasRoleStartingWith() checks entirely when it's absent,
        // which is exactly the state of a plain non-Admin/Management role user.

        // Needed unconditionally by applyAccessControlFilter()'s warehouse
        // manager/admin lookup, even though this test doesn't exercise it.
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->timestamps();
        });

        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('records');
        Schema::dropIfExists('warehouse_admins');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('user_access_levels');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    private function recordModel(): Model
    {
        return new class extends Model
        {
            protected $table = 'records';

            protected $guarded = [];

            public $timestamps = true;
        };
    }

    private function filterAsController($query, User $user)
    {
        $controller = new class
        {
            use AccessControlFilterTrait;

            public function run($query, User $user)
            {
                return $this->applyAccessControlFilter($query, $user, 'created_by', 'created_by', 'branch_id');
            }
        };

        return $controller->run($query, $user);
    }

    public function test_user_without_an_explicit_branch_access_level_still_sees_own_branch_data(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Sender', 'branch_id' => 24]);
        // The reported case: role "Warehouse Pusat Manager", branch_id set on
        // the user row, zero user_access_levels rows.
        DB::table('users')->insert(['id' => 2, 'name' => 'Logistik 04', 'branch_id' => 24]);

        DB::table('records')->insert(['id' => 100, 'created_by' => 1, 'branch_id' => 24]);
        DB::table('records')->insert(['id' => 200, 'created_by' => 1, 'branch_id' => 99]);

        $viewer = User::findOrFail(2);
        $visibleIds = $this->filterAsController($this->recordModel()->newQuery(), $viewer)
            ->pluck('id')
            ->all();

        $this->assertSame([100], $visibleIds, 'Should see the record in their own branch, and only that one.');
    }

    public function test_user_with_no_branch_id_at_all_sees_only_their_own_records(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Sender', 'branch_id' => 24]);
        DB::table('users')->insert(['id' => 3, 'name' => 'No branch assigned', 'branch_id' => null]);

        DB::table('records')->insert(['id' => 100, 'created_by' => 1, 'branch_id' => 24]);
        DB::table('records')->insert(['id' => 101, 'created_by' => 3, 'branch_id' => null]);

        $viewer = User::findOrFail(3);
        $visibleIds = $this->filterAsController($this->recordModel()->newQuery(), $viewer)
            ->pluck('id')
            ->all();

        $this->assertSame([101], $visibleIds, 'No branch_id to fall back to - only their own records, same as before this fix.');
    }

    public function test_multi_branch_pivot_assignment_wins_over_the_single_branch_id_fallback(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Sender A', 'branch_id' => 24]);
        // Multi-Branch assignment page (BranchUserController::updateUserBranches)
        // stamps only the primary branch onto users.branch_id, even though this
        // user is assigned to branches 24, 55, and 99 via the branch_user pivot.
        DB::table('users')->insert(['id' => 5, 'name' => 'Multi-branch gudang manager', 'branch_id' => 24]);
        DB::table('branch_user')->insert(['branch_id' => 24, 'user_id' => 5, 'is_primary' => true]);
        DB::table('branch_user')->insert(['branch_id' => 55, 'user_id' => 5, 'is_primary' => false]);
        DB::table('branch_user')->insert(['branch_id' => 99, 'user_id' => 5, 'is_primary' => false]);

        DB::table('records')->insert(['id' => 100, 'created_by' => 1, 'branch_id' => 24]);
        DB::table('records')->insert(['id' => 200, 'created_by' => 1, 'branch_id' => 55]);
        DB::table('records')->insert(['id' => 300, 'created_by' => 1, 'branch_id' => 99]);
        DB::table('records')->insert(['id' => 400, 'created_by' => 1, 'branch_id' => 77]);

        $viewer = User::findOrFail(5);
        $visibleIds = $this->filterAsController($this->recordModel()->newQuery(), $viewer)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([100, 200, 300], $visibleIds, 'Should see records across all pivot-assigned branches, not just the primary one stamped onto users.branch_id.');
    }

    public function test_explicit_branch_access_level_config_still_wins_over_the_fallback(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Sender A', 'branch_id' => 24]);
        DB::table('users')->insert(['id' => 4, 'name' => 'Explicit multi-branch viewer', 'branch_id' => 24]);
        DB::table('user_access_levels')->insert([
            'user_id' => 4,
            'access_type' => 'branch',
            'access_config' => json_encode(['allowed_branches' => [24, 99]]),
            'is_active' => true,
        ]);

        DB::table('records')->insert(['id' => 100, 'created_by' => 1, 'branch_id' => 24]);
        DB::table('records')->insert(['id' => 200, 'created_by' => 1, 'branch_id' => 99]);
        DB::table('records')->insert(['id' => 300, 'created_by' => 1, 'branch_id' => 77]);

        $viewer = User::findOrFail(4);
        $visibleIds = $this->filterAsController($this->recordModel()->newQuery(), $viewer)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([100, 200], $visibleIds, 'Explicit allowed_branches config must still be honored, not overridden by the fallback.');
    }
}
