<?php
require_once "config/db.php";

// Admin credentials - using simpler password
$username = "admin";
$password = "admin123"; // Simpler password
$email = "admin@admin.com";
$role = "admin";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// First, let's check if the database connection is working
if($conn === false) {
    die("ERROR: Database connection failed. " . mysqli_connect_error());
}

// Check if users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if(mysqli_num_rows($table_check) == 0) {
    die("ERROR: Users table does not exist. Please run database.sql first.");
}

// Check if admin already exists
$check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
if($check_stmt === false) {
    die("ERROR: Could not prepare statement. " . mysqli_error($conn));
}

mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if(mysqli_stmt_num_rows($check_stmt) > 0) {
    // If admin exists, update the password
    $update_sql = "UPDATE users SET password = ? WHERE username = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $username);
    
    if(mysqli_stmt_execute($update_stmt)) {
        echo "Admin password has been reset successfully!<br>";
        echo "Username: " . $username . "<br>";
        echo "New Password: " . $password . "<br>";
        echo "Please delete this file after use for security reasons.";
    } else {
        echo "Error updating admin password: " . mysqli_error($conn);
    }
    mysqli_stmt_close($update_stmt);
} else {
    // Insert new admin user
    $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if($stmt === false) {
        die("ERROR: Could not prepare statement. " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $email, $role);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "Admin user created successfully!<br>";
        echo "Username: " . $username . "<br>";
        echo "Password: " . $password . "<br>";
        echo "Please delete this file after use for security reasons.";
    } else {
        echo "Error creating admin user: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

mysqli_stmt_close($check_stmt);
mysqli_close($conn);
?> 