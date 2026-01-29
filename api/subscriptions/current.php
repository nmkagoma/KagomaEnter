<?php
/**
 * Get Current Subscription Endpoint
 * Retrieves user's current subscription details
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

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get current subscription
$subscription = $db->fetch(
    "SELECT s.*, sp.name as plan_name, sp.slug as plan_slug, 
    sp.price, sp.interval, sp.features, sp.video_quality, sp.max_devices
    FROM subscriptions s
    LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
    WHERE s.user_id = ? AND s.status = 'active'
    ORDER BY s.ends_at DESC
    LIMIT 1",
    [$user['id']]
);

if (!$subscription) {
    // Get free subscription if exists
    $subscription = $db->fetch(
        "SELECT s.*, sp.name as plan_name, sp.slug as plan_slug,
        sp.features, sp.video_quality, sp.max_devices
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.type = 'free'
        ORDER BY s.ends_at DESC
        LIMIT 1",
        [$user['id']]
    );
    
    if ($subscription) {
        $subscription['features'] = json_decode($subscription['features'] ?? '[]', true);
        Response::success('Current subscription retrieved', [
            'subscription' => $subscription
        ], 200);
    } else {
        Response::error('No active subscription found', 404);
    }
}

$subscription['features'] = json_decode($subscription['features'] ?? '[]', true);

// Check if subscription is expiring soon
$daysRemaining = 0;
if ($subscription['ends_at']) {
    $daysRemaining = ceil((strtotime($subscription['ends_at']) - time()) / (60 * 60 * 24));
}

$subscription['days_remaining'] = $daysRemaining;
$subscription['is_expiring'] = $daysRemaining <= 7 && $daysRemaining > 0;

// Get subscription history
$history = $db->fetchAll(
    "SELECT * FROM subscriptions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 10",
    [$user['id']]
);

Response::success('Current subscription retrieved', [
    'subscription' => $subscription,
    'history' => $history
], 200);

