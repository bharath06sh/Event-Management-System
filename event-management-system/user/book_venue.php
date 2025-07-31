<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get available venues
$venues_sql = "SELECT * FROM venues WHERE is_available = 1";
$venues_result = mysqli_query($conn, $venues_sql);

$venue_id = $booking_date = $start_time = $end_time = "";
$venue_err = $date_err = $time_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate venue
    if(empty(trim($_POST["venue_id"]))){
        $venue_err = "Please select a venue.";
    } else {
        $venue_id = trim($_POST["venue_id"]);
    }
    
    // Validate date
    if(empty(trim($_POST["booking_date"]))){
        $date_err = "Please select booking date.";
    } else {
        $booking_date = trim($_POST["booking_date"]);
    }
    
    // Validate times
    if(empty(trim($_POST["start_time"])) || empty(trim($_POST["end_time"]))){
        $time_err = "Please select both start and end times.";
    } else {
        $start_time = trim($_POST["start_time"]);
        $end_time = trim($_POST["end_time"]);
    }
    
    // If no errors, proceed with booking
    if(empty($venue_err) && empty($date_err) && empty($time_err)){
        $sql = "INSERT INTO venue_bookings (user_id, venue_id, booking_date, start_time, end_time) 
                VALUES (?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "iisss", $param_user, $param_venue, $param_date, $param_start, $param_end);
            
            $param_user = $_SESSION["id"];
            $param_venue = $venue_id;
            $param_date = $booking_date;
            $param_start = $start_time;
            $param_end = $end_time;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: dashboard.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

include "../includes/header.php";
?>

<div class="book-venue-container">
    <div class="book-venue-header">
        <h1><i class="fas fa-map-marked-alt"></i> Book a Venue</h1>
        <p>Select a venue and choose your preferred date and time</p>
    </div>

    <div class="book-venue-form">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label class="form-label">Select Venue</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <select name="venue_id" class="form-control <?php echo (!empty($venue_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Choose a venue</option>
                        <?php while($venue = mysqli_fetch_assoc($venues_result)): ?>
                            <option value="<?php echo $venue['id']; ?>" <?php echo ($venue_id == $venue['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($venue['name']); ?> - $<?php echo htmlspecialchars($venue['price_per_day']); ?>/day
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <span class="invalid-feedback"><?php echo $venue_err; ?></span>
            </div>

            <div class="form-group">
                <label class="form-label">Booking Date</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    <input type="date" name="booking_date" class="form-control <?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $booking_date; ?>">
                </div>
                <span class="invalid-feedback"><?php echo $date_err; ?></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                        <input type="time" name="start_time" class="form-control <?php echo (!empty($time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_time; ?>">
                    </div>
                    <span class="invalid-feedback"><?php echo $time_err; ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                        <input type="time" name="end_time" class="form-control <?php echo (!empty($time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_time; ?>">
                    </div>
                    <span class="invalid-feedback"><?php echo $time_err; ?></span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-book">
                    <i class="fas fa-check-circle"></i> Book Venue
                </button>
                <a href="dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
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

    .book-venue-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    .book-venue-header {
        text-align: center;
        margin-bottom: 2rem;
        animation: slideIn 0.8s ease-out;
    }

    .book-venue-header h1 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        animation: pulse 3s infinite;
    }

    .book-venue-header p {
        color: #34495e;
        font-size: 1.1rem;
    }

    .book-venue-form {
        background: rgba(255, 255, 255, 0.95);
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.2);
        border: 1px solid rgba(52, 152, 219, 0.3);
        backdrop-filter: blur(10px);
        animation: fadeIn 1s ease-out;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .book-venue-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(52, 152, 219, 0.3);
    }

    .form-group {
        margin-bottom: 1.5rem;
        animation: slideIn 0.8s ease-out;
        animation-fill-mode: both;
    }

    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.2s; }
    .form-group:nth-child(3) { animation-delay: 0.3s; }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2c3e50;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .form-label:hover {
        color: #3498db;
    }

    .input-group {
        display: flex;
        align-items: center;
        transition: transform 0.3s ease;
    }

    .input-group:hover {
        transform: translateX(5px);
    }

    .input-group-text {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: 1px solid #3498db;
        border-right: none;
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }

    .input-group:hover .input-group-text {
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    }

    .form-control {
        background: white;
        border: 1px solid #bdc3c7;
        color: #2c3e50;
        padding: 0.75rem 1rem;
        border-radius: 0 8px 8px 0;
        width: 100%;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        background: white;
        transform: translateY(-2px);
    }

    .invalid-feedback {
        color: #e74c3c;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        animation: slideIn 0.3s ease-out;
    }

    .form-control.is-invalid {
        border-color: #e74c3c;
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        animation: fadeIn 1s ease-out;
    }

    .btn-book {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .btn-book::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: 0.5s;
    }

    .btn-book:hover::before {
        left: 100%;
    }

    .btn-book:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    }

    .btn-cancel {
        background: white;
        color: #2c3e50;
        border: 1px solid #bdc3c7;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #3498db;
        color: #3498db;
    }

    select.form-control {
        background: white;
        color: #2c3e50;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    select.form-control:hover {
        border-color: #3498db;
    }

    select.form-control option {
        background: white;
        color: #2c3e50;
    }

    @media (max-width: 768px) {
        .book-venue-container {
            padding: 1rem;
            margin: 1rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-book, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php include "../includes/footer.php"; ?> 