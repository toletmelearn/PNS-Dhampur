<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Escape LIKE wildcards to prevent SQL injection in LIKE queries
     * 
     * @param string $value
     * @return string
     */
    public static function escapeLikeWildcards($value)
    {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }

    /**
     * Sanitize search input for LIKE queries
     * 
     * @param string $search
     * @return string
     */
    public static function sanitizeSearchInput($search)
    {
        // Remove null bytes and trim whitespace
        $search = str_replace("\0", '', trim($search));
        
        // Escape LIKE wildcards
        return self::escapeLikeWildcards($search);
    }

    /**
     * Build safe LIKE pattern with wildcards
     * 
     * @param string $value
     * @param string $pattern ('both', 'start', 'end')
     * @return string
     */
    public static function buildLikePattern($value, $pattern = 'both')
    {
        $escaped = self::sanitizeSearchInput($value);
        
        switch ($pattern) {
            case 'start':
                return $escaped . '%';
            case 'end':
                return '%' . $escaped;
            case 'both':
            default:
                return '%' . $escaped . '%';
        }
    }
}