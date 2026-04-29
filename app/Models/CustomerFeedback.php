<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'feedback_type',
        'rating',
        'comments'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('feedback_type', $type);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRating($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getRatingStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getRatingTextAttribute()
    {
        $ratings = [
            1 => 'Very Poor',
            2 => 'Poor',
            3 => 'Average',
            4 => 'Good',
            5 => 'Excellent'
        ];
        
        return $ratings[$this->rating] ?? 'Unknown';
    }

    public function getShortCommentsAttribute()
    {
        return strlen($this->comments) > 100 
            ? substr($this->comments, 0, 100) . '...' 
            : $this->comments;
    }

    // Static Methods
    public static function getFeedbackTypes()
    {
        return [
            'service' => 'Service Quality',
            'product' => 'Product Quality',
            'support' => 'Customer Support',
            'delivery' => 'Delivery Service',
            'billing' => 'Billing Process',
            'general' => 'General Feedback'
        ];
    }

    public static function getAverageRating($customerId = null)
    {
        $query = static::query();
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        return $query->avg('rating');
    }
}
