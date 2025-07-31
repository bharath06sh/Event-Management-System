<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get user's events and approved events
$sql = "SELECT e.*, v.name as venue_name 
        FROM events e 
        LEFT JOIN venues v ON e.venue_id = v.id 
        WHERE (e.user_id = ? OR e.status = 'approved')
        ORDER BY e.event_date DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

include "../includes/header.php";
?>

<div class="events-container">
    <div class="events-header">
        <h1><i class="fas fa-calendar-alt"></i> My Events</h1>
        <p>Manage and view all your events</p>
    </div>

    <div class="events-grid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($event = mysqli_fetch_assoc($result)): ?>
                <div class="event-card">
                    <?php if($event['image_path']): ?>
                        <div class="event-image">
                            <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($event['title']); ?>"
                                 style="width: 100%; height: 200px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    
                    <div class="event-content">
                        <div class="event-date">
                            <span class="date"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                        </div>
                        
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        
                        <div class="event-details">
                            <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                               <?php echo date('h:i A', strtotime($event['end_time'])); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue_name']); ?></p>
                            <p><i class="fas fa-ticket-alt"></i> $<?php echo number_format($event['ticket_price'], 2); ?></p>
                            <p><i class="fas fa-users"></i> <?php echo $event['total_tickets']; ?> tickets</p>
                        </div>
                        
                        <div class="event-description">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </div>
                        
                        <div class="event-actions">
                            <a href="book_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn-book">
                                <i class="fas fa-ticket-alt"></i> Book Ticket
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <h3>No Events Found</h3>
                <p>You haven't created any events yet.</p>
                <a href="create_event.php" class="btn-create">Create Your First Event</a>
            </div>
        <?php endif; ?>
    </div>
</div>

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

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    body {
        background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 50%, #E0F7FF 100%);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        color: #2c3e50;
        min-height: 100vh;
    }

    .events-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    .events-header {
        text-align: center;
        margin-bottom: 2rem;
        animation: slideIn 0.8s ease-out;
    }

    .events-header h1 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        animation: pulse 3s infinite;
    }

    .events-header p {
        color: #34495e;
        font-size: 1.1rem;
    }

    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding: 1rem;
    }

    .event-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.2);
        border: 1px solid rgba(52, 152, 219, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeIn 0.8s ease-out;
    }

    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(52, 152, 219, 0.3);
    }

    .event-image {
        width: 100%;
        height: 200px;
        overflow: hidden;
    }

    .event-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .event-card:hover .event-image img {
        transform: scale(1.05);
    }

    .event-content {
        padding: 1.5rem;
        position: relative;
    }

    .event-date {
        position: absolute;
        top: -30px;
        right: 20px;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
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

    .event-title {
        color: #2c3e50;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        padding-right: 80px;
    }

    .event-details {
        margin-bottom: 1rem;
    }

    .event-details p {
        color: #34495e;
        margin: 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-details i {
        color: #3498db;
        width: 20px;
    }

    .event-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .event-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-book {
        flex: 1;
        padding: 0.75rem;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border: none;
    }

    .btn-book:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    }

    .no-events {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.2);
        border: 1px solid rgba(52, 152, 219, 0.3);
        animation: fadeIn 1s ease-out;
    }

    .no-events i {
        font-size: 4rem;
        color: #3498db;
        margin-bottom: 1rem;
    }

    .no-events h3 {
        color: #2c3e50;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .no-events p {
        color: #6c757d;
        margin-bottom: 2rem;
    }

    .btn-create {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    }

    @media (max-width: 768px) {
        .events-container {
            padding: 1rem;
            margin: 1rem;
        }

        .events-grid {
            grid-template-columns: 1fr;
        }

        .event-actions {
            flex-direction: column;
        }

        .btn-book {
            width: 100%;
        }
    }
</style>

<?php include "../includes/footer.php"; ?> 