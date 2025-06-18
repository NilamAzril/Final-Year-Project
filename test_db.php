<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    // Test database connection
    $conn = getDBConnection();
    echo "Database connection successful!\n";

    // Check if database exists
    $databases = $conn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nAvailable databases:\n";
    print_r($databases);

    // Select our database
    $conn->query("USE contractor_billing_system");

    // Check if admin table exists
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables in database:\n";
    print_r($tables);

    if (in_array('admin', $tables)) {
        echo "\nAdmin table structure:\n";
        $columns = $conn->query("DESCRIBE admin")->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);

        // Try to find admin user
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :username");
        $stmt->execute(['username' => 'admin']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nAdmin user found:\n";
        if ($user) {
            // Don't show the actual password hash
            $hashedPassword = $user['password'];
            $user['password'] = 'HIDDEN';
            print_r($user);
            
            // Test password verification
            $testPassword = 'password';
            $passwordValid = password_verify($testPassword, $hashedPassword);
            echo "\nPassword verification test for 'password': " . ($passwordValid ? "VALID" : "INVALID") . "\n";
            
            if (!$passwordValid) {
                echo "Current password hash: " . $hashedPassword . "\n";
                echo "Example of correct hash for 'password': " . password_hash('password', PASSWORD_DEFAULT) . "\n";
            }
        } else {
            echo "No admin user found with username 'admin'\n";
        }
    } else {
        echo "\nAdmin table does not exist!\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?> 