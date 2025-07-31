<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if($booking_id > 0) {
    // Get booking details with event and payment information
    $sql = "SELECT b.*, e.title as event_title, e.event_date, e.start_time, e.end_time,
                   v.name as venue_name, p.bank_name, p.account_number, p.ifsc_code
            FROM bookings b
            JOIN events e ON b.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN payments p ON b.id = p.booking_id
            WHERE b.id = ? AND b.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if(!$booking) {
        header("location: view_events.php");
        exit;
    }
}

include "../includes/header.php";
?>

<div class="container">
    <div class="confirmation-card">
        <div class="confirmation-header">
            <i class="fas fa-check-circle success-icon"></i>
            <h2>Booking Confirmed!</h2>
            <p class="booking-id">Booking ID: #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></p>
        </div>

        <div class="confirmation-details">
            <div class="event-info">
                <h3><?php echo htmlspecialchars($booking['event_title']); ?></h3>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                           <?php echo date('h:i A', strtotime($booking['end_time'])); ?></p>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($booking['venue_name']); ?></p>
                    </div>
                </div>
            </div>

            <div class="booking-summary">
                <h4>Booking Summary</h4>
                <div class="summary-item">
                    <span>Number of Tickets:</span>
                    <span><?php echo $booking['num_tickets']; ?></span>
                </div>
                <div class="summary-item">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Payment Status:</span>
                    <span class="status-completed">Completed</span>
                </div>
            </div>

            <div class="payment-details">
                <h4>Payment Details</h4>
                <div class="payment-info">
                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($booking['bank_name']); ?></p>
                    <p><strong>Account Number:</strong> <?php echo substr($booking['account_number'], 0, 4) . '****' . substr($booking['account_number'], -4); ?></p>
                    <p><strong>IFSC Code:</strong> <?php echo htmlspecialchars($booking['ifsc_code']); ?></p>
                </div>
            </div>
        </div>

        <div class="confirmation-actions">
            <a href="my_bookings.php" class="btn btn-primary">
                <i class="fas fa-ticket-alt"></i> View My Bookings
            </a>
            <a href="view_events.php" class="btn btn-secondary">
                <i class="fas fa-calendar-alt"></i> Browse More Events
            </a>
        </div>
    </div>
</div>

<style>
    .confirmation-card {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .confirmation-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f2f5;
    }

    .success-icon {
        font-size: 4rem;
        color: #2ecc71;
        margin-bottom: 1rem;
    }

    .booking-id {
        color: #7f8c8d;
        font-size: 1.1rem;
    }

    .confirmation-details {
        margin-bottom: 2rem;
    }

    .event-info {
        margin-bottom: 2rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
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

    .booking-summary, .payment-details {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .status-completed {
        color: #2ecc71;
        font-weight: 600;
    }

    .payment-info p {
        margin-bottom: 0.5rem;
    }

    .confirmation-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        color: white;
    }

    .btn-secondary {
        background: #95a5a6;
        border: none;
        color: white;
    }

    .btn-secondary:hover {
        background: #7f8c8d;
        color: white;
    }

    @media (max-width: 768px) {
        .confirmation-card {
            margin: 1rem;
            padding: 1rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .confirmation-actions {
            flex-direction: column;
        }
    }
</style>

<?php include "../includes/footer.php"; ?> 