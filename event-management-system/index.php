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

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    }

    h1.text-center {
        color: #2c3e50;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        margin: 30px 0;
    }

    .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .card h2 {
        color: #3498db;
        margin-bottom: 15px;
    }

    .card p {
        color: #34495e;
        margin-bottom: 20px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        border: none;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(127, 140, 141, 0.4);
    }

    h2 {
        color: #2c3e50;
        margin: 30px 0 20px;
    }

    .event-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        padding: 10px;
    }

    .event-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .event-card-body {
        padding: 20px;
    }

    .event-card-body h3 {
        color: #3498db;
        margin-bottom: 15px;
        font-size: 1.4rem;
    }

    .event-card-body p {
        color: #34495e;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .event-card-body strong {
        color: #2c3e50;
    }

    @media (max-width: 768px) {
        .event-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

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