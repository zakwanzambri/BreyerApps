<?php
/**
 * Test Admin Login API - Campus Hub Enhanced
 */

echo "🔐 TESTING ADMIN LOGIN API\n";
echo "=========================\n\n";

// Test login via API
$login_data = [
    'username' => 'admin',
    'password' => 'admin123'
];

$postdata = http_build_query($login_data);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postdata
    ]
]);

$url = 'http://localhost/BreyerApps/campus-hub/php/api/auth.php?action=login';

echo "🌐 Testing URL: $url\n";
echo "📤 Sending data: " . json_encode($login_data) . "\n\n";

try {
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to connect to API\n";
        echo "💡 Make sure XAMPP Apache is running\n";
    } else {
        echo "📥 Response received:\n";
        echo $response . "\n\n";
        
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                echo "✅ LOGIN SUCCESS!\n";
                echo "🎉 Admin can now access admin panel\n";
            } else {
                echo "❌ LOGIN FAILED: " . $data['message'] . "\n";
            }
        } else {
            echo "⚠️  Invalid JSON response\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 NEXT STEPS:\n";
echo "1. Open: http://localhost/BreyerApps/campus-hub/admin/\n";
echo "2. Login with: admin / admin123\n";
echo "3. Access admin dashboard\n";
?>
