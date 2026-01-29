<?php
/**
 * API Endpoint Diagnostic Tool
 * Tests various API endpoints to diagnose issues
 */

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>API Diagnostic Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #e0e0e0; }
        h1 { color: #4CAF50; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #333; border-radius: 5px; }
        .pass { border-color: #4CAF50; background: #1b3a1b; }
        .fail { border-color: #f44336; background: #3a1b1b; }
        .pending { border-color: #FF9800; background: #3a2b1b; }
        pre { background: #2a2a2a; padding: 10px; overflow: auto; max-height: 300px; }
        .status { font-weight: bold; }
        .pass .status { color: #4CAF50; }
        .fail .status { color: #f44336; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🔧 KagomaEnter API Diagnostic Tool</h1>
    
    <div style="margin-bottom: 20px;">
        <button class="btn" onclick="location.reload()">Refresh</button>
    </div>

<?php
// Define base paths
$basePath = dirname(__DIR__);
$includesPath = $basePath . '/includes/';
$configPath = $basePath . '/config/config.php';

$results = [];

function test($name, $testFn) {
    global $results;
    echo '<div class="test pending" id="test-' . md5($name) . '">';
    echo '<span class="status">⏳ Testing:</span> ' . htmlspecialchars($name) . '<br>';
    echo '<div class="details">';
    
    try {
        $result = $testFn();
        $results[$name] = $result;
        
        if ($result['status'] === 'pass') {
            echo '<div class="pass"><span class="status">✅ PASS:</span> ' . htmlspecialchars($result['message']) . '</div>';
            if (isset($result['data'])) {
                echo '<pre>' . htmlspecialchars(print_r($result['data'], true)) . '</pre>';
            }
        } else {
            echo '<div class="fail"><span class="status">❌ FAIL:</span> ' . htmlspecialchars($result['message']) . '</div>';
            if (isset($result['error'])) {
                echo '<pre style="color: #ff6b6b;">' . htmlspecialchars($result['error']) . '</pre>';
            }
        }
    } catch (Exception $e) {
        $results[$name] = ['status' => 'fail', 'message' => $e->getMessage(), 'error' => $e->getTraceAsString()];
        echo '<div class="fail"><span class="status">❌ ERROR:</span> ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    
    echo '</div></div>';
}

// Test 1: Configuration Loading
test('Config File Loading', function() use ($configPath) {
    if (!file_exists($configPath)) {
        return ['status' => 'fail', 'message' => 'Config file not found', 'error' => "Path: $configPath"];
    }
    
    // Check if it can be included without errors
    try {
        require_once $configPath;
        return ['status' => 'pass', 'message' => 'Config file loaded successfully'];
    } catch (Exception $e) {
        return ['status' => 'fail', 'message' => 'Config file has errors', 'error' => $e->getMessage()];
    }
});

// Test 2: Database Connection
test('Database Connection', function() use ($includesPath) {
    require_once $includesPath . 'Database.php';
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Test query
        $result = $db->fetch("SELECT 1 as test");
        if ($result) {
            return ['status' => 'pass', 'message' => 'Database connected and query working', 'data' => $result];
        }
        
        return ['status' => 'fail', 'message' => 'Database connected but test query failed'];
    } catch (Exception $e) {
        return ['status' => 'fail', 'message' => 'Database connection error', 'error' => $e->getMessage()];
    }
});

// Test 3: Required Tables Exist
test('Required Tables Exist', function() use ($includesPath) {
    require_once $includesPath . 'Database.php';
    $db = Database::getInstance();
    
    $requiredTables = ['users', 'content', 'comments', 'ratings', 'comment_likes'];
    $missing = [];
    $existing = [];
    
    foreach ($requiredTables as $table) {
        $result = $db->fetch("SHOW TABLES LIKE '$table'");
        if ($result) {
            $existing[] = $table;
        } else {
            $missing[] = $table;
        }
    }
    
    if (empty($missing)) {
        return ['status' => 'pass', 'message' => 'All required tables exist', 'data' => ['tables' => $existing]];
    }
    
    return ['status' => 'fail', 'message' => 'Missing tables: ' . implode(', ', $missing), 'data' => ['existing' => $existing, 'missing' => $missing]];
});

// Test 4: Comments Table Structure
test('Comments Table Structure', function() use ($includesPath) {
    require_once $includesPath . 'Database.php';
    $db = Database::getInstance();
    
    $columns = $db->fetchAll("DESCRIBE comments");
    $columnNames = array_column($columns, 'Field');
    
    $required = ['id', 'user_id', 'content_id', 'comment', 'created_at'];
    $missing = array_diff($required, $columnNames);
    
    if (empty($missing)) {
        return ['status' => 'pass', 'message' => 'Comments table has all required columns', 'data' => ['columns' => $columnNames]];
    }
    
    return ['status' => 'fail', 'message' => 'Missing columns: ' . implode(', ', $missing), 'data' => ['columns' => $columnNames, 'missing' => $missing]];
});

// Test 5: Ratings Table Structure
test('Ratings Table Structure', function() use ($includesPath) {
    require_once $includesPath . 'Database.php';
    $db = Database::getInstance();
    
    $columns = $db->fetchAll("DESCRIBE ratings");
    $columnNames = array_column($columns, 'Field');
    
    $required = ['id', 'user_id', 'content_id', 'rating', 'created_at'];
    $missing = array_diff($required, $columnNames);
    
    if (empty($missing)) {
        return ['status' => 'pass', 'message' => 'Ratings table has all required columns', 'data' => ['columns' => $columnNames]];
    }
    
    return ['status' => 'fail', 'message' => 'Missing columns: ' . implode(', ', $missing), 'data' => ['columns' => $columnNames, 'missing' => $missing]];
});

// Test 6: Content Exists for Testing
test('Test Content Exists', function() use ($includesPath) {
    require_once $includesPath . 'Database.php';
    $db = Database::getInstance();
    
    $content = $db->fetch("SELECT id, title FROM content WHERE id = 20");
    
    if ($content) {
        return ['status' => 'pass', 'message' => 'Content ID 20 exists', 'data' => $content];
    }
    
    return ['status' => 'fail', 'message' => 'Content ID 20 not found', 'data' => 'Try running seed data first'];
});

// Test 7: Comments List Endpoint
test('Comments List Endpoint', function() {
    $url = 'http://localhost/KagomaEnter/api/comments/list.php?content_id=20';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'fail', 'message' => 'cURL error', 'error' => $error];
    }
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return ['status' => 'pass', 'message' => 'Endpoint returned 200 OK', 'data' => $data];
    }
    
    return ['status' => 'fail', 'message' => "HTTP $httpCode", 'error' => $response];
});

// Test 8: Ratings Endpoint (POST required)
test('Ratings Rate Endpoint (POST)', function() {
    $url = 'http://localhost/KagomaEnter/api/ratings/rate.php';
    $data = json_encode(['content_id' => 20, 'rating' => 4.5]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'fail', 'message' => 'cURL error', 'error' => $error];
    }
    
    // 401 Unauthorized is expected without auth
    if ($httpCode === 401) {
        return ['status' => 'pass', 'message' => 'Endpoint working (401 = auth required)', 'data' => json_decode($response, true)];
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        return ['status' => 'pass', 'message' => 'Endpoint returned 200 OK', 'data' => $responseData];
    }
    
    return ['status' => 'fail', 'message' => "HTTP $httpCode", 'error' => $response];
});

// Test 9: Ratings Endpoint with rating=0
test('Ratings Rate Endpoint (rating=0)', function() {
    $url = 'http://localhost/KagomaEnter/api/ratings/rate.php';
    $data = json_encode(['content_id' => 20, 'rating' => 0]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'fail', 'message' => 'cURL error', 'error' => $error];
    }
    
    // 400 error means validation failed for rating=0
    if ($httpCode === 400) {
        return ['status' => 'fail', 'message' => 'Endpoint rejected rating=0 (validation error)', 'error' => $response];
    }
    
    // 401 Unauthorized is expected without auth (this is OK - means validation passed)
    if ($httpCode === 401) {
        return ['status' => 'pass', 'message' => 'Endpoint accepts rating=0 (401 = auth required)', 'data' => json_decode($response, true)];
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        return ['status' => 'pass', 'message' => 'Endpoint returned 200 OK', 'data' => $responseData];
    }
    
    return ['status' => 'fail', 'message' => "HTTP $httpCode", 'error' => $response];
});

// Test 10: PHP Version Check
test('PHP Version', function() {
    $version = PHP_VERSION;
    
    if (version_compare($version, '7.4.0', '>=')) {
        return ['status' => 'pass', 'message' => "PHP $version is compatible", 'data' => ['version' => $version]];
    }
    
    return ['status' => 'fail', 'message' => "PHP $version may have compatibility issues", 'data' => ['version' => $version]];
});

// Test 11: Required Extensions
test('Required PHP Extensions', function() {
    $required = ['mysqli', 'json', 'curl', 'mbstring'];
    $missing = [];
    $loaded = [];
    
    foreach ($required as $ext) {
        if (extension_loaded($ext)) {
            $loaded[] = $ext;
        } else {
            $missing[] = $ext;
        }
    }
    
    if (empty($missing)) {
        return ['status' => 'pass', 'message' => 'All required extensions loaded', 'data' => ['extensions' => $loaded]];
    }
    
    return ['status' => 'fail', 'message' => 'Missing extensions: ' . implode(', ', $missing), 'data' => ['loaded' => $loaded, 'missing' => $missing]];
});

// Summary
$passed = count(array_filter($results, fn($r) => $r['status'] === 'pass'));
$failed = count(array_filter($results, fn($r) => $r['status'] === 'fail'));
$total = count($results);

echo "<h2>Summary: $passed/$total tests passed</h2>";

if ($failed > 0) {
    echo "<p style='color: #f44336;'>$failed test(s) failed. Check the errors above for details.</p>";
} else {
    echo "<p style='color: #4CAF50;'>All tests passed! API endpoints should be working.</p>";
}

?>

</body>
</html>

