<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$venue = isset($_GET['venue']) ? trim($_GET['venue']) : '';

// Build query
$sql = "SELECT e.*, v.name as venue_name, u.username as organizer 
        FROM events e 
        LEFT JOIN venues v ON e.venue_id = v.id 
        LEFT JOIN users u ON e.user_id = u.id 
        WHERE e.status = 'approved'";

$params = array();
$types = "";

if(!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if(!empty($date)) {
    $sql .= " AND e.event_date = ?";
    $params[] = $date;
    $types .= "s";
}

if(!empty($venue)) {
    $sql .= " AND e.venue_id = ?";
    $params[] = $venue;
    $types .= "i";
}

$sql .= " ORDER BY e.event_date ASC";

// Get venues for filter
$venues_sql = "SELECT * FROM venues WHERE is_available = 1";
$venues_result = mysqli_query($conn, $venues_sql);

// Prepare and execute the main query
$stmt = mysqli_prepare($conn, $sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include "../includes/header.php";
?>

<div class="card mb-2">
    <h2>Search Events</h2>
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row">
        <div class="form-group col-md-4">
            <label>Search</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or description">
        </div>
        <div class="form-group col-md-3">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
        </div>
        <div class="form-group col-md-3">
            <label>Venue</label>
            <select name="venue" class="form-control">
                <option value="">All Venues</option>
                <?php while($venue_row = mysqli_fetch_assoc($venues_result)): ?>
                    <option value="<?php echo $venue_row['id']; ?>" <?php echo ($venue == $venue_row['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($venue_row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group col-md-2">
            <label>&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

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
                    <p><strong>Price:</strong> $<?php echo htmlspecialchars($event['ticket_price']); ?></p>
                    
                    <?php
                    // Check available tickets
                    $available_sql = "SELECT (e.total_tickets - COALESCE(SUM(b.num_tickets), 0)) as available 
                                    FROM events e 
                                    LEFT JOIN bookings b ON e.id = b.event_id 
                                    WHERE e.id = ? 
                                    GROUP BY e.id";
                    $available_stmt = mysqli_prepare($conn, $available_sql);
                    mysqli_stmt_bind_param($available_stmt, "i", $event['id']);
                    mysqli_stmt_execute($available_stmt);
                    $available_result = mysqli_stmt_get_result($available_stmt);
                    $available = mysqli_fetch_assoc($available_result)['available'];
                    ?>
                    
                    <p><strong>Available Tickets:</strong> <?php echo $available; ?></p>
                    
                    <?php if($available > 0): ?>
                        <a href="book_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary">Book Tickets</a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>Sold Out</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No events found matching your criteria.</p>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 