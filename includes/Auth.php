<?php
class Auth {
    private $db;
    private $secret_key;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->secret_key = JWT_SECRET;
    }

    public function register($data) {
        // Validation
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($this->emailExists($data['email'])) {
            $errors['email'] = 'Email already exists';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create user
        $userData = [
            'name' => htmlspecialchars($data['name']),
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'user',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            // Create user profile
            $profileData = [
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('user_profiles', $profileData);
            
            // Create free subscription
            $subscriptionData = [
                'user_id' => $userId,
                'type' => 'free',
                'status' => 'active',
                'starts_at' => date('Y-m-d H:i:s'),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('subscriptions', $subscriptionData);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
                'token' => $this->generateToken($userId)
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Update last login
        $this->db->update('users', 
            ['updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $this->generateToken($user['id'])
        ];
    }

    public function generateToken($userId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60) // 7 days
        ]);
        
        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', 
            $base64Header . "." . $base64Payload, 
            $this->secret_key, 
            true
        );
        
        $base64Signature = $this->base64UrlEncode($signature);
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = $this->base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', 
            $base64Header . "." . $base64Payload, 
            $this->secret_key, 
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode($this->base64UrlDecode($base64Payload), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }

    public function getCurrentUser() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }
        
        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = $this->db->fetch(
            "SELECT id, name, email, role, is_active FROM users WHERE id = ?",
            [$payload['user_id']]
        );
        
        return $user;
    }

    /**
     * Get current user with full details
     */
    public function getCurrentUserFull() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }
        
        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = $this->db->fetch(
            "SELECT id, name, email, role, is_active, avatar_url, subscription_plan, created_at, updated_at FROM users WHERE id = ?",
            [$payload['user_id']]
        );
        
        return $user;
    }

    /**
     * Check if current user has specific role
     */
    public function hasRole($roles) {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // Handle single role or array of roles
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($user['role'], $roles);
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Check if current user is regular user
     */
    public function isUser() {
        return $this->hasRole('user');
    }

    /**
     * Require specific role(s) - returns error if not authorized
     */
    public function requireRole($roles) {
        if (!$this->hasRole($roles)) {
            return [
                'success' => false,
                'message' => 'Unauthorized: Insufficient permissions',
                'code' => 403
            ];
        }
        
        return ['success' => true];
    }

    /**
     * Require admin role
     */
    public function requireAdmin() {
        return $this->requireRole('admin');
    }

    /**
     * Social login (Google/Facebook)
     */
    public function socialLogin($provider, $accessToken) {
        // Validate provider
        if (!in_array($provider, ['google', 'facebook'])) {
            return ['success' => false, 'message' => 'Invalid provider'];
        }
        
        // Get user info from provider
        $providerData = $this->getProviderUserInfo($provider, $accessToken);
        
        if (!$providerData) {
            return ['success' => false, 'message' => 'Invalid access token'];
        }
        
        // Check if user exists
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$providerData['email']]
        );
        
        if (!$user) {
            // Create new user
            $userId = $this->createSocialUser($provider, $providerData);
            
            if (!$userId) {
                return ['success' => false, 'message' => 'Failed to create account'];
            }
            
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        }
        
        // Check if active
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account has been deactivated'];
        }
        
        // Update last login
        $this->db->update('users',
            ['updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $this->generateToken($user['id']),
            'provider' => $provider
        ];
    }

    /**
     * Get user info from OAuth provider
     */
    private function getProviderUserInfo($provider, $accessToken) {
        if ($provider === 'google') {
            return $this->getGoogleUserInfo($accessToken);
        } elseif ($provider === 'facebook') {
            return $this->getFacebookUserInfo($accessToken);
        }
        return null;
    }

    /**
     * Get user info from Google
     */
    private function getGoogleUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['email'])) {
            return null;
        }
        
        return [
            'provider_id' => $data['sub'] ?? $data['id'] ?? null,
            'email' => $data['email'],
            'name' => $data['name'] ?? '',
            'first_name' => $data['given_name'] ?? '',
            'last_name' => $data['family_name'] ?? '',
            'avatar_url' => $data['picture'] ?? null,
            'verified_email' => $data['email_verified'] ?? false
        ];
    }

    /**
     * Get user info from Facebook
     */
    private function getFacebookUserInfo($accessToken) {
        // Get app access token
        $appTokenUrl = sprintf(
            'https://graph.facebook.com/v18.0/oauth/access_token?client_id=%s&client_secret=%s&grant_type=client_credentials',
            FACEBOOK_APP_ID,
            FACEBOOK_APP_SECRET
        );
        
        $ch = curl_init($appTokenUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $appResponse = curl_exec($ch);
        curl_close($ch);
        
        $appData = json_decode($appResponse, true);
        
        if (!isset($appData['access_token'])) {
            return null;
        }
        
        // Verify user token
        $verifyUrl = sprintf(
            'https://graph.facebook.com/v18.0/debug_token?input_token=%s&access_token=%s',
            urlencode($accessToken),
            urlencode($appData['access_token'])
        );
        
        $ch = curl_init($verifyUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $verifyResponse = curl_exec($ch);
        curl_close($ch);
        
        $verifyData = json_decode($verifyResponse, true);
        
        if (!isset($verifyData['data']) || !$verifyData['data']['is_valid']) {
            return null;
        }
        
        $userId = $verifyData['data']['user_id'];
        
        // Get user profile
        $profileUrl = sprintf(
            'https://graph.facebook.com/v18.0/%s?fields=id,name,email,picture&access_token=%s',
            $userId,
            urlencode($accessToken)
        );
        
        $ch = curl_init($profileUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $profileResponse = curl_exec($ch);
        curl_close($ch);
        
        $profileData = json_decode($profileResponse, true);
        
        if (!$profileData || !isset($profileData['email'])) {
            return null;
        }
        
        return [
            'provider_id' => $userId,
            'email' => $profileData['email'],
            'name' => $profileData['name'] ?? '',
            'first_name' => '',
            'last_name' => '',
            'avatar_url' => $profileData['picture']['data']['url'] ?? null,
            'verified_email' => true
        ];
    }

    /**
     * Create user from social login
     */
    private function createSocialUser($provider, $providerData) {
        $name = !empty($providerData['name']) ? $providerData['name'] : 
                trim($providerData['first_name'] . ' ' . $providerData['last_name']);
        
        if (empty($name)) {
            $name = explode('@', $providerData['email'])[0];
        }
        
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $userData = [
            'name' => htmlspecialchars($name),
            'email' => $providerData['email'],
            'password' => $password,
            'role' => 'user',
            'is_active' => 1,
            'email_verified_at' => $providerData['verified_email'] ? date('Y-m-d H:i:s') : null,
            'avatar_url' => $providerData['avatar_url'] ?? null,
            'provider' => $provider,
            'provider_id' => $providerData['provider_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('users', $userData);
    }

    /**
     * Send email verification
     */
    public function sendEmailVerification($userId) {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if ($user['email_verified_at']) {
            return ['success' => false, 'message' => 'Email already verified'];
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
        
        // Store token
        $this->db->delete('email_verification_tokens', 'user_id = ?', [$userId]);
        
        $tokenData = [
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('email_verification_tokens', $tokenData);
        
        // Build verify URL
        $verifyUrl = BASE_URL . 'frontend/verify-email.html?token=' . urlencode($token) . '&user_id=' . $userId;
        
        // Log for development
        error_log("Email verification for {$user['email']}: {$verifyUrl}");
        
        return [
            'success' => true,
            'message' => 'Verification email sent',
            'verify_url' => $verifyUrl
        ];
    }

    /**
     * Verify email token
     */
    public function verifyEmailToken($token, $userId) {
        $storedToken = $this->db->fetch(
            "SELECT * FROM email_verification_tokens WHERE user_id = ? AND expires_at > NOW()",
            [$userId]
        );
        
        if (!$storedToken) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        if (!hash_equals($storedToken['token'], hash('sha256', $token))) {
            return ['success' => false, 'message' => 'Invalid token'];
        }
        
        // Update user
        $this->db->update('users',
            [
                'email_verified_at' => date('Y-m-d H:i:s'),
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$userId]
        );
        
        // Delete token
        $this->db->delete('email_verification_tokens', 'user_id = ?', [$userId]);
        
        return ['success' => true, 'message' => 'Email verified successfully'];
    }

    public function emailExists($email) {
        $result = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        return $result !== false;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), 
            strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updated = $this->db->update('users',
            ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
        
        if ($updated) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to change password'];
    }

    public function logout($token) {
        // In a stateless JWT system, we typically don't store tokens server-side
        // But we could implement a token blacklist if needed
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
}