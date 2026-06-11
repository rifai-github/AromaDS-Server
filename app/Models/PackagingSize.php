<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use Illuminate\Support\Str;

class PackagingSize extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    protected static function booted(): void
    {
        static::deleting(function (PackagingSize $packagingSize): void {
            if ($packagingSize->isForceDeleting()) {
                return;
            }

            $packagingSize->archiveCodeForDeletion();
        });
    }

    public static function isDeletedCode(?string $code): bool
    {
        return is_string($code) && str_contains(strtolower($code), '_deleted_');
    }

    public static function makeDeletedCode(string $code, int|string|null $id = null, CarbonInterface|string|null $deletedAt = null): string
    {
        $timestamp = $deletedAt instanceof CarbonInterface
            ? $deletedAt->format('YmdHis')
            : Carbon::parse($deletedAt ?? now())->format('YmdHis');

        $suffix = '_deleted_' . ($id !== null ? "{$id}_" : '') . $timestamp;
        $baseCode = trim($code) !== '' ? trim($code) : 'UNKNOWN';

        return Str::limit($baseCode, 255 - strlen($suffix), '') . $suffix;
    }

    public function archiveCodeForDeletion(CarbonInterface|string|null $deletedAt = null): bool
    {
        if (self::isDeletedCode($this->code)) {
            return false;
        }

        $this->forceFill([
            'code' => self::makeDeletedCode((string) $this->code, $this->getKey(), $deletedAt),
        ]);

        return $this->save();
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(MasterProduct::class, 'packaging_size_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }
}
