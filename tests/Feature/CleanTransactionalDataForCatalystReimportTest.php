<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Import ulang Catalyst 29 Nov 2026: data transaksi dikosongkan, tetapi user, role, dan master
 * tidak boleh ikut terhapus (permintaan tim, WA 25 Nov). Tabel yang tidak diklasifikasi tidak
 * pernah disentuh.
 */
class CleanTransactionalDataForCatalystReimportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customers', 'contracts', 'roles', 'teams', 'mystery_table'] as $table) {
            Schema::dropIfExists($table);
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
            });
        }
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
            });
        }
        Schema::dropIfExists('source_import_maps');
        Schema::create('source_import_maps', function (Blueprint $t) {
            $t->id();
            $t->string('source_table');
            $t->string('source_key');
            $t->string('target_table');
            $t->unsignedBigInteger('target_id');
        });

        DB::table('customers')->insert([['name' => 'PT A'], ['name' => 'PT B']]);
        DB::table('contracts')->insert(['name' => 'ADS-AG/26-10/0074']);
        DB::table('roles')->insert(['name' => 'Teknisi']);
        DB::table('teams')->insert(['name' => 'Tim Service Ops']);
        DB::table('mystery_table')->insert(['name' => 'jangan dihapus']);
        DB::table('users')->insert(['name' => 'Hengky']);
        DB::table('source_import_maps')->insert([
            ['source_table' => 'MsCustomer', 'source_key' => 'C1', 'target_table' => 'customers', 'target_id' => 1],
            ['source_table' => 'MKTContractHd', 'source_key' => 'K1', 'target_table' => 'contracts', 'target_id' => 1],
            ['source_table' => 'MsEmployee', 'source_key' => 'E1', 'target_table' => 'users', 'target_id' => 1],
        ]);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->artisan('catalyst:clean-transactional-data')->assertSuccessful();

        $this->assertSame(2, DB::table('customers')->count());
        $this->assertSame(1, DB::table('contracts')->count());
        $this->assertSame(3, DB::table('source_import_maps')->count());
    }

    public function test_apply_clears_transactions_and_keeps_users_roles_masters_and_unknown_tables(): void
    {
        $this->artisan('catalyst:clean-transactional-data', ['--apply' => true, '--force' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('customers')->count());
        $this->assertSame(0, DB::table('contracts')->count());

        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(1, DB::table('roles')->count());
        $this->assertSame(1, DB::table('teams')->count());
        $this->assertSame(1, DB::table('mystery_table')->count(), 'Tabel yang tidak diklasifikasi tidak boleh dihapus.');

        // Peta user tetap ada (import berikutnya mengenali user lama), peta customer/kontrak dihapus.
        $this->assertSame(['users'], DB::table('source_import_maps')->pluck('target_table')->all());
    }

    public function test_master_tables_are_never_in_the_clear_list(): void
    {
        $clear = \App\Console\Commands\CleanTransactionalDataForCatalystReimport::CLEAR_TABLES;

        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'user_access_levels', 'branches',
            'departments', 'teams', 'team_members', 'companies', 'master_products', 'master_rentals', 'rental_details',
            'rental_components', 'master_options', 'option_details', 'source_import_maps', 'migrations'] as $master) {
            $this->assertNotContains($master, $clear, "{$master} tidak boleh dikosongkan.");
        }

        $this->assertSame([], array_values(array_intersect(
            $clear,
            \App\Console\Commands\CleanTransactionalDataForCatalystReimport::KEEP_TABLES
        )));
    }
}
