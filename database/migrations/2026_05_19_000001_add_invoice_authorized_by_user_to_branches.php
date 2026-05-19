<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'invoice_authorized_by_user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreignId('invoice_authorized_by_user_id')
                    ->nullable()
                    ->after('is_taxable')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'invoice_authorized_by_user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_authorized_by_user_id');
            });
        }
    }
};
