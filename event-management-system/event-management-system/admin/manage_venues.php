<?php
require_once "../config/db.php";
require_once "../auth/auth_check.php";

// Check if user is admin
checkAdmin();

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/venues/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "add" || $_POST["action"] == "edit"){
            $name = trim($_POST["name"]);
            $address = trim($_POST["address"]);
            $capacity = trim($_POST["capacity"]);
            $price = trim($_POST["price"]);
            $description = trim($_POST["description"]);
            $image_path = null;
            
            // Handle image upload
            if(isset($_FILES["venue_image"]) && $_FILES["venue_image"]["error"] == 0) {
                $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
                $filename = $_FILES["venue_image"]["name"];
                $filetype = $_FILES["venue_image"]["type"];
                $filesize = $_FILES["venue_image"]["size"];
                
                // Verify file extension
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if(!array_key_exists($ext, $allowed)) {
                    die("Error: Please select a valid file format.");
                }
                
                // Verify file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if($filesize > $maxsize) {
                    die("Error: File size is larger than the allowed limit.");
                }
                
                // Generate unique filename
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if(move_uploaded_file($_FILES["venue_image"]["tmp_name"], $upload_path)) {
                    $image_path = "uploads/venues/" . $new_filename;
                }
            }
            
            if(!empty($name) && !empty($address) && !empty($capacity) && !empty($price)){
                if($_POST["action"] == "add"){
                    $sql = "INSERT INTO venues (name, address, capacity, price_per_day, description, image_path) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssisd", $name, $address, $capacity, $price, $description, $image_path);
                } else {
                    // For edit, only update image if new one is uploaded
                    if($image_path) {
                        $sql = "UPDATE venues SET name = ?, address = ?, capacity = ?, price_per_day = ?, description = ?, image_path = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssisdsi", $name, $address, $capacity, $price, $description, $image_path, $_POST["venue_id"]);
                    } else {
                        $sql = "UPDATE venues SET name = ?, address = ?, capacity = ?, price_per_day = ?, description = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssisdi", $name, $address, $capacity, $price, $description, $_POST["venue_id"]);
                    }
                }
                
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } elseif($_POST["action"] == "delete" && isset($_POST["venue_id"])){
            // Get image path before deleting
            $sql = "SELECT image_path FROM venues WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_POST["venue_id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $old_image_path);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            // Delete the venue
            $sql = "DELETE FROM venues WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $_POST["venue_id"]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Delete the image file if it exists
                if($old_image_path && file_exists("../" . $old_image_path)) {
                    unlink("../" . $old_image_path);
                }
            }
        }
    }
}

// Fetch all venues
$sql = "SELECT * FROM venues ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

include "../includes/header.php";
?>

<h2>Manage Venues</h2>

<div class="card mb-2">
    <h3>Add New Venue</h3>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Venue Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label>Capacity</label>
            <input type="number" name="capacity" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Price per Day ($)</label>
            <input type="number" step="0.01" name="price" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <label>Venue Image</label>
            <input type="file" name="venue_image" class="form-control" accept="image/*">
            <small class="form-text text-muted">Max file size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</small>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Add Venue">
        </div>
    </form>
</div>

<div class="card">
    <h3>Existing Venues</h3>
    <?php if(mysqli_num_rows($result) > 0): ?>
        <div class="venue-grid">
            <?php while($venue = mysqli_fetch_assoc($result)): ?>
                <div class="venue-card">
                    <?php if($venue['image_path']): ?>
                        <img src="../<?php echo htmlspecialchars($venue['image_path']); ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>" class="venue-image">
                    <?php else: ?>
                        <div class="venue-image-placeholder">No Image</div>
                    <?php endif; ?>
                    <div class="venue-details">
                        <h4><?php echo htmlspecialchars($venue['name']); ?></h4>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($venue['address']); ?></p>
                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars($venue['capacity']); ?></p>
                        <p><strong>Price/Day:</strong> $<?php echo htmlspecialchars($venue['price_per_day']); ?></p>
                        <p><strong>Status:</strong> <?php echo $venue['is_available'] ? 'Available' : 'Not Available'; ?></p>
                        <div class="venue-actions">
                            <button class="btn btn-primary" onclick="editVenue(<?php echo htmlspecialchars(json_encode($venue)); ?>)">Edit</button>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                                <button type="submit" class="btn btn-secondary ml-2" onclick="return confirm('Are you sure you want to delete this venue?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No venues found.</p>
    <?php endif; ?>
</div>

<!-- Edit Venue Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Edit Venue</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="venue_id" id="edit_venue_id">
            <div class="form-group">
                <label>Venue Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="edit_address" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Price per Day ($)</label>
                <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>Venue Image</label>
                <input type="file" name="venue_image" class="form-control" accept="image/*">
                <small class="form-text text-muted">Max file size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</small>
                <div id="current_image" class="mt-2"></div>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Update Venue">
                <button type="button" class="btn btn-secondary ml-2" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    width: 500px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.venue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.venue-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.venue-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.venue-image-placeholder {
    width: 100%;
    height: 200px;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-style: italic;
}

.venue-details {
    padding: 15px;
}

.venue-details h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.venue-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

#current_image {
    margin-top: 10px;
}

#current_image img {
    max-width: 200px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 4px;
}
</style>

<script>
function editVenue(venue) {
    document.getElementById('edit_venue_id').value = venue.id;
    document.getElementById('edit_name').value = venue.name;
    document.getElementById('edit_address').value = venue.address;
    document.getElementById('edit_capacity').value = venue.capacity;
    document.getElementById('edit_price').value = venue.price_per_day;
    document.getElementById('edit_description').value = venue.description;
    
    // Show current image if exists
    const currentImageDiv = document.getElementById('current_image');
    if(venue.image_path) {
        currentImageDiv.innerHTML = `<p>Current Image:</p><img src="../${venue.image_path}" alt="Current venue image">`;
    } else {
        currentImageDiv.innerHTML = '<p>No image uploaded</p>';
    }
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include "../includes/footer.php"; ?> 