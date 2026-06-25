<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quotation_rentals', 'top_mismatch_warning')) {
            Schema::table('quotation_rentals', function (Blueprint $table) {
                $table->string('top_mismatch_warning')->nullable()->after('requires_approval');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quotation_rentals', 'top_mismatch_warning')) {
            Schema::table('quotation_rentals', function (Blueprint $table) {
                $table->dropColumn('top_mismatch_warning');
            });
        }
    }
};
