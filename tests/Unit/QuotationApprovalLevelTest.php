<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\QuotationApprovalLevel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationApprovalLevelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotation_approval_levels', 'permissions', 'roles', 'role_permissions'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('quotation_approval_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level_code', 50)->unique();
            $table->string('level_name', 100);
            $table->decimal('max_discount_percentage', 5, 2)->default(0);
            $table->string('permission_name', 150)->unique();
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Senior GM', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Marketing Manager', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_creating_a_level_generates_its_backing_permission(): void
    {
        $level = $this->makeLevel('senior-gm', 'Senior GM', 75);

        $this->assertSame('marketing.quotations.approve-level-senior-gm', $level->permission_name);
        $this->assertNotNull(Permission::where('name', $level->permission_name)->first());
    }

    public function test_sync_roles_attaches_and_detaches_only_this_levels_permission(): void
    {
        $level = $this->makeLevel('senior-gm', 'Senior GM', 75);
        $other = $this->makeLevel('manager', 'Manager', 20);

        // An unrelated permission held by the same role must survive.
        $other->syncRoles([1]);
        $level->syncRoles([1, 2]);

        $this->assertEqualsCanonicalizing([1, 2], $level->roleIds());
        $this->assertSame([1], $other->roleIds());

        $level->syncRoles([2]);

        $this->assertSame([2], $level->roleIds());
        $this->assertSame([1], $other->roleIds(), 'Detaching one level must not disturb another');
    }

    public function test_roles_granted_directly_in_role_permissions_are_visible_to_the_level(): void
    {
        $level = $this->makeLevel('director', 'Director', 100);
        $permission = Permission::where('name', $level->permission_name)->firstOrFail();

        // Simulates an admin ticking the box in the Role & Permission screen.
        DB::table('role_permissions')->insert([
            'role_id' => 2,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame([2], $level->roleIds());
    }

    public function test_renaming_a_level_keeps_its_role_assignments(): void
    {
        $level = $this->makeLevel('gm', 'General Manager', 50);
        $level->syncRoles([1]);

        $level->update(['level_name' => 'Group General Manager']);

        $this->assertSame([1], $level->fresh()->roleIds());
    }

    public function test_soft_deleting_deactivates_the_permission_but_keeps_role_links(): void
    {
        $level = $this->makeLevel('gm', 'General Manager', 50);
        $level->syncRoles([1]);

        $level->delete();

        $permission = Permission::where('name', $level->permission_name)->firstOrFail();
        $this->assertFalse((bool) $permission->is_active);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => 1,
            'permission_id' => $permission->id,
        ]);

        $level->restore();

        $this->assertTrue((bool) Permission::where('name', $level->permission_name)->firstOrFail()->is_active);
    }

    public function test_resolve_for_discount_picks_the_lowest_sufficient_level(): void
    {
        $this->makeLevel('manager', 'Manager', 20);
        $this->makeLevel('gm', 'General Manager', 50);
        $this->makeLevel('director', 'Director', 100);

        $this->assertSame('manager', QuotationApprovalLevel::resolveForDiscount(15)->level_code);
        $this->assertSame('manager', QuotationApprovalLevel::resolveForDiscount(20)->level_code);
        $this->assertSame('gm', QuotationApprovalLevel::resolveForDiscount(20.01)->level_code);
        $this->assertSame('director', QuotationApprovalLevel::resolveForDiscount(99)->level_code);
        $this->assertSame('director', QuotationApprovalLevel::resolveForDiscount(100)->level_code);
    }

    public function test_a_level_inserted_between_two_others_takes_over_its_band(): void
    {
        $this->makeLevel('manager', 'Manager', 20);
        $this->makeLevel('gm', 'General Manager', 50);
        $this->makeLevel('director', 'Director', 100);

        $this->assertSame('director', QuotationApprovalLevel::resolveForDiscount(60)->level_code);

        // Adding a rung must never take authority away from a senior level.
        $this->makeLevel('senior-gm', 'Senior GM', 75);

        $this->assertSame('senior-gm', QuotationApprovalLevel::resolveForDiscount(60)->level_code);
        $this->assertGreaterThanOrEqual(
            60,
            (float) QuotationApprovalLevel::where('level_code', 'director')->first()->max_discount_percentage
        );
    }

    public function test_inactive_levels_are_ignored(): void
    {
        $this->makeLevel('manager', 'Manager', 20);
        $this->makeLevel('director', 'Director', 100, isActive: false);

        $this->assertNull(QuotationApprovalLevel::resolveForDiscount(80));
        $this->assertSame('manager', QuotationApprovalLevel::highest()->level_code);
    }

    private function makeLevel(string $code, string $name, float $maxDiscount, bool $isActive = true): QuotationApprovalLevel
    {
        return QuotationApprovalLevel::create([
            'level_code' => $code,
            'level_name' => $name,
            'max_discount_percentage' => $maxDiscount,
            'is_active' => $isActive,
        ]);
    }
}
