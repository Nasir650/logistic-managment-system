<?php
// create_admin.php - Script to create a working admin account
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<h1>Create Admin Account</h1>";

// Test current admin accounts
echo "<h2>Current Admin Accounts</h2>";
try {
    $admins = $pdo->query("SELECT id, email, name, status FROM users WHERE role = 'admin'")->fetchAll();
    if ($admins) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Status</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['name']}</td>";
            echo "<td>{$admin['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No admin accounts found.<br>";
    }
} catch (Exception $e) {
    echo "Error checking admins: " . $e->getMessage() . "<br>";
}

?>

<h2>Test Login with Current Admin</h2>
<form method="post" style="border: 1px solid #ccc; padding: 15px; margin: 10px 0;">
    <input type="email" name="test_email" placeholder="Email" value="admin@logitrack.com" style="display: block; margin: 5px 0; padding: 8px; width: 300px;"><br>
    <input type="password" name="test_password" placeholder="Password" value="secret" style="display: block; margin: 5px 0; padding: 8px; width: 300px;"><br>
    <button type="submit" name="test_login" style="padding: 8px 15px; background: #007cba; color: white; border: none;">Test Login</button>
</form>

<?php
if (isset($_POST['test_login'])) {
    echo "<h3>Login Test Result:</h3>";
    
    $email = trim($_POST['test_email'] ?? '');
    $password = $_POST['test_password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "❌ Admin user not found<br>";
        } else {
            echo "✅ Admin user found<br>";
            echo "Password hash in database: " . substr($user['password_hash'], 0, 30) . "...<br>";
            
            $password_valid = password_verify($password, $user['password_hash']);
            echo "Password verification: " . ($password_valid ? "✅ Valid" : "❌ Invalid") . "<br>";
            
            if (!$password_valid) {
                echo "<strong style='color: red;'>Password does not match. Creating new admin...</strong><br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
?>

<h2>Create New Admin Account</h2>
<form method="post" style="border: 1px solid #ccc; padding: 15px; margin: 10px 0;">
    <h3>Admin Account Details</h3>
    <input type="email" name="admin_email" placeholder="Admin Email" value="admin@logitrack.com" required style="display: block; margin: 5px 0; padding: 8px; width: 300px;"><br>
    <input type="text" name="admin_name" placeholder="Admin Name" value="System Administrator" required style="display: block; margin: 5px 0; padding: 8px; width: 300px;"><br>
    <input type="password" name="admin_password" placeholder="Admin Password" value="admin123" required style="display: block; margin: 5px 0; padding: 8px; width: 300px;"><br>
    <button type="submit" name="create_admin" style="padding: 8px 15px; background: #dc3545; color: white; border: none;">Create/Update Admin</button>
</form>

<?php
if (isset($_POST['create_admin'])) {
    echo "<h3>Creating Admin Account...</h3>";
    
    $email = trim($_POST['admin_email'] ?? '');
    $name = trim($_POST['admin_name'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    
    if (empty($email) || empty($name) || empty($password)) {
        echo "❌ All fields are required<br>";
    } else {
        try {
            // Generate a fresh password hash
            $hash = password_hash($password, PASSWORD_DEFAULT);
            echo "Generated password hash: " . substr($hash, 0, 30) . "...<br>";
            
            // Check if admin already exists
            $existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $existing->execute([$email]);
            $admin_exists = $existing->fetch();
            
            if ($admin_exists) {
                // Update existing admin
                echo "Updating existing admin account...<br>";
                $update = $pdo->prepare("UPDATE users SET password_hash = ?, name = ?, status = 'active' WHERE email = ?");
                $update->execute([$hash, $name, $email]);
                echo "✅ Admin account updated successfully!<br>";
            } else {
                // Create new admin
                echo "Creating new admin account...<br>";
                $insert = $pdo->prepare("
                    INSERT INTO users (role, email, password_hash, name, status, email_verified, created_at) 
                    VALUES ('admin', ?, ?, ?, 'active', 1, NOW())
                ");
                $insert->execute([$email, $hash, $name]);
                echo "✅ Admin account created successfully!<br>";
            }
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>Admin Login Credentials:</strong><br>";
            echo "Email: <strong>$email</strong><br>";
            echo "Password: <strong>$password</strong><br>";
            echo "</div>";
            
            // Test the new password immediately
            echo "<h4>Testing New Password:</h4>";
            $verify_test = password_verify($password, $hash);
            echo "Password verification test: " . ($verify_test ? "✅ Success" : "❌ Failed") . "<br>";
            
        } catch (Exception $e) {
            echo "❌ Error creating admin: " . $e->getMessage() . "<br>";
        }
    }
}
?>

<h2>Quick Admin Creation</h2>
<p>Click this button to quickly create/update an admin with standard credentials:</p>
<form method="post">
    <button type="submit" name="quick_admin" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Create Admin: admin@logitrack.com / admin123
    </button>
</form>

<?php
if (isset($_POST['quick_admin'])) {
    echo "<h3>Quick Admin Creation...</h3>";
    
    try {
        $email = 'admin@logitrack.com';
        $password = 'admin123';
        $name = 'System Administrator';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Delete any existing admin with this email
        $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
        
        // Create fresh admin
        $stmt = $pdo->prepare("
            INSERT INTO users (role, email, password_hash, name, status, email_verified, created_at) 
            VALUES ('admin', ?, ?, ?, 'active', 1, NOW())
        ");
        $stmt->execute([$email, $hash, $name]);
        
        echo "✅ Quick admin created successfully!<br>";
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "<strong>🔑 Admin Login:</strong><br>";
        echo "📧 Email: <code>admin@logitrack.com</code><br>";
        echo "🔒 Password: <code>admin123</code><br>";
        echo "<br><a href='login.php' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Go to Login</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>