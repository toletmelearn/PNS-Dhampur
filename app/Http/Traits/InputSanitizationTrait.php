<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait InputSanitizationTrait
{
    /**
     * Sanitize string input to prevent XSS and injection attacks
     *
     * @param string|null $input
     * @param array $options
     * @return string|null
     */
    protected function sanitizeString(?string $input, array $options = []): ?string
    {
        if ($input === null) {
            return null;
        }

        $options = array_merge([
            'strip_tags' => true,
            'trim' => true,
            'remove_special_chars' => false,
            'max_length' => null,
            'allow_html' => false,
            'allowed_tags' => '<p><br><strong><em><ul><ol><li>',
        ], $options);

        // Trim whitespace
        if ($options['trim']) {
            $input = trim($input);
        }

        // Remove or escape HTML tags
        if ($options['strip_tags'] && !$options['allow_html']) {
            $input = strip_tags($input);
        } elseif ($options['allow_html']) {
            $input = strip_tags($input, $options['allowed_tags']);
        }

        // Remove special characters if requested
        if ($options['remove_special_chars']) {
            $input = preg_replace('/[^\w\s\-\.\@]/', '', $input);
        }

        // Encode HTML entities to prevent XSS
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        // Limit length if specified
        if ($options['max_length']) {
            $input = Str::limit($input, $options['max_length']);
        }

        return $input;
    }

    /**
     * Sanitize email input
     *
     * @param string|null $email
     * @return string|null
     */
    protected function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim(strtolower($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Additional validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Sanitize phone number input
     *
     * @param string|null $phone
     * @return string|null
     */
    protected function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Remove all non-numeric characters except + and -
        $phone = preg_replace('/[^\d\+\-]/', '', trim($phone));
        
        // Limit length
        $phone = substr($phone, 0, 20);

        return $phone;
    }

    /**
     * Sanitize numeric input
     *
     * @param mixed $input
     * @param array $options
     * @return int|float|null
     */
    protected function sanitizeNumeric($input, array $options = [])
    {
        if ($input === null || $input === '') {
            return null;
        }

        $options = array_merge([
            'type' => 'int', // 'int' or 'float'
            'min' => null,
            'max' => null,
        ], $options);

        // Convert to string and remove non-numeric characters
        $input = preg_replace('/[^\d\.\-]/', '', (string)$input);

        if ($options['type'] === 'float') {
            $value = (float)$input;
        } else {
            $value = (int)$input;
        }

        // Apply min/max constraints
        if ($options['min'] !== null && $value < $options['min']) {
            $value = $options['min'];
        }
        if ($options['max'] !== null && $value > $options['max']) {
            $value = $options['max'];
        }

        return $value;
    }

    /**
     * Sanitize array input recursively
     *
     * @param array|null $input
     * @param array $options
     * @return array|null
     */
    protected function sanitizeArray(?array $input, array $options = []): ?array
    {
        if ($input === null) {
            return null;
        }

        $options = array_merge([
            'max_depth' => 5,
            'max_items' => 100,
            'sanitize_strings' => true,
        ], $options);

        if ($options['max_depth'] <= 0) {
            return [];
        }

        $sanitized = [];
        $count = 0;

        foreach ($input as $key => $value) {
            if ($count >= $options['max_items']) {
                break;
            }

            // Sanitize key
            $key = $this->sanitizeString((string)$key, ['remove_special_chars' => true]);

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, [
                    'max_depth' => $options['max_depth'] - 1,
                    'max_items' => $options['max_items'],
                    'sanitize_strings' => $options['sanitize_strings'],
                ]);
            } elseif (is_string($value) && $options['sanitize_strings']) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }

            $count++;
        }

        return $sanitized;
    }

    /**
     * Sanitize filename for secure file uploads
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Get file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Sanitize filename
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        $name = trim($name, '_');
        $name = substr($name, 0, 100); // Limit length

        // Ensure we have a name
        if (empty($name)) {
            $name = 'file_' . time();
        }

        // Sanitize extension
        $extension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));

        return $name . '.' . $extension;
    }

    /**
     * Sanitize URL input
     *
     * @param string|null $url
     * @return string|null
     */
    protected function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Check for allowed protocols
        $allowedProtocols = ['http', 'https'];
        $protocol = parse_url($url, PHP_URL_SCHEME);
        
        if (!in_array($protocol, $allowedProtocols)) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize JSON input
     *
     * @param string|null $json
     * @param array $options
     * @return array|null
     */
    protected function sanitizeJson(?string $json, array $options = []): ?array
    {
        if ($json === null) {
            return null;
        }

        $options = array_merge([
            'max_depth' => 10,
            'max_size' => 1048576, // 1MB
        ], $options);

        // Check size limit
        if (strlen($json) > $options['max_size']) {
            Log::warning('JSON input exceeds size limit', ['size' => strlen($json)]);
            return null;
        }

        try {
            $decoded = json_decode($json, true, $options['max_depth'], JSON_THROW_ON_ERROR);
            
            // Sanitize the decoded array
            return $this->sanitizeArray($decoded, [
                'max_depth' => $options['max_depth'],
                'sanitize_strings' => true,
            ]);
        } catch (\JsonException $e) {
            Log::warning('Invalid JSON input', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Sanitize search query input
     *
     * @param string|null $query
     * @return string|null
     */
    protected function sanitizeSearchQuery(?string $query): ?string
    {
        if ($query === null) {
            return null;
        }

        // Remove potentially dangerous characters
        $query = preg_replace('/[<>"\'\(\){}]/', '', trim($query));
        
        // Limit length
        $query = substr($query, 0, 255);

        // Remove excessive whitespace
        $query = preg_replace('/\s+/', ' ', $query);

        return trim($query);
    }

    /**
     * Sanitize all request input
     *
     * @param array $input
     * @param array $rules
     * @return array
     */
    protected function sanitizeRequestInput(array $input, array $rules = []): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $rule = $rules[$key] ?? 'string';

            switch ($rule) {
                case 'email':
                    $sanitized[$key] = $this->sanitizeEmail($value);
                    break;
                case 'phone':
                    $sanitized[$key] = $this->sanitizePhone($value);
                    break;
                case 'url':
                    $sanitized[$key] = $this->sanitizeUrl($value);
                    break;
                case 'numeric':
                case 'integer':
                    $sanitized[$key] = $this->sanitizeNumeric($value, ['type' => 'int']);
                    break;
                case 'float':
                    $sanitized[$key] = $this->sanitizeNumeric($value, ['type' => 'float']);
                    break;
                case 'array':
                    $sanitized[$key] = $this->sanitizeArray($value);
                    break;
                case 'json':
                    $sanitized[$key] = $this->sanitizeJson($value);
                    break;
                case 'search':
                    $sanitized[$key] = $this->sanitizeSearchQuery($value);
                    break;
                default:
                    $sanitized[$key] = $this->sanitizeString($value);
            }
        }

        return $sanitized;
    }
}