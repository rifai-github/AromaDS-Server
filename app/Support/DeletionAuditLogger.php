<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\AuditTrail;
use App\Traits\HasComprehensiveAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 17 Nov 2026: banyak aksi hapus/hide tidak tercatat di Audit Trail karena hanya ~51 dari
 * ~300 model yang memakai HasComprehensiveAuditTrail. Listener global ini mencatat setiap
 * penghapusan model Eloquent (soft maupun permanen) untuk model yang BELUM punya trait audit,
 * sehingga tidak tercatat dobel.
 *
 * Batasan: delete lewat query builder (Model::where(...)->delete(), DB::table()->delete())
 * tidak memicu event model, jadi tetap tidak tercatat.
 */
class DeletionAuditLogger
{
    private static ?bool $auditTableExists = null;

    /** Atribut sensitif yang tidak boleh ikut tersimpan di audit log. */
    private const HIDDEN_KEYS = ['password', 'remember_token', 'api_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    public static function handle(string $eventName, array $payload): void
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model) {
            return;
        }

        if ($model instanceof AuditLog || $model instanceof AuditTrail) {
            return;
        }

        if (in_array(HasComprehensiveAuditTrail::class, class_uses_recursive($model), true)) {
            return; // trait sudah mencatat 'deleted' sendiri
        }

        // Seluruh pencatatan di dalam try: apa pun yang gagal (termasuk Auth yang di-mock di
        // test) tidak boleh menggagalkan penghapusan itu sendiri.
        try {
            $user = Auth::user();
            if (! $user || ! self::auditTableExists()) {
                return;
            }

            $request = request();

            AuditLog::create([
                'user_id' => $user->getAuthIdentifier(),
                'action' => 'deleted',
                'model_type' => get_class($model),
                'model_id' => (int) $model->getKey(),
                'old_values' => self::snapshot($model),
                'new_values' => null,
                'changed_fields' => [],
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'page_name' => $request ? '/'.ltrim($request->path(), '/') : null,
                'module_name' => $request ? (explode('/', trim($request->path(), '/'))[0] ?: null) : null,
            ]);
        } catch (\Throwable $e) {
            // Mencatat audit tidak boleh menggagalkan penghapusan itu sendiri.
            Log::warning('DeletionAuditLogger gagal mencatat penghapusan: '.$e->getMessage(), [
                'model' => get_class($model),
                'id' => $model->getKey(),
            ]);
        }
    }

    private static function snapshot(Model $model): array
    {
        $values = [];
        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, self::HIDDEN_KEYS, true) || in_array($key, $model->getHidden(), true)) {
                continue;
            }
            if (is_string($value) && strlen($value) > 2000) {
                $value = substr($value, 0, 2000).'…';
            }
            $values[$key] = $value;
        }

        return $values;
    }

    private static function auditTableExists(): bool
    {
        if (self::$auditTableExists === null) {
            try {
                self::$auditTableExists = Schema::hasTable('audit_logs');
            } catch (\Throwable) {
                self::$auditTableExists = false;
            }
        }

        return self::$auditTableExists;
    }

    /** Hanya untuk test: reset cache keberadaan tabel. */
    public static function resetCache(): void
    {
        self::$auditTableExists = null;
    }
}
