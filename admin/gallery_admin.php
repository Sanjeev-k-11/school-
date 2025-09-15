<?php
// School/admin/gallery.php
session_start();

// Include database connection and Cloudinary handler
require_once "../config.php";
require_once "./cloudinary_upload_handler.php";

use Cloudinary\Api\Admin\AdminApi;

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can manage the gallery.</p>";
    header("location: ../login.php");
    exit;
}

$message = "";

// --- Handle Image Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_image'])) {
    $uploadResult = uploadToCloudinary($_FILES['gallery_image'], 'school_gallery');

    if (isset($uploadResult['error'])) {
        $message = "<div class='message error'>Error: " . htmlspecialchars($uploadResult['error']) . "</div>";
    } elseif ($uploadResult) {
        $secureUrl = $uploadResult['secure_url'];
        $publicId = $uploadResult['public_id'];
        $title = mysqli_real_escape_string($link, $_POST['image_title'] ?? 'Untitled');

        $sql = "INSERT INTO gallery_images (title, public_id, secure_url, uploaded_at) VALUES ('$title', '$publicId', '$secureUrl', NOW())";
        if (mysqli_query($link, $sql)) {
            $message = "<div class='message success'>Image uploaded successfully!</div>";
        } else {
            // If DB insert fails, delete from Cloudinary to avoid orphans
            (new AdminApi())->deleteAssets([$publicId]);
            $message = "<div class='message error'>Error saving image to database: " . mysqli_error($link) . "</div>";
        }
    }
}

// --- Handle Image Deletion ---
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    $sql_select = "SELECT public_id FROM gallery_images WHERE id = $deleteId";
    $result = mysqli_query($link, $sql_select);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $publicIdToDelete = $row['public_id'];

        // Delete from Cloudinary
        try {
            (new AdminApi())->deleteAssets([$publicIdToDelete]);
            
            // Delete from database
            $sql_delete = "DELETE FROM gallery_images WHERE id = $deleteId";
            if (mysqli_query($link, $sql_delete)) {
                $message = "<div class='message success'>Image deleted successfully.</div>";
            } else {
                $message = "<div class='message error'>Error deleting from database: " . mysqli_error($link) . "</div>";
            }
        } catch (\Exception $e) {
            // Log Cloudinary error but still delete from local DB
            error_log("Cloudinary Deletion Error: " . $e->getMessage());
            $sql_delete = "DELETE FROM gallery_images WHERE id = $deleteId";
            mysqli_query($link, $sql_delete);
            $message = "<div class='message warning'>Warning: Could not delete from Cloudinary, but the database record was removed.</div>";
        }
    } else {
        $message = "<div class='message error'>Image not found in the database.</div>";
    }

    header("Location: gallery_admin.php");
    exit();
}

// --- Fetch all images from the database to display ---
$images = [];
$sql_fetch = "SELECT id, title, secure_url FROM gallery_images ORDER BY uploaded_at DESC";
$result_fetch = mysqli_query($link, $sql_fetch);

if ($result_fetch) {
    while ($row = mysqli_fetch_assoc($result_fetch)) {
        $images[] = $row;
    }
}

mysqli_close($link);

require_once "./admin_header.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gallery Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            margin-top: 80px;
            margin-bottom: 80px;
            padding: 20px;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .success { background-color: #d1fae5; color: #065f46; }
        .error { background-color: #fee2e2; color: #b91c1c; }
        .warning { background-color: #fffce0; color: #b45309; }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .gallery-item {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .gallery-item img {
            width: 100%;
            height: 200px; /* Fixed height for uniformity */
            object-fit: cover; /* Crop the image to fit the container */
            display: block;
            margin-bottom: 10px;
        }
        .gallery-item a.delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: #fff;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body class="p-8">
    <div class="container">
        <h1 class="text-4xl font-bold text-center mb-6">Admin Gallery Management</h1>

        <?php echo $message; ?>

        <div class="bg-gray-100 p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-2xl font-semibold mb-4">Upload New Image</h3>
            <form action="gallery_admin.php" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                <input type="text" name="image_title" placeholder="Image Title (optional)" class="flex-grow p-2 border border-gray-300 rounded-md">
                <input type="file" name="gallery_image" required class="flex-grow p-2 border border-gray-300 rounded-md">
                <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-md hover:bg-indigo-700 transition duration-300">
                    Upload
                </button>
            </form>
        </div>

        <h3 class="text-2xl font-semibold mb-4">Current Gallery Images</h3>
        <div class="gallery-grid">
            <?php if (empty($images)): ?>
                <p class="text-center text-gray-500 col-span-full">No images found in the gallery.</p>
            <?php else: ?>
                <?php foreach ($images as $image): ?>
                    <div class="gallery-item">
                        <img src="<?= htmlspecialchars($image['secure_url']) ?>" alt="<?= htmlspecialchars($image['title']) ?>">
                        <p class="text-sm text-gray-700 mt-2"><?= htmlspecialchars($image['title']) ?></p>
                        <a href="gallery_admin.php?delete_id=<?= htmlspecialchars($image['id']) ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this image?');">&times;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


<?php
// Include the footer file
require_once "./admin_footer.php";
?>