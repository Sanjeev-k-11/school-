<?php
// School/teacher/edit_homework.php

session_start();
require_once "../config.php";
require_once "../admin/cloudinary_upload_handler.php"; // Include the Cloudinary upload handler

// Security check: Only teachers can edit homework
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Edit Homework";
$teacher_id = $_SESSION['id'];

// Initialize variables for form and errors
$homework_id = null;
$class_name = $subject_name = $title = $description = $due_date = "";
$current_file_path = "";
$class_name_err = $subject_name_err = $title_err = $description_err = $due_date_err = $file_err = "";
$operation_message = "";
$assigned_classes = [];

// 1. Fetch the classes assigned to the logged-in teacher
$sql_get_classes = "SELECT classes_taught FROM staff WHERE staff_id = ?";
if ($stmt_classes = mysqli_prepare($link, $sql_get_classes)) {
    mysqli_stmt_bind_param($stmt_classes, "i", $teacher_id);
    if (mysqli_stmt_execute($stmt_classes)) {
        $result_classes = mysqli_stmt_get_result($stmt_classes);
        if ($row = mysqli_fetch_assoc($result_classes)) {
            if (!empty($row['classes_taught'])) {
                $assigned_classes = array_map('trim', explode(',', $row['classes_taught']));
            }
        }
    }
    mysqli_stmt_close($stmt_classes);
}

// 2. Handle GET request to load data for editing
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $homework_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if (!$homework_id) {
        $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid homework ID.</p>";
        header("location: view_assigned_homework.php");
        exit;
    }

    $sql_get_homework = "SELECT * FROM homework WHERE id = ? AND teacher_id = ?";
    if ($stmt = mysqli_prepare($link, $sql_get_homework)) {
        mysqli_stmt_bind_param($stmt, "ii", $homework_id, $teacher_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $homework = mysqli_fetch_assoc($result);
                $class_name = $homework['class_name'];
                $subject_name = $homework['subject_name'];
                $title = $homework['title'];
                $description = $homework['description'];
                $due_date = $homework['due_date'];
                $current_file_path = $homework['file_path'];
            } else {
                $_SESSION['operation_message'] = "<p class='text-red-600'>Homework not found or you don't have permission to edit it.</p>";
                header("location: view_assigned_homework.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 3. Handle POST request to update data
    $homework_id = filter_var($_POST['homework_id'], FILTER_VALIDATE_INT);
    if (!$homework_id) {
        $operation_message = "<p class='text-red-600'>Invalid homework ID provided.</p>";
    }

    // Validate class name
    if (empty(trim($_POST["class_name"]))) {
        $class_name_err = "Please select a class.";
    } elseif (!in_array(trim($_POST["class_name"]), $assigned_classes)) {
        $class_name_err = "You are not authorized to assign homework to this class.";
    } else {
        $class_name = trim($_POST["class_name"]);
    }
    // Validate other fields
    if (empty(trim($_POST["subject_name"]))) { $subject_name_err = "Please enter a subject."; } else { $subject_name = trim($_POST["subject_name"]); }
    if (empty(trim($_POST["title"]))) { $title_err = "Please enter a title."; } else { $title = trim($_POST["title"]); }
    if (empty(trim($_POST["description"]))) { $description_err = "Please provide a description."; } else { $description = trim($_POST["description"]); }
    if (empty(trim($_POST["due_date"]))) { $due_date_err = "Please set a due date."; } else { $due_date = trim($_POST["due_date"]); }
    $current_file_path = $_POST['current_file_path'] ?? null;
    $file_path = $current_file_path; // Default to existing file path

    // File upload handling with Cloudinary
    if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] == UPLOAD_ERR_OK) {
        $uploadResult = uploadToCloudinary($_FILES['homework_file'], 'homework_attachments');
        if (isset($uploadResult['secure_url'])) {
            $file_path = $uploadResult['secure_url']; // Update to new file path
        } else {
            $file_err = $uploadResult['error'] ?? 'Sorry, there was an unexpected error with the file upload.';
        }
    } elseif (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $php_upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        ];
        $file_err = $php_upload_errors[$_FILES['homework_file']['error']] ?? 'An unknown PHP upload error occurred.';
    }

    // If no errors, update the database
    if (empty($class_name_err) && empty($subject_name_err) && empty($title_err) && empty($description_err) && empty($due_date_err) && empty($file_err) && !empty($homework_id)) {
        $sql_update = "UPDATE homework SET class_name = ?, subject_name = ?, title = ?, description = ?, due_date = ?, file_path = ? WHERE id = ? AND teacher_id = ?";
        if ($stmt = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt, "ssssssii", $class_name, $subject_name, $title, $description, $due_date, $file_path, $homework_id, $teacher_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Homework assignment updated successfully!</p>";
                header("location: view_assigned_homework.php");
                exit();
            } else {
                $operation_message = "<p class='text-red-600'>Something went wrong. Please try again later.</p>";
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Redirect if no ID is provided in GET request
    header("location: view_assigned_homework.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .hidden-mobile {
            display: none;
        }
        @media (min-width: 768px) {
            .hidden-mobile {
                display: block;
            }
        }
        .block-mobile {
            display: block;
        }
        @media (min-width: 768px) {
            .block-mobile {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="mt-12">
    
    <?php
    // INCLUDE NAVBAR
    // IMPORTANT: Verify the path to your staff_navbar.php file
    $navbar_path = "./staff_navbar.php"; // Assuming it's in the same directory as staff_dashboard.php (School/)

    if (file_exists($navbar_path)) {
        require_once $navbar_path;
    } else {
        // Basic fallback if navbar file is missing
        echo '<div class="alert alert-danger" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"> Staff navbar file not found! Please check the path: <code>' . htmlspecialchars($navbar_path) . '</code></span>
              </div>';
    }
    ?>
</div>


<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Edit Homework Assignment</h1>

    <?php if(!empty($operation_message)) echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>{$operation_message}</div>"; ?>
    <?php if(!empty($_SESSION['operation_message'])) { echo "<div class='p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . $_SESSION['operation_message'] . "</div>"; unset($_SESSION['operation_message']); } ?>

    <div class="bg-white p-8 rounded-2xl shadow-lg">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="homework_id" value="<?php echo htmlspecialchars($homework_id); ?>">
            <input type="hidden" name="current_file_path" value="<?php echo htmlspecialchars($current_file_path); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="class_name" class="block text-sm font-medium text-gray-700">Class</label>
                    <select id="class_name" name="class_name" class="mt-1 block w-full py-2 px-3 border <?php echo !empty($class_name_err) ? 'border-red-500' : 'border-gray-300'; ?> bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Select a class</option>
                        <?php foreach($assigned_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>" <?php if($class_name == $class) echo 'selected'; ?>><?php echo htmlspecialchars($class); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-red-500 text-xs"><?php echo $class_name_err; ?></span>
                </div>
                <div>
                    <label for="subject_name" class="block text-sm font-medium text-gray-700">Subject</label>
                    <input type="text" name="subject_name" id="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>" class="mt-1 block w-full py-2 px-3 border <?php echo !empty($subject_name_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm">
                    <span class="text-red-500 text-xs"><?php echo $subject_name_err; ?></span>
                </div>
            </div>

            <div class="mt-6">
                <label for="title" class="block text-sm font-medium text-gray-700">Title / Topic</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" class="mt-1 block w-full py-2 px-3 border <?php echo !empty($title_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm">
                <span class="text-red-500 text-xs"><?php echo $title_err; ?></span>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700">Description / Instructions</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full py-2 px-3 border <?php echo !empty($description_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm"><?php echo htmlspecialchars($description); ?></textarea>
                <span class="text-red-500 text-xs"><?php echo $description_err; ?></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($due_date); ?>" class="mt-1 block w-full py-2 px-3 border <?php echo !empty($due_date_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm">
                    <span class="text-red-500 text-xs"><?php echo $due_date_err; ?></span>
                </div>
                <div>
                    <label for="homework_file" class="block text-sm font-medium text-gray-700">Attach File (Optional)</label>
                    <input type="file" name="homework_file" id="homework_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <span class="text-red-500 text-xs"><?php echo $file_err; ?></span>
                    <?php if (!empty($current_file_path)): ?>
                        <div class="mt-2 text-sm text-gray-500">
                            Current file: <a href="<?php echo htmlspecialchars($current_file_path); ?>" target="_blank" class="text-indigo-600 hover:underline">View Current File</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200">
                <div class="flex justify-end">
                    <a href="view_assigned_homework.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Update Homework</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    });
</script>

</body>
</html>


<?php 
require_once "./teacher_footer.php";
?>
