<?php
/**
 * Subscribe to Plan Endpoint
 * User subscribes to a paid plan
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validation.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();
$validation = new Validation();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate
if (empty($input['plan_id'])) {
    Response::validationError(['plan_id' => 'Plan ID is required']);
}

$planId = (int)$input['plan_id'];

// Get plan
$plan = $db->fetch(
    "SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1",
    [$planId]
);

if (!$plan) {
    Response::error('Subscription plan not found', 404);
}

// Free plan handling
if ($plan['price'] <= 0) {
    // Update to free plan
    $subscriptionData = [
        'user_id' => $user['id'],
        'plan_id' => $planId,
        'type' => $plan['slug'],
        'status' => 'active',
        'starts_at' => date('Y-m-d H:i:s'),
        'ends_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Cancel any existing subscription
    $db->update('subscriptions',
        ['status' => 'canceled', 'updated_at' => date('Y-m-d H:i:s')],
        'user_id = ?',
        [$user['id']]
    );
    
    $subscriptionId = $db->insert('subscriptions', $subscriptionData);
    
    if ($subscriptionId) {
        Response::success('Subscribed to free plan successfully', [
            'subscription' => [
                'id' => $subscriptionId,
                'plan_name' => $plan['name'],
                'type' => $plan['slug'],
                'status' => 'active'
            ]
        ], 201);
    } else {
        Response::error('Failed to subscribe', 500);
    }
    exit;
}

// In production, process payment here
// For now, we'll simulate a successful payment

// Cancel any existing subscription
$db->update('subscriptions',
    ['status' => 'canceled', 'updated_at' => date('Y-m-d H:i:s')],
    'user_id = ?',
    [$user['id']]
);

// Calculate subscription period
$intervalDays = $plan['interval'] === 'monthly' ? 30 : 365;

// Create new subscription
$subscriptionData = [
    'user_id' => $user['id'],
    'plan_id' => $planId,
    'type' => $plan['slug'],
    'status' => 'active',
    'starts_at' => date('Y-m-d H:i:s'),
    'ends_at' => date('Y-m-d H:i:s', strtotime("+$intervalDays days")),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$subscriptionId = $db->insert('subscriptions', $subscriptionData);

if ($subscriptionId) {
    // In production: record payment transaction
    // $db->insert('payment_transactions', [...]);
    
    Response::success('Subscription activated successfully', [
        'subscription' => [
            'id' => $subscriptionId,
            'plan_name' => $plan['name'],
            'type' => $plan['slug'],
            'price' => $plan['price'],
            'interval' => $plan['interval'],
            'starts_at' => $subscriptionData['starts_at'],
            'ends_at' => $subscriptionData['ends_at'],
            'status' => 'active'
        ],
        'message' => 'Payment processed successfully. Your subscription is now active.'
    ], 201);
} else {
    Response::error('Failed to process subscription', 500);
}

