<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban klien 26 Nov 2026: role "PIC Info" berlaku di Survey, SQ, dan Kontrak (halaman web dan
 * PDF), bukan hanya kontrak. Hanya deskripsi permission yang diperbarui; key
 * marketing.contract-pic-info.view sengaja TIDAK diganti (sudah dipakai role & kode).
 */
return new class extends Migration
{
    private const NAME = 'marketing.contract-pic-info.view';

    private const NEW_DESCRIPTION = 'View PIC email & phone (Survey, SQ, Contract)';

    private const OLD_DESCRIPTION = 'View PIC email & phone on contracts (PIC Info)';

    private const ROLE = 'PIC Info';

    private const NEW_ROLE_DESCRIPTION = 'Role tambahan: boleh melihat email & telepon PIC di Survey, SQ, dan Kontrak';

    private const OLD_ROLE_DESCRIPTION = 'Role tambahan: boleh melihat email & telepon PIC di kontrak';

    public function up(): void
    {
        $this->setDescription(self::NEW_DESCRIPTION, self::NEW_ROLE_DESCRIPTION);
    }

    public function down(): void
    {
        $this->setDescription(self::OLD_DESCRIPTION, self::OLD_ROLE_DESCRIPTION);
    }

    private function setDescription(string $description, string $roleDescription): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', self::NAME)
                ->update(['description' => $description, 'updated_at' => now()]);
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'description')) {
            DB::table('roles')
                ->where('name', self::ROLE)
                ->update(['description' => $roleDescription, 'updated_at' => now()]);
        }
    }
};
