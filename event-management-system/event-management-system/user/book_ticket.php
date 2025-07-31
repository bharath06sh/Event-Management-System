<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$num_tickets = "";
$errors = [];

// Get event details
if($event_id > 0) {
    $event_sql = "SELECT e.*, v.name as venue_name, u.username as organizer,
                         (e.total_tickets - COALESCE(SUM(b.num_tickets), 0)) as available_tickets
                  FROM events e 
                  LEFT JOIN venues v ON e.venue_id = v.id 
                  LEFT JOIN users u ON e.user_id = u.id
                  LEFT JOIN bookings b ON e.id = b.event_id
                  WHERE e.id = ? AND e.status = 'approved'
                  GROUP BY e.id";
    
    $stmt = mysqli_prepare($conn, $event_sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($event_result);
    
    if(!$event || $event['available_tickets'] <= 0) {
        header("location: view_events.php");
        exit;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate number of tickets
    if(empty(trim($_POST["num_tickets"]))) {
        $errors[] = "Please enter number of tickets.";
    } else {
        $num_tickets = (int)trim($_POST["num_tickets"]);
        if($num_tickets < 1) {
            $errors[] = "Number of tickets must be at least 1.";
        } elseif($num_tickets > $event['available_tickets']) {
            $errors[] = "Only " . $event['available_tickets'] . " tickets available.";
        }
    }
    
    // Process booking if no errors
    if(empty($errors)) {
        $total_price = $event['ticket_price'] * $num_tickets;
        
        $sql = "INSERT INTO bookings (event_id, user_id, num_tickets, total_price, payment_status) 
                VALUES (?, ?, ?, ?, 'pending')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiid", $event_id, $_SESSION['id'], $num_tickets, $total_price);
        
        if(mysqli_stmt_execute($stmt)) {
            $booking_id = mysqli_insert_id($conn);
            header("location: pay_ticket.php?booking_id=" . $booking_id);
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

include "../includes/header.php";
?>

<div class="container">
    <h2>Book Tickets</h2>
    
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($event)): ?>
        <div class="event-details card mb-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo htmlspecialchars($event['description']); ?></p>
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($event['start_time']); ?> - <?php echo htmlspecialchars($event['end_time']); ?></p>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
                <p><strong>Price per ticket:</strong> $<?php echo htmlspecialchars($event['ticket_price']); ?></p>
                <p><strong>Available tickets:</strong> <?php echo htmlspecialchars($event['available_tickets']); ?></p>
            </div>
        </div>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?event_id=" . $event_id); ?>">
            <div class="form-group">
                <label>Number of Tickets</label>
                <input type="number" name="num_tickets" class="form-control" 
                       min="1" max="<?php echo $event['available_tickets']; ?>" 
                       value="<?php echo htmlspecialchars($num_tickets); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Proceed to Payment</button>
            <a href="view_events.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">Invalid event selected or no tickets available.</div>
        <a href="view_events.php" class="btn btn-primary">View Events</a>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 