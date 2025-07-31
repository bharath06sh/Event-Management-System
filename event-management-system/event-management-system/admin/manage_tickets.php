<?php
session_start();
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: ../auth/login.php");
    exit;
}

// Get date range filters with validation
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d');

// Validate dates
if (!validateDate($start_date) || !validateDate($end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

$error = "";
$stats = array(
    'total_bookings' => 0,
    'total_tickets_sold' => 0,
    'total_revenue' => 0,
    'unique_events' => 0,
    'unique_customers' => 0
);

try {
    // Get overall ticket statistics
    $stats_sql = "SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(num_tickets), 0) as total_tickets_sold,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(DISTINCT event_id) as unique_events,
        COUNT(DISTINCT user_id) as unique_customers
    FROM bookings 
    WHERE payment_status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?";

    $stats_stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stats_stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $stats = mysqli_fetch_assoc($stats_result);

    // Get daily ticket sales data for chart
    $daily_sql = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as num_bookings,
        COALESCE(SUM(num_tickets), 0) as tickets_sold,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM bookings 
    WHERE payment_status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date";

    $daily_stmt = mysqli_prepare($conn, $daily_sql);
    mysqli_stmt_bind_param($daily_stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);

    // Get top events by ticket sales
    $top_events_sql = "SELECT 
        e.title,
        v.name as venue_name,
        COUNT(b.id) as num_bookings,
        COALESCE(SUM(b.num_tickets), 0) as tickets_sold,
        COALESCE(SUM(b.total_amount), 0) as revenue
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE b.payment_status = 'completed'
    AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY e.id, e.title, v.name
    ORDER BY tickets_sold DESC
    LIMIT 5";

    $top_events_stmt = mysqli_prepare($conn, $top_events_sql);
    mysqli_stmt_bind_param($top_events_stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($top_events_stmt);
    $top_events_result = mysqli_stmt_get_result($top_events_stmt);

} catch (Exception $e) {
    $error = "Error fetching ticket data: " . $e->getMessage();
}

// Helper function to validate date format
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

include "../includes/header.php";
?>

<div class="container">
    <h2>Ticket Management</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row">
                <div class="form-group col-md-4">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo htmlspecialchars($start_date); ?>"
                           max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($end_date); ?>"
                           max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Tickets Sold</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_tickets_sold']); ?></h2>
                    <p class="card-text">From <?php echo number_format($stats['total_bookings']); ?> bookings</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="card-text">$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                    <p class="card-text"><?php echo number_format($stats['unique_customers']); ?> unique customers</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Events with Sales</h5>
                    <h2 class="card-text"><?php echo number_format($stats['unique_events']); ?></h2>
                    <p class="card-text">Active events with ticket sales</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h4>Ticket Sales Trend</h4>
            <canvas id="ticketSalesChart"></canvas>
        </div>
    </div>
    
    <!-- Top Events Table -->
    <div class="card">
        <div class="card-body">
            <h4>Top Performing Events</h4>
            <?php if($top_events_result && mysqli_num_rows($top_events_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Venue</th>
                                <th>Bookings</th>
                                <th>Tickets Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($event = mysqli_fetch_assoc($top_events_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($event['num_bookings']); ?></td>
                                    <td><?php echo number_format($event['tickets_sold']); ?></td>
                                    <td>$<?php echo number_format($event['revenue'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No ticket sales data available for the selected period.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Prepare data for charts -->
<script>
var salesData = {
    dates: [],
    tickets: [],
    revenue: []
};

<?php 
if($daily_result && mysqli_num_rows($daily_result) > 0):
    mysqli_data_seek($daily_result, 0);
    while($day = mysqli_fetch_assoc($daily_result)): 
?>
    salesData.dates.push("<?php echo $day['date']; ?>");
    salesData.tickets.push(<?php echo $day['tickets_sold']; ?>);
    salesData.revenue.push(<?php echo $day['revenue']; ?>);
<?php 
    endwhile;
endif;
?>
</script>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Create the sales chart
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('ticketSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.dates,
            datasets: [{
                label: 'Tickets Sold',
                data: salesData.tickets,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                yAxisID: 'y'
            }, {
                label: 'Revenue ($)',
                data: salesData.revenue,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Tickets Sold'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
});
</script>

<?php include "../includes/footer.php"; ?> 