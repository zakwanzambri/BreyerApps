<?php
/**
 * Simple Auth Test Page - Campus Hub Enhanced
 */

// Handle the login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    header('Content-Type: application/json');
    
    try {
        // Direct database connection
        $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            exit;
        }
        
        // Find user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Auth Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>🔐 Simple Auth Test</h1>
    
    <form id="test-form">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="admin" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="admin123" required>
        </div>
        
        <button type="submit">Test Login</button>
    </form>
    
    <div id="result"></div>
    
    <script>
        document.getElementById('test-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('test_login', '1');
            formData.append('username', document.getElementById('username').value);
            formData.append('password', document.getElementById('password').value);
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '⏳ Testing...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <h3>✅ LOGIN SUCCESS!</h3>
                            <p><strong>User:</strong> ${data.data.username}</p>
                            <p><strong>Role:</strong> ${data.data.role}</p>
                            <p><strong>Email:</strong> ${data.data.email}</p>
                            <p>🎉 Authentication is working!</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>❌ LOGIN FAILED</h3>
                            <p><strong>Error:</strong> ${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>❌ CONNECTION ERROR</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
