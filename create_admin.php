<?php
require_once 'config/database.php';

try {
    // Add profile_picture column if it doesn't exist
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    $stmt->execute();
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        $stmt->execute();
        echo "Added profile_picture column to users table<br>";
    }

    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin' AND role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin) {
        // Create admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, profile_picture) VALUES (?, ?, 'admin', 'System Administrator', 'admin@example.com', NULL)");
        $stmt->execute(['admin', $password]);
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        // Reset admin password
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin' AND role = 'admin'");
        $stmt->execute([$password]);
        echo "Admin password reset successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 