<?php
require_once 'config/database.php';

try {
    // Check database connection
    $conn = getDBConnection();
    echo "Database connection successful!\n";

    // Check if tables exist
    $tables = ['admin', 'projects', 'invoices', 'contractor', 'client', 'expenses'];
    foreach ($tables as $table) {
        $result = fetchSingle("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "Table '$table' exists\n";
            
            // Show table structure
            $columns = fetchAll("SHOW COLUMNS FROM $table");
            echo "Columns in $table:\n";
            foreach ($columns as $col) {
                echo "- {$col['Field']} ({$col['Type']})\n";
            }
            echo "\n";
        } else {
            echo "Table '$table' does NOT exist\n";
        }
    }

    // Check if admin table has data
    $admin = fetchSingle("SELECT * FROM admin LIMIT 1");
    if ($admin) {
        echo "Admin data exists\n";
        print_r($admin);
    } else {
        echo "No admin data found\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 