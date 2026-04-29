<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'message',
        'context'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    // Constants for log levels
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    // Scopes
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeEmergency($query)
    {
        return $query->where('level', self::EMERGENCY);
    }

    public function scopeAlert($query)
    {
        return $query->where('level', self::ALERT);
    }

    public function scopeCritical($query)
    {
        return $query->where('level', self::CRITICAL);
    }

    public function scopeError($query)
    {
        return $query->where('level', self::ERROR);
    }

    public function scopeWarning($query)
    {
        return $query->where('level', self::WARNING);
    }

    public function scopeNotice($query)
    {
        return $query->where('level', self::NOTICE);
    }

    public function scopeInfo($query)
    {
        return $query->where('level', self::INFO);
    }

    public function scopeDebug($query)
    {
        return $query->where('level', self::DEBUG);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function getLevelColorAttribute()
    {
        $colors = [
            self::EMERGENCY => 'red',
            self::ALERT => 'red',
            self::CRITICAL => 'red',
            self::ERROR => 'red',
            self::WARNING => 'yellow',
            self::NOTICE => 'blue',
            self::INFO => 'green',
            self::DEBUG => 'gray'
        ];

        return $colors[$this->level] ?? 'gray';
    }

    public function getLevelIconAttribute()
    {
        $icons = [
            self::EMERGENCY => 'fas fa-exclamation-triangle',
            self::ALERT => 'fas fa-exclamation-triangle',
            self::CRITICAL => 'fas fa-exclamation-triangle',
            self::ERROR => 'fas fa-times-circle',
            self::WARNING => 'fas fa-exclamation-circle',
            self::NOTICE => 'fas fa-info-circle',
            self::INFO => 'fas fa-info-circle',
            self::DEBUG => 'fas fa-bug'
        ];

        return $icons[$this->level] ?? 'fas fa-info-circle';
    }

    public function getFormattedContextAttribute()
    {
        if (!$this->context) {
            return 'No context';
        }

        return json_encode($this->context, JSON_PRETTY_PRINT);
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    public function isError()
    {
        return in_array($this->level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR]);
    }

    public function isWarning()
    {
        return $this->level === self::WARNING;
    }

    public function isInfo()
    {
        return in_array($this->level, [self::NOTICE, self::INFO]);
    }

    public function isDebug()
    {
        return $this->level === self::DEBUG;
    }

    // Static methods
    public static function getLevels()
    {
        return [
            self::EMERGENCY => 'Emergency',
            self::ALERT => 'Alert',
            self::CRITICAL => 'Critical',
            self::ERROR => 'Error',
            self::WARNING => 'Warning',
            self::NOTICE => 'Notice',
            self::INFO => 'Info',
            self::DEBUG => 'Debug'
        ];
    }

    public static function getLevelNames()
    {
        return array_keys(self::getLevels());
    }

    public static function log($level, $message, $context = [])
    {
        return self::create([
            'level' => $level,
            'message' => $message,
            'context' => $context
        ]);
    }

    public static function emergency($message, $context = [])
    {
        return self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert($message, $context = [])
    {
        return self::log(self::ALERT, $message, $context);
    }

    public static function critical($message, $context = [])
    {
        return self::log(self::CRITICAL, $message, $context);
    }

    public static function error($message, $context = [])
    {
        return self::log(self::ERROR, $message, $context);
    }

    public static function warning($message, $context = [])
    {
        return self::log(self::WARNING, $message, $context);
    }

    public static function notice($message, $context = [])
    {
        return self::log(self::NOTICE, $message, $context);
    }

    public static function info($message, $context = [])
    {
        return self::log(self::INFO, $message, $context);
    }

    public static function debug($message, $context = [])
    {
        return self::log(self::DEBUG, $message, $context);
    }
}
