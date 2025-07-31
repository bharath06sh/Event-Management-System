<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is admin
checkAdmin();

// Get total users
$users_sql = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$users_result = mysqli_query($conn, $users_sql);
$users_count = mysqli_fetch_assoc($users_result)['total_users'];

// Get total events
$events_sql = "SELECT COUNT(*) as total_events FROM events";
$events_result = mysqli_query($conn, $events_sql);
$events_count = mysqli_fetch_assoc($events_result)['total_events'];

// Get total venues
$venues_sql = "SELECT COUNT(*) as total_venues FROM venues";
$venues_result = mysqli_query($conn, $venues_sql);
$venues_count = mysqli_fetch_assoc($venues_result)['total_venues'];

// Get total bookings
$bookings_sql = "SELECT COUNT(*) as total_bookings FROM bookings";
$bookings_result = mysqli_query($conn, $bookings_sql);
$bookings_count = mysqli_fetch_assoc($bookings_result)['total_bookings'];

// Get pending events
$pending_sql = "SELECT e.*, u.username as organizer, v.name as venue_name 
                FROM events e 
                LEFT JOIN users u ON e.user_id = u.id 
                LEFT JOIN venues v ON e.venue_id = v.id 
                WHERE e.status = 'pending' 
                ORDER BY e.created_at DESC";
$pending_result = mysqli_query($conn, $pending_sql);

// Get monthly booking stats for chart
$monthly_stats_sql = "SELECT 
                        DATE_FORMAT(booking_date, '%Y-%m') as month,
                        COUNT(*) as total_bookings,
                        SUM(total_amount) as total_revenue
                      FROM bookings
                      WHERE booking_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
                      ORDER BY month ASC";
$monthly_stats_result = mysqli_query($conn, $monthly_stats_sql);

$months = [];
$bookings_data = [];
$revenue_data = [];

while($stat = mysqli_fetch_assoc($monthly_stats_result)) {
    $months[] = date('M Y', strtotime($stat['month'] . '-01'));
    $bookings_data[] = $stat['total_bookings'];
    $revenue_data[] = $stat['total_revenue'];
}

include "../includes/header.php";
?>

<h1>Admin Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p class="stat-number"><?php echo $users_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Events</h3>
        <p class="stat-number"><?php echo $events_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Venues</h3>
        <p class="stat-number"><?php echo $venues_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Bookings</h3>
        <p class="stat-number"><?php echo $bookings_count; ?></p>
    </div>
</div>

<div class="card">
    <h2>Booking Analytics</h2>
    <canvas id="bookingChart"></canvas>
</div>

<div class="card mt-2">
    <h2>Pending Events</h2>
    <?php if(mysqli_num_rows($pending_result) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organizer</th>
                    <th>Venue</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($event = mysqli_fetch_assoc($pending_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                        <td><?php echo htmlspecialchars($event['venue_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                        <td>
                            <a href="approve_event.php?id=<?php echo $event['id']; ?>&action=approve" class="btn btn-primary">Approve</a>
                            <a href="approve_event.php?id=<?php echo $event['id']; ?>&action=reject" class="btn btn-secondary ml-2">Reject</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending events to approve.</p>
    <?php endif; ?>
</div>

<script>
const ctx = document.getElementById('bookingChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Number of Bookings',
            data: <?php echo json_encode($bookings_data); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'Revenue ($)',
            data: <?php echo json_encode($revenue_data); ?>,
            type: 'line',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 2,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Bookings'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Revenue ($)'
                }
            }
        }
    }
});
</script>

<?php include "../includes/footer.php"; ?> 