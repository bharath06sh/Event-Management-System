<?php
session_start();
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: ../auth/login.php");
    exit;
}

// Handle user deletion
if(isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $success = false;
    $error = "";
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
    
        // Don't allow admin deletion
        $check_sql = "SELECT role FROM users WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($result);
        
        if($user && $user['role'] !== 'admin') {
            // Delete user's bookings first
            $delete_bookings = "DELETE FROM bookings WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $delete_bookings);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Delete user's events
            $delete_events = "DELETE FROM events WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $delete_events);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Finally delete the user
            $delete_user = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_user);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
                
            // Commit transaction
            mysqli_commit($conn);
            $success = true;
        } else {
            $error = "Cannot delete admin users.";
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'username';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'ASC';

// Build query with proper error handling
try {
    $sql = "SELECT u.*, 
                   COUNT(DISTINCT e.id) as total_events,
                   COUNT(DISTINCT b.id) as total_bookings
            FROM users u
            LEFT JOIN events e ON u.id = e.user_id
            LEFT JOIN bookings b ON u.id = b.user_id
            WHERE 1=1";

    $params = array();
    $types = "";

    if(!empty($search)) {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }

    if(!empty($role)) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
        $types .= "s";
    }

    $sql .= " GROUP BY u.id";

    // Validate and apply sorting
    $allowed_sort_columns = ['username', 'email', 'role', 'created_at'];
    $sort = in_array($sort, $allowed_sort_columns) ? $sort : 'username';
    $order = $order === 'DESC' ? 'DESC' : 'ASC';
    $sql .= " ORDER BY u.$sort $order";

    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $sql);
    if(!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} catch (Exception $e) {
    $error = "Error fetching users: " . $e->getMessage();
    $result = false;
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

    .manage-users-container {
        padding: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    .page-header {
        margin-bottom: 2rem;
        animation: slideIn 0.8s ease-out;
    }

    .page-header h2 {
        color: #2d3748;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: #718096;
        font-size: 1.1rem;
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    .filter-card .form-group {
        margin-bottom: 1rem;
    }

    .filter-card label {
        color: #4a5568;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
    }

    .filter-card .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem;
        width: 100%;
        transition: all 0.3s ease;
    }

    .filter-card .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }

    .btn-secondary:hover {
        background: #cbd5e0;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        color: white;
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
    }

    .table-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.8s ease-out;
        overflow-x: auto;
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
        border-bottom: 2px solid #e2e8f0;
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #4a5568;
        vertical-align: middle;
    }

    .table tr:hover {
        background: #f7fafc;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .badge-admin {
        background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        color: white;
    }

    .badge-user {
        background: linear-gradient(135deg, #4299e1 0%, #2b6cb0 100%);
        color: white;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        animation: fadeIn 0.8s ease-out;
    }

    .alert-success {
        background: #c6f6d5;
        color: #2f855a;
        border: 1px solid #9ae6b4;
    }

    .alert-danger {
        background: #fed7d7;
        color: #c53030;
        border: 1px solid #feb2b2;
    }

    .alert-info {
        background: #e9d8fd;
        color: #553c9a;
        border: 1px solid #d6bcfa;
    }

    @media (max-width: 768px) {
        .manage-users-container {
            padding: 1rem;
        }

        .page-header h2 {
            font-size: 2rem;
        }

        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>

<div class="manage-users-container">
    <div class="page-header">
        <h2><i class="fas fa-users-cog"></i> Manage Users</h2>
        <p>View and manage all users in the system</p>
    </div>
    
    <?php if(isset($success) && $success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> User deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($error) && !empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Search and Filter Form -->
    <div class="filter-card">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row">
            <div class="form-group col-md-4">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" name="search" class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by username or email">
            </div>
            
            <div class="form-group col-md-3">
                <label><i class="fas fa-user-tag"></i> Role</label>
                <select name="role" class="form-control">
                    <option value="">All Roles</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <div class="form-group col-md-3">
                <label><i class="fas fa-sort"></i> Sort By</label>
                <select name="sort" class="form-control">
                    <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                    <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                    <option value="role" <?php echo $sort === 'role' ? 'selected' : ''; ?>>Role</option>
                    <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Join Date</option>
                </select>
            </div>
            
            <div class="form-group col-md-2">
                <label><i class="fas fa-sort-amount-down"></i> Order</label>
                <select name="order" class="form-control">
                    <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                </select>
            </div>
            
            <div class="form-group col-md-12 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <?php if($result && mysqli_num_rows($result) > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-user"></i> Username</th>
                    <th><i class="fas fa-envelope"></i> Email</th>
                    <th><i class="fas fa-user-tag"></i> Role</th>
                    <th><i class="fas fa-calendar-check"></i> Events Created</th>
                    <th><i class="fas fa-ticket-alt"></i> Bookings Made</th>
                    <th><i class="fas fa-clock"></i> Join Date</th>
                    <th><i class="fas fa-cogs"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($user['total_events']); ?></td>
                        <td><?php echo number_format($user['total_bookings']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if($user['role'] !== 'admin'): ?>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                                      onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their events and bookings.');" 
                                      style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No users found.
        </div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 