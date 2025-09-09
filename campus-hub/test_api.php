<?php
/**
 * API Test Script - Campus Hub Enhanced
 * Test all API endpoints to demonstrate functionality
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 Testing Campus Hub Enhanced API Endpoints\n";
echo "=============================================\n\n";

$base_url = 'http://localhost/BreyerApps/campus-hub/php/api/';

// Test endpoints
$endpoints = [
    'news.php?action=get_all' => 'Get All News',
    'events.php?action=get_all' => 'Get All Events',
    'auth.php?action=check_session' => 'Check Session Status'
];

foreach ($endpoints as $endpoint => $description) {
    echo "🎯 Testing: $description\n";
    echo "URL: {$base_url}{$endpoint}\n";
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json\r\n'
            ]
        ]);
        
        $response = file_get_contents($base_url . $endpoint, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                if ($data['success']) {
                    echo "✅ SUCCESS: " . $data['message'] . "\n";
                    if (isset($data['data']) && is_array($data['data'])) {
                        echo "📊 Data count: " . count($data['data']) . " items\n";
                    }
                } else {
                    echo "❌ FAILED: " . $data['message'] . "\n";
                }
            } else {
                echo "⚠️  Invalid JSON response\n";
            }
        } else {
            echo "❌ Failed to fetch\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "🎉 API Testing Complete!\n";
echo "\n💡 Admin Panel Login:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "\n🌐 URLs to test:\n";
echo "Main Portal: http://localhost/BreyerApps/campus-hub/\n";
echo "Admin Panel: http://localhost/BreyerApps/campus-hub/admin/\n";
?>
