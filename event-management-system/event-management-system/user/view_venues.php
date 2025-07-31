<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is logged in
checkLogin();

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_capacity = isset($_GET['min_capacity']) ? (int)trim($_GET['min_capacity']) : '';
$max_price = isset($_GET['max_price']) ? (float)trim($_GET['max_price']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'name';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'ASC';

// Build query
$sql = "SELECT v.*, 
               COUNT(DISTINCT e.id) as total_events,
               CASE 
                   WHEN EXISTS (
                       SELECT 1 FROM events e2 
                       WHERE e2.venue_id = v.id 
                       AND e2.event_date >= CURDATE()
                   ) THEN 0 
                   ELSE 1 
               END as is_available
        FROM venues v
        LEFT JOIN events e ON v.id = e.venue_id
        WHERE 1=1";

$params = array();
$types = "";

if(!empty($search)) {
    $sql .= " AND (v.name LIKE ? OR v.address LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if(!empty($min_capacity)) {
    $sql .= " AND v.capacity >= ?";
    $params[] = $min_capacity;
    $types .= "i";
}

if(!empty($max_price)) {
    $sql .= " AND v.price_per_day <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$sql .= " GROUP BY v.id";

// Validate and apply sorting
$allowed_sort_columns = ['name', 'capacity', 'price_per_day'];
$sort = in_array($sort, $allowed_sort_columns) ? $sort : 'name';
$order = $order === 'DESC' ? 'DESC' : 'ASC';
$sql .= " ORDER BY v.$sort $order";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include "../includes/header.php";
?>

<div class="container">
    <h2>Available Venues</h2>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row">
                <div class="form-group col-md-3">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name or address">
                </div>
                
                <div class="form-group col-md-3">
                    <label>Minimum Capacity</label>
                    <input type="number" name="min_capacity" class="form-control" 
                           value="<?php echo htmlspecialchars($min_capacity); ?>" 
                           placeholder="Min. capacity">
                </div>
                
                <div class="form-group col-md-2">
                    <label>Maximum Price/Day</label>
                    <input type="number" name="max_price" class="form-control" 
                           value="<?php echo htmlspecialchars($max_price); ?>" 
                           placeholder="Max price" step="0.01">
                </div>
                
                <div class="form-group col-md-2">
                    <label>Sort By</label>
                    <select name="sort" class="form-control">
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="capacity" <?php echo $sort === 'capacity' ? 'selected' : ''; ?>>Capacity</option>
                        <option value="price_per_day" <?php echo $sort === 'price_per_day' ? 'selected' : ''; ?>>Price</option>
                    </select>
                </div>
                
                <div class="form-group col-md-2">
                    <label>Order</label>
                    <select name="order" class="form-control">
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Venues Grid -->
    <div class="row">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($venue = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <?php if($venue['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($venue['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($venue['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($venue['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($venue['address']); ?></p>
                            
                            <div class="venue-details">
                                <p><strong>Capacity:</strong> <?php echo number_format($venue['capacity']); ?> people</p>
                                <p><strong>Price per day:</strong> $<?php echo number_format($venue['price_per_day'], 2); ?></p>
                                <p><strong>Total Events:</strong> <?php echo $venue['total_events']; ?></p>
                                
                                <?php if($venue['is_available']): ?>
                                    <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Has Upcoming Events</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="venue-amenities mt-2">
                                <?php if($venue['has_parking']): ?>
                                    <span class="badge badge-info">Parking</span>
                                <?php endif; ?>
                                <?php if($venue['has_wifi']): ?>
                                    <span class="badge badge-info">WiFi</span>
                                <?php endif; ?>
                                <?php if($venue['has_catering']): ?>
                                    <span class="badge badge-info">Catering</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <?php if($venue['is_available']): ?>
                                <a href="book_venue.php?venue_id=<?php echo $venue['id']; ?>" 
                                   class="btn btn-primary btn-block">Book Venue</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-block" disabled>Currently Unavailable</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No venues found matching your criteria.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.venue-details p {
    margin-bottom: 0.5rem;
}
.venue-amenities .badge {
    margin-right: 0.5rem;
}
</style>

<?php include "../includes/footer.php"; ?> 