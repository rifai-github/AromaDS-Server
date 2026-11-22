<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MOM 19 Nov 2026 p6: log suspend/unsuspend room kontrak. Baris aktif = belum di-unsuspend.
 * Suspend berlaku mulai suspend_date (bisa dijadwalkan ke depan).
 */
class ContractRoomSuspension extends Model
{
    protected $fillable = [
        'contract_id',
        'contract_room_id',
        'room_id',
        'suspend_date',
        'suspended_by',
        'suspended_at',
        'unsuspended_by',
        'unsuspended_at',
        'notes',
    ];

    protected $casts = [
        'suspend_date' => 'date',
        'suspended_at' => 'datetime',
        'unsuspended_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class);
    }

    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function unsuspendedBy()
    {
        return $this->belongsTo(User::class, 'unsuspended_by');
    }

    /** Belum di-unsuspend (termasuk yang dijadwalkan mulai nanti). */
    public function scopeOpen($query)
    {
        return $query->whereNull('unsuspended_at');
    }

    /** Sedang berlaku pada tanggal tertentu (default hari ini). */
    public function scopeInEffect($query, $date = null)
    {
        $date = ($date ? \Illuminate\Support\Carbon::parse($date) : now())->toDateString();

        return $query->whereNull('unsuspended_at')->whereDate('suspend_date', '<=', $date);
    }
}
