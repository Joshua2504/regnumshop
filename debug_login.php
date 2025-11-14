<?php
// Simple debug script to test COR API manually
require_once __DIR__ . '/config/config.php';

// Test COR API directly
function testCORAPI($username, $password) {
    echo "Testing COR API with URL: " . COR_API_URL . "\n";
    echo "API Key: " . (COR_API_KEY ? 'Present' : 'Missing') . "\n\n";
    
    $ch = curl_init(COR_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-API-Key: ' . COR_API_KEY,
        'User-Agent: RegnumShop/1.0'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'password' => $password
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "HTTP Code: " . $httpCode . "\n";
    echo "cURL Error: " . ($curlError ?: 'None') . "\n";
    echo "Response: " . $response . "\n";
    
    if ($response) {
        $result = json_decode($response, true);
        echo "Parsed JSON: " . print_r($result, true) . "\n";
        echo "JSON Error: " . (json_last_error_msg() ?: 'None') . "\n";
    }
}

// Test database connection
function testDatabase() {
    try {
        require_once __DIR__ . '/src/Database.php';
        $db = Database::getInstance();
        echo "Database connection: SUCCESS\n";
        
        // Test basic query
        $stmt = $db->query('SELECT COUNT(*) as count FROM users');
        if ($stmt) {
            $result = $stmt->fetch();
            echo "Users table accessible: YES (count: " . $result['count'] . ")\n";
        } else {
            echo "Users table accessible: NO\n";
        }
        
    } catch (Exception $e) {
        echo "Database connection: FAILED - " . $e->getMessage() . "\n";
    }
}

echo "=== COR FORUM API & DATABASE TEST ===\n\n";

echo "1. Database Test:\n";
testDatabase();
echo "\n";

echo "2. API Test (you can run this manually with real credentials):\n";
echo "Usage: php debug_login.php username password\n";

if ($argc >= 3) {
    $username = $argv[1];
    $password = $argv[2];
    echo "Testing with username: $username\n\n";
    testCORAPI($username, $password);
}

echo "\n=== END TEST ===\n";
?>