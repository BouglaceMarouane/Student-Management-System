<?php
require_once 'config/database.php';

try {
    $conn = getConnection();
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Create admin user
        $admin_name = "admin";
        $admin_email = "admin@example.com";
        $admin_password = "admin123"; // Change this in production
        
        // Make sure we're using PASSWORD_DEFAULT for hashing
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', CURDATE())");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        echo "Admin user created successfully!<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    // Check if filieres exist
    $stmt = $conn->prepare("SELECT id_filiere FROM filiere");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Insert filieres
        $filieres = [
            'Développement Digital',
            'UI/UX',
            'Infrastructure Digital',
            'Intéligence Artificielle'
        ];
        
        $stmt = $conn->prepare("INSERT INTO filiere (filiere) VALUES (?)");
        
        foreach ($filieres as $filiere) {
            $stmt->execute([$filiere]);
        }
        
        echo "Filieres added successfully!<br>";
    } else {
        echo "Filieres already exist.<br>";
    }
    
    echo "Database initialization complete.<br>";
    echo "<a href='login.php'>Go to login page</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
