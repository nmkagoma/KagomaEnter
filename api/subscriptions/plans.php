<?php
/**
 * Get Subscription Plans Endpoint
 * Retrieves all available subscription plans
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user's subscription
$user = $auth->getCurrentUser();
$currentSubscription = null;

if ($user) {
    $currentSubscription = $db->fetch(
        "SELECT s.*, sp.name as plan_name, sp.features 
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.ends_at DESC
        LIMIT 1",
        [$user['id']]
    );
}

// Get all active plans
$plans = $db->fetchAll(
    "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC"
);

// Decode features JSON
foreach ($plans as &$plan) {
    $plan['features'] = json_decode($plan['features'] ?? '[]', true);
}

Response::success('Subscription plans retrieved successfully', [
    'plans' => $plans,
    'current_subscription' => $currentSubscription
], 200);

