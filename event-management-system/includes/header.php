<?php
require_once __DIR__ . "/../auth/auth_check.php";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System</title>
    <link rel="stylesheet" href="/event-management-system/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/event-management-system/index.php">Home</a>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                    <a href="/event-management-system/admin/dashboard.php">Admin Dashboard</a>
                    <a href="/event-management-system/admin/manage_users.php">Manage Users</a>
                    <a href="/event-management-system/admin/manage_venues.php">Manage Venues</a>
                    <a href="/event-management-system/admin/manage_tickets.php">Manage Tickets</a>
                <?php else: ?>
                    <a href="/event-management-system/user/dashboard.php">Dashboard</a>
                    <a href="/event-management-system/user/create_event.php">Create Event</a>
                    <a href="/event-management-system/user/book_venue.php">Book Venue</a>
                    <a href="/event-management-system/user/view_events.php">View Events</a>
                <?php endif; ?>
                <a href="/event-management-system/auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="/event-management-system/auth/login.php">Login</a>
                <a href="/event-management-system/auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container"> 