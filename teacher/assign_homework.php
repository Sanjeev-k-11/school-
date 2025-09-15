<?php
// School/teacher/assign_homework.php

session_start();
require_once "../config.php";
require_once "../admin/cloudinary_upload_handler.php"; // Include the Cloudinary upload handler

// Security check: Only teachers can assign homework
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Assign Homework";
$teacher_id = $_SESSION['id'];
$teacher_name = $_SESSION['name'];

// Initialize variables
$assigned_classes = [];
$class_name = $subject_name = $title = $description = $due_date = "";
$class_name_err = $subject_name_err = $title_err = $description_err = $due_date_err = $file_err = "";
$operation_message = "";

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate class name
    if (empty(trim($_POST["class_name"]))) {
        $class_name_err = "Please select a class.";
    } elseif (!in_array(trim($_POST["class_name"]), $assigned_classes)) {
        $class_name_err = "You are not authorized to assign homework to this class.";
    } else {
        $class_name = trim($_POST["class_name"]);
    }

    // Validate subject, title, description, due_date...
    if (empty(trim($_POST["subject_name"]))) { $subject_name_err = "Please enter a subject."; } else { $subject_name = trim($_POST["subject_name"]); }
    if (empty(trim($_POST["title"]))) { $title_err = "Please enter a title."; } else { $title = trim($_POST["title"]); }
    if (empty(trim($_POST["description"]))) { $description_err = "Please provide a description."; } else { $description = trim($_POST["description"]); }
    if (empty(trim($_POST["due_date"]))) { $due_date_err = "Please set a due date."; } else { $due_date = trim($_POST["due_date"]); }

    // File upload handling with Cloudinary
    $file_path = NULL;
    // Check if a file was submitted and there are no PHP upload errors
    if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] == UPLOAD_ERR_OK) {
        // Use the function from cloudinary_upload_handler.php
        $uploadResult = uploadToCloudinary($_FILES['homework_file'], 'homework_attachments');

        // Check the result from the Cloudinary handler
        if (isset($uploadResult['secure_url'])) { // Correctly check for 'secure_url'
            $file_path = $uploadResult['secure_url']; // Store the secure URL from Cloudinary
        } else {
            // Assign the specific error message from the handler
            $file_err = $uploadResult['error'] ?? 'Sorry, there was an unexpected error with the file upload.';
        }
    } elseif (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle PHP-level upload errors for a more specific message
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

    // If no errors, insert into database
    if (empty($class_name_err) && empty($subject_name_err) && empty($title_err) && empty($description_err) && empty($due_date_err) && empty($file_err)) {
        $sql_insert = "INSERT INTO homework (class_name, subject_name, teacher_id, teacher_name, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "ssisssss", $class_name, $subject_name, $teacher_id, $teacher_name, $title, $description, $due_date, $file_path);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Homework assigned successfully!</p>";
                header("location: staff_dashboard.php");
                exit();
            } else {
                $operation_message = "<p class='text-red-600'>Something went wrong. Please try again later.</p>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="mt-12">
<?php
    // --- INCLUDE NAVBAR ---
    // Assuming staff_navbar.php is in the SAME directory as staff_dashboard.php (School/teacher/)
    // If it's in the parent directory (School/), use "../staff_navbar.php"
    $navbar_path = "./staff_navbar.php"; // Path relative to THIS file (staff_dashboard.php)

    if (file_exists($navbar_path)) {
        require_once $navbar_path;
    } else {
        echo '<div class="alert alert-danger" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"> Staff navbar file not found! Check path: `' . htmlspecialchars($navbar_path) . '`</span>
              </div>';
        // In a real application, you might halt execution here or provide a fallback navbar
    }
    ?>
    </div>
<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Assign New Homework</h1>
<div class="flex justify-end mb-4">
    <a href="view_assigned_homework.php" class="inline-block px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-md">
        View Assigned Homework
    </a>
</div>

    <?php if(!empty($operation_message)) echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>{$operation_message}</div>"; ?>
    <?php if(!empty($_SESSION['operation_message'])) { echo "<div class='p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . $_SESSION['operation_message'] . "</div>"; unset($_SESSION['operation_message']); } ?>


    <div class="bg-white p-8 rounded-2xl shadow-lg">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
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
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200">
                <div class="flex justify-end">
                    <a href="staff_dashboard.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Assign Homework</button>
                </div>
            </div>
        </form>
    </div>
</div>

</body>
</html>



<?php
// Include the footer
require_once "./teacher_footer.php";
?>
