<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

$venue_id = isset($_GET['venue_id']) ? (int)$_GET['venue_id'] : 0;
$booking_date = $num_days = "";
$errors = [];

// Get venue details
if($venue_id > 0) {
    $venue_sql = "SELECT * FROM venues WHERE id = ? AND is_available = 1";
    $stmt = mysqli_prepare($conn, $venue_sql);
    mysqli_stmt_bind_param($stmt, "i", $venue_id);
    mysqli_stmt_execute($stmt);
    $venue_result = mysqli_stmt_get_result($stmt);
    $venue = mysqli_fetch_assoc($venue_result);
    
    if(!$venue) {
        header("location: view_venues.php");
        exit;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate booking date
    if(empty(trim($_POST["booking_date"]))) {
        $errors[] = "Please select a booking date.";
    } else {
        $booking_date = trim($_POST["booking_date"]);
        // Check if date is in the future
        if(strtotime($booking_date) < strtotime(date('Y-m-d'))) {
            $errors[] = "Booking date must be in the future.";
        }
    }
    
    // Validate number of days
    if(empty(trim($_POST["num_days"]))) {
        $errors[] = "Please enter number of days.";
    } else {
        $num_days = trim($_POST["num_days"]);
        if($num_days < 1) {
            $errors[] = "Number of days must be at least 1.";
        }
    }
    
    // Check venue availability for the selected dates
    if(empty($errors)) {
        $end_date = date('Y-m-d', strtotime($booking_date . ' + ' . ($num_days - 1) . ' days'));
        
        $check_sql = "SELECT COUNT(*) as count FROM events 
                     WHERE venue_id = ? 
                     AND ((event_date BETWEEN ? AND ?) 
                     OR (DATE_ADD(event_date, INTERVAL duration - 1 DAY) BETWEEN ? AND ?))";
        
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "issss", $venue_id, $booking_date, $end_date, $booking_date, $end_date);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $booking_count = mysqli_fetch_assoc($check_result)['count'];
        
        if($booking_count > 0) {
            $errors[] = "Venue is not available for the selected dates.";
        }
    }
    
    // Process booking if no errors
    if(empty($errors)) {
        $total_price = $venue['price_per_day'] * $num_days;
        
        $sql = "INSERT INTO venue_bookings (venue_id, user_id, start_date, num_days, total_price, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisid", $venue_id, $_SESSION['id'], $booking_date, $num_days, $total_price);
        
        if(mysqli_stmt_execute($stmt)) {
            $booking_id = mysqli_insert_id($conn);
            header("location: pay_venue.php?booking_id=" . $booking_id);
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

include "../includes/header.php";
?>

<div class="container">
    <h2>Book Venue</h2>
    
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($venue)): ?>
        <div class="venue-details card mb-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($venue['address']); ?></p>
                <p><strong>Capacity:</strong> <?php echo htmlspecialchars($venue['capacity']); ?> people</p>
                <p><strong>Price per day:</strong> $<?php echo htmlspecialchars($venue['price_per_day']); ?></p>
            </div>
        </div>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?venue_id=" . $venue_id); ?>">
            <div class="form-group">
                <label>Booking Date</label>
                <input type="date" name="booking_date" class="form-control" 
                       min="<?php echo date('Y-m-d'); ?>" 
                       value="<?php echo htmlspecialchars($booking_date); ?>">
            </div>
            
            <div class="form-group">
                <label>Number of Days</label>
                <input type="number" name="num_days" class="form-control" 
                       min="1" value="<?php echo htmlspecialchars($num_days); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Proceed to Payment</button>
            <a href="view_venues.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">Invalid venue selected.</div>
        <a href="view_venues.php" class="btn btn-primary">View Venues</a>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 