<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ContractFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'file_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
        'uploaded_at',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Methods
    public function verify($userId, $notes = null)
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    public function reject($userId, $notes)
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
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
}
