<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_schedule_id',
        'job_schedule_room_id',
        'job_schedule_unit_id',
        'photo_path',
        'photo_type',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'job_schedule_id');
    }

    public function jobScheduleRoom()
    {
        return $this->belongsTo(JobScheduleRoom::class, 'job_schedule_room_id');
    }

    // Note: approved_by column doesn't exist in job_photos table
    // public function approver()
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

    // Scopes
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByUploadedBy($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByPhotoType($query, $photoType)
    {
        return $query->where('photo_type', $photoType);
    }

    // Accessors

    public function getDisplayUpdatedAtAttribute()
    {
        return $this->updated_at ?: $this->created_at;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('uploads/' . $this->photo_path) : null;
    }

    public function getThumbnailUrlAttribute()
    {
        if (!$this->photo_path) {
            return null;
        }

        $pathInfo = pathinfo($this->photo_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        
        return asset('uploads/' . $thumbnailPath);
    }


    // Methods
    public function canDownload()
    {
        return file_exists(public_path('uploads/' . $this->photo_path));
    }

    public function canDelete()
    {
        return auth()->id() === $this->uploaded_by || auth()->user()->hasRole('admin');
    }

    public function getFileExistsAttribute()
    {
        return file_exists(public_path('uploads/' . $this->photo_path));
    }

    public function generateThumbnail()
    {
        if (!$this->fileExists) {
            return false;
        }

        $sourcePath = public_path('uploads/' . $this->photo_path);
        $pathInfo = pathinfo($this->photo_path);
        $thumbnailDir = public_path('uploads/' . $pathInfo['dirname'] . '/thumbnails');
        $thumbnailPath = $thumbnailDir . '/' . $pathInfo['basename'];

        // Create thumbnail directory if it doesn't exist
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // Generate thumbnail using GD or Imagick
        $image = imagecreatefromstring(file_get_contents($sourcePath));
        if ($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Create thumbnail (200x200 max)
            $thumbWidth = min(200, $width);
            $thumbHeight = min(200, $height);
            
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
            
            imagejpeg($thumbnail, $thumbnailPath, 80);
            imagedestroy($image);
            imagedestroy($thumbnail);
            
            return true;
        }

        return false;
    }
}
