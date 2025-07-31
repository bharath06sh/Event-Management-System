<?php
require_once "config/db.php";
include "includes/header.php";

// Fetch featured events (approved events)
$sql = "SELECT e.*, v.name as venue_name, u.username as organizer 
        FROM events e 
        LEFT JOIN venues v ON e.venue_id = v.id 
        LEFT JOIN users u ON e.user_id = u.id 
        WHERE e.status = 'approved' 
        ORDER BY e.event_date DESC 
        LIMIT 6";
$result = mysqli_query($conn, $sql);
?>

<h1 class="text-center">Welcome to Event Management System</h1>

<?php if(!isset($_SESSION["loggedin"])): ?>
<div class="card mb-2">
    <h2>Get Started</h2>
    <p>Join us to create and manage events, book venues, and more!</p>
    <div>
        <a href="auth/register.php" class="btn btn-primary">Register Now</a>
        <a href="auth/login.php" class="btn btn-secondary ml-2">Login</a>
    </div>
</div>
<?php endif; ?>

<h2>Featured Events</h2>
<div class="event-grid">
    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($event = mysqli_fetch_assoc($result)): ?>
            <div class="event-card">
                <div class="event-card-body">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p><?php echo htmlspecialchars($event['description']); ?></p>
                    <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($event['start_time']); ?> - <?php echo htmlspecialchars($event['end_time']); ?></p>
                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
                    <p><strong>Ticket Price:</strong> $<?php echo htmlspecialchars($event['ticket_price']); ?></p>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <a href="user/book_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary">Book Tickets</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary">Login to Book</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No events available at the moment.</p>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?> 