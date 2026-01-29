<?php
/**
 * Utility Functions
 * Helper functions for the application
 */

class Utils {
    /**
     * Generate a URL-friendly slug
     */
    public static function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    /**
     * Format duration from seconds to human readable
     */
    public static function formatDuration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'M d, Y') {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Format relative time (e.g., "2 hours ago")
     */
    public static function relativeTime($date) {
        if (empty($date)) return '';
        
        $timestamp = strtotime($date);
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            return 'Just now';
        } elseif ($difference < 3600) {
            $minutes = floor($difference / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 604800) {
            $days = floor($difference / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 2592000) {
            $weeks = floor($difference / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 31536000) {
            $months = floor($difference / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        }
        
        $years = floor($difference / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
    
    /**
     * Truncate text
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Sanitize HTML but keep allowed tags
     */
    public static function sanitize($html, $allowedTags = '<p><a><strong><em><br>') {
        return strip_tags($html, $allowedTags);
    }
    
    /**
     * Validate email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Generate random string
     */
    public static function randomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
    
    /**
     * Generate unique filename
     */
    public static function uniqueFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = self::slugify($basename);
        return $basename . '_' . time() . '_' . self::randomString(8) . '.' . $extension;
    }
    
    /**
     * Get file extension
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if file type is allowed
     */
    public static function isAllowedFileType($filename, $allowedTypes) {
        $extension = self::getFileExtension($filename);
        return in_array($extension, $allowedTypes);
    }
    
    /**
     * Format number with suffix (1K, 1M, 1B)
     */
    public static function formatNumber($num) {
        if ($num >= 1000000000) {
            return number_format($num / 1000000000, 1) . 'B';
        }
        if ($num >= 1000000) {
            return number_format($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return number_format($num / 1000, 1) . 'K';
        }
        return $num;
    }
    
    /**
     * Get content type label
     */
    public static function getContentTypeLabel($type) {
        $labels = [
            'movie' => 'Movie',
            'series' => 'Series',
            'live' => 'Live TV',
            'documentary' => 'Documentary',
            'anime' => 'Anime'
        ];
        return $labels[$type] ?? ucfirst($type);
    }
    
    /**
     * Get age rating label
     */
    public static function getAgeRatingLabel($rating) {
        $labels = [
            'G' => 'General Audiences',
            'PG' => 'Parental Guidance',
            'PG-13' => 'Parents Strongly Cautioned',
            'R' => 'Restricted',
            'NC-17' => 'Adults Only',
            'TV-Y' => 'All Children',
            'TV-PG' => 'Parental Guidance',
            'TV-14' => 'Parents Strongly Cautioned',
            'TV-MA' => 'Mature Audiences'
        ];
        return $labels[$rating] ?? $rating;
    }
    
    /**
     * Validate URL
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Clean phone number
     */
    public static function cleanPhone($phone) {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
    
    /**
     * Array map recursive
     */
    public static function arrayMapRecursive($callback, $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::arrayMapRecursive($callback, $value);
            } else {
                $array[$key] = $callback($value, $key);
            }
        }
        return $array;
    }
    
    /**
     * Flatten array
     */
    public static function flattenArray($array, $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Chunk array by size
     */
    public static function chunkArray($array, $size) {
        return array_chunk($array, $size);
    }
    
    /**
     * Remove empty values from array
     */
    public static function removeEmpty($array) {
        return array_filter($array, function ($value) {
            return $value !== '' && $value !== null;
        });
    }
    
    /**
     * Pluck array values
     */
    public static function pluck($array, $key) {
        return array_map(function ($item) use ($key) {
            return is_array($item) && isset($item[$key]) ? $item[$key] : null;
        }, $array);
    }
    
    /**
     * Get array value with default
     */
    public static function get($array, $key, $default = null) {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }
    
    /**
     * Check if associative array
     */
    public static function isAssociative($array) {
        if (empty($array)) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

