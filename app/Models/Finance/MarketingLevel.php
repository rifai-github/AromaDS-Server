<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class MarketingLevel extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'marketing_levels';

    protected $fillable = [
        'level_code',
        'level_name',
        'sort_order',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_marketing_levels', 'marketing_level_id', 'user_id')
            ->withPivot('custom_level_id', 'effective_from', 'effective_to', 'notes')
            ->withTimestamps();
    }

    public function customUsers()
    {
        return $this->belongsToMany(User::class, 'user_marketing_levels', 'custom_level_id', 'user_id')
            ->withPivot('marketing_level_id', 'effective_from', 'effective_to', 'notes')
            ->withTimestamps();
    }

    public function contracts()
    {
        return $this->hasMany(\App\Models\Contract::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('level_code', $code);
    }
}
