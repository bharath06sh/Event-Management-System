<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$num_tickets = "";
$errors = [];

// Initialize payment form variables
$bank_name = $account_number = $ifsc_code = $pin = "";
$bank_name_err = $account_number_err = $ifsc_code_err = $pin_err = "";

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

    // Validate bank details
    if(empty(trim($_POST["bank_name"]))) {
        $bank_name_err = "Please enter bank name.";
    } else {
        $bank_name = trim($_POST["bank_name"]);
    }

    if(empty(trim($_POST["account_number"]))) {
        $account_number_err = "Please enter account number.";
    } elseif(!preg_match('/^[0-9]{9,18}$/', trim($_POST["account_number"]))) {
        $account_number_err = "Account number should be between 9 and 18 digits.";
    } else {
        $account_number = trim($_POST["account_number"]);
    }

    if(empty(trim($_POST["ifsc_code"]))) {
        $ifsc_code_err = "Please enter IFSC code.";
    } elseif(!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', trim($_POST["ifsc_code"]))) {
        $ifsc_code_err = "Invalid IFSC code format.";
    } else {
        $ifsc_code = trim($_POST["ifsc_code"]);
    }

    if(empty(trim($_POST["pin"]))) {
        $pin_err = "Please enter PIN.";
    } elseif(!preg_match('/^[0-9]{4,6}$/', trim($_POST["pin"]))) {
        $pin_err = "PIN should be 4-6 digits.";
    } else {
        $pin = trim($_POST["pin"]);
    }
    
    // Process booking if no errors
    if(empty($errors) && empty($bank_name_err) && empty($account_number_err) && 
       empty($ifsc_code_err) && empty($pin_err)) {
        
        $total_price = $event['ticket_price'] * $num_tickets;
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert booking
            $sql = "INSERT INTO bookings (event_id, user_id, num_tickets, total_amount, payment_status, booking_date) 
                    VALUES (?, ?, ?, ?, 'completed', NOW())";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiid", $event_id, $_SESSION['id'], $num_tickets, $total_price);
            
            if(mysqli_stmt_execute($stmt)) {
                $booking_id = mysqli_insert_id($conn);
                
                // Insert payment details
                $payment_sql = "INSERT INTO payments (booking_id, bank_name, account_number, ifsc_code, amount, payment_date) 
                               VALUES (?, ?, ?, ?, ?, NOW())";
                
                $payment_stmt = mysqli_prepare($conn, $payment_sql);
                mysqli_stmt_bind_param($payment_stmt, "isssd", $booking_id, $bank_name, $account_number, $ifsc_code, $total_price);
                
                if(mysqli_stmt_execute($payment_stmt)) {
                    mysqli_commit($conn);
                    header("location: booking_confirmation.php?booking_id=" . $booking_id);
                    exit;
                } else {
                    throw new Exception("Error processing payment.");
                }
            } else {
                throw new Exception("Error creating booking.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}

include "../includes/header.php";
?>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        min-height: 100vh;
    }

    .booking-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        animation: fadeIn 0.5s ease-out;
    }

    .event-details {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .event-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .event-icon {
        font-size: 2.5rem;
        color: #3498db;
        margin-right: 1rem;
    }

    .event-title {
        color: #2c3e50;
        font-size: 1.8rem;
        margin: 0;
    }

    .event-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-item i {
        color: #3498db;
        font-size: 1.2rem;
    }

    .info-item p {
        margin: 0;
        color: #34495e;
    }

    .info-item strong {
        color: #2c3e50;
    }

    .booking-form {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 2px solid #e4e8f0;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .invalid-feedback {
        color: #e74c3c;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .price-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .price-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #34495e;
    }

    .price-total {
        border-top: 2px solid #e4e8f0;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .btn-book {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }

    .btn-book:hover {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    }

    .btn-cancel {
        background: white;
        border: 2px solid #e4e8f0;
        color: #34495e;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .btn-cancel:hover {
        background: #f8f9fa;
        border-color: #3498db;
        color: #3498db;
    }

    @media (max-width: 768px) {
        .booking-container {
            margin: 1rem;
            padding: 1rem;
        }

        .event-info {
            grid-template-columns: 1fr;
        }
    }

    .payment-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .payment-details h4 {
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        padding: 10px 20px;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    }

    .btn-secondary {
        background: #95a5a6;
        border: none;
        padding: 10px 20px;
    }

    .btn-secondary:hover {
        background: #7f8c8d;
    }
</style>

<div class="booking-container">
    <div class="event-details">
        <div class="event-header">
            <i class="fas fa-ticket-alt event-icon"></i>
            <h1 class="event-title">Book Tickets</h1>
        </div>

        <div class="event-info">
            <div class="info-item">
                <i class="fas fa-calendar"></i>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                   <?php echo date('h:i A', strtotime($event['end_time'])); ?></p>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name']); ?></p>
            </div>
            <div class="info-item">
                <i class="fas fa-user"></i>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
            </div>
        </div>

        <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
    </div>

    <div class="booking-form">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?event_id=" . $event_id); ?>" method="post">
            <div class="form-group">
                <label class="form-label">Number of Tickets</label>
                <input type="number" name="num_tickets" class="form-control <?php echo (!empty($errors)) ? 'is-invalid' : ''; ?>" 
                       value="<?php echo isset($num_tickets) ? $num_tickets : 1; ?>" min="1" max="<?php echo $event['available_tickets']; ?>">
                <span class="invalid-feedback"><?php echo implode("<br>", $errors); ?></span>
            </div>

            <!-- Payment Details Section -->
            <div class="payment-details mt-4">
                <h4>Payment Details</h4>
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control <?php echo (!empty($bank_name_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo isset($bank_name) ? $bank_name : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $bank_name_err ?? ''; ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-control <?php echo (!empty($account_number_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo isset($account_number) ? $account_number : ''; ?>" 
                           pattern="[0-9]{9,18}" title="Account number should be between 9 and 18 digits" required>
                    <span class="invalid-feedback"><?php echo $account_number_err ?? ''; ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" name="ifsc_code" class="form-control <?php echo (!empty($ifsc_code_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo isset($ifsc_code) ? $ifsc_code : ''; ?>" 
                           pattern="^[A-Z]{4}0[A-Z0-9]{6}$" title="IFSC code should be 11 characters (e.g., SBIN0001234)" required>
                    <span class="invalid-feedback"><?php echo $ifsc_code_err ?? ''; ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label">PIN</label>
                    <input type="password" name="pin" class="form-control <?php echo (!empty($pin_err)) ? 'is-invalid' : ''; ?>" 
                           pattern="[0-9]{4,6}" title="PIN should be 4-6 digits" required>
                    <span class="invalid-feedback"><?php echo $pin_err ?? ''; ?></span>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Confirm Booking & Pay
                </button>
                <a href="view_events.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[name="num_tickets"]').addEventListener('input', function() {
    const quantity = this.value;
    const pricePerTicket = <?php echo $event['ticket_price']; ?>;
    const total = quantity * pricePerTicket;
    
    document.getElementById('quantity-display').textContent = quantity;
    document.getElementById('total-price').textContent = '$' + total.toFixed(2);
});
</script>

<?php include "../includes/footer.php"; ?> 