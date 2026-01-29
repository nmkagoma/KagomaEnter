<?php
/**
 * Company Admin Approval API
 * 
 * Admin endpoint to approve/reject company applications
 * POST /api/company/admin/approve.php
 * POST /api/company/admin/reject.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/Database.php';
require_once dirname(__DIR__, 3) . '/includes/Response.php';
require_once dirname(__DIR__, 3) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

// Authenticate admin
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user || $user['role'] !== 'admin') {
    Response::error('Access denied. Admin access required.', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    Response::error('Invalid input', 400);
}

$company_id = (int)($input['company_id'] ?? 0);
$action = $input['action'] ?? '';
$notes = trim($input['notes'] ?? '');

if (!$company_id || !in_array($action, ['approve', 'reject'])) {
    Response::validationError(['Invalid company ID or action']);
}

// Get company
$company = $db->fetch("SELECT id, user_id, name, verification_status FROM companies WHERE id = ?", [$company_id]);
if (!$company) {
    Response::error('Company not found', 404);
}

try {
    if ($action === 'approve') {
        // Approve the company
        $db->update('companies', [
            'verification_status' => 'verified',
            'verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $notes ?: 'Approved by admin'
        ], "id = $company_id");
        
        // Update application status
        $db->update('content_applications', [
            'status' => 'approved',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => $user['id'],
            'notes' => $notes ?: 'Approved'
        ], "company_id = $company_id");
        
        // Update user role to ensure company access
        $db->update('users', [
            'role' => 'company',
            'user_type' => 'company',
            'updated_at' => date('Y-m-d H:i:s')
        ], "id = {$company['user_id']}");
        
        Response::success("Company '{$company['name']}' has been verified and can now upload content.");
        
    } else {
        // Reject the company
        $db->update('companies', [
            'verification_status' => 'rejected',
            'updated_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $notes ?: 'Rejected by admin'
        ], "id = $company_id");
        
        // Update application status
        $db->update('content_applications', [
            'status' => 'rejected',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => $user['id'],
            'notes' => $notes ?: 'Rejected'
        ], "company_id = $company_id");
        
        Response::success("Company '{$company['name']}' application has been rejected.");
    }
    
} catch (Exception $e) {
    error_log('Company approval error: ' . $e->getMessage());
    Response::error('Operation failed. Please try again.', 500);
}

