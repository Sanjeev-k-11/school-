<?php
// School/admin/download_students_excel.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php"; // Adjust path as necessary

// Check if user is logged in and is ADMIN or Principal
// Redirect to login if not authorized
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins and Principals can download student data.</p>";
    header("location: ../login.php");
    exit;
}

// --- Filtering Logic (replicated from manage_students.php) ---
$isFeesDueFilter = isset($_GET['filter']) && $_GET['filter'] === 'fees_due';

// --- SQL Query Construction (Dynamic based on filter) ---

// Base SELECT columns (includes all necessary fields for the Excel report)
$sql_select_columns = "s.user_id, s.virtual_id, s.full_name, s.phone_number, s.current_class, s.is_active, s.created_at, s.photo_filename";
// Adding more detailed fields for the Excel download if available in `students` table
$sql_select_columns .= ", s.father_name, s.mother_name, s.whatsapp_number, s.previous_class, s.previous_school, s.previous_marks_percentage, s.current_marks, s.student_fees, s.optional_fees, s.address, s.pincode, s.state";


// Base FROM clause
$sql_from_clause = "FROM students s";

// Base WHERE clause (only active students by default for download)
$sql_where_clause = "WHERE s.is_active = 1";

// Initialize GROUP BY and HAVING clauses
$sql_group_by_clause = "";
$sql_having_clause = "";

// Add join and conditions for the fees_due filter
if ($isFeesDueFilter) {
    // Add SUM of outstanding fee to selected columns
    $sql_select_columns .= ", SUM(smf.amount_due - smf.amount_paid) AS total_outstanding_fee";

    // Join with student_monthly_fees table
    $sql_from_clause .= " JOIN student_monthly_fees smf ON s.user_id = smf.student_id";

    // Modify WHERE clause to include the fee condition
    $sql_where_clause .= " AND smf.amount_due > smf.amount_paid"; // Only consider fee records with outstanding balance

    // Group by student ID to sum fees per student
    $sql_group_by_clause = "GROUP BY s.user_id";

    // Filter the grouped results to only include students with a positive total outstanding fee
    $sql_having_clause = "HAVING SUM(smf.amount_due - smf.amount_paid) > 0";
}

// ORDER BY clause
$sql_order_by_clause = "ORDER BY s.created_at DESC";

// Construct the final SQL query (NO LIMIT/OFFSET for download)
$final_sql_query = "SELECT $sql_select_columns $sql_from_clause $sql_where_clause $sql_group_by_clause $sql_having_clause $sql_order_by_clause";

// --- Set CSV Headers ---
header('Content-Type: text/csv');
$filename_prefix = $isFeesDueFilter ? 'students_with_fees_due_' : 'all_students_';
$filename = $filename_prefix . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Disable caching
header('Pragma: no-cache');
header('Expires: 0');

// Open a file pointer for output
$output = fopen('php://output', 'w');

// --- Write CSV Headers (Column Names) ---
$headers = [
    'Photo URL', 'User ID', 'Virtual ID', 'Full Name', 'Father\'s Name', 'Mother\'s Name',
    'Phone', 'WhatsApp', 'Current Class', 'Previous Class', 'Previous School',
    'Previous Marks (%)', 'Current Marks (%)', 'Assigned Student Fees', 'Assigned Optional Fees',
    'Address', 'Pincode', 'State', 'Status', 'Created At'
];
if ($isFeesDueFilter) {
    $headers[] = 'Total Outstanding Fee';
}
fputcsv($output, $headers);

// --- Fetch Data and Write to CSV ---
if ($stmt = mysqli_prepare($link, $final_sql_query)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $status = $row['is_active'] == 1 ? 'Active' : 'Inactive';

                // Construct full photo URL
                $photo_url = $row['photo_filename'] ?? '';
                if (!empty($photo_url)) {
                    // Check if it's already a full URL (http, https, or //)
                    if (!(strpos($photo_url, 'http://') === 0 || strpos($photo_url, 'https://') === 0 || strpos($photo_url, '//') === 0)) {
                        // IMPORTANT: Replace 'https://yourdomain.com/School/' with the actual base URL of your application.
                        // This assumes photo_filename is relative to the 'School' root directory.
                        // Example: if your project folder is 'School' and it's in your web root, and photo_filename is 'uploads/students/image.jpg'
                        // Then the base URL for images might be 'http://localhost/School/' or 'https://yourdomain.com/School/'
                        $photo_url = 'https://yourdomain.com/School/' . $photo_url; // <-- !!! UPDATE THIS BASE URL !!!
                    }
                } else {
                    $photo_url = ''; // Or a placeholder like 'No Photo Available'
                }

                $data = [
                    $photo_url,
                    $row['user_id'],
                    $row['virtual_id'] ?? 'N/A',
                    $row['full_name'],
                    $row['father_name'] ?? 'N/A',
                    $row['mother_name'] ?? 'N/A',
                    $row['phone_number'] ?? 'N/A',
                    $row['whatsapp_number'] ?? 'N/A',
                    $row['current_class'] ?? 'N/A',
                    $row['previous_class'] ?? 'N/A',
                    $row['previous_school'] ?? 'N/A',
                    $row['previous_marks_percentage'] ?? 'N/A',
                    $row['current_marks'] ?? 'N/A',
                    number_format($row['student_fees'] ?? 0, 2, '.', ''), // Format currency
                    number_format($row['optional_fees'] ?? 0, 2, '.', ''), // Format currency
                    $row['address'] ?? 'N/A',
                    $row['pincode'] ?? 'N/A',
                    $row['state'] ?? 'N/A',
                    $status,
                    date('Y-m-d H:i:s', strtotime($row['created_at']))
                ];
                if ($isFeesDueFilter) {
                    $data[] = number_format($row['total_outstanding_fee'], 2, '.', ''); // Format as number with 2 decimal places
                }
                fputcsv($output, $data);
            }
            mysqli_free_result($result);
        } else {
            error_log("Download Students Excel get_result failed: " . mysqli_stmt_error($stmt));
        }
    } else {
        error_log("Download Students Excel query failed: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Download Students Excel prepare statement failed: " . mysqli_error($link));
}

// Close database connection
mysqli_close($link);

// Close the file pointer
fclose($output);

exit; // Ensure no further output
?>