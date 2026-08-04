<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One rung of the quotation approval ladder.
 *
 * `max_discount_percentage` is how far below the configured bottom price this
 * level is allowed to approve. A BIGGER number means MORE authority
 * (Director 100% may approve anything, Manager 20% only shallow discounts).
 *
 * Authority is derived from that column alone - never from `sort_order`, which
 * only controls display order, and never from the level name.
 */
class QuotationApprovalLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'level_code',
        'level_name',
        'max_discount_percentage',
        'permission_name',
        'sort_order',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'max_discount_percentage' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public const PERMISSION_PREFIX = 'marketing.quotations.approve-level-';

    protected static function booted(): void
    {
        static::creating(function (self $level) {
            $level->permission_name = $level->permission_name ?: static::permissionNameFor($level->level_code);
        });

        static::created(function (self $level) {
            $level->syncPermissionRecord();
        });

        // Renaming a level must never disturb which roles hold it.
        static::updated(function (self $level) {
            $level->syncPermissionRecord();
        });

        static::deleted(function (self $level) {
            $level->togglePermissionActive(false);
        });

        static::restored(function (self $level) {
            $level->togglePermissionActive(true);
        });
    }

    public static function permissionNameFor(string $levelCode): string
    {
        return static::PERMISSION_PREFIX.str($levelCode)->slug()->value();
    }

    // ---------------------------------------------------------------------
    // Rule direction lives HERE and nowhere else.
    //
    // If the business ever restates the rule (e.g. back to "minimum price as a
    // percentage of bottom price"), these three methods are the only thing that
    // changes - the evaluator, the authorizer and the Blade views all go
    // through them.
    // ---------------------------------------------------------------------

    /**
     * Lowest level that is still allowed to approve the given discount.
     * Returns null when no active level covers it (fail-closed).
     */
    public static function resolveForDiscount(float $discountPercentage): ?self
    {
        return static::query()
            ->active()
            ->where('max_discount_percentage', '>=', round($discountPercentage, 4))
            ->orderBy('max_discount_percentage')
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Permission names of every level able to approve the given discount.
     * A user holding any one of these is authorised.
     */
    public static function permissionNamesCovering(float $discountPercentage): array
    {
        return static::query()
            ->active()
            ->where('max_discount_percentage', '>=', round($discountPercentage, 4))
            ->pluck('permission_name')
            ->all();
    }

    /** The most senior active level - used when a discount cannot be computed. */
    public static function highest(): ?self
    {
        return static::query()
            ->active()
            ->orderByDesc('max_discount_percentage')
            ->orderBy('sort_order')
            ->first();
    }

    // ---------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('max_discount_percentage');
    }

    public function permission(): ?Permission
    {
        if (! $this->permission_name) {
            return null;
        }

        return Permission::withTrashed()->where('name', $this->permission_name)->first();
    }

    /** Roles currently holding this level, read straight from role_permissions. */
    public function roles()
    {
        $permission = $this->permission();

        if (! $permission) {
            return Role::query()->whereRaw('1 = 0');
        }

        return Role::query()
            ->whereIn('id', RolePermission::query()
                ->where('permission_id', $permission->id)
                ->select('role_id'));
    }

    public function roleIds(): array
    {
        $permission = $this->permission();

        if (! $permission) {
            return [];
        }

        return RolePermission::query()
            ->where('permission_id', $permission->id)
            ->pluck('role_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Attach/detach this level's permission so it matches $roleIds exactly.
     * Touches only this level's permission - other permissions held by those
     * roles are left alone.
     */
    public function syncRoles(array $roleIds): void
    {
        $permission = $this->syncPermissionRecord();

        if (! $permission) {
            return;
        }

        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $current = $this->roleIds();

        $toDetach = array_diff($current, $roleIds);
        $toAttach = array_diff($roleIds, $current);

        if ($toDetach) {
            RolePermission::query()
                ->where('permission_id', $permission->id)
                ->whereIn('role_id', $toDetach)
                ->delete();
        }

        foreach ($toAttach as $roleId) {
            RolePermission::query()->updateOrCreate([
                'role_id' => $roleId,
                'permission_id' => $permission->id,
            ]);
        }
    }

    /** Create the backing permission row if missing, keep its description fresh. */
    public function syncPermissionRecord(): ?Permission
    {
        if (! $this->permission_name) {
            return null;
        }

        $permission = Permission::withTrashed()->where('name', $this->permission_name)->first();

        if ($permission) {
            if ($permission->trashed()) {
                $permission->restore();
            }

            $permission->update([
                'description' => $this->permissionDescription(),
                'is_active' => (bool) $this->is_active,
            ]);

            return $permission;
        }

        return Permission::create([
            'name' => $this->permission_name,
            'description' => $this->permissionDescription(),
            'is_active' => (bool) $this->is_active,
            'system_reserved' => false,
        ]);
    }

    private function permissionDescription(): string
    {
        return 'Approve quotation - '.$this->level_name.' (maks diskon '.rtrim(rtrim(number_format((float) $this->max_discount_percentage, 2, '.', ''), '0'), '.').'%)';
    }

    private function togglePermissionActive(bool $isActive): void
    {
        $permission = $this->permission();

        // Role assignments are deliberately kept so a restore is lossless.
        $permission?->update(['is_active' => $isActive]);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
