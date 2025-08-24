<?php
// simple_debug.php - Basic debug without any includes
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Simple Debug Test</h1>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection directly
echo "<h2>Database Connection Test</h2>";
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=wildlife_logi;charset=utf8mb4",
        'wildlife_logi',
        'wildlife_logi',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✅ Database connection successful<br>";
    
    // Check database name
    $result = $pdo->query("SELECT DATABASE() as db_name")->fetch();
    echo "Connected to database: " . $result['db_name'] . "<br>";
    
    // Check if users table exists
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if ($tables) {
            echo "✅ Users table exists<br>";
            
            // Count users
            $count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
            echo "Users in database: " . $count['count'] . "<br>";
            
            // Show all users
            if ($count['count'] > 0) {
                echo "<h3>All users in database:</h3>";
                $users = $pdo->query("SELECT id, email, role, name, status FROM users ORDER BY id")->fetchAll();
                foreach ($users as $user) {
                    echo "ID: {$user['id']} | Email: {$user['email']} | Role: {$user['role']} | Name: {$user['name']} | Status: {$user['status']}<br>";
                }
            } else {
                echo "❌ No users found in database<br>";
            }
            
        } else {
            echo "❌ Users table does not exist<br>";
            echo "Available tables:<br>";
            $all_tables = $pdo->query("SHOW TABLES")->fetchAll();
            foreach ($all_tables as $table) {
                echo "- " . array_values($table)[0] . "<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test password hashing
echo "<h2>Password Hash Test</h2>";
$test_password = 'secret';
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "Generated hash for 'secret': $hash<br>";
$verify = password_verify($test_password, $hash);
echo "Verification test: " . ($verify ? "✅ Success" : "❌ Failed") . "<br>";

?>

<h2>Create Demo Users</h2>
<p>If no users exist, click this button to create them:</p>
<form method="post">
    <button type="submit" name="create_users" style="padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer;">Create Demo Users</button>
</form>

<?php
if (isset($_POST['create_users'])) {
    echo "<h3>Creating Demo Users...</h3>";
    
    try {
        // Create users table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role ENUM('admin','customer','driver') NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(120) NOT NULL,
                company VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                status ENUM('active','pending','suspended') DEFAULT 'active',
                email_verified TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL
            ) ENGINE=InnoDB
        ");
        
        // Create driver_profiles table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS driver_profiles (
                user_id INT PRIMARY KEY,
                license_number VARCHAR(60) NOT NULL,
                vehicle_make VARCHAR(100) DEFAULT NULL,
                vehicle_model VARCHAR(100) DEFAULT NULL,
                vehicle_plate VARCHAR(50) DEFAULT NULL,
                capacity_lbs INT DEFAULT NULL,
                availability TINYINT(1) DEFAULT 1,
                rating DECIMAL(2,1) DEFAULT 5.0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        
        // Create demo users with simple password 'secret'
        $simple_hash = password_hash('secret', PASSWORD_DEFAULT);
        
        // Clear existing demo users
        $pdo->exec("DELETE FROM driver_profiles WHERE user_id IN (1,2,3)");
        $pdo->exec("DELETE FROM users WHERE id IN (1,2,3)");
        
        // Insert demo users
        $stmt = $pdo->prepare("
            INSERT INTO users (id, role, email, password_hash, name, company, status, email_verified) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', 1)
        ");
        
        $stmt->execute([1, 'admin', 'admin@logitrack.com', $simple_hash, 'Super Admin', null]);
        $stmt->execute([2, 'customer', 'customer@acme.com', $simple_hash, 'John Customer', 'Acme Corp']);
        $stmt->execute([3, 'driver', 'driver@demo.com', $simple_hash, 'Mike Driver', null]);
        
        // Insert driver profile
        $driver_stmt = $pdo->prepare("
            INSERT INTO driver_profiles (user_id, license_number, vehicle_make, vehicle_model, vehicle_plate, capacity_lbs, availability, rating)
            VALUES (3, 'CDL-458796', 'Ford', 'Transit', 'ABC-1234', 5000, 1, 5.0)
        ");
        $driver_stmt->execute();
        
        echo "✅ Demo users created successfully!<br>";
        echo "<strong>Login credentials:</strong><br>";
        echo "Admin: admin@logitrack.com / secret<br>";
        echo "Customer: customer@acme.com / secret<br>";
        echo "Driver: driver@demo.com / secret<br>";
        
    } catch (Exception $e) {
        echo "❌ Error creating users: " . $e->getMessage() . "<br>";
    }
}
?>