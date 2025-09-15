<?php
// Start the session
session_start();

// Adjust path to config.php based on directory structure
require_once "../config.php";

// --- ACCESS CONTROL ---
// Define roles that are allowed to access this page
$allowed_roles = ['principal', 'staff']; 

// Check if the user is NOT logged in, or if their role is not allowed.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['operation_message'] = "Access denied. You do not have permission to deactivate student records.";
    header("location: ./login.php");
    exit;
}

// Check if a student ID is provided in the URL
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    $_SESSION['operation_message'] = "Error: No student ID specified for deactivation.";
    header("location: ./view_all_students.php");
    exit;
}

// Get and sanitize the student ID from the URL
$student_id = trim($_GET['id']);
$operation_message = "";

// Ensure the ID is a valid integer to prevent SQL injection
if (!is_numeric($student_id)) {
    $_SESSION['operation_message'] = "Error: Invalid student ID.";
    header("location: ./view_all_students.php");
    exit;
}

// Prepare an update statement to set is_active to 0
$sql = "UPDATE students SET is_active = 0 WHERE user_id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind the student ID parameter
    mysqli_stmt_bind_param($stmt, "i", $student_id);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // Check if a row was actually affected
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['operation_message'] = "Student ID " . htmlspecialchars($student_id) . " has been deactivated successfully.";
        } else {
            $_SESSION['operation_message'] = "Warning: Student ID " . htmlspecialchars($student_id) . " not found or was already inactive.";
        }
    } else {
        $_SESSION['operation_message'] = "Error: Could not deactivate the student record. Please try again later.";
        error_log("Deactivate Student: Update query failed for ID " . $student_id . ": " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['operation_message'] = "Error: Could not prepare the deactivation statement. " . mysqli_error($link);
    error_log("Deactivate Student: Could not prepare statement: " . mysqli_error($link));
}

// Close the database connection
if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
}

// Redirect back to the view all students page
header("location: ./view_all_students.php");
exit;
?>