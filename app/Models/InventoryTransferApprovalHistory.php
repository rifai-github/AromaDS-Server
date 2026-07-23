<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferApprovalHistory extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'action',
        'actor_id',
        'notes',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function transfer()
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
