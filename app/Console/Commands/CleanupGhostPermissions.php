<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 17 Nov (B5) + QA 23 Nov 2026: Master Roles menampilkan permission "hantu":
 *  - NESTED: "marketing.system.salutations.view" - dibuat bug lama form role (diperbaiki 7a56d12)
 *    yang menempelkan prefix tab ke permission milik modul lain; aslinya "system.salutations.view".
 *  - TWIN: "marketing.contract-files.*" vs "marketing.contract_files.*" - dua ejaan, hanya satu
 *    yang dipakai kode.
 *  - UNUSED: resource yang tak disebut di kode sama sekali (mis. company data/files).
 *
 * Default dry-run. --apply memindahkan centang role/user dari hantu NESTED/TWIN ke permission
 * aslinya lalu menonaktifkan + soft-delete hantunya. UNUSED hanya dilaporkan; --deactivate-unused
 * menonaktifkannya (is_active=0, bisa dikembalikan), tidak menghapus.
 */
class CleanupGhostPermissions extends Command
{
    protected $signature = 'permissions:cleanup-ghosts
                            {--apply : Simpan perubahan (default dry-run)}
                            {--deactivate-unused : Nonaktifkan permission UNUSED (butuh --apply)}';

    protected $description = 'Bersihkan permission hantu di Master Roles (nested prefix, ejaan kembar, tak dipakai kode)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Mode APPLY - perubahan disimpan.' : 'Mode DRY-RUN - tidak ada yang disimpan.');

        $permissions = DB::table('permissions')
            ->when(Schema::hasColumn('permissions', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'name', 'is_active', Schema::hasColumn('permissions', 'system_reserved') ? 'system_reserved' : DB::raw('0 as system_reserved')]);
        $byName = $permissions->keyBy('name');
        $corpus = $this->codeCorpus();

        $ghosts = [];   // id => [permission, target permission, reason]
        $unused = [];

        foreach ($permissions as $permission) {
            $name = (string) $permission->name;
            $parts = explode('.', $name);

            // NESTED: "{tab}.{modul}.{resource}.{aksi}" padahal "{modul}.{resource}.{aksi}" ada.
            if (count($parts) >= 4) {
                $inner = implode('.', array_slice($parts, 1));
                if ($byName->has($inner) && ! $this->resourceUsed($name, $corpus)) {
                    $ghosts[$permission->id] = [$permission, $byName->get($inner), 'NESTED'];

                    continue;
                }
            }

            // TWIN: ejaan "-" vs "_" untuk resource yang sama; hanya satu yang disebut kode.
            $twinName = str_contains($name, '_') ? str_replace('_', '-', $name) : (str_contains($name, '-') ? str_replace('-', '_', $name) : null);
            if ($twinName && $twinName !== $name && $byName->has($twinName)) {
                $selfUsed = $this->resourceUsed($name, $corpus);
                $twinUsed = $this->resourceUsed($twinName, $corpus);
                if (! $selfUsed && $twinUsed) {
                    $ghosts[$permission->id] = [$permission, $byName->get($twinName), 'TWIN'];
                }

                continue;
            }

            if (! $permission->system_reserved && ! $this->resourceUsed($name, $corpus)) {
                $unused[] = $permission;
            }
        }

        $roleLinks = fn (int $id) => Schema::hasTable('role_permissions') ? DB::table('role_permissions')->where('permission_id', $id)->count() : 0;
        $userLinks = fn (int $id) => Schema::hasTable('user_permission') ? DB::table('user_permission')->where('permission_id', $id)->count() : 0;

        $rows = [];
        foreach ($ghosts as [$ghost, $target, $reason]) {
            $rows[] = [$reason, $ghost->id, $ghost->name, $target->name, $roleLinks((int) $ghost->id), $userLinks((int) $ghost->id)];
        }
        if ($rows) {
            $this->table(['Jenis', 'ID', 'Permission hantu', 'Dipindah ke', 'Role', 'User'], $rows);
        }

        if ($unused) {
            $this->line('');
            $this->warn('UNUSED (tak disebut kode; '.($apply && $this->option('deactivate-unused') ? 'dinonaktifkan' : 'hanya dilaporkan').'):');
            $this->table(['ID', 'Permission', 'Aktif', 'Role', 'User'], array_map(
                fn ($p) => [$p->id, $p->name, $p->is_active ? 'ya' : 'tidak', $roleLinks((int) $p->id), $userLinks((int) $p->id)],
                $unused
            ));
        }

        if ($apply) {
            DB::transaction(function () use ($ghosts, $unused) {
                foreach ($ghosts as [$ghost, $target]) {
                    $this->moveLinks('role_permissions', 'role_id', (int) $ghost->id, (int) $target->id);
                    $this->moveLinks('user_permission', 'user_id', (int) $ghost->id, (int) $target->id);
                    DB::table('permissions')->where('id', $ghost->id)->update(array_filter([
                        'is_active' => false,
                        'updated_at' => now(),
                        'deleted_at' => Schema::hasColumn('permissions', 'deleted_at') ? now() : null,
                    ], fn ($value) => $value !== null));
                    Log::info("permissions:cleanup-ghosts hapus {$ghost->name} -> {$target->name}");
                }

                if ($this->option('deactivate-unused')) {
                    DB::table('permissions')->whereIn('id', array_map(fn ($p) => $p->id, $unused))
                        ->update(['is_active' => false, 'updated_at' => now()]);
                }
            });
        }

        $this->line('');
        $this->line('Hantu (nested/kembar): '.count($ghosts).'; tak dipakai kode: '.count($unused).'.');
        if (! $apply) {
            $this->line('Dry-run selesai. Jalankan ulang dengan --apply (opsional --deactivate-unused).');
        }

        return self::SUCCESS;
    }

    /** Pindahkan centang dari permission hantu ke permission asli tanpa membuat duplikat. */
    private function moveLinks(string $table, string $ownerColumn, int $fromId, int $toId): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (DB::table($table)->where('permission_id', $fromId)->get() as $link) {
            $exists = DB::table($table)->where($ownerColumn, $link->{$ownerColumn})->where('permission_id', $toId)->exists();
            if (! $exists) {
                $insert = [$ownerColumn => $link->{$ownerColumn}, 'permission_id' => $toId];
                if (Schema::hasColumn($table, 'created_at')) {
                    $insert['created_at'] = now();
                    $insert['updated_at'] = now();
                }
                DB::table($table)->insert($insert);
            }
        }

        DB::table($table)->where('permission_id', $fromId)->delete();
    }

    /** Resource (nama tanpa aksi terakhir) disebut di kode/route/view? */
    private function resourceUsed(string $name, string $corpus): bool
    {
        $parts = explode('.', $name);
        $resource = count($parts) > 1 ? implode('.', array_slice($parts, 0, -1)) : $name;

        // Disebut sebagai string ('x' / "x"), di middleware ("permission:x", "...|x"), atau daftar
        // ("a,x"). Resource harus diikuti titik/kutip/akhir supaya "finance.pay" tak cocok "finance.payments".
        $pattern = '/[\'"|:,\s(]'.preg_quote($resource, '/').'(?=[.\'"|,\s)])/';

        return (bool) preg_match($pattern, $corpus);
    }

    private function codeCorpus(): string
    {
        $corpus = '';
        foreach ([app_path(), base_path('routes'), resource_path('views'), config_path()] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                // Command ini sendiri menyebut contoh nama hantu di komentar.
                if ($file->getRealPath() === __FILE__) {
                    continue;
                }
                $corpus .= "\n".$file->getContents();
            }
        }

        return $corpus;
    }
}
