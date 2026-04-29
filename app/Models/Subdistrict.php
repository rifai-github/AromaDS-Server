<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subdistrict extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'district_id',
        'name',
        'postal_code'
    ];

    // Relationships
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

}
