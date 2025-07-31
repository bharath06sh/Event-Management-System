<?php
require_once "db.php";

// Drop and recreate events table to ensure proper structure
$drop_sql = "DROP TABLE IF EXISTS events";
if(mysqli_query($conn, $drop_sql)) {
    echo "Old events table dropped successfully.<br>";
}

// Create events table with all required columns
$sql = "CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    venue_id INT,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL,
    total_tickets INT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(mysqli_query($conn, $sql)){
    echo "Events table created successfully.<br>";
} else {
    echo "Error creating events table: " . mysqli_error($conn) . "<br>";
}

// Create venues table if it doesn't exist
$venues_sql = "CREATE TABLE IF NOT EXISTS venues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(mysqli_query($conn, $venues_sql)){
    echo "Venues table created successfully or already exists.<br>";
} else {
    echo "Error creating venues table: " . mysqli_error($conn) . "<br>";
}

// Verify the events table structure
$verify_sql = "DESCRIBE events";
$result = mysqli_query($conn, $verify_sql);

if($result) {
    echo "<br>Events table structure:<br>";
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Error verifying table structure: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?> 