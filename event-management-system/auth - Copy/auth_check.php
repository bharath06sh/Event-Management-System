<?php
// Ensure session_start() is only called if no session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// Check if the user is logged in
function checkLogin() {
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: ../auth/login.php");
        exit;
    }
}

// Check if the user is an admin
function checkAdmin() {
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
        header("location: ../auth/login.php");
        exit;
    }
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION["id"]) ? $_SESSION["id"] : null;
}

// Get current user role
function getCurrentUserRole() {
    return isset($_SESSION["role"]) ? $_SESSION["role"] : null;
}

// Get current username
function getCurrentUsername() {
    return isset($_SESSION["username"]) ? $_SESSION["username"] : null;
}
?>