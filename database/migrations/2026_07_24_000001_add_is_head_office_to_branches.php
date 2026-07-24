<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'is_head_office')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('is_head_office')->default(false)->after('address_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'is_head_office')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('is_head_office');
            });
        }
    }
};
