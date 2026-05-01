<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoice_activities MODIFY activity_type ENUM('created','sent','viewed','paid','overdue','updated','cancelled') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE invoice_activities SET activity_type = 'updated' WHERE activity_type = 'cancelled'");
        DB::statement("ALTER TABLE invoice_activities MODIFY activity_type ENUM('created','sent','viewed','paid','overdue','updated') NOT NULL");
    }
};
