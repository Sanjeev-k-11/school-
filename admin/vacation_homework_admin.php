<?php
// admin/vacation_homework_admin.php - Admin panel to manage vacation homework.

session_start();
require_once "../config.php"; // Adjust the path to your config file.
require_once "./cloudinary_upload_handler.php"; // Include the file with the upload function.

// --- Basic Admin Authentication (Replace with your actual authentication logic) ---
// This is a placeholder. In a real application, you would check if the user is logged in
// and has administrator privileges before allowing access to this page.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // header("Location: login.php"); // Redirect to a login page
    // exit;
}

// --- HANDLE FORM SUBMISSION (CREATE HOMEWORK) ---
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_homework'])) {
    // Sanitize and validate inputs
    $className = mysqli_real_escape_string($link, $_POST['class_name']);
    $subjectName = mysqli_real_escape_string($link, $_POST['subject_name']);
    $displayOrder = (int)$_POST['display_order'];
    $imageUrl = ''; // Initialize image URL variable

    // Check if an image file was uploaded
    if (isset($_FILES['image_file'])) {
        $uploadResult = uploadToCloudinary($_FILES['image_file'], "vacation_homework");

        // --- Corrected logic for Cloudinary response ---
        if ($uploadResult === false) {
            // Case: No file was selected
            $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4'>Please select an image file to upload.</div>";
        } elseif (is_array($uploadResult) && isset($uploadResult['secure_url'])) {
            // Case: Successful upload
            $imageUrl = $uploadResult['secure_url'];
        } elseif (is_array($uploadResult) && isset($uploadResult['error'])) {
            // Case: Upload failed with an error message
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Image upload failed: " . htmlspecialchars($uploadResult['error']) . "</div>";
        } else {
            // Case: Unexpected result from the upload function
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>An unknown error occurred during image upload.</div>";
        }
    } else {
        // Fallback if $_FILES['image_file'] is not set at all
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4'>Please select an image file to upload.</div>";
    }

    // Only proceed with database insertion if all fields are valid and an image was successfully uploaded
    if (!empty($className) && !empty($subjectName) && !empty($imageUrl)) {
        $sql = "INSERT INTO vacation_homework (class_name, subject_name, image_url, display_order) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $className, $subjectName, $imageUrl, $displayOrder);
            if (mysqli_stmt_execute($stmt)) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Homework added successfully!</div>";
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Error: " . mysqli_error($link) . "</div>";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // If the database insertion conditions are not met, and no upload message has been set yet, set a generic one.
        if (empty($message)) {
            $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4'>Please fill in all required fields.</div>";
        }
    }
}

// --- HANDLE DELETE REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $homeworkId = (int)$_GET['id'];
    $sql = "DELETE FROM vacation_homework WHERE id = ?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $homeworkId);
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Homework deleted successfully!</div>";
        } else {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Error: " . mysqli_error($link) . "</div>";
        }
        mysqli_stmt_close($stmt);
    }
    // Redirect to prevent re-submitting the delete action on refresh
    header("Location: vacation_homework_admin.php");
    exit;
}

// --- FETCH ALL HOMEWORK FOR DISPLAY ---
$homework_data = [];
$sql = "SELECT id, class_name, subject_name, image_url, display_order FROM vacation_homework ORDER BY class_name ASC, display_order ASC";
$result = mysqli_query($link, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Group the results by class name
        $homework_data[$row['class_name']][] = $row;
    }
}

// --- HTML STRUCTURE ---
?>

<?php 
require_once "./admin_header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Vacation Homework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 text-center mb-6">Admin Panel: Manage Vacation Homework</h1>
    <?php echo $message; ?>

    <!-- ADD NEW HOMEWORK FORM -->
    <div class="bg-white p-8 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-semibold mb-4 text-teal-600">Add New Homework</h2>
        <!-- Form updated for file upload -->
        <form method="POST" action="vacation_homework_admin.php" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700">Class Name</label>
                <input type="text" id="class_name" name="class_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50 p-2">
            </div>
            <div>
                <label for="subject_name" class="block text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" id="subject_name" name="subject_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50 p-2">
            </div>
            <div>
                <label for="image_file" class="block text-sm font-medium text-gray-700">Upload Image</label>
                <input type="file" id="image_file" name="image_file" required class="mt-1 block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-teal-50 file:text-teal-700
                    hover:file:bg-teal-100
                ">
            </div>
            <div>
                <label for="display_order" class="block text-sm font-medium text-gray-700">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50 p-2">
            </div>
            <div class="text-right">
                <button type="submit" name="submit_homework" class="px-6 py-2 bg-teal-600 text-white font-semibold rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-colors">Add Homework</button>
            </div>
        </form>
    </div>

    <!-- VIEW EXISTING HOMEWORK -->
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-teal-600">Existing Homework Assignments</h2>
        <?php if (empty($homework_data)): ?>
            <p class="text-gray-500">No homework entries found.</p>
        <?php else: ?>
            <?php foreach ($homework_data as $class => $assignments): ?>
                <div class="border rounded-lg shadow-sm mb-4">
                    <div class="bg-gray-100 p-4 font-bold text-lg text-gray-800 rounded-t-lg"><?php echo htmlspecialchars($class); ?></div>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($assignments as $assignment): ?>
                            <li class="p-4 flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($assignment['subject_name']); ?></p>
                                    <p class="text-gray-500 text-sm">Image URL: <span class="break-all"><?php echo htmlspecialchars($assignment['image_url']); ?></span></p>
                                    <p class="text-gray-500 text-sm">Order: <?php echo htmlspecialchars($assignment['display_order']); ?></p>
                                </div>
                                <a href="vacation_homework_admin.php?action=delete&id=<?php echo htmlspecialchars($assignment['id']); ?>" onclick="return confirm('Are you sure you want to delete this homework?');" class="ml-4 px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">Delete</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php 
require_once "./admin_footer.php";
?>