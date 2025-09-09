<?php
/**
 * Test Login API Call - Campus Hub Enhanced
 */

echo "🔐 TESTING LOGIN API CALL\n";
echo "=========================\n\n";

// Simulate the exact same request that admin login form makes
$username = 'admin';
$password = 'admin123';

echo "📤 Testing credentials:\n";
echo "Username: $username\n";
echo "Password: $password\n\n";

// Include the auth API and test it directly
try {
    // Set up the environment like a real POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['action'] = 'login';
    $_POST['username'] = $username;
    $_POST['password'] = $password;
    
    // Start output buffering to capture API response
    ob_start();
    
    // Include and run the auth API
    include 'php/api/auth.php';
    
    // Get the output
    $response = ob_get_clean();
    
    echo "📥 API Response:\n";
    echo $response . "\n\n";
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    if ($data) {
        if ($data['success']) {
            echo "✅ LOGIN SUCCESS!\n";
            echo "🎉 User data received:\n";
            if (isset($data['data'])) {
                foreach ($data['data'] as $key => $value) {
                    if ($key !== 'token') { // Don't show sensitive token
                        echo "  $key: $value\n";
                    }
                }
            }
        } else {
            echo "❌ LOGIN FAILED: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "⚠️  Invalid JSON response or error in API\n";
        echo "Raw response: $response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Now test in browser:\n";
echo "1. Go to: http://localhost/BreyerApps/campus-hub/admin/login.html\n";
echo "2. Use credentials: admin / admin123\n";
echo "3. Click Login button\n";
?>
