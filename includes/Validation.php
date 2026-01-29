<?php
/**
 * Validation Helper Class
 * Provides data validation and sanitization methods
 */

class Validation {
    
    /**
     * Validate that a value is required (not empty)
     * 
     * @param mixed $value The value to check
     * @return bool
     */
    public function required($value) {
        if ($value === null || $value === '' || $value === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email The email to validate
     * @return bool
     */
    public function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $value The value to check
     * @param int $min Minimum length
     * @return bool
     */
    public function minLength($value, $min) {
        return strlen(trim($value)) >= $min;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $value The value to check
     * @param int $max Maximum length
     * @return bool
     */
    public function maxLength($value, $max) {
        return strlen(trim($value)) <= $max;
    }
    
    /**
     * Validate that value is numeric
     * 
     * @param mixed $value The value to check
     * @return bool
     */
    public function numeric($value) {
        return is_numeric($value);
    }
    
    /**
     * Validate that value is an integer
     * 
     * @param mixed $value The value to check
     * @return bool
     */
    public function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate that value is in an array
     * 
     * @param mixed $value The value to check
     * @param array $allowed Array of allowed values
     * @return bool
     */
    public function inArray($value, $allowed) {
        return in_array($value, $allowed, true);
    }
    
    /**
     * Validate date format
     * 
     * @param string $date The date to validate
     * @param string $format The expected format
     * @return bool
     */
    public function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $url The URL to validate
     * @return bool
     */
    public function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate that value is a string
     * 
     * @param mixed $value The value to check
     * @return bool
     */
    public function string($value) {
        return is_string($value);
    }
    
    /**
     * Validate strong password
     * 
     * @param string $password The password to validate
     * @return bool
     */
    public function strongPassword($password) {
        // At least 8 characters, one uppercase, one lowercase, one number, one special char
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password) === 1;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone The phone number to validate
     * @return bool
     */
    public function phone($phone) {
        // Basic phone validation - allows digits, spaces, dashes, parentheses, +
        return preg_match('/^[+\d\s\-\(\)]{7,20}$/', $phone) === 1;
    }
    
    /**
     * Validate username format
     * 
     * @param string $username The username to validate
     * @return bool
     */
    public function username($username) {
        // Alphanumeric, 3-20 characters
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
    }
    
    /**
     * Validate IP address
     * 
     * @param string $ip The IP address to validate
     * @return bool
     */
    public function ipAddress($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Validate MAC address
     * 
     * @param string $mac The MAC address to validate
     * @return bool
     */
    public function macAddress($mac) {
        return filter_var($mac, FILTER_VALIDATE_MAC) !== false;
    }
    
    /**
     * Run full validation on data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = isset($data[$field]) ? $data[$field] : null;
            $fieldLabel = $field;
            
            foreach ($fieldRules as $rule) {
                if (is_array($rule)) {
                    $ruleName = $rule[0];
                    $ruleParam = $rule[1] ?? null;
                    
                    switch ($ruleName) {
                        case 'required':
                            if (!$this->required($value)) {
                                $errors[$field][] = "{$fieldLabel} is required";
                            }
                            break;
                            
                        case 'email':
                            if (!empty($value) && !$this->email($value)) {
                                $errors[$field][] = "{$fieldLabel} must be a valid email";
                            }
                            break;
                            
                        case 'minLength':
                            if (!empty($value) && !$this->minLength($value, $ruleParam)) {
                                $errors[$field][] = "{$fieldLabel} must be at least {$ruleParam} characters";
                            }
                            break;
                            
                        case 'maxLength':
                            if (!empty($value) && !$this->maxLength($value, $ruleParam)) {
                                $errors[$field][] = "{$fieldLabel} must be at most {$ruleParam} characters";
                            }
                            break;
                            
                        case 'min':
                            if (!empty($value) && $value < $ruleParam) {
                                $errors[$field][] = "{$fieldLabel} must be at least {$ruleParam}";
                            }
                            break;
                            
                        case 'max':
                            if (!empty($value) && $value > $ruleParam) {
                                $errors[$field][] = "{$fieldLabel} must be at most {$ruleParam}";
                            }
                            break;
                            
                        case 'in':
                            if (!empty($value) && !$this->inArray($value, $ruleParam)) {
                                $errors[$field][] = "{$fieldLabel} must be one of: " . implode(', ', $ruleParam);
                            }
                            break;
                            
                        case 'numeric':
                            if (!empty($value) && !$this->numeric($value)) {
                                $errors[$field][] = "{$fieldLabel} must be a number";
                            }
                            break;
                            
                        case 'integer':
                            if (!empty($value) && !$this->integer($value)) {
                                $errors[$field][] = "{$fieldLabel} must be an integer";
                            }
                            break;
                            
                        case 'date':
                            if (!empty($value) && !$this->date($value, $ruleParam ?? 'Y-m-d')) {
                                $errors[$field][] = "{$fieldLabel} must be a valid date";
                            }
                            break;
                            
                        case 'url':
                            if (!empty($value) && !$this->url($value)) {
                                $errors[$field][] = "{$fieldLabel} must be a valid URL";
                            }
                            break;
                            
                        case 'strongPassword':
                            if (!empty($value) && !$this->strongPassword($value)) {
                                $errors[$field][] = "{$fieldLabel} must contain uppercase, lowercase, number, and special character";
                            }
                            break;
                    }
                } else {
                    // Simple rules
                    switch ($rule) {
                        case 'required':
                            if (!$this->required($value)) {
                                $errors[$field][] = "{$fieldLabel} is required";
                            }
                            break;
                            
                        case 'email':
                            if (!empty($value) && !$this->email($value)) {
                                $errors[$field][] = "{$fieldLabel} must be a valid email";
                            }
                            break;
                            
                        case 'numeric':
                            if (!empty($value) && !$this->numeric($value)) {
                                $errors[$field][] = "{$fieldLabel} must be a number";
                            }
                            break;
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // ========== Static Helper Methods ==========
    
    /**
     * Sanitize a string value
     * 
     * @param mixed $value The value to sanitize
     * @return string
     */
    public static function sanitize($value) {
        if ($value === null) {
            return '';
        }
        
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        
        // Remove tags and encode special characters
        $value = strip_tags((string)$value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return trim($value);
    }
    
    /**
     * Generate URL-friendly slug from text
     * 
     * @param string $text The text to convert
     * @return string
     */
    public static function generateSlug($text) {
        // Convert to lowercase
        $slug = strtolower($text);
        
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Escape for SQL (alternative to prepared statements)
     * Note: Prepared statements are preferred for security
     * 
     * @param mysqli $conn Database connection
     * @param string $value Value to escape
     * @return string
     */
    public static function escape($conn, $value) {
        return $conn->real_escape_string(self::sanitize($value));
    }
}

