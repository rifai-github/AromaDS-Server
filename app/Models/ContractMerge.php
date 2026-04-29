<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractMerge extends Model
{
    use HasFactory;

    protected $fillable = [
        'new_contract_id',
        'source_contract_id',
        'rooms_copied',
        'rentals_copied',
        'jobs_cancelled',
        'merged_by',
        'merged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
    ];

    public function newContract()
    {
        return $this->belongsTo(Contract::class, 'new_contract_id');
    }

    public function sourceContract()
    {
        return $this->belongsTo(Contract::class, 'source_contract_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
