<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * QA 26 Nov 2026 (JKT-IF/26-11/0003): foto bukti scan SN tampil dengan ruangan & rental milik
 * customer lain ("Hijab Mandjha Purwokerto"). Penyebabnya di upload foto sudah diperbaiki
 * (resolveJobPhotoRoomId hanya mencari di job itu sendiri); perintah ini membereskan foto lama.
 *
 * Untuk tiap foto yang job_schedule_room_id-nya menunjuk baris ruangan job LAIN (di luar job
 * foto itu dan saudara ber-nomor job sama), nilai lamanya dibaca sebagai id JobAdviceRoom —
 * itulah yang dulu dikirim APK — lalu dipetakan ke ruangan job foto itu sendiri. Bila tidak ada
 * padanannya, kolom dikosongkan (fotonya tetap menempel ke job). Dry-run default.
 */
class RepairJobPhotoForeignRooms extends Command
{
    protected $signature = 'job-photos:repair-foreign-rooms {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Perbaiki foto job yang menempel ke ruangan job lain (dry-run default)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $photos = DB::table('job_photos as p')
            ->join('job_schedule_rooms as r', 'r.id', '=', 'p.job_schedule_room_id')
            ->join('job_schedules as own', 'own.id', '=', 'p.job_schedule_id')
            ->join('job_schedules as other', 'other.id', '=', 'r.job_schedule_id')
            ->whereColumn('r.job_schedule_id', '!=', 'p.job_schedule_id')
            ->where(function ($query) {
                $query->whereNull('own.job_number')
                    ->orWhereNull('other.job_number')
                    ->orWhereColumn('own.job_number', '!=', 'other.job_number');
            })
            ->select('p.id', 'p.job_schedule_id', 'p.job_schedule_room_id', 'own.job_number', 'r.room_name as wrong_room')
            ->orderBy('p.id')
            ->get();

        $remapped = 0;
        $nulled = 0;
        $rows = [];

        foreach ($photos as $photo) {
            $scope = [(int) $photo->job_schedule_id];
            if ($photo->job_number) {
                $scope = array_merge($scope, DB::table('job_schedules')->where('job_number', $photo->job_number)->pluck('id')->map(fn ($id) => (int) $id)->all());
            }

            $target = DB::table('job_schedule_rooms')
                ->where('job_advice_room_id', $photo->job_schedule_room_id)
                ->whereIn('job_schedule_id', $scope)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN job_schedule_id = ? THEN 0 ELSE 1 END', [$photo->job_schedule_id])
                ->orderBy('id')
                ->value('id');

            if (! $target) {
                $target = DB::table('job_schedule_room_rentals as rr')
                    ->join('job_schedule_rooms as r', 'r.id', '=', 'rr.job_schedule_room_id')
                    ->where('rr.job_advice_room_id', $photo->job_schedule_room_id)
                    ->whereIn('r.job_schedule_id', $scope)
                    ->whereNull('rr.deleted_at')
                    ->value('r.id');
            }

            // Job yang hanya punya satu ruangan: foto itu pasti milik ruangan tersebut
            // (prod 26 Nov: JKT-CSR/26-10/0001 hanya "Ruang Periksa Simo", fotonya tertulis LOBBY).
            if (! $target) {
                $ownRooms = DB::table('job_schedule_rooms')
                    ->where('job_schedule_id', $photo->job_schedule_id)
                    ->whereNull('deleted_at')
                    ->pluck('id');
                if ($ownRooms->count() === 1) {
                    $target = (int) $ownRooms->first();
                }
            }

            $newRoom = $target ? DB::table('job_schedule_rooms')->where('id', $target)->value('room_name') : null;
            $rows[] = [$photo->id, $photo->job_number ?: ('job#'.$photo->job_schedule_id), $photo->wrong_room, $target ? "{$newRoom} (#{$target})" : '(dikosongkan)'];
            $target ? $remapped++ : $nulled++;

            if ($apply) {
                DB::table('job_photos')->where('id', $photo->id)->update([
                    'job_schedule_room_id' => $target ?: null,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->table(['Foto', 'Job', 'Ruangan salah', 'Menjadi'], $rows);
        $this->info(($apply ? 'Diperbaiki' : 'Akan diperbaiki').": {$remapped} dipetakan ulang, {$nulled} dikosongkan (dari ".$photos->count().' foto).');

        if (! $apply && $photos->isNotEmpty()) {
            $this->warn('Dry-run: belum ada yang ditulis. Jalankan dengan --apply.');
        }

        return self::SUCCESS;
    }
}
