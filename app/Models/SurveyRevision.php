<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Arsip versi survey yang ditolak sebelum dibuka lagi sebagai revisi (jawaban klien #17).
 */
class SurveyRevision extends Model
{
    protected $fillable = [
        'survey_id',
        'revision_number',
        'status_before',
        'rejection_reason',
        'snapshot',
        'created_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'revision_number' => 'integer',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
