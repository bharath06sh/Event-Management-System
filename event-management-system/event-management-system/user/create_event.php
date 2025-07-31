<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get available venues
$venues_sql = "SELECT * FROM venues WHERE is_available = 1";
$venues_result = mysqli_query($conn, $venues_sql);

$title = $description = $venue_id = $event_date = $start_time = $end_time = $ticket_price = $total_tickets = "";
$title_err = $description_err = $venue_err = $date_err = $time_err = $price_err = $tickets_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter event title.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter event description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate venue
    if(empty(trim($_POST["venue_id"]))){
        $venue_err = "Please select a venue.";
    } else {
        $venue_id = trim($_POST["venue_id"]);
    }
    
    // Validate date
    if(empty(trim($_POST["event_date"]))){
        $date_err = "Please select event date.";
    } else {
        $event_date = trim($_POST["event_date"]);
    }
    
    // Validate times
    if(empty(trim($_POST["start_time"])) || empty(trim($_POST["end_time"]))){
        $time_err = "Please select both start and end times.";
    } else {
        $start_time = trim($_POST["start_time"]);
        $end_time = trim($_POST["end_time"]);
    }
    
    // Validate price
    if(empty(trim($_POST["ticket_price"]))){
        $price_err = "Please enter ticket price.";
    } elseif(!is_numeric(trim($_POST["ticket_price"])) || trim($_POST["ticket_price"]) <= 0){
        $price_err = "Please enter a valid price.";
    } else {
        $ticket_price = trim($_POST["ticket_price"]);
    }
    
    // Validate total tickets
    if(empty(trim($_POST["total_tickets"]))){
        $tickets_err = "Please enter total tickets.";
    } elseif(!is_numeric(trim($_POST["total_tickets"])) || trim($_POST["total_tickets"]) <= 0){
        $tickets_err = "Please enter a valid number of tickets.";
    } else {
        $total_tickets = trim($_POST["total_tickets"]);
    }
    
    // If no errors, proceed with insertion
    if(empty($title_err) && empty($description_err) && empty($venue_err) && empty($date_err) && 
       empty($time_err) && empty($price_err) && empty($tickets_err)){
        
        $sql = "INSERT INTO events (title, description, venue_id, user_id, event_date, start_time, end_time, 
                ticket_price, total_tickets) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssiissssii", $param_title, $param_description, $param_venue, 
                                 $param_user, $param_date, $param_start, $param_end, $param_price, $param_tickets);
            
            $param_title = $title;
            $param_description = $description;
            $param_venue = $venue_id;
            $param_user = getCurrentUserId();
            $param_date = $event_date;
            $param_start = $start_time;
            $param_end = $end_time;
            $param_price = $ticket_price;
            $param_tickets = $total_tickets;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: dashboard.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

include "../includes/header.php";
?>

<h2>Create New Event</h2>
<div class="card">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
            <span class="invalid-feedback"><?php echo $title_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
            <span class="invalid-feedback"><?php echo $description_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Venue</label>
            <select name="venue_id" class="form-control <?php echo (!empty($venue_err)) ? 'is-invalid' : ''; ?>">
                <option value="">Select Venue</option>
                <?php while($venue = mysqli_fetch_assoc($venues_result)): ?>
                    <option value="<?php echo $venue['id']; ?>" <?php echo ($venue_id == $venue['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($venue['name']); ?> - $<?php echo htmlspecialchars($venue['price_per_day']); ?>/day
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="invalid-feedback"><?php echo $venue_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" class="form-control <?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $event_date; ?>">
            <span class="invalid-feedback"><?php echo $date_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Start Time</label>
            <input type="time" name="start_time" class="form-control <?php echo (!empty($time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_time; ?>">
            <span class="invalid-feedback"><?php echo $time_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>End Time</label>
            <input type="time" name="end_time" class="form-control <?php echo (!empty($time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_time; ?>">
            <span class="invalid-feedback"><?php echo $time_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Ticket Price ($)</label>
            <input type="number" step="0.01" name="ticket_price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $ticket_price; ?>">
            <span class="invalid-feedback"><?php echo $price_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Total Tickets</label>
            <input type="number" name="total_tickets" class="form-control <?php echo (!empty($tickets_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $total_tickets; ?>">
            <span class="invalid-feedback"><?php echo $tickets_err; ?></span>
        </div>
        
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Create Event">
            <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
        </div>
    </form>
</div>

<?php include "../includes/footer.php"; ?> 