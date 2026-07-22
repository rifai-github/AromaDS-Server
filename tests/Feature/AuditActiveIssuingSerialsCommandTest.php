<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditActiveIssuingSerialsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number');
            $table->string('status');
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id');
            $table->foreignId('serial_number_id')->nullable();
        });

        DB::table('serial_numbers')->insert([
            ['id' => 1, 'serial_number' => 'SN-DUP-001'],
            ['id' => 2, 'serial_number' => 'SN-OK-002'],
        ]);

        DB::table('inventory_issuings')->insert([
            ['id' => 10, 'issuing_number' => 'JKT-WI/001', 'status' => 'pending'],
            ['id' => 11, 'issuing_number' => 'JKT-WI/002', 'status' => 'sent'],
            ['id' => 12, 'issuing_number' => 'JKT-WI/003', 'status' => 'completed'],
        ]);

        DB::table('inventory_issuing_items')->insert([
            ['id' => 20, 'inventory_issuing_id' => 10, 'serial_number_id' => 1],
            ['id' => 21, 'inventory_issuing_id' => 11, 'serial_number_id' => 1],
            ['id' => 22, 'inventory_issuing_id' => 12, 'serial_number_id' => 2],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('inventory_issuings');
        Schema::dropIfExists('serial_numbers');

        parent::tearDown();
    }

    public function test_command_reports_active_duplicates_without_changing_data(): void
    {
        $before = DB::table('inventory_issuing_items')->orderBy('id')->get()->toJson();

        $exitCode = Artisan::call('warehouse:audit-active-issuing-serials', ['--format' => 'json']);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SN-DUP-001', $output);
        $this->assertStringContainsString('JKT-WI/001 (pending)', $output);
        $this->assertStringContainsString('JKT-WI/002 (sent)', $output);
        $this->assertStringNotContainsString('SN-OK-002', $output);
        $this->assertSame($before, DB::table('inventory_issuing_items')->orderBy('id')->get()->toJson());
    }

    public function test_command_can_filter_one_serial_case_insensitively(): void
    {
        $exitCode = Artisan::call('warehouse:audit-active-issuing-serials', [
            '--serial' => 'sn-dup-001',
            '--format' => 'table',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SN-DUP-001', Artisan::output());
    }
}
