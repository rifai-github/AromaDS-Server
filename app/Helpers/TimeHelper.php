<?php

if (!function_exists('formatIndonesianTime')) {
    /**
     * Format waktu ke format Indonesia (WIB/WITA/WIT)
     * 
     * @param \Carbon\Carbon|\DateTime|string $datetime
     * @param string $format
     * @param string $timezone
     * @return string
     */
    function formatIndonesianTime($datetime, $format = 'd/m/Y H:i:s', $timezone = 'Asia/Jakarta')
    {
        if (!$datetime) {
            return 'N/A';
        }

        try {
            $carbon = \Carbon\Carbon::parse($datetime);
            $carbon->setTimezone($timezone);
            
            // Determine timezone abbreviation
            $timezoneAbbr = 'WIB'; // Default Jakarta
            if ($timezone === 'Asia/Makassar') {
                $timezoneAbbr = 'WITA';
            } elseif ($timezone === 'Asia/Jayapura') {
                $timezoneAbbr = 'WIT';
            }
            
            return $carbon->format($format) . ' ' . $timezoneAbbr;
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }
}

if (!function_exists('formatAppDate')) {
    /**
     * Format tanggal standar tampilan aplikasi: dd/Mon/yyyy.
     *
     * @param \Carbon\Carbon|\DateTime|string|null $datetime
     * @param string $timezone
     * @param string $fallback
     * @return string
     */
    function formatAppDate($datetime, $timezone = 'Asia/Jakarta', $fallback = '-')
    {
        if (!$datetime) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($datetime)
                ->setTimezone($timezone)
                ->format('d/M/Y');
        } catch (\Exception $e) {
            return $fallback;
        }
    }
}

if (!function_exists('formatAppDateTime')) {
    /**
     * Format tanggal dan waktu standar tampilan aplikasi: dd/Mon/yyyy HH:mm.
     *
     * @param \Carbon\Carbon|\DateTime|string|null $datetime
     * @param string $timezone
     * @param string $fallback
     * @return string
     */
    function formatAppDateTime($datetime, $timezone = 'Asia/Jakarta', $fallback = '-')
    {
        if (!$datetime) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($datetime)
                ->setTimezone($timezone)
                ->format('d/M/Y H:i');
        } catch (\Exception $e) {
            return $fallback;
        }
    }
}

if (!function_exists('formatIndonesianDate')) {
    /**
     * Format tanggal ke format Indonesia
     * 
     * @param \Carbon\Carbon|\DateTime|string $datetime
     * @param string $timezone
     * @return string
     */
    function formatIndonesianDate($datetime, $timezone = 'Asia/Jakarta')
    {
        return formatIndonesianTime($datetime, 'd M Y', $timezone);
    }
}

if (!function_exists('formatIndonesianDateTime')) {
    /**
     * Format tanggal dan waktu ke format Indonesia
     * 
     * @param \Carbon\Carbon|\DateTime|string $datetime
     * @param string $timezone
     * @return string
     */
    function formatIndonesianDateTime($datetime, $timezone = 'Asia/Jakarta')
    {
        return formatIndonesianTime($datetime, 'd/m/Y H:i:s', $timezone);
    }
}

if (!function_exists('formatIndonesianTimeOnly')) {
    /**
     * Format waktu saja ke format Indonesia
     * 
     * @param \Carbon\Carbon|\DateTime|string $datetime
     * @param string $timezone
     * @return string
     */
    function formatIndonesianTimeOnly($datetime, $timezone = 'Asia/Jakarta')
    {
        return formatIndonesianTime($datetime, 'H:i:s', $timezone);
    }
}
