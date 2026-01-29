<?php
/**
 * Rate Content Endpoint
 * User rates a content item or likes/unlikes content
 */

// Include configuration
require_once __DIR__ . '/../../config/config.php';
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

// Initialize classes
$db = Database::getInstance();
$auth = new Auth();
$validation = new Validation();

/**
 * Recalculate and update content average rating
 */
function recalculateContentRating($db, $contentId) {
    $avgRating = $db->fetch(
        "SELECT AVG(rating) as avg FROM ratings WHERE content_id = ? AND rating > 0",
        [$contentId]
    );
    
    $avgRating = round($avgRating['avg'] ?? 0, 2);
    
    $db->update('content',
        ['rating' => $avgRating, 'updated_at' => date('Y-m-d H:i:s')],
        'id = ?',
        [$contentId]
    );
    
    return $avgRating;
}

try {
    // Get current user
    $user = $auth->getCurrentUser();

    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input exists
    if (empty($input)) {
        Response::error('Invalid request data', 400);
    }

    // Validate content_id
    if (empty($input['content_id'])) {
        Response::validationError(['content_id' => 'Content ID is required']);
    }

    // Validate rating - allow 0 for unlike, 0.5-5 for rating
    if (!isset($input['rating']) || $input['rating'] === '') {
        Response::validationError(['rating' => 'Rating is required']);
    }

    $rating = (float)$input['rating'];
    
    // Allow rating 0 (for removing like) or 0.5 to 5
    if ($rating !== 0 && ($rating < 0.5 || $rating > 5)) {
        Response::validationError(['rating' => 'Rating must be between 0.5 and 5']);
    }

    $contentId = (int)$input['content_id'];

    // Check if content exists
    $content = $db->fetch(
        "SELECT id FROM content WHERE id = ?",
        [$contentId]
    );

    if (!$content) {
        Response::error('Content not found', 404);
    }

    // Handle rating = 0 as "unlike" (delete rating)
    if ($rating === 0) {
        // Delete existing rating
        $deleted = $db->delete('ratings', 'user_id = ? AND content_id = ?', [$user['id'], $contentId]);
        
        if ($deleted !== false) {
            recalculateContentRating($db, $contentId);
            
            Response::success('Rating removed successfully', [
                'rating' => 0
            ], 200);
        } else {
            Response::error('Failed to remove rating', 500);
        }
    }
    
    // Normal rating (0.5 to 5)
    // Check if user already rated
    $existing = $db->fetch(
        "SELECT id, rating FROM ratings WHERE user_id = ? AND content_id = ?",
        [$user['id'], $contentId]
    );

    if ($existing) {
        // Update existing rating
        $updateData = [
            'rating' => $rating,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($input['review'])) {
            $updateData['review'] = htmlspecialchars($input['review']);
        }
        
        $updated = $db->update('ratings', $updateData, 'id = ?', [$existing['id']]);
        
        if ($updated !== false) {
            recalculateContentRating($db, $contentId);
            
            Response::success('Rating updated successfully', [
                'rating' => $rating
            ], 200);
        } else {
            Response::error('Failed to update rating', 500);
        }
    } else {
        // Create new rating
        $ratingData = [
            'user_id' => $user['id'],
            'content_id' => $contentId,
            'rating' => $rating,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($input['review'])) {
            $ratingData['review'] = htmlspecialchars($input['review']);
        }
        
        $ratingId = $db->insert('ratings', $ratingData);
        
        if ($ratingId) {
            recalculateContentRating($db, $contentId);
            
            Response::success('Rating submitted successfully', [
                'rating_id' => $ratingId,
                'rating' => $rating
            ], 201);
        } else {
            Response::error('Failed to submit rating', 500);
        }
    }

} catch (Exception $e) {
    // Log the error for debugging
    error_log('Rate API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    // Return proper error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request',
        'debug' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $e->getMessage() : null
    ], JSON_PRETTY_PRINT);
    exit;
}

