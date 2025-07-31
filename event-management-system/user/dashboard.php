<?php
require_once "../includes/header.php";
require_once "../config/db.php";

// Check and create tables if they don't exist
$tables_sql = [
    "CREATE TABLE IF NOT EXISTS venues (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        capacity INT NOT NULL,
        description TEXT,
        price_per_hour DECIMAL(10,2) NOT NULL,
        status ENUM('available', 'unavailable') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS venue_bookings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        venue_id INT NOT NULL,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (venue_id) REFERENCES venues(id)
    )"
];

foreach ($tables_sql as $sql) {
    if (!mysqli_query($conn, $sql)) {
        // If there's an error with foreign keys, try creating without them
        if (strpos($sql, "FOREIGN KEY") !== false) {
            $sql = str_replace("FOREIGN KEY (user_id) REFERENCES users(id),\n        FOREIGN KEY (venue_id) REFERENCES venues(id)", "", $sql);
            mysqli_query($conn, $sql);
        }
    }
}

// Initialize counts
$events_count = 0;
$bookings_count = 0;

// Get user's events count
$events_sql = "SELECT COUNT(*) as event_count FROM events WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $events_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $events_result = mysqli_stmt_get_result($stmt);
    if($events_result) {
        $events_count = mysqli_fetch_assoc($events_result)['event_count'];
    }
}

// Get user's bookings count
$bookings_sql = "SELECT COUNT(*) as booking_count FROM venue_bookings WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $bookings_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $bookings_result = mysqli_stmt_get_result($stmt);
    if($bookings_result) {
        $bookings_count = mysqli_fetch_assoc($bookings_result)['booking_count'];
    }
}

// Get upcoming events
$upcoming_events = null;
$upcoming_sql = "SELECT e.*, v.name as venue_name 
                 FROM events e 
                 LEFT JOIN venues v ON e.venue_id = v.id 
                 WHERE e.user_id = ? AND e.event_date >= CURDATE() 
                 ORDER BY e.event_date ASC LIMIT 5";

if($stmt = mysqli_prepare($conn, $upcoming_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $upcoming_events = mysqli_stmt_get_result($stmt);
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <p>Here's an overview of your event management activities</p>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Total Events</h3>
                <p><?php echo $events_count; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Venue Bookings</h3>
                <p><?php echo $bookings_count; ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card upcoming-events">
            <h2><i class="fas fa-calendar-day"></i> Upcoming Events</h2>
            <?php if($upcoming_events && mysqli_num_rows($upcoming_events) > 0): ?>
                <div class="event-list">
                    <?php while($event = mysqli_fetch_assoc($upcoming_events)): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <span class="date"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="event-details">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($event['start_time'])); ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue_name']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-events">No upcoming events found.</p>
            <?php endif; ?>
        </div>

        <div class="dashboard-card quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="create_event.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    Create New Event
                </a>
                <a href="book_venue.php" class="action-btn">
                    <i class="fas fa-map-marked-alt"></i>
                    Book a Venue
                </a>
                <a href="view_events.php" class="action-btn">
                    <i class="fas fa-list"></i>
                    View All Events
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 50%, #E0F7FF 100%);
        color: #2c3e50;
    }

    .dashboard-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .dashboard-header {
        text-align: center;
        margin-bottom: 2rem;
        color: #2c3e50;
    }

    .dashboard-header h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: #2c3e50;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .dashboard-header p {
        color: #34495e;
        font-size: 1.1rem;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        border: 1px solid #2980b9;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(52, 152, 219, 0.2);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-right: 1rem;
        color: #ffffff;
    }

    .stat-info h3 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #f8f9fa;
    }

    .stat-info p {
        font-size: 2rem;
        font-weight: 600;
        margin: 0;
        color: #ffffff;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .dashboard-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(52, 152, 219, 0.3);
    }

    .dashboard-card h2 {
        color: #3498db;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .event-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        transition: transform 0.3s ease;
        border: 1px solid #e9ecef;
    }

    .event-item:hover {
        transform: translateX(5px);
        background: #e9ecef;
    }

    .event-date {
        background: #3498db;
        color: white;
        padding: 0.5rem;
        border-radius: 8px;
        text-align: center;
        min-width: 60px;
        margin-right: 1rem;
    }

    .event-date .date {
        font-size: 1.5rem;
        font-weight: 600;
        display: block;
    }

    .event-date .month {
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    .event-details h4 {
        margin: 0 0 0.5rem 0;
        color: #2c3e50;
    }

    .event-details p {
        margin: 0.25rem 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .event-details i {
        width: 20px;
        color: #3498db;
    }

    .action-buttons {
        display: grid;
        gap: 1rem;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: #3498db;
        border-radius: 10px;
        color: #ffffff;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #2980b9;
    }

    .action-btn:hover {
        background: #2980b9;
        color: white;
        transform: translateX(5px);
        border-color: #2471a3;
    }

    .action-btn i {
        font-size: 1.2rem;
    }

    .no-events {
        text-align: center;
        color: #6c757d;
        padding: 2rem;
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }

        .stats-container {
            grid-template-columns: 1fr;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require_once "../includes/footer.php"; ?> 