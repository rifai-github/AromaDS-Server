<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\JobSchedule;

class JobFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_schedule_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }
}
