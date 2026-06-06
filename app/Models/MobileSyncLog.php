<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSyncLog extends Model
{
    protected $fillable = [
        'user_id',
        'job_schedule_id',
        'job_schedule_room_id',
        'action',
        'idempotency_key',
        'client_clicked_at',
        'client_delivery_mode',
        'client_queued_at',
        'client_synced_at',
        'server_received_at',
        'sync_status',
        'payload_hash',
        'error_message',
    ];

    protected $casts = [
        'client_clicked_at' => 'datetime',
        'client_queued_at' => 'datetime',
        'client_synced_at' => 'datetime',
        'server_received_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function jobScheduleRoom(): BelongsTo
    {
        return $this->belongsTo(JobScheduleRoom::class);
    }
}
