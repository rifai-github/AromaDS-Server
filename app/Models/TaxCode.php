<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'rate',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate' => 'decimal:2'
    ];
}
