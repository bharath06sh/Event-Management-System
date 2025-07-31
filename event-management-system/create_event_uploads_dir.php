<?php
// Create uploads directory for events if it doesn't exist
$upload_dir = "uploads/events/";
if (!file_exists($upload_dir)) {
    if(mkdir($upload_dir, 0777, true)) {
        echo "Created directory: " . $upload_dir . "<br>";
    } else {
        echo "Failed to create directory: " . $upload_dir . "<br>";
    }
} else {
    echo "Directory already exists: " . $upload_dir . "<br>";
}

// Set proper permissions
chmod($upload_dir, 0777);
echo "Set permissions for directory: " . $upload_dir . "<br>";
?> 