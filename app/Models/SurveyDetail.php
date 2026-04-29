<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;

class SurveyDetail extends Model
{
    use HasFactory, AutoFilterable;

    protected $fillable = [
        'survey_id',
        'room_name',
        'room_id', // Add room_id to fillable
        'room_type',
        'room_area',
        'quantity_needed',
        'specifications',
        'created_by',
        'updated_by'
    ];

    protected $appends = []; // Removed room_display_name

    protected $casts = [
        'room_area' => 'decimal:2',
        'quantity_needed' => 'integer'
    ];

    // Relationships
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function getRoomNameAttribute($value)
    {
        // Option 1: Prefer real-time master room name if available
        if ($this->room) {
            return $this->room->room_name;
        }
        
        // Fallback to snapshot name
        return $value;
    }

    public function getRoomTypeAttribute($value)
    {
        if ($this->room) {
            return $this->room->room_type;
        }
        return $value;
    }

    public function getQuantityNeededAttribute($value)
    {
        if ($this->room) {
            return $this->room->room_qty;
        }
        return $value;
    }

    // Since the view decodes the raw JSON specifications, we must return a JSON string
    // that contains the updated values from Master Room
    public function getSpecificationsAttribute($value)
    {
        if ($this->room) {
            $specs = json_decode($value ?? '{}', true);
            $room = $this->room;
            
            // Override keys with Master Room data
            $specs['floor'] = $room->room_floor;
            $specs['intensity'] = $room->room_intensity;
            $specs['installation_type'] = $room->room_installation_type;
            $specs['qty'] = $room->room_qty;
            $specs['length'] = $room->room_length;
            $specs['width'] = $room->room_width;
            $specs['height'] = $room->room_height;
            $specs['temperature'] = $room->room_temperature;
            $specs['remark'] = $room->room_remark;
            
            return json_encode($specs);
        }
        
        return $value;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getFormattedRoomAreaAttribute()
    {
        return $this->room_area ? $this->room_area . ' m²' : '-';
    }

    public function getTotalAreaAttribute()
    {
        return $this->room_area * $this->quantity_needed;
    }

    public function getFormattedTotalAreaAttribute()
    {
        return $this->total_area . ' m²';
    }
}
