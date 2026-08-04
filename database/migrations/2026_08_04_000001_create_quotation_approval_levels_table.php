<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tiered quotation approval: each level stores the maximum discount (as a
     * percentage below the configured bottom price) that it is allowed to approve.
     * A bigger percentage means MORE authority.
     *
     * The default rows below are seeded here so a plain `php artisan migrate`
     * leaves a working ladder; they are ordinary data and may be edited or
     * deleted from the master screen afterwards.
     */
    public function up(): void
    {
        if (! Schema::hasTable('quotation_approval_levels')) {
            Schema::create('quotation_approval_levels', function (Blueprint $table) {
                $table->id();
                $table->string('level_code', 50)->unique();
                $table->string('level_name', 100);
                // Authority is derived from this column only - never from sort_order.
                $table->decimal('max_discount_percentage', 5, 2)->default(0);
                $table->string('permission_name', 150)->unique();
                $table->integer('sort_order')->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('max_discount_percentage');
                $table->index('is_active');
            });
        }

        $this->seedDefaultLevels();
    }

    public function down(): void
    {
        if (Schema::hasTable('quotation_approval_levels') && Schema::hasTable('permissions')) {
            $permissionNames = DB::table('quotation_approval_levels')->pluck('permission_name');

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            if (Schema::hasTable('role_permissions')) {
                DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            }

            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::dropIfExists('quotation_approval_levels');
    }

    private function seedDefaultLevels(): void
    {
        $now = now();

        $defaults = [
            ['code' => 'manager', 'name' => 'Manager', 'max_discount' => 20, 'sort' => 10],
            ['code' => 'gm', 'name' => 'General Manager', 'max_discount' => 50, 'sort' => 20],
            ['code' => 'director', 'name' => 'Director', 'max_discount' => 100, 'sort' => 30],
        ];

        foreach ($defaults as $default) {
            $permissionName = 'marketing.quotations.approve-level-'.$default['code'];

            // Never overwrite a percentage the user has already tuned.
            if (! DB::table('quotation_approval_levels')->where('level_code', $default['code'])->exists()) {
                DB::table('quotation_approval_levels')->insert([
                    'level_code' => $default['code'],
                    'level_name' => $default['name'],
                    'max_discount_percentage' => $default['max_discount'],
                    'permission_name' => $permissionName,
                    'sort_order' => $default['sort'],
                    'description' => 'Boleh menyetujui quotation dengan diskon sampai '.$default['max_discount'].'% di bawah bottom price.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->ensurePermission($permissionName, 'Approve quotation - '.$default['name'], $now);
        }

        // CRUD permissions for the master screen itself. Deliberately left
        // unassigned: Admin/Management bypass permission checks, and anyone else
        // should be granted this explicitly via Role & Permission.
        $crudPermissions = [
            'marketing.quotation-approval-levels' => 'Access Quotation Approval Levels',
            'marketing.quotation-approval-levels.view' => 'View Quotation Approval Levels',
            'marketing.quotation-approval-levels.create' => 'Create Quotation Approval Levels',
            'marketing.quotation-approval-levels.edit' => 'Edit Quotation Approval Levels',
            'marketing.quotation-approval-levels.delete' => 'Delete Quotation Approval Levels',
        ];

        foreach ($crudPermissions as $name => $description) {
            $this->ensurePermission($name, $description, $now);
        }
    }

    private function ensurePermission(string $name, string $description, $now): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        if (DB::table('permissions')->where('name', $name)->exists()) {
            return;
        }

        $values = [
            'name' => $name,
            'description' => $description,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('permissions', 'system_reserved')) {
            $values['system_reserved'] = false;
        }

        DB::table('permissions')->insert($values);
    }
};
