<?php
/**
 * Rate Limiter
 * Prevents abuse by limiting request frequency
 */

class RateLimiter {
    private $db;
    private $limit;
    private $window;
    
    public function __construct($limit = 100, $window = 60) {
        $this->db = Database::getInstance();
        $this->limit = $limit;
        $this->window = $window;
    }
    
    /**
     * Get client identifier (IP or API key)
     */
    private function getClientIdentifier() {
        // Check for API key
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (strpos($authHeader, 'Bearer ') === 0) {
                return 'token:' . substr($authHeader, 7);
            }
        }
        
        // Fall back to IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'ip:' . $ip;
    }
    
    /**
     * Check if request is allowed
     */
    public function isAllowed() {
        $identifier = $this->getClientIdentifier();
        $now = time();
        $windowStart = $now - $this->window;
        
        // Clean old entries
        $this->db->delete(
            "DELETE FROM cache WHERE `key` LIKE ? AND expiration < ?",
            ['ratelimit:' . $identifier . ':%', $now]
        );
        
        // Count recent requests
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM cache 
            WHERE `key` LIKE ? AND expiration > ?",
            ['ratelimit:' . $identifier . ':%', $now]
        );
        
        $count = (int)($result['count'] ?? 0);
        
        if ($count >= $this->limit) {
            return false;
        }
        
        // Record this request
        $key = 'ratelimit:' . $identifier . ':' . $now;
        $this->db->insert('cache', [
            'key' => $key,
            'value' => json_encode(['timestamp' => $now]),
            'expiration' => $now + $this->window
        ]);
        
        return true;
    }
    
    /**
     * Get remaining requests
     */
    public function getRemaining() {
        $identifier = $this->getClientIdentifier();
        $now = time();
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM cache 
            WHERE `key` LIKE ? AND expiration > ?",
            ['ratelimit:' . $identifier . ':%', $now]
        );
        
        $count = (int)($result['count'] ?? 0);
        return max(0, $this->limit - $count);
    }
    
    /**
     * Get reset time
     */
    public function getResetTime() {
        return time() + $this->window;
    }
    
    /**
     * Set custom limits
     */
    public function setLimits($limit, $window) {
        $this->limit = $limit;
        $this->window = $window;
    }
}

/**
 * Middleware to apply rate limiting
 */
function applyRateLimiting($limit = 100, $window = 60) {
    $limiter = new RateLimiter($limit, $window);
    
    if (!$limiter->isAllowed()) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header('Retry-After: ' . $limiter->getResetTime());
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $limiter->getResetTime()
        ]);
        exit;
    }
    
    // Add rate limit headers
    header('X-RateLimit-Limit: ' . $limit);
    header('X-RateLimit-Remaining: ' . $limiter->getRemaining());
    header('X-RateLimit-Reset: ' . $limiter->getResetTime());
}

