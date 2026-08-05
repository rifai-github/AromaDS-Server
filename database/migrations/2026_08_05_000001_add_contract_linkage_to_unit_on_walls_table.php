<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_on_walls')) {
            return;
        }

        $columns = [
            'contract_id',
            'contract_room_id',
            'install_job_schedule_id',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('unit_on_walls', $column)) {
                continue;
            }

            Schema::table('unit_on_walls', function (Blueprint $table) use ($column) {
                match ($column) {
                    'contract_id' => $table->unsignedBigInteger('contract_id')->nullable()->after('customer_id'),
                    'contract_room_id' => $table->unsignedBigInteger('contract_room_id')->nullable()->after('contract_id'),
                    'install_job_schedule_id' => $table->unsignedBigInteger('install_job_schedule_id')->nullable()->after('contract_room_id'),
                };
            });
        }

        foreach ([
            ['contract_id', 'uow_contract_idx'],
            ['contract_room_id', 'uow_contract_room_idx'],
            ['install_job_schedule_id', 'uow_install_job_idx'],
        ] as [$column, $index]) {
            if (Schema::hasColumn('unit_on_walls', $column)
                && ! Schema::hasIndex('unit_on_walls', [$column])) {
                Schema::table('unit_on_walls', function (Blueprint $table) use ($column, $index) {
                    $table->index($column, $index);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_on_walls')) {
            return;
        }

        foreach ([
            'install_job_schedule_id',
            'contract_room_id',
            'contract_id',
        ] as $column) {
            if (! Schema::hasColumn('unit_on_walls', $column)) {
                continue;
            }

            if (Schema::hasIndex('unit_on_walls', [$column])) {
                Schema::table('unit_on_walls', function (Blueprint $table) use ($column) {
                    $table->dropIndex([$column]);
                });
            }

            Schema::table('unit_on_walls', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
