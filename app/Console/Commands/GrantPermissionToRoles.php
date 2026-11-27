<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Master Role 27 Nov 2026: beri / cabut satu izin ke beberapa role sekaligus, tanpa membuka
 * Master Role satu per satu. Default DRY-RUN; tulis ke database hanya dengan --apply.
 *
 *   php artisan permissions:grant system.dashboard.view --role="Marketing Manager" --role="Operational Manager"
 *   php artisan permissions:grant system.dashboard.view --role="Marketing Manager" --apply
 *   php artisan permissions:grant system.dashboard.view --role="Marketing Manager" --revoke --apply
 */
class GrantPermissionToRoles extends Command
{
    protected $signature = 'permissions:grant
        {permission : Nama izin persis, mis. system.dashboard.view}
        {--role=* : Nama role persis (boleh diulang)}
        {--revoke : Cabut izin dari role, bukan memberi}
        {--apply : Benar-benar menulis ke database (tanpa ini hanya DRY-RUN)}';

    protected $description = 'Beri/cabut satu izin ke daftar role (default dry-run)';

    public function handle(): int
    {
        $name = trim((string) $this->argument('permission'));
        $roleNames = array_values(array_filter(array_map('trim', (array) $this->option('role'))));
        $revoke = (bool) $this->option('revoke');
        $apply = (bool) $this->option('apply');

        if ($roleNames === []) {
            $this->error('Sebutkan minimal satu --role="Nama Role".');

            return self::FAILURE;
        }

        $permission = DB::table('permissions')
            ->where('name', $name)
            ->when(Schema::hasColumn('permissions', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('id')
            ->first();

        if (! $permission) {
            $this->error("Izin '{$name}' tidak ada di tabel permissions. Jalankan migrasi dulu.");

            return self::FAILURE;
        }

        $this->line(($apply ? 'Mode APPLY' : 'Mode DRY-RUN (tambahkan --apply untuk menulis)')
            ." - {$name} (id {$permission->id}) - ".($revoke ? 'CABUT' : 'BERI'));

        $rows = [];
        $failed = false;
        foreach ($roleNames as $roleName) {
            $role = DB::table('roles')
                ->where('name', $roleName)
                ->when(Schema::hasColumn('roles', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->first();

            if (! $role) {
                $rows[] = [$roleName, '-', 'ROLE TIDAK DITEMUKAN'];
                $failed = true;

                continue;
            }

            $has = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();

            if ($revoke) {
                if (! $has) {
                    $rows[] = [$roleName, $role->id, 'tidak punya, dilewati'];

                    continue;
                }
                if ($apply) {
                    DB::table('role_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->delete();
                }
                $rows[] = [$roleName, $role->id, $apply ? 'DICABUT' : 'akan dicabut'];

                continue;
            }

            if ($has) {
                $rows[] = [$roleName, $role->id, 'sudah punya, dilewati'];

                continue;
            }
            if ($apply) {
                $insert = ['role_id' => $role->id, 'permission_id' => $permission->id];
                if (Schema::hasColumn('role_permissions', 'created_at')) {
                    $insert['created_at'] = now();
                    $insert['updated_at'] = now();
                }
                DB::table('role_permissions')->insert($insert);
            }
            $rows[] = [$roleName, $role->id, $apply ? 'DIBERI' : 'akan diberi'];
        }

        $this->table(['Role', 'ID', 'Hasil'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
