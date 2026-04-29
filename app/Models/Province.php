<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'country',
        'description',
        'created_by',
        'updated_by'
    ];

    // Relationships
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }


    public function customers()
    {
        return $this->hasMany(Customer::class);
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
