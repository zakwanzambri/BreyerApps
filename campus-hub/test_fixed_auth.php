<?php
/**
 * Test Auth API Fix - Campus Hub Enhanced
 */

echo "🔐 TESTING FIXED AUTH API\n";
echo "=========================\n\n";

// Test the auth API via HTTP request
$post_data = http_build_query([
    'username' => 'admin',
    'password' => 'admin123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post_data
    ]
]);

$url = 'http://localhost/BreyerApps/campus-hub/php/api/auth.php?action=login';

echo "📤 Testing URL: $url\n";
echo "📋 Data: username=admin&password=admin123\n\n";

try {
    $response = file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "📥 Raw Response:\n";
        echo $response . "\n\n";
        
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                echo "✅ LOGIN API: SUCCESS!\n";
                echo "🎉 Auth API is now working correctly!\n";
            } else {
                echo "❌ LOGIN API: FAILED\n";
                echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "⚠️ Invalid JSON response\n";
        }
    } else {
        echo "❌ Failed to connect to API\n";
        echo "HTTP Context Error\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n🌐 Now try the login page again!\n";
echo "URL: http://localhost/BreyerApps/campus-hub/admin/login.html\n";
?>
