<?php
require_once 'config/database.php';

try {
    // Check database connection
    $conn = getDBConnection();
    echo "Database connection successful!<br>";

    // Check if admin table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($stmt->rowCount() > 0) {
        echo "Admin table exists!<br>";
    } else {
        echo "Admin table does not exist!<br>";
    }

    // Check admin account
    $admin = fetchSingle("SELECT * FROM admin WHERE username = 'admin'");
    if ($admin) {
        echo "Admin account found!<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Full Name: " . $admin['full_name'] . "<br>";
    } else {
        echo "Admin account not found!<br>";
    }

    // Check if password matches
    if ($admin && password_verify('password', $admin['password'])) {
        echo "Password verification successful!<br>";
    } else {
        echo "Password verification failed!<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 