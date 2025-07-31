<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$errors = [];

// Get booking details
if($booking_id > 0) {
    $booking_sql = "SELECT b.*, e.title as event_title, e.event_date, e.ticket_price,
                           v.name as venue_name
                    FROM bookings b
                    JOIN events e ON b.event_id = e.id
                    JOIN venues v ON e.venue_id = v.id
                    WHERE b.id = ? AND b.user_id = ? AND b.payment_status = 'pending'";
    
    $stmt = mysqli_prepare($conn, $booking_sql);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $booking_result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($booking_result);
    
    if(!$booking) {
        header("location: view_events.php");
        exit;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate payment details
    if(empty(trim($_POST["card_number"]))) {
        $errors[] = "Please enter card number.";
    } elseif(!preg_match("/^\d{16}$/", str_replace(" ", "", $_POST["card_number"]))) {
        $errors[] = "Invalid card number format.";
    }
    
    if(empty(trim($_POST["expiry_date"]))) {
        $errors[] = "Please enter expiry date.";
    } elseif(!preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $_POST["expiry_date"])) {
        $errors[] = "Invalid expiry date format (MM/YY).";
    }
    
    if(empty(trim($_POST["cvv"]))) {
        $errors[] = "Please enter CVV.";
    } elseif(!preg_match("/^\d{3,4}$/", $_POST["cvv"])) {
        $errors[] = "Invalid CVV format.";
    }
    
    // Process payment if no errors
    if(empty($errors)) {
        // In a real application, integrate with a payment gateway here
        // For demonstration, we'll simulate a successful payment
        
        // Update booking status
        $update_sql = "UPDATE bookings SET payment_status = 'completed' WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $booking_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            // Send confirmation email
            $to = $_SESSION["email"];
            $subject = "Booking Confirmation - " . $booking['event_title'];
            $message = "Dear " . $_SESSION["username"] . ",\n\n";
            $message .= "Your booking for '" . $booking['event_title'] . "' is confirmed.\n\n";
            $message .= "Details:\n";
            $message .= "Number of Tickets: " . $booking['num_tickets'] . "\n";
            $message .= "Total Amount: $" . $booking['total_price'] . "\n";
            $message .= "Event Date: " . $booking['event_date'] . "\n";
            $message .= "Venue: " . $booking['venue_name'] . "\n\n";
            $message .= "Thank you for your booking!\n";
            $message .= "Event Management System";
            
            $headers = "From: noreply@eventmanagement.com";
            
            mail($to, $subject, $message, $headers);
            
            // Redirect to dashboard
            header("location: dashboard.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

include "../includes/header.php";
?>

<div class="container">
    <h2>Payment</h2>
    
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($booking)): ?>
        <div class="booking-details card mb-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($booking['event_title']); ?></h3>
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($booking['venue_name']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['event_date']); ?></p>
                <p><strong>Number of Tickets:</strong> <?php echo htmlspecialchars($booking['num_tickets']); ?></p>
                <p><strong>Price per Ticket:</strong> $<?php echo htmlspecialchars($booking['ticket_price']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo htmlspecialchars($booking['total_price']); ?></p>
            </div>
        </div>
        
        <div class="payment-form card">
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?booking_id=" . $booking_id); ?>">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" name="card_number" class="form-control" 
                               placeholder="1234 5678 9012 3456" maxlength="19">
                        <small class="form-text text-muted">For testing, use: 4242 4242 4242 4242</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Expiry Date</label>
                            <input type="text" name="expiry_date" class="form-control" 
                                   placeholder="MM/YY" maxlength="5">
                            <small class="form-text text-muted">For testing, use: 12/25</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label>CVV</label>
                            <input type="text" name="cvv" class="form-control" 
                                   placeholder="123" maxlength="4">
                            <small class="form-text text-muted">For testing, use: 123</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Pay Now</button>
                    <a href="view_events.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Invalid booking or payment already completed.</div>
        <a href="view_events.php" class="btn btn-primary">View Events</a>
    <?php endif; ?>
</div>

<script>
// Format card number input with spaces
document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = '';
    for(let i = 0; i < value.length; i++) {
        if(i > 0 && i % 4 === 0) {
            formattedValue += ' ';
        }
        formattedValue += value[i];
    }
    e.target.value = formattedValue;
});

// Format expiry date input
document.querySelector('input[name="expiry_date"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if(value.length > 2) {
        value = value.substr(0, 2) + '/' + value.substr(2);
    }
    e.target.value = value;
});
</script>

<?php include "../includes/footer.php"; ?> 