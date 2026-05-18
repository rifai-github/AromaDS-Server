<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('serial_numbers_serial_number_unique')) {
            DB::statement('ALTER TABLE serial_numbers DROP INDEX serial_numbers_serial_number_unique');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('serial_numbers_serial_number_unique')) {
            return;
        }

        $duplicates = DB::table('serial_numbers')
            ->select('serial_number')
            ->groupBy('serial_number')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if (! $duplicates) {
            DB::statement('ALTER TABLE serial_numbers ADD UNIQUE INDEX serial_numbers_serial_number_unique (serial_number)');
        }
    }

    private function indexExists(string $indexName): bool
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'serial_numbers')
            ->where('index_name', $indexName)
            ->exists();
    }
};
