<?php
/**
 * Generic Entity CRUD API
 * Handles studios, distributors, producers, networks
 *
 * POST   /api/entities/crud.php?type=studios - Create
 * GET    /api/entities/crud.php?type=studios - List all
 * GET    /api/entities/crud.php?type=studios&id=123 - Get one
 * PUT    /api/entities/crud.php?type=studios&id=123 - Update
 * DELETE /api/entities/crud.php?type=studios&id=123 - Delete
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/connection.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';
require_once dirname(__DIR__, 2) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

// Get entity type from query parameter
$entity_type = $_GET['type'] ?? '';
$valid_types = ['studios', 'distributors', 'producers', 'networks'];

if (!in_array($entity_type, $valid_types)) {
    Response::error('Invalid entity type. Must be: ' . implode(', ', $valid_types), 400);
}

// Get entity ID if provided
$entity_id = $_GET['id'] ?? null;

// Check authentication for write operations
$requires_auth = in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']);
if ($requires_auth) {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
        Response::error('Unauthorized. Please provide a valid token.', 401);
    }

    $token = str_replace('Bearer ', '', $auth_header);
    $user = $auth->validateToken($token);

    if (!$user) {
        Response::error('Invalid or expired token', 401);
    }

    // Only admins and companies can manage entities
    if (!in_array($user['role'], ['admin', 'company'])) {
        Response::error('Access denied. Admin or company account required.', 403);
    }
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($entity_id) {
                // Get single entity
                $entity = $db->fetch("SELECT * FROM {$entity_type} WHERE id = ? AND is_active = 1", [$entity_id]);
                if (!$entity) {
                    Response::error('Entity not found', 404);
                }
                Response::success('Entity retrieved', $entity);
            } else {
                // List all entities
                $search = $_GET['search'] ?? '';
                $limit = (int)($_GET['limit'] ?? 50);
                $offset = (int)($_GET['offset'] ?? 0);

                $where = "WHERE is_active = 1";
                $params = [];

                if ($search) {
                    $where .= " AND name LIKE ?";
                    $params[] = "%{$search}%";
                }

                $entities = $db->fetchAll("SELECT * FROM {$entity_type} {$where} ORDER BY name LIMIT {$limit} OFFSET {$offset}", $params);
                $total = $db->fetch("SELECT COUNT(*) as count FROM {$entity_type} {$where}", $params)['count'];

                Response::success('Entities retrieved', [
                    'entities' => $entities,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]);
            }
            break;

        case 'POST':
            // Create new entity
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            // Validate required fields
            $required = ['name'];
            $errors = [];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $errors[$field] = ucfirst($field) . ' is required';
                }
            }
            if (!empty($errors)) {
                Response::validationError($errors);
            }

            // Generate slug
            $name = trim($input['name']);
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
            $slug = preg_replace('/-+/', '-', $slug);

            // Check if name already exists
            $existing = $db->fetch("SELECT id FROM {$entity_type} WHERE name = ?", [$name]);
            if ($existing) {
                Response::error('Entity with this name already exists', 409);
            }

            // Prepare data
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => trim($input['description'] ?? ''),
                'website' => trim($input['website'] ?? ''),
                'country' => trim($input['country'] ?? ''),
                'founded_year' => $input['founded_year'] ? (int)$input['founded_year'] : null,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Handle logo upload if present
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(__DIR__, 3) . '/uploads/entity_logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed_image = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($file_ext, $allowed_image)) {
                    Response::error('Invalid image format. Allowed: JPG, PNG, WebP', 400);
                }

                $file_name = $entity_type . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                    $data['logo'] = '/KagomaEnter/uploads/entity_logos/' . $file_name;
                }
            }

            $entity_id = $db->insert($entity_type, $data);
            if (!$entity_id) {
                throw new Exception('Failed to create entity');
            }

            $entity = $db->fetch("SELECT * FROM {$entity_type} WHERE id = ?", [$entity_id]);
            Response::success('Entity created successfully', $entity, 201);
            break;

        case 'PUT':
            if (!$entity_id) {
                Response::error('Entity ID required for update', 400);
            }

            // Check if entity exists
            $existing = $db->fetch("SELECT * FROM {$entity_type} WHERE id = ?", [$entity_id]);
            if (!$existing) {
                Response::error('Entity not found', 404);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            // Prepare update data
            $update_data = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $allowed_fields = ['name', 'description', 'website', 'country', 'founded_year'];
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'name') {
                        $new_name = trim($input[$field]);
                        // Check if name conflicts with another entity
                        $conflict = $db->fetch("SELECT id FROM {$entity_type} WHERE name = ? AND id != ?", [$new_name, $entity_id]);
                        if ($conflict) {
                            Response::error('Entity with this name already exists', 409);
                        }
                        // Update slug if name changed
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $new_name));
                        $slug = preg_replace('/-+/', '-', $slug);
                        $update_data['name'] = $new_name;
                        $update_data['slug'] = $slug;
                    } else {
                        $update_data[$field] = $field === 'founded_year' && $input[$field] ? (int)$input[$field] : trim($input[$field]);
                    }
                }
            }

            // Handle logo upload if present
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(__DIR__, 3) . '/uploads/entity_logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed_image = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($file_ext, $allowed_image)) {
                    Response::error('Invalid image format. Allowed: JPG, PNG, WebP', 400);
                }

                $file_name = $entity_type . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                    $update_data['logo'] = '/KagomaEnter/uploads/entity_logos/' . $file_name;
                }
            }

            $db->update($entity_type, $update_data, "id = {$entity_id}");
            $updated = $db->fetch("SELECT * FROM {$entity_type} WHERE id = ?", [$entity_id]);

            Response::success('Entity updated successfully', $updated);
            break;

        case 'DELETE':
            if (!$entity_id) {
                Response::error('Entity ID required for deletion', 400);
            }

            // Check if entity exists
            $existing = $db->fetch("SELECT * FROM {$entity_type} WHERE id = ?", [$entity_id]);
            if (!$existing) {
                Response::error('Entity not found', 404);
            }

            // Soft delete by setting is_active = 0
            $db->update($entity_type, ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], "id = {$entity_id}");

            Response::success('Entity deleted successfully');
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('Entity CRUD error: ' . $e->getMessage());
    Response::error('Operation failed. Please try again.', 500);
}
