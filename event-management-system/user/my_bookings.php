<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Fetch user's bookings
$sql = "SELECT b.*, e.title, e.event_date, e.start_time, e.end_time, e.ticket_price, 
               v.name as venue_name, u.username as organizer
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        LEFT JOIN venues v ON e.venue_id = v.id
        LEFT JOIN users u ON e.user_id = u.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
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

    .bookings-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        animation: fadeIn 0.5s ease-out;
    }

    .page-header {
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-icon {
        font-size: 2.5rem;
        color: #3498db;
        margin-right: 1rem;
    }

    .page-title {
        color: #2c3e50;
        font-size: 2rem;
        margin: 0;
    }

    .booking-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .booking-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .booking-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f2f5;
    }

    .event-title {
        color: #2c3e50;
        font-size: 1.4rem;
        margin: 0;
    }

    .booking-date {
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    .booking-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1rem;
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

    .booking-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #34495e;
    }

    .summary-total {
        border-top: 2px solid #e4e8f0;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .no-bookings {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .no-bookings i {
        font-size: 4rem;
        color: #bdc3c7;
        margin-bottom: 1rem;
    }

    .no-bookings h3 {
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .no-bookings p {
        color: #7f8c8d;
        margin-bottom: 1.5rem;
    }

    .btn-view-events {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-view-events:hover {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        color: white;
    }

    @media (max-width: 768px) {
        .bookings-container {
            margin: 1rem;
            padding: 1rem;
        }

        .booking-info {
            grid-template-columns: 1fr;
        }

        .booking-header {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
</style>

<div class="bookings-container">
    <div class="page-header">
        <i class="fas fa-ticket-alt page-icon"></i>
        <h1 class="page-title">My Bookings</h1>
    </div>

    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($booking = mysqli_fetch_assoc($result)): ?>
            <div class="booking-card">
                <div class="booking-header">
                    <h2 class="event-title"><?php echo htmlspecialchars($booking['title']); ?></h2>
                    <div class="booking-date">
                        <i class="fas fa-calendar-check"></i>
                        Booked on <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                    </div>
                </div>

                <div class="booking-info">
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <p><strong>Event Date:</strong> <?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
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
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <p><strong>Organizer:</strong> <?php echo htmlspecialchars($booking['organizer']); ?></p>
                    </div>
                </div>

                <div class="booking-summary">
                    <div class="summary-item">
                        <span>Number of Tickets:</span>
                        <span><?php echo $booking['num_tickets']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Price per Ticket:</span>
                        <span>$<?php echo number_format($booking['ticket_price'], 2); ?></span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-ticket-alt"></i>
            <h3>No Bookings Found</h3>
            <p>You haven't booked any events yet. Start exploring our events!</p>
            <a href="view_events.php" class="btn-view-events">
                <i class="fas fa-calendar-alt"></i> View Events
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 