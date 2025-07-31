<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get user's events
$user_id = getCurrentUserId();
$events_sql = "SELECT e.*, v.name as venue_name 
               FROM events e 
               LEFT JOIN venues v ON e.venue_id = v.id 
               WHERE e.user_id = ? 
               ORDER BY e.event_date DESC";
$stmt = mysqli_prepare($conn, $events_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$events_result = mysqli_stmt_get_result($stmt);

// Get user's bookings
$bookings_sql = "SELECT b.*, e.title as event_title, e.event_date, e.start_time 
                 FROM bookings b 
                 LEFT JOIN events e ON b.event_id = e.id 
                 WHERE b.user_id = ? 
                 ORDER BY b.booking_date DESC";
$stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$bookings_result = mysqli_stmt_get_result($stmt);

include "../includes/header.php";
?>

<h1>Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</h1>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>My Events</h3>
        <p class="stat-number"><?php echo mysqli_num_rows($events_result); ?></p>
    </div>
    <div class="stat-card">
        <h3>My Bookings</h3>
        <p class="stat-number"><?php echo mysqli_num_rows($bookings_result); ?></p>
    </div>
</div>

<div class="card">
    <h2>My Events</h2>
    <?php if(mysqli_num_rows($events_result) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Venue</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($event = mysqli_fetch_assoc($events_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['venue_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                        <td><?php echo htmlspecialchars($event['status']); ?></td>
                        <td>
                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No events created yet. <a href="create_event.php">Create your first event</a></p>
    <?php endif; ?>
</div>

<div class="card mt-2">
    <h2>My Bookings</h2>
    <?php if(mysqli_num_rows($bookings_result) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Tickets</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['event_title']); ?></td>
                        <td><?php echo htmlspecialchars($booking['event_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['num_tickets']); ?></td>
                        <td>$<?php echo htmlspecialchars($booking['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($booking['payment_status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No bookings yet. <a href="view_events.php">Browse events</a></p>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 