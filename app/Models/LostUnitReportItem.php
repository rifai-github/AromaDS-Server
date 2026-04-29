<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LostUnitReportItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lost_unit_report_id',
        'room_id',
        'master_rental_id',
        'price'
    ];

    public function report()
    {
        return $this->belongsTo(LostUnitReport::class, 'lost_unit_report_id');
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class, 'master_rental_id');
    }
}
