<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            if (! Schema::hasColumn('serial_numbers', 'condition_status')) {
                $table->string('condition_status', 32)
                    ->nullable()
                    ->after('status')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('serial_numbers', 'condition_status')) {
                $table->dropIndex(['condition_status']);
                $table->dropColumn('condition_status');
            }
        });
    }
};
