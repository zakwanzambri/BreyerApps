<?php
/**
 * DIRECT AUTH API TEST - Campus Hub Enhanced
 */

echo "🔐 TESTING AUTH API DIRECTLY\n";
echo "============================\n\n";

// Set up environment like a real web request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/BreyerApps/campus-hub/php/api/auth.php';
$_SERVER['SCRIPT_NAME'] = '/BreyerApps/campus-hub/php/api/auth.php';
$_GET['action'] = 'login';
$_POST['username'] = 'admin';
$_POST['password'] = 'admin123';

echo "📋 Request simulation:\n";
echo "Method: POST\n";
echo "Action: login\n";
echo "Username: admin\n";
echo "Password: admin123\n\n";

// Capture output
ob_start();

try {
    // Include the auth API
    include 'php/api/auth.php';
    
    // Get the response
    $response = ob_get_clean();
    
    echo "📤 API Response:\n";
    echo $response . "\n\n";
    
    // Parse JSON
    $data = json_decode($response, true);
    
    if ($data) {
        if (isset($data['success'])) {
            if ($data['success']) {
                echo "✅ LOGIN API: SUCCESS\n";
                echo "Message: " . $data['message'] . "\n";
                if (isset($data['data'])) {
                    echo "User data received:\n";
                    foreach ($data['data'] as $key => $value) {
                        if ($key !== 'token') {
                            echo "  $key: $value\n";
                        }
                    }
                }
            } else {
                echo "❌ LOGIN API: FAILED\n";
                echo "Error: " . $data['message'] . "\n";
            }
        } else {
            echo "⚠️ Invalid API response format\n";
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: $response\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "❌ PHP Error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Next: Test in browser at:\n";
echo "http://localhost/BreyerApps/campus-hub/admin/login.html\n";
?>
