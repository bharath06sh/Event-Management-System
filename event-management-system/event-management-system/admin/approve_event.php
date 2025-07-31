<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is admin
checkAdmin();

if(isset($_GET["id"]) && isset($_GET["action"])){
    $event_id = $_GET["id"];
    $action = $_GET["action"];
    
    if($action == "approve" || $action == "reject"){
        $sql = "UPDATE events SET status = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            $status = ($action == "approve") ? "approved" : "rejected";
            mysqli_stmt_bind_param($stmt, "si", $status, $event_id);
            
            if(mysqli_stmt_execute($stmt)){
                // Get event details for email notification
                $event_sql = "SELECT e.title, u.email, u.username 
                             FROM events e 
                             JOIN users u ON e.user_id = u.id 
                             WHERE e.id = ?";
                if($event_stmt = mysqli_prepare($conn, $event_sql)){
                    mysqli_stmt_bind_param($event_stmt, "i", $event_id);
                    mysqli_stmt_execute($event_stmt);
                    $event_result = mysqli_stmt_get_result($event_stmt);
                    
                    if($event = mysqli_fetch_assoc($event_result)){
                        // Send email notification
                        $to = $event['email'];
                        $subject = "Event Status Update - " . $event['title'];
                        $message = "Dear " . $event['username'] . ",\n\n";
                        $message .= "Your event '" . $event['title'] . "' has been " . $status . ".\n\n";
                        if($status == "approved"){
                            $message .= "Your event is now live and visible to all users.\n";
                        } else {
                            $message .= "Please contact the administrator for more information.\n";
                        }
                        $message .= "\nBest regards,\nEvent Management System";
                        
                        $headers = "From: noreply@eventmanagement.com";
                        
                        mail($to, $subject, $message, $headers);
                    }
                    mysqli_stmt_close($event_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Redirect back to admin dashboard
header("location: dashboard.php");
exit();
?> 