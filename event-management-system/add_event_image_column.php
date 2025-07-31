<?php
require_once "config/db.php";

// First check if events table exists
$check_table = "SHOW TABLES LIKE 'events'";
$table_exists = mysqli_query($conn, $check_table);

if(mysqli_num_rows($table_exists) == 0) {
    // Create events table if it doesn't exist
    $create_table = "CREATE TABLE events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        venue_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        ticket_price DECIMAL(10,2) NOT NULL,
        total_tickets INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        image_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (venue_id) REFERENCES venues(id)
    )";
    
    if(mysqli_query($conn, $create_table)) {
        echo "Events table created successfully with image_path column<br>";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Check if image_path column exists
    $check_column = "SHOW COLUMNS FROM events LIKE 'image_path'";
    $column_exists = mysqli_query($conn, $check_column);
    
    if(mysqli_num_rows($column_exists) == 0) {
        // Add image_path column if it doesn't exist
        $sql = "ALTER TABLE events ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
        if(mysqli_query($conn, $sql)) {
            echo "Column 'image_path' added successfully to events table<br>";
        } else {
            echo "Error adding column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column 'image_path' already exists in events table<br>";
    }
}

// Show table structure
$result = mysqli_query($conn, "DESCRIBE events");
echo "<br>Current table structure:<br>";
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

mysqli_close($conn);
?> 