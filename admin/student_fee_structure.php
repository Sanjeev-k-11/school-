<?php
session_start();

require_once "../config.php"; // Adjust path if necessary

// Access control: Only Admins can access this page for global fee structure management.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can manage global student fee structures.</p>";
    header("location: ../login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables for form and errors
$academic_year_input = '';
$general_error = '';

$toast_message = '';
$toast_type = ''; // success, error, warning, info

$students = []; // To store all student data for display
$existing_fee_structures = []; // To show if students already have a structure for the selected year
$student_fee_errors = []; // To store validation errors for individual student fees
$classes = []; // To store distinct classes for filtering

// Function to generate academic year string (e.g., "2023-2024")
function generateAcademicYear() {
    $current_year = date('Y');
    $current_month = date('n'); // Month without leading zeros (1-12)

    // Academic year typically starts around July/August.
    // Adjust this logic if your school's academic year starts differently.
    if ($current_month >= 7) {
        return $current_year . '-' . ($current_year + 1);
    } else {
        return ($current_year - 1) . '-' . $current_year;
    }
}

// Populate academic year suggestion if not set (first load or errors occurred)
// Prefer GET for filtering academic year, then POST (if form submission error), then default
$academic_year_input = $_GET['academic_year'] ?? $_POST['academic_year'] ?? generateAcademicYear();
$academic_year_err = '';

// Get filter/search parameters from GET request
$filter_class = $_GET['filter_class'] ?? '';
$search_query = trim($_GET['search_query'] ?? '');


// Handle POST request (form submission for saving fees)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $general_error = "Invalid request token. Please try again.";
        $toast_type = 'error';
        // Regenerate token to prevent resubmission with same invalid token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Token is valid, unset it to prevent replay attacks for this specific form submission
        // (A new token will be generated on the next page load if needed)
        unset($_SESSION['csrf_token']);

        $academic_year_input = trim($_POST['academic_year'] ?? '');
        $fees_data = $_POST['fees'] ?? []; // This will contain an array of student fees

        // Validate academic year
        if (empty($academic_year_input)) {
            $academic_year_err = "Academic year is required.";
        } elseif (!preg_match("/^\d{4}-\d{4}$/", $academic_year_input)) {
            $academic_year_err = "Invalid academic year format. Use YYYY-YYYY (e.g., 2023-2024).";
        }

        $overall_has_errors = !empty($academic_year_err);

        // If no validation errors for academic year, proceed with student-specific data
        if (!$overall_has_errors) {
            if ($link === false) {
                $general_error = "Database connection error. Could not add fee structures.";
                $toast_type = 'error';
                error_log("Add All Fee Structure DB connection failed (POST): " . mysqli_connect_error());
            } else {
                // Prepare the insert/update statement for student_fee_structures
                // Using INSERT ... ON DUPLICATE KEY UPDATE to handle existing entries
                $sql_insert_update_fee_structure = "
                    INSERT INTO student_fee_structures (student_id, academic_year, base_monthly_fee, monthly_van_fee_component)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        base_monthly_fee = VALUES(base_monthly_fee),
                        monthly_van_fee_component = VALUES(monthly_van_fee_component),
                        updated_at = CURRENT_TIMESTAMP
                ";
                $stmt_insert_update_fee = mysqli_prepare($link, $sql_insert_update_fee_structure);

                if ($stmt_insert_update_fee) {
                    $successful_operations = 0;
                    $skipped_no_change = 0;
                    $failed_operations = 0;

                    // Loop through the submitted fees data for each student
                    foreach ($fees_data as $student_id => $fee_values) {
                        $current_student_base_fee_input = trim($fee_values['base_monthly_fee'] ?? '');
                        $current_student_van_fee_input = trim($fee_values['monthly_van_fee_component'] ?? '0');

                        // Validate individual student fee inputs
                        $base_monthly_fee_for_db = filter_var($current_student_base_fee_input, FILTER_VALIDATE_FLOAT);
                        if ($base_monthly_fee_for_db === false || $base_monthly_fee_for_db < 0) {
                            $student_fee_errors[$student_id]['base_monthly_fee'] = "Invalid base fee.";
                            $overall_has_errors = true; // Mark overall form as having errors
                            $failed_operations++;
                            continue; // Skip this student for DB operation, but still show error on form
                        }

                        $monthly_van_fee_component_for_db = filter_var($current_student_van_fee_input, FILTER_VALIDATE_FLOAT);
                        if ($monthly_van_fee_component_for_db === false || $monthly_van_fee_component_for_db < 0) {
                            $student_fee_errors[$student_id]['monthly_van_fee_component'] = "Invalid van fee.";
                            $overall_has_errors = true; // Mark overall form as having errors
                            $failed_operations++;
                            continue;
                        }

                        // If previous continuations were hit, this student is skipped
                        // This check ensures we only bind and execute for valid data
                        if (!isset($student_fee_errors[$student_id])) {
                            mysqli_stmt_bind_param($stmt_insert_update_fee, "isdd",
                                                    $student_id,
                                                    $academic_year_input,
                                                    $base_monthly_fee_for_db,
                                                    $monthly_van_fee_component_for_db);

                            if (mysqli_stmt_execute($stmt_insert_update_fee)) {
                                $affected_rows = mysqli_stmt_affected_rows($stmt_insert_update_fee);
                                if ($affected_rows > 0) {
                                    $successful_operations++;
                                } else {
                                    // affected_rows = 0 means the row already existed with identical values, so no actual change was made
                                    $skipped_no_change++;
                                }
                            } else {
                                $failed_operations++;
                                error_log("Failed to insert/update fee structure for student ID " . $student_id . ": " . mysqli_stmt_error($stmt_insert_update_fee));
                                $student_fee_errors[$student_id]['general'] = "Database error for this student.";
                                $overall_has_errors = true;
                            }
                        }
                    }
                    mysqli_stmt_close($stmt_insert_update_fee);

                    if ($overall_has_errors) {
                        $toast_message = "Some fees could not be processed due to validation or database errors. Please check the form.";
                        $toast_type = 'error';
                    } elseif ($successful_operations > 0 || $skipped_no_change > 0) {
                        $toast_message = "Fee structure bulk update complete: $successful_operations records added/updated. $skipped_no_change records had no changes.";
                        $toast_type = 'success';
                    } else {
                        $toast_message = "No fee structures were updated. Please check your inputs.";
                        $toast_type = 'info';
                    }

                    if ($failed_operations > 0) {
                        $toast_message .= " ($failed_operations records failed.)";
                        if ($toast_type === 'success') { // Upgrade toast type if there were failures amidst successes
                            $toast_type = 'warning';
                        }
                    }

                } else {
                    $general_error = "Database error: Could not prepare fee structure statement.";
                    $toast_type = 'error';
                    error_log("Add All Fee Structure prepare insert/update failed: " . mysqli_error($link));
                }
            }
        } else {
            $toast_message = "Validation errors found. Please correct the form and try again.";
            $toast_type = 'error';
        }
    }
    // Re-generate CSRF token after successful or failed POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Data fetching for initial page load or re-display after POST/GET ---

if ($link === false) {
    if (empty($general_error)) { // Only set if not already set by POST
        $general_error = "Database connection error. Could not load student data.";
        $toast_type = 'error';
    }
    error_log("Add All Fee Structure DB connection failed (GET/Refetch): " . mysqli_connect_error());
} else {
    // Fetch distinct classes for filter dropdown
    $sql_classes = "SELECT DISTINCT current_class FROM students WHERE is_active = 1 AND current_class IS NOT NULL AND current_class != '' ORDER BY current_class ASC";
    if ($stmt_classes = mysqli_prepare($link, $sql_classes)) {
        if (mysqli_stmt_execute($stmt_classes)) {
            $result_classes = mysqli_stmt_get_result($stmt_classes);
            while ($row = mysqli_fetch_assoc($result_classes)) {
                $classes[] = $row['current_class'];
            }
            mysqli_free_result($result_classes);
        } else {
            error_log("Add All Fee Structure fetch classes query failed: " . mysqli_stmt_error($stmt_classes));
        }
        mysqli_stmt_close($stmt_classes);
    } else {
        error_log("Add All Fee Structure prepare classes fetch failed: " . mysqli_error($link));
    }


    // Fetch all students for display, applying filters/search
    $sql_students = "SELECT user_id, virtual_id, full_name, current_class FROM students WHERE is_active = 1";
    $params = [];
    $types = '';

    if (!empty($filter_class)) {
        $sql_students .= " AND current_class = ?";
        $params[] = $filter_class;
        $types .= 's';
    }

    if (!empty($search_query)) {
        $sql_students .= " AND (full_name LIKE ? OR virtual_id LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
        $types .= 'ss';
    }

    $sql_students .= " ORDER BY full_name ASC";

    if ($stmt_students = mysqli_prepare($link, $sql_students)) {
        if (!empty($params)) {
            // Need to pass parameters by reference for bind_param
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $key => $value) {
                $refs[$key] = &$bind_names[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_students], $refs));
        }

        if (mysqli_stmt_execute($stmt_students)) {
            $result_students = mysqli_stmt_get_result($stmt_students);
            while ($row = mysqli_fetch_assoc($result_students)) {
                $students[] = $row;
            }
            mysqli_free_result($result_students);
        } else {
            if (empty($general_error)) {
                $general_error = "Error fetching student list.";
                $toast_type = 'error';
            }
            error_log("Add All Fee Structure fetch students query failed: " . mysqli_stmt_error($stmt_students));
        }
        mysqli_stmt_close($stmt_students);
    } else {
        if (empty($general_error)) {
            $general_error = "Error preparing student list fetch statement.";
            $toast_type = 'error';
        }
        error_log("Add All Fee Structure prepare students fetch failed: " . mysqli_error($link));
    }

    // Fetch existing fee structures for the currently selected/proposed academic year to pre-fill inputs
    if (!empty($students) && empty($academic_year_err)) {
        $student_ids = array_column($students, 'user_id');
        if (!empty($student_ids)) { // Only proceed if there are students
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

            $sql_existing_fees = "SELECT student_id, base_monthly_fee, monthly_van_fee_component FROM student_fee_structures WHERE academic_year = ? AND student_id IN ($placeholders)";
            if ($stmt_existing_fees = mysqli_prepare($link, $sql_existing_fees)) {
                // Prepare the array of arguments for bind_param, ensuring they are references
                $bind_args = [];
                $types_existing = 's' . str_repeat('i', count($student_ids)); // 's' for academic_year, 'i' for each student_id

                // Add the types string as the first argument to bind_param
                $bind_args[] = $types_existing;

                // Add a reference to the academic_year_input
                $bind_args[] = &$academic_year_input; // This needs to be a reference

                // Add references to each student_id
                foreach ($student_ids as &$s_id) { // Iterate by reference to ensure persistent references
                    $bind_args[] = &$s_id;
                }

                call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_existing_fees], $bind_args));

                if (mysqli_stmt_execute($stmt_existing_fees)) {
                    $result_existing = mysqli_stmt_get_result($stmt_existing_fees);
                    while ($row = mysqli_fetch_assoc($result_existing)) {
                        $existing_fee_structures[$row['student_id']] = $row;
                    }
                    mysqli_free_result($result_existing);
                } else {
                    if (empty($general_error)) {
                        $general_error = "Error checking for existing fee structures.";
                        $toast_type = 'error';
                    }
                    error_log("Add All Fee Structure existing fees query failed: " . mysqli_stmt_error($stmt_existing_fees));
                }
                mysqli_stmt_close($stmt_existing_fees);
            } else {
                if (empty($general_error)) {
                    $general_error = "Error preparing existing fee structures check.";
                    $toast_type = 'error';
                }
                error_log("Add All Fee Structure prepare existing fees failed: " . mysqli_error($link));
            }
        }
    }
}

// Close DB connection if it's open
if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
}

// Include header *after* all PHP processing to ensure toasts are generated
require_once "./admin_header.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Student Fee Structures</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- For icons -->
    <style>
        body {
            background-color: #f8fafc; /* Lighter gray background */
            min-height: 100vh;
            font-family: 'Inter', sans-serif; /* A modern sans-serif font */
        }
        /* Custom scrollbar for better aesthetics */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* Gray-300 */
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* Gray-400 */
        }

        .form-error {
            color: #ef4444; /* red-500 */
            font-size: 0.8em;
            margin-top: 0.25em;
            display: block;
        }
        .form-control.is-invalid {
            border-color: #ef4444; /* red-500 */
        }
        .form-control.is-invalid:focus {
            border-color: #dc2626; /* red-600 */
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5); /* red-500 with opacity */
            outline: none;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .student-table th, .student-table td {
            padding: 1rem 0.75rem; /* More padding for spacious feel */
            border-bottom: 1px solid #e2e8f0; /* slate-200 */
            text-align: left;
        }
        .student-table th {
            background-color: #f1f5f9; /* slate-100 */
            font-weight: 600;
            color: #475569; /* slate-700 */
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.85em;
        }
        .student-table tbody tr:nth-child(even) {
            background-color: #f8fafc; /* slate-50 */
        }
        .student-table tbody tr:hover {
            background-color: #f0f4f8; /* slate-100 on hover */
            transition: background-color 0.2s ease;
        }
        .student-table td {
            color: #334155; /* slate-800 */
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none; /* Allow clicks to pass through container */
        }
        .toast {
            background-color: #ffffff;
            color: #333;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            pointer-events: auto; /* Re-enable clicks for individual toasts */
            min-width: 250px;
            max-width: 350px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 6px solid; /* For colored border */
        }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast-icon {
            font-size: 1.5rem;
        }
        .toast-success {
            border-color: #10b981; /* emerald-500 */
            color: #065f46; /* emerald-800 */
        }
        .toast-success .toast-icon { color: #10b981; }

        .toast-error {
            border-color: #ef4444; /* red-500 */
            color: #991b1b; /* red-800 */
        }
        .toast-error .toast-icon { color: #ef4444; }

        .toast-warning {
            border-color: #f59e0b; /* amber-500 */
            color: #9a3412; /* amber-800 */
        }
        .toast-warning .toast-icon { color: #f59e0b; }

        .toast-info {
            border-color: #3b82f6; /* blue-500 */
            color: #1e40af; /* blue-800 */
        }
        .toast-info .toast-icon { color: #3b82f6; }

        .toast .close-button {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit; /* Inherit color from toast type */
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 0.25rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        .toast .close-button:hover {
            opacity: 1;
        }

        /* Loading Overlay */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85); /* Slightly less opaque */
            backdrop-filter: blur(4px); /* Modern blur effect */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10000; /* Ensure it's on top */
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            opacity: 0;
            visibility: hidden;
            gap: 1rem;
        }
        #loadingOverlay.show {
            opacity: 1;
            visibility: visible;
        }
        .spinner {
            border: 5px solid rgba(0, 0, 0, 0.1);
            border-left-color: #6366f1; /* indigo-500 */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Sticky header for table */
        .student-table thead.sticky-header th {
            position: -webkit-sticky;
            position: sticky;
            top: 0; /* This will be set dynamically by JS */
            background-color: #f1f5f9; /* slate-100, matching thead background */
            z-index: 10;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.08); /* More subtle shadow */
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastContainer = document.getElementById('toastContainer');
            const mainForm = document.getElementById('feeStructureForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const studentTable = document.querySelector('.student-table');
            const stickyHeader = document.querySelector('.student-table thead');

            function showToast(message, type = 'info', duration = 5000) {
                if (!message || !toastContainer) return;

                const toast = document.createElement('div');
                toast.classList.add('toast', `toast-${type}`);
                
                let iconClass = '';
                if (type === 'success') iconClass = 'fa-check-circle';
                else if (type === 'error') iconClass = 'fa-times-circle';
                else if (type === 'warning') iconClass = 'fa-exclamation-triangle';
                else if (type === 'info') iconClass = 'fa-info-circle';

                if (iconClass) {
                    const icon = document.createElement('i');
                    icon.classList.add('fas', iconClass, 'toast-icon');
                    toast.appendChild(icon);
                }

                const textSpan = document.createElement('span');
                textSpan.textContent = message;
                toast.appendChild(textSpan);

                const closeButton = document.createElement('button');
                closeButton.classList.add('close-button');
                closeButton.innerHTML = '&times;'; // HTML entity for 'x'
                closeButton.setAttribute('aria-label', 'Close');
                closeButton.onclick = () => {
                    toast.classList.remove('show');
                    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
                };
                toast.appendChild(closeButton);

                toastContainer.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.add('show');
                });

                if (duration > 0) {
                    setTimeout(() => {
                        if (toast.classList.contains('show')) { // Only hide if still visible
                            toast.classList.remove('show');
                            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
                        }
                    }, duration);
                }
            }

            const phpMessage = <?php echo json_encode($toast_message); ?>;
            const messageType = <?php echo json_encode($toast_type); ?>;

            if (phpMessage) {
                showToast(phpMessage, messageType);
            }

            // --- Apply to All Functionality ---
            const applyAllBaseFeeBtn = document.getElementById('applyAllBaseFee');
            const applyAllVanFeeBtn = document.getElementById('applyAllVanFee');
            const defaultBaseFeeInput = document.getElementById('default_base_monthly_fee');
            const defaultVanFeeInput = document.getElementById('default_monthly_van_fee_component');

            if (applyAllBaseFeeBtn && defaultBaseFeeInput) {
                applyAllBaseFeeBtn.addEventListener('click', function() {
                    const value = defaultBaseFeeInput.value;
                    document.querySelectorAll('input[name$="[base_monthly_fee]"]').forEach(input => {
                        input.value = value;
                    });
                    showToast('Base fee applied to all students.', 'info', 3000);
                });
            }

            if (applyAllVanFeeBtn && defaultVanFeeInput) {
                applyAllVanFeeBtn.addEventListener('click', function() {
                    const value = defaultVanFeeInput.value;
                    document.querySelectorAll('input[name$="[monthly_van_fee_component]"]').forEach(input => {
                        input.value = value;
                    });
                    showToast('Van fee applied to all students.', 'info', 3000);
                });
            }

            // --- Loading Overlay ---
            if (mainForm && loadingOverlay) {
                mainForm.addEventListener('submit', function(event) {
                    // Confirmation before submission
                    if (!confirm('Are you sure you want to save these fee structures? This will update existing records for the selected academic year.')) {
                        event.preventDefault();
                        return;
                    }
                    loadingOverlay.classList.add('show');
                });

                // Hide overlay if page finishes loading and it's still visible (e.g., if there was a JS error earlier)
                window.addEventListener('load', function() {
                    loadingOverlay.classList.remove('show');
                });
            }

            // --- Sticky Table Header ---
            if (stickyHeader && studentTable) {
                // Add a class to enable sticky CSS
                stickyHeader.classList.add('sticky-header');

                function updateStickyHeaderPosition() {
                    // Calculate the offset from the top based on the admin header's height
                    const adminHeader = document.querySelector('header'); // Assuming your admin_header.php outputs a <header> tag
                    const adminHeaderHeight = adminHeader ? adminHeader.offsetHeight : 0;
                    stickyHeader.style.top = `${adminHeaderHeight}px`;
                }

                // Update position on scroll and initial load
                window.addEventListener('scroll', updateStickyHeaderPosition);
                updateStickyHeaderPosition(); // Set initial position
            }
        });
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center py-8 px-4">

    <div id="toastContainer" class="toast-container"></div>

    <div id="loadingOverlay">
        <div class="spinner"></div>
        <p class="mt-4 text-lg font-medium text-gray-700">Processing your request...</p>
        <p class="text-sm text-gray-500">Please wait, this may take a moment.</p>
    </div>

    <div class="bg-white mx-auto my-8 p-8 rounded-xl shadow-lg w-full max-w-5xl">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Manage Student Fee Structures (Bulk)</h2>

        <div class="mb-6 text-left">
            <a href="admin_dashboard.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 hover:underline text-base font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($general_error)): ?>
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                <div class="flex items-center">
                    <div class="py-1"><i class="fas fa-exclamation-triangle fa-lg mr-3"></i></div>
                    <div>
                        <p class="font-bold">Error!</p>
                        <p class="text-sm"><?php echo htmlspecialchars($general_error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter and Search Section -->
        <div class="mb-10 p-6 bg-blue-50 border border-blue-200 rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold text-blue-800 mb-5">Filter & Search Students</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-4 items-end">
                <div>
                    <label for="academic_year_filter" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <input type="text" name="academic_year" id="academic_year_filter"
                        class="form-input block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($academic_year_input); ?>" placeholder="e.g., 2023-2024"
                        aria-describedby="academic-year-hint">
                    <p id="academic-year-hint" class="mt-1 text-xs text-gray-500">Format: YYYY-YYYY</p>
                </div>
                <div>
                    <label for="filter_class" class="block text-sm font-medium text-gray-700 mb-1">Filter by Class</label>
                    <select name="filter_class" id="filter_class"
                        class="form-select block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($filter_class === $class) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2"> <!-- Allow search to take more space on smaller screens -->
                    <label for="search_query" class="block text-sm font-medium text-gray-700 mb-1">Search by Name/ID</label>
                    <input type="text" name="search_query" id="search_query"
                        class="form-input block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Student Name or Virtual ID">
                </div>
                <div class="md:col-span-4 flex justify-end gap-3 mt-4">
                    <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-sync-alt mr-2"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <form id="feeStructureForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <!-- Keep academic year input for POST submission -->
            <input type="hidden" name="academic_year" value="<?php echo htmlspecialchars($academic_year_input); ?>">

            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-2xl font-semibold text-gray-800 border-b pb-4 mb-6">Set Fee Components Per Student</h3>

                <!-- Apply to All Section -->
                <div class="mb-8 p-5 border border-purple-200 bg-purple-50 rounded-lg shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 md:gap-8">
                    <p class="text-base font-medium text-purple-800 flex-shrink-0">
                        <i class="fas fa-magic mr-2 text-purple-600"></i> Quick Apply to All Students:
                    </p>
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label for="default_base_monthly_fee" class="text-sm font-medium text-gray-700 flex-shrink-0">Base Fee:</label>
                        <input type="number" id="default_base_monthly_fee"
                            class="w-24 px-3 py-1.5 border border-purple-300 rounded-md shadow-sm text-sm focus:ring-purple-500 focus:border-purple-500"
                            step="0.01" min="0" value="0.00" placeholder="0.00">
                        <button type="button" id="applyAllBaseFee"
                            class="px-4 py-1.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 text-sm font-medium transition duration-150 ease-in-out">
                            Apply
                        </button>
                    </div>
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label for="default_monthly_van_fee_component" class="text-sm font-medium text-gray-700 flex-shrink-0">Van Fee:</label>
                        <input type="number" id="default_monthly_van_fee_component"
                            class="w-24 px-3 py-1.5 border border-purple-300 rounded-md shadow-sm text-sm focus:ring-purple-500 focus:border-purple-500"
                            step="0.01" min="0" value="0.00" placeholder="0.00">
                        <button type="button" id="applyAllVanFee"
                            class="px-4 py-1.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 text-sm font-medium transition duration-150 ease-in-out">
                            Apply
                        </button>
                    </div>
                </div>

                <?php if (!empty($students)): ?>
                    <div class="overflow-x-auto max-h-[600px] border border-gray-200 rounded-lg shadow-sm">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Virtual ID</th>
                                    <th>Full Name</th>
                                    <th>Class</th>
                                    <th>Base Fee (Monthly)</th>
                                    <th>Van Fee (Monthly)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student):
                                    $s_id = htmlspecialchars($student['user_id']);
                                    // Use submitted values if available (e.g., after a failed POST), otherwise existing, otherwise 0
                                    $current_base_fee_value = $_POST['fees'][$student['user_id']]['base_monthly_fee'] ?? ($existing_fee_structures[$student['user_id']]['base_monthly_fee'] ?? '0.00');
                                    $current_van_fee_value = $_POST['fees'][$student['user_id']]['monthly_van_fee_component'] ?? ($existing_fee_structures[$student['user_id']]['monthly_van_fee_component'] ?? '0.00');

                                    $base_fee_error = $student_fee_errors[$student['user_id']]['base_monthly_fee'] ?? '';
                                    $van_fee_error = $student_fee_errors[$student['user_id']]['monthly_van_fee_component'] ?? '';
                                    $general_student_error = $student_fee_errors[$student['user_id']]['general'] ?? '';

                                    $status_icon = '';
                                    $status_text_display = '';
                                    $status_color_class = '';

                                    if (!empty($general_student_error)) {
                                        $status_icon = '<i class="fas fa-exclamation-circle text-red-500 mr-1"></i>';
                                        $status_text_display = "<span class='text-red-600 font-medium'>" . htmlspecialchars($general_student_error) . "</span>";
                                    } elseif (isset($existing_fee_structures[$student['user_id']])) {
                                        $status_icon = '<i class="fas fa-check-circle text-green-500 mr-1"></i>';
                                        $status_text_display = "<span class='text-green-600 font-medium'>Existing</span>";
                                    } else {
                                        $status_icon = '<i class="fas fa-plus-circle text-yellow-500 mr-1"></i>';
                                        $status_text_display = "<span class='text-yellow-600 font-medium'>New</span>";
                                    }
                                ?>
                                    <tr>
                                        <td class="text-sm text-gray-700"><?php echo $s_id; ?></td>
                                        <td class="text-sm text-gray-700"><?php echo htmlspecialchars($student['virtual_id']); ?></td>
                                        <td class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td class="text-sm text-gray-700"><?php echo htmlspecialchars($student['current_class']); ?></td>
                                        <td>
                                            <input type="number" name="fees[<?php echo $s_id; ?>][base_monthly_fee]"
                                                class="w-24 px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 <?php echo (!empty($base_fee_error)) ? 'is-invalid' : ''; ?>"
                                                step="0.01" min="0" value="<?php echo htmlspecialchars($current_base_fee_value); ?>" placeholder="0.00"
                                                aria-label="Base monthly fee for <?php echo htmlspecialchars($student['full_name']); ?>">
                                            <?php if (!empty($base_fee_error)): ?>
                                                <span class="form-error"><?php echo htmlspecialchars($base_fee_error); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="number" name="fees[<?php echo $s_id; ?>][monthly_van_fee_component]"
                                                class="w-24 px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 <?php echo (!empty($van_fee_error)) ? 'is-invalid' : ''; ?>"
                                                step="0.01" min="0" value="<?php echo htmlspecialchars($current_van_fee_value); ?>" placeholder="0.00"
                                                aria-label="Monthly van fee component for <?php echo htmlspecialchars($student['full_name']); ?>">
                                            <?php if (!empty($van_fee_error)): ?>
                                                <span class="form-error"><?php echo htmlspecialchars($van_fee_error); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="whitespace-nowrap flex items-center justify-start text-sm">
                                            <?php echo $status_icon . $status_text_display; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end mt-8">
                        <button type="submit" class="inline-flex items-center px-8 py-3 border border-transparent rounded-md shadow-lg text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            <i class="fas fa-save mr-3"></i> Save All Fee Structures
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 italic py-8 text-center bg-gray-50 border border-gray-200 rounded-lg">
                        <i class="fas fa-info-circle mr-2 text-gray-500"></i> No active students found matching your criteria to manage fee structures. Please adjust filters or ensure students are active.
                    </p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>

<?php
// Include the footer file.
require_once "./admin_footer.php";
?>