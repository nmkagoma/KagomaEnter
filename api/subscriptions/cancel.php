<?php
/**
 * Cancel Subscription Endpoint
 * User cancels their subscription
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
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$subscriptionId = (int)($input['id'] ?? $_GET['id'] ?? 0);

// Get current active subscription
$subscription = $db->fetch(
    "SELECT s.*, sp.name as plan_name 
    FROM subscriptions s
    LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
    WHERE s.user_id = ? AND s.status = 'active'",
    [$user['id']]
);

if (!$subscription) {
    Response::error('No active subscription found', 404);
}

// Cancel subscription
$canceled = $db->update('subscriptions',
    [
        'status' => 'canceled',
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$subscription['id']]
);

if ($canceled) {
    // Create free subscription
    $freePlan = $db->fetch(
        "SELECT id FROM subscription_plans WHERE slug = 'free'"
    );
    
    if ($freePlan) {
        $db->insert('subscriptions', [
            'user_id' => $user['id'],
            'plan_id' => $freePlan['id'],
            'type' => 'free',
            'status' => 'active',
            'starts_at' => date('Y-m-d H:i:s'),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    Response::success('Subscription canceled successfully. You have been switched to the free plan.', [
        'canceled_subscription' => [
            'plan_name' => $subscription['plan_name'],
            'ended_at' => date('Y-m-d H:i:s')
        ]
    ], 200);
} else {
    Response::error('Failed to cancel subscription', 500);
}

