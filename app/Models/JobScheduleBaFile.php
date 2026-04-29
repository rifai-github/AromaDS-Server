<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobScheduleBaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_schedule_id',
        'room_id',
        'room_name',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'needed_for_invoice',
        'is_approved',
        'uploaded_by',
        'uploaded_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
        'needed_for_invoice' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // Relationships
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Methods
    public function verify($userId, $notes = null)
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'is_approved' => true,
            'updated_by' => $userId,
        ]);
    }

    public function reject($userId, $notes)
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'updated_by' => $userId,
        ]);
    }

    public function getFileUrl()
    {
        return asset($this->file_path);
    }

    public function deleteFile()
    {
        if (file_exists(public_path($this->file_path))) {
            unlink(public_path($this->file_path));
        }
        $this->delete();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match($this->verification_status) {
            'pending' => 'badge-warning',
            'verified' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->verification_status) {
            'pending' => 'Waiting Approval',
            'verified' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->verification_status),
        };
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
