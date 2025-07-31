<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$event = $result->fetch_assoc();

// Get available venues
$venues_result = $conn->query("SELECT * FROM venues WHERE is_available = 1");
$venues = $venues_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $venue_id = $_POST['venue_id'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $ticket_price = $_POST['ticket_price'];
    $total_tickets = $_POST['total_tickets'];

    // Update event
    $stmt = $conn->prepare("UPDATE events SET 
        title = ?, 
        description = ?, 
        venue_id = ?, 
        event_date = ?, 
        start_time = ?, 
        end_time = ?, 
        ticket_price = ?, 
        total_tickets = ?,
        status = 'pending'
        WHERE id = ? AND user_id = ?");
    
    $stmt->bind_param("ssisssddiii", 
        $title, 
        $description, 
        $venue_id, 
        $event_date, 
        $start_time, 
        $end_time, 
        $ticket_price, 
        $total_tickets,
        $event_id,
        $user_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Event updated successfully!";
        header('Location: view_events.php');
        exit();
    } else {
        $error = "Error updating event: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Event</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="title" class="form-label">Event Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="venue_id" class="form-label">Venue</label>
                <select class="form-control" id="venue_id" name="venue_id" required>
                    <?php foreach ($venues as $venue): ?>
                        <option value="<?php echo $venue['id']; ?>" <?php echo ($venue['id'] == $event['venue_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($venue['name']); ?> (Capacity: <?php echo $venue['capacity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="event_date" class="form-label">Event Date</label>
                <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $event['start_time']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo $event['end_time']; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ticket_price" class="form-label">Ticket Price</label>
                    <input type="number" class="form-control" id="ticket_price" name="ticket_price" step="0.01" value="<?php echo $event['ticket_price']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="total_tickets" class="form-label">Total Tickets</label>
                    <input type="number" class="form-control" id="total_tickets" name="total_tickets" value="<?php echo $event['total_tickets']; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Update Event</button>
                <a href="view_events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 