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

<div class="container">
    <h2>Manage Users</h2>
    
    <?php if(isset($success) && $success): ?>
        <div class="alert alert-success">User deleted successfully.</div>
    <?php endif; ?>
    
    <?php if(isset($error) && !empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row">
                <div class="form-group col-md-4">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by username or email">
                </div>
                
                <div class="form-group col-md-3">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group col-md-3">
                    <label>Sort By</label>
                    <select name="sort" class="form-control">
                        <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                        <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="role" <?php echo $sort === 'role' ? 'selected' : ''; ?>>Role</option>
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Join Date</option>
                    </select>
                </div>
                
                <div class="form-group col-md-2">
                    <label>Order</label>
                    <select name="order" class="form-control">
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                
                <div class="form-group col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <?php if($result && mysqli_num_rows($result) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Events Created</th>
                    <th>Bookings Made</th>
                    <th>Join Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
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
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                        Delete
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
        <div class="alert alert-info">No users found.</div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?> 