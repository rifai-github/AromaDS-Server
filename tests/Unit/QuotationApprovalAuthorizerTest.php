<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Quotation;
use App\Models\QuotationApprovalLevel;
use App\Models\User;
use App\Services\Marketing\QuotationApprovalAuthorizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationApprovalAuthorizerTest extends TestCase
{
    private const LEGACY_PERMISSION = 'marketing.quotations.approve';

    protected function setUp(): void
    {
        parent::setUp();

        $tables = [
            'users', 'roles', 'permissions', 'user_roles', 'role_permissions', 'user_permission',
            'quotations', 'quotation_details', 'master_rentals', 'rental_bottom_prices',
            'quotation_approval_levels',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('roles')->nullable();
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

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('rental_unit')->nullable();
            $table->string('status')->default('waiting_for_approval');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('master_rental_id');
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('qty_free')->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });

        Schema::create('rental_bottom_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('offer_type')->default('bulan');
            $table->decimal('bottom_price', 15, 2);
            $table->decimal('replacement_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
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

        Permission::create(['name' => self::LEGACY_PERMISSION, 'is_active' => true]);

        foreach ([['manager', 'Manager', 20], ['gm', 'General Manager', 50], ['director', 'Director', 100]] as [$code, $name, $max]) {
            QuotationApprovalLevel::create([
                'level_code' => $code,
                'level_name' => $name,
                'max_discount_percentage' => $max,
                'is_active' => true,
            ]);
        }

        DB::table('master_rentals')->insert([
            'id' => 10, 'rental_name' => 'Rental 1x 1bln', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_a_manager_cannot_approve_a_discount_that_needs_a_director(): void
    {
        $quotation = $this->quotationWithDiscount(60); // needs Director
        $user = $this->userWithLevels('manager');

        $this->assertFalse($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_director_can_approve_the_deepest_discount(): void
    {
        $quotation = $this->quotationWithDiscount(60);
        $user = $this->userWithLevels('director');

        $this->assertTrue($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_senior_level_can_approve_what_a_junior_level_covers(): void
    {
        $quotation = $this->quotationWithDiscount(10); // needs only Manager
        $user = $this->userWithLevels('gm');

        $this->assertTrue($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_user_on_the_ladder_is_not_rescued_by_the_legacy_fallback(): void
    {
        config(['quotation.legacy_approve_is_highest' => true]);

        $quotation = $this->quotationWithDiscount(60);
        $user = $this->userWithLevels('manager');

        $this->assertFalse(
            $this->authorizer()->canApprove($user, $quotation),
            'Holding any level means the ladder decides, not the legacy flag'
        );
    }

    public function test_a_legacy_approver_without_any_level_keeps_working_while_the_flag_is_on(): void
    {
        config(['quotation.legacy_approve_is_highest' => true]);

        $quotation = $this->quotationWithDiscount(60);
        $user = $this->userWithLevels();

        $this->assertTrue($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_legacy_approver_is_blocked_once_the_flag_is_off(): void
    {
        config(['quotation.legacy_approve_is_highest' => false]);

        $quotation = $this->quotationWithDiscount(60);
        $user = $this->userWithLevels();

        $this->assertFalse($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_user_without_the_base_permission_can_never_approve(): void
    {
        $quotation = $this->quotationWithDiscount(10);
        $user = $this->userWithoutBasePermission('director');

        $this->assertFalse($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_a_quotation_that_needs_no_approval_only_needs_the_base_permission(): void
    {
        config(['quotation.legacy_approve_is_highest' => false]);

        $quotation = $this->quotationWithDiscount(0); // priced at the floor
        $user = $this->userWithLevels();

        $this->assertTrue($this->authorizer()->canApprove($user, $quotation));
    }

    public function test_it_reports_the_users_highest_level(): void
    {
        $user = $this->userWithLevels('manager', 'gm');

        $this->assertSame('gm', $this->authorizer()->highestLevelFor($user)->level_code);
    }

    private function authorizer(): QuotationApprovalAuthorizer
    {
        return app(QuotationApprovalAuthorizer::class);
    }

    /** Builds a quotation whose single line sits $discount% below a 1.000.000 floor. */
    private function quotationWithDiscount(float $discount): Quotation
    {
        $unitPrice = 1_000_000 * (1 - ($discount / 100));

        DB::table('quotations')->insert([
            'id' => 1,
            'quotation_number' => 'SBY-SQ/26-08/0001',
            'branch_id' => 2,
            'rental_unit' => 'bulan',
            'status' => 'waiting_for_approval',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotation_details')->insert([
            'quotation_id' => 1,
            'master_rental_id' => 10,
            'room_name' => 'Ruang 1',
            'quantity' => 1,
            'qty_free' => 0,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rental_bottom_prices')->insert([
            'master_rental_id' => 10,
            'branch_id' => 2,
            'offer_type' => 'bulan',
            'bottom_price' => 1_000_000,
            'replacement_price' => 5_000_000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Quotation::findOrFail(1);
    }

    private function userWithLevels(string ...$levelCodes): User
    {
        return $this->buildUser($levelCodes, true);
    }

    private function userWithoutBasePermission(string ...$levelCodes): User
    {
        return $this->buildUser($levelCodes, false);
    }

    private function buildUser(array $levelCodes, bool $withLegacyPermission): User
    {
        static $sequence = 0;
        $sequence++;

        $userId = DB::table('users')->insertGetId([
            'name' => 'Approver '.$sequence,
            'email' => 'approver'.$sequence.'@example.test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Approver Role '.$sequence,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionNames = [];

        if ($withLegacyPermission) {
            $permissionNames[] = self::LEGACY_PERMISSION;
        }

        foreach ($levelCodes as $code) {
            $permissionNames[] = QuotationApprovalLevel::where('level_code', $code)->firstOrFail()->permission_name;
        }

        foreach ($permissionNames as $permissionName) {
            $permissionId = Permission::where('name', $permissionName)->firstOrFail()->id;

            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::findOrFail($userId);
    }
}
