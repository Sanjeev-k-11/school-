<?php
// Start the session
session_start();

// Adjust path to config.php
require_once "../config.php";

// --- ACCESS CONTROL ---
// Define roles that are allowed to access this page
$allowed_roles = ['principal', 'staff'];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['operation_message'] = "Access denied. You do not have permission to edit student records.";
    header("location: ./login.php");
    exit;
}

// Check if a student ID is provided in the URL
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    $_SESSION['operation_message'] = "Error: No student ID specified.";
    header("location: ./view_all_students.php");
    exit;
}

$student_id = trim($_GET['id']);
$student_data = null;
$operation_message = "";
$message_type = "info";

// --- HANDLE FORM SUBMISSION (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize all form data
    $full_name = trim($_POST['full_name']);
    $virtual_id = trim($_POST['virtual_id']);
    $current_class = trim($_POST['current_class']);
    $phone_number = trim($_POST['phone_number']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $previous_class = trim($_POST['previous_class']);
    $previous_school = trim($_POST['previous_school']);
    $current_marks = trim($_POST['current_marks']);
    $student_id_post = trim($_POST['user_id']); // Get ID from hidden field

    // Validate input (add more validation as needed)
    if (empty($full_name) || empty($current_class) || empty($phone_number)) {
        $operation_message = "Please fill in all required fields.";
        $message_type = "danger";
    } else {
        // Prepare an update statement
        $sql = "UPDATE students SET full_name=?, virtual_id=?, current_class=?, phone_number=?, whatsapp_number=?, previous_class=?, previous_school=?, current_marks=? WHERE user_id=?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters to the statement
            mysqli_stmt_bind_param($stmt, "ssssssssi", $full_name, $virtual_id, $current_class, $phone_number, $whatsapp_number, $previous_class, $previous_school, $current_marks, $student_id_post);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                $operation_message = "Student record updated successfully!";
                $message_type = "success";
                // Redirect back to view_all_students to see the updated list
                $_SESSION['operation_message'] = $operation_message;
                header("location: ./view_all_students.php");
                exit;
            } else {
                $operation_message = "Error: Could not update the record. " . mysqli_error($link);
                $message_type = "danger";
                error_log("Principal Edit Student: Update query failed for ID " . $student_id_post . ": " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $operation_message = "Error preparing the update statement. Please try again.";
            $message_type = "danger";
            error_log("Principal Edit Student: Could not prepare update statement: " . mysqli_error($link));
        }
    }
}

// --- FETCH EXISTING STUDENT DATA (FOR FORM PRE-POPULATION) ---
// This runs for GET requests or if a POST request fails
if ($link !== false) {
    $sql = "SELECT user_id, photo_filename, full_name, virtual_id, current_class, phone_number, whatsapp_number, previous_class, previous_school, current_marks FROM students WHERE user_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $student_data = mysqli_fetch_assoc($result);
            } else {
                $_SESSION['operation_message'] = "Student record not found.";
                header("location: ./view_all_students.php");
                exit;
            }
        } else {
            $_SESSION['operation_message'] = "Error retrieving student data.";
            header("location: ./view_all_students.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $operation_message = "Database connection error.";
    $message_type = "danger";
}

// Check for and display messages from session if any
if (isset($_SESSION['operation_message'])) {
    $operation_message = $_SESSION['operation_message'];
    unset($_SESSION['operation_message']);
    // You might also need to set message_type here if it's stored in the session
}

// Close the database connection
if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student: <?php echo htmlspecialchars($student_data['full_name'] ?? 'N/A'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(-45deg, #b5e2ff, #d9f4ff, #b5e2ff, #d9f4ff); background-size: 400% 400%; animation: gradientAnimation 15s ease infinite; background-attachment: fixed; }
        @keyframes gradientAnimation { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .form-container { max-width: 600px; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151; }
        .form-input, .form-select { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem; transition: all 0.15s ease-in-out; }
        .form-input:focus, .form-select:focus { border-color: #60a5fa; outline: 0; box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25); }
        .btn-primary { color: #fff; background-color: #10b981; border: 1px solid #10b981; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.15s ease-in-out; }
        .btn-primary:hover { background-color: #059669; }
        .alert-success { background-color: #dcfce7; border-color: #22c55e; color: #15803d; }
        .alert-danger { background-color: #fee2e2; border-color: #ef4444; color: #b91c1c; }
        .alert { padding: 1rem; border-left-width: 4px; margin-bottom: 1.5rem; border-radius: 0.375rem; }
    </style>
</head>
<body>
    
    <?php require_once "./staff_navbar.php"; ?>

    <main class="w-full max-w-screen-md mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-8">
        
        <div class="bg-white p-8 rounded-lg shadow-xl">
            <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Edit Student Record</h1>
            
            <?php
            // Display operation message if any
            if (!empty($operation_message)) {
                $alert_class = ($message_type === 'success') ? 'alert-success' : 'alert-danger';
                echo "<div class='alert " . $alert_class . "' role='alert'>". htmlspecialchars($operation_message) ."</div>";
            }
            ?>

            <?php if ($student_data): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo htmlspecialchars($student_id); ?>" method="post">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($student_data['user_id']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name:</label>
                        <input type="text" name="full_name" id="full_name" class="form-input" value="<?php echo htmlspecialchars($student_data['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="virtual_id" class="form-label">Virtual ID:</label>
                        <input type="text" name="virtual_id" id="virtual_id" class="form-input" value="<?php echo htmlspecialchars($student_data['virtual_id']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="current_class" class="form-label">Current Class:</label>
                        <input type="text" name="current_class" id="current_class" class="form-input" value="<?php echo htmlspecialchars($student_data['current_class']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone Number:</label>
                        <input type="tel" name="phone_number" id="phone_number" class="form-input" value="<?php echo htmlspecialchars($student_data['phone_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_number" class="form-label">WhatsApp Number:</label>
                        <input type="tel" name="whatsapp_number" id="whatsapp_number" class="form-input" value="<?php echo htmlspecialchars($student_data['whatsapp_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="previous_class" class="form-label">Previous Class:</label>
                        <input type="text" name="previous_class" id="previous_class" class="form-input" value="<?php echo htmlspecialchars($student_data['previous_class']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="previous_school" class="form-label">Previous School:</label>
                        <input type="text" name="previous_school" id="previous_school" class="form-input" value="<?php echo htmlspecialchars($student_data['previous_school']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="current_marks" class="form-label">Current Marks (%):</label>
                        <input type="number" step="0.01" name="current_marks" id="current_marks" class="form-input" value="<?php echo htmlspecialchars($student_data['current_marks']); ?>">
                    </div>
                </div>
                
                <div class="mt-8 flex justify-between items-center">
                    <a href="./view_all_students.php" class="text-gray-600 hover:underline">Cancel</a>
                    <button type="submit" class="btn-primary">Update Record</button>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">Could not load student data. Please go back and try again.</div>
                <a href="./view_all_students.php" class="text-gray-600 hover:underline">Back to Student List</a>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>