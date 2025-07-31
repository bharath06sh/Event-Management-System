<?php
require_once "config/db.php";

// First check if venues table exists
$check_table = "SHOW TABLES LIKE 'venues'";
$table_exists = mysqli_query($conn, $check_table);

if(mysqli_num_rows($table_exists) == 0) {
    // Create venues table if it doesn't exist
    $create_table = "CREATE TABLE venues (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        capacity INT NOT NULL,
        price_per_day DECIMAL(10,2) NOT NULL,
        description TEXT,
        is_available BOOLEAN DEFAULT TRUE,
        image_path VARCHAR(255) DEFAULT NULL
    )";
    
    if(mysqli_query($conn, $create_table)) {
        echo "Venues table created successfully with image_path column<br>";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Check if image_path column exists
    $check_column = "SHOW COLUMNS FROM venues LIKE 'image_path'";
    $column_exists = mysqli_query($conn, $check_column);
    
    if(mysqli_num_rows($column_exists) == 0) {
        // Add image_path column if it doesn't exist
        $sql = "ALTER TABLE venues ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
        if(mysqli_query($conn, $sql)) {
            echo "Column 'image_path' added successfully to venues table<br>";
        } else {
            echo "Error adding column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column 'image_path' already exists in venues table<br>";
    }
}

// Show table structure
$result = mysqli_query($conn, "DESCRIBE venues");
echo "<br>Current table structure:<br>";
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

mysqli_close($conn);
?> 