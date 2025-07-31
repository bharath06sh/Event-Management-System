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

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
        from { transform: translateX(-20px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }

    .dashboard-container {
        padding: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    .dashboard-header {
        margin-bottom: 2rem;
        animation: slideIn 0.8s ease-out;
    }

    .dashboard-header h1 {
        color: #2d3748;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .dashboard-header p {
        color: #718096;
        font-size: 1.1rem;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        animation: fadeIn 0.8s ease-out;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .stat-card h3 {
        color: #4a5568;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .stat-card .stat-number {
        color: #2d3748;
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        animation: pulse 3s infinite;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .dashboard-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.8s ease-out;
    }

    .dashboard-card h2 {
        color: #2d3748;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 1rem;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        background: #f7fafc;
        color: #4a5568;
        font-weight: 600;
        padding: 1rem;
        text-align: left;
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #4a5568;
    }

    .table tr:hover {
        background: #f7fafc;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #e2e8f0;
        border: none;
        color: #4a5568;
    }

    .btn-secondary:hover {
        background: #cbd5e0;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome to your event management control center</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Total Users</h3>
            <p class="stat-number"><?php echo $users_count; ?></p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-calendar-check"></i> Total Events</h3>
            <p class="stat-number"><?php echo $events_count; ?></p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-map-marker-alt"></i> Total Venues</h3>
            <p class="stat-number"><?php echo $venues_count; ?></p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-ticket-alt"></i> Total Bookings</h3>
            <p class="stat-number"><?php echo $bookings_count; ?></p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h2><i class="fas fa-chart-line"></i> Booking Analytics</h2>
            <div class="chart-container">
                <canvas id="bookingChart"></canvas>
            </div>
        </div>

        <div class="dashboard-card">
            <h2><i class="fas fa-clock"></i> Pending Events</h2>
            <?php if(mysqli_num_rows($pending_result) > 0): ?>
                <div class="table-responsive">
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
                                        <a href="approve_event.php?id=<?php echo $event['id']; ?>&action=reject" class="btn btn-secondary">Reject</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No pending events to approve.</p>
            <?php endif; ?>
        </div>
    </div>
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
            backgroundColor: 'rgba(102, 126, 234, 0.2)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'Revenue ($)',
            data: <?php echo json_encode($revenue_data); ?>,
            type: 'line',
            backgroundColor: 'rgba(118, 75, 162, 0.2)',
            borderColor: 'rgba(118, 75, 162, 1)',
            borderWidth: 2,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Bookings'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Revenue ($)'
                },
                grid: {
                    display: false
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            }
        }
    }
});
</script>

<?php include "../includes/footer.php"; ?> 