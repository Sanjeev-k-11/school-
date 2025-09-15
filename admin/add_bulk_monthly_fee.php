<?php
// School/admin/add_bulk_monthly_fee.php

session_start();

require_once "../config.php"; // Adjust path as needed

// Check if user is logged in and is ADMIN or Principal
// Allowing Principal as they often manage finances/fees
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins and Principals can add bulk fee records.</p>";
    header("location: ../login.php"); // Redirect unauthorized users
    exit;
}

// Set the page title *before* including the header
$pageTitle = "Add Bulk Monthly Fee Records";


// --- Variables ---
// Filter variables (for both GET and POST to retain state)
$selected_academic_year_filter = $_GET['academic_year_filter'] ?? ($_POST['academic_year_filter'] ?? ''); // This will be used for structure lookup
$selected_class = $_GET['current_class'] ?? ($_POST['current_class'] ?? '');
$selected_van_filter = $_GET['van_filter'] ?? ($_POST['van_filter'] ?? ''); // 'all', 'yes', 'no'

// Fee input variables (for POST submission)
$fee_month_input = $_POST['fee_month'] ?? '';
$fee_year_input = $_POST['fee_year'] ?? '';
$base_monthly_fee_input = $_POST['base_monthly_fee'] ?? ''; // Only used for manual input
$monthly_van_fee_input = $_POST['monthly_van_fee'] ?? ''; // Only used for manual input
$monthly_exam_fee_input = $_POST['monthly_exam_fee'] ?? '';
$monthly_electricity_fee_input = $_POST['monthly_electricity_fee'] ?? '';

// Display variables (retain POST values on error, use defaults on GET)
$fee_month_display = $fee_month_input;
$fee_year_display = $fee_year_input ?: date('Y'); // Default year to current
$base_monthly_fee_display = $base_monthly_fee_input; // Used only for manual input form
$monthly_van_fee_display = $monthly_van_fee_input; // Used only for manual input form
$monthly_exam_fee_display = $monthly_exam_fee_input;
$monthly_electricity_fee_display = $monthly_electricity_fee_input;


// Error variables
$fee_input_errors = [];
$processing_message = null; // To store summary of bulk operation
$skipped_no_structure_count = 0; // New counter for students without a fee structure


// Available options for filters (fetched from DB)
$available_academic_years = [];
$available_classes = [];

// Variables for the toast message system
$toast_message = '';
$toast_type = ''; // 'success', 'error', 'warning', 'info'

// Check for operation messages set in other pages or previous requests
if (isset($_SESSION['operation_message'])) {
    $msg = $_SESSION['operation_message'];
    $msg_lower = strtolower(strip_tags($msg)); // Use strip_tags for safety

     if (strpos($msg_lower, 'successfully') !== false || strpos($msg_lower, 'added') !== false || strpos($msg_lower, 'updated') !== false || strpos($msg_lower, 'deleted') !== false || strpos($msg_lower, 'complete') !== false) {
          $toast_type = 'success';
     } elseif (strpos($msg_lower, 'access denied') !== false || strpos($msg_lower, 'error') !== false || strpos($msg_lower, 'failed') !== false || strpos($msg_lower, 'could not') !== false || strpos($msg_lower, 'invalid') !== false || strpos($msg_lower, 'problem') !== false) {
          $toast_type = 'error';
     } elseif (strpos($msg_lower, 'warning') !== false || strpos($msg_lower, 'not found') !== false || strpos($msg_lower, 'correct the errors') !== false || strpos($msg_lower, 'already') !== false || strpos($msg_lower, 'please select') !== false || strpos($msg_lower, 'no records found') !== false || strpos($msg_lower, 'skipped') !== false) {
          $toast_type = 'warning';
     } else {
          $toast_type = 'info';
     }
    $toast_message = strip_tags($msg); // Pass the stripped message to JS
    unset($_SESSION['operation_message']); // Clear the session message
}


// --- Fetch Filter Options ---
if ($link === false) {
    // DB connection error handled below, no filters fetched
     if (empty($toast_message)) { // Only set if no other toast message exists
         $toast_message = "Database connection error. Cannot load filter options.";
         $toast_type = 'error';
     }
     error_log("Add Bulk Fee DB connection failed: " . mysqli_connect_error());
} else {
    // Fetch distinct academic years from student_fee_structures
    $sql_years = "SELECT DISTINCT academic_year FROM student_fee_structures WHERE academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC";
    if ($result_years = mysqli_query($link, $sql_years)) {
        while ($row = mysqli_fetch_assoc($result_years)) {
            $available_academic_years[] = htmlspecialchars($row['academic_year']);
        }
        mysqli_free_result($result_years);
    } else {
         error_log("Error fetching academic years for filter: " . mysqli_error($link));
    }

    // Fetch distinct classes from students table
    $sql_classes = "SELECT DISTINCT current_class FROM students WHERE current_class IS NOT NULL AND current_class != '' ORDER BY current_class ASC";
     if ($result_classes = mysqli_query($link, $sql_classes)) {
         while ($row = mysqli_fetch_assoc($result_classes)) {
             $available_classes[] = htmlspecialchars($row['current_class']);
         }
         mysqli_free_result($result_classes);
     } else {
          error_log("Error fetching classes for filter: " . mysqli_error($link));
     }
}


// Helper function for mysqli_stmt_bind_param for dynamic arguments
function refs(&$arr) { // Pass by reference for PHP < 5.3 compatibility with call_user_func_array
    if (strnatcmp(phpversion(), '5.3') >= 0) {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

// --- Handle POST Request (Bulk Fee Addition) ---
$students_list_for_display = []; // This will hold the students found after applying filters

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? 'manual_add'; // Determine if it's manual add or add from structure

    // Re-validate common fee inputs (month, year, exam, electricity)
    $fee_month_for_db = null;
    $fee_year_for_db = null;
    $monthly_exam_fee_for_db = 0.0;
    $monthly_electricity_fee_for_db = 0.0;

    // Validate Month
    if (empty($fee_month_input)) {
        $fee_input_errors['fee_month'] = "Please select a month.";
    } else {
        $month_int = filter_var($fee_month_input, FILTER_VALIDATE_INT);
        if ($month_int === false || $month_int < 1 || $month_int > 12) {
            $fee_input_errors['fee_month'] = "Invalid month selected.";
        } else {
            $fee_month_for_db = $month_int;
        }
    }

    // Validate Year
    if (empty($fee_year_input)) {
        $fee_input_errors['fee_year'] = "Please enter a year.";
    } else {
         $year_int = filter_var($fee_year_input, FILTER_VALIDATE_INT);
         if ($year_int === false || $year_int < 2000 || $year_int > 2100) {
              $fee_input_errors['fee_year'] = "Invalid year (e.g., 2000-2100).";
         } else {
              $fee_year_for_db = $year_int;
         }
    }

    // Validate Optional Fee fields (can be empty, treated as 0)
     if (!empty($monthly_exam_fee_input)) {
        $exam_fee_float = filter_var($monthly_exam_fee_input, FILTER_VALIDATE_FLOAT);
         if ($exam_fee_float === false || $exam_fee_float < 0) {
             $fee_input_errors['monthly_exam_fee'] = "Invalid Exam fee.";
         } else {
             $monthly_exam_fee_for_db = $exam_fee_float;
         }
    } else {
        $monthly_exam_fee_for_db = 0.0;
    }

     if (!empty($monthly_electricity_fee_input)) {
        $elec_fee_float = filter_var($monthly_electricity_fee_input, FILTER_VALIDATE_FLOAT);
         if ($elec_fee_float === false || $elec_fee_float < 0) {
             $fee_input_errors['monthly_electricity_fee'] = "Invalid Electricity fee.";
         } else {
             $monthly_electricity_fee_for_db = $elec_fee_float;
         }
    } else {
         $monthly_electricity_fee_for_db = 0.0;
    }


    // Logic specific to 'manual_add' or 'add_from_structure'
    $base_monthly_fee_for_db = null; // Will be determined based on action
    $monthly_van_fee_for_db = null;  // Will be determined based on action

    if ($action === 'manual_add') {
        // Validate Base Fee for manual entry
        if ($base_monthly_fee_input === '') {
            $fee_input_errors['base_monthly_fee'] = "Base fee is required.";
        } else {
             $base_fee_float = filter_var($base_monthly_fee_input, FILTER_VALIDATE_FLOAT);
             if ($base_fee_float === false || $base_fee_float < 0 ) {
                  $fee_input_errors['base_monthly_fee'] = "Please enter a valid non-negative number for base fee.";
             } else {
                  $base_monthly_fee_for_db = $base_fee_float;
             }
        }
        // Validate Van Fee for manual entry
        if (!empty($monthly_van_fee_input)) {
            $van_fee_float = filter_var($monthly_van_fee_input, FILTER_VALIDATE_FLOAT);
             if ($van_fee_float === false || $van_fee_float < 0) {
                 $fee_input_errors['monthly_van_fee'] = "Invalid Van fee.";
             } else {
                 $monthly_van_fee_for_db = $van_fee_float;
             }
        } else {
            $monthly_van_fee_for_db = 0.0;
        }

    } elseif ($action === 'add_from_structure') {
        // For 'add_from_structure', base_monthly_fee and monthly_van_fee come from student_fee_structures
        // No direct validation on these form fields needed here.
        // We do need the selected academic year for lookup.
        if (empty($selected_academic_year_filter)) {
            $fee_input_errors['academic_year_filter'] = "Please select an Academic Year to fetch fee structures.";
        }
    }


    // Proceed if all common fee inputs are valid AND DB connection is good
    if (empty($fee_input_errors) && $link !== false) {

        // --- Fetch Students based on Filters (Using POSTed filter values) ---
         $sql_select_students = "SELECT user_id, full_name, current_class, whatsapp_number, takes_van FROM students";
         $student_where_clauses = [];
         $student_param_types = "";
         $student_param_values = [];

        if (!empty($selected_class)) {
            $student_where_clauses[] = "current_class = ?";
            $student_param_types .= "s";
            $student_param_values[] = $selected_class;
        }

         if ($selected_van_filter === 'yes') {
             $student_where_clauses[] = "takes_van = 1";
         } elseif ($selected_van_filter === 'no') {
             $student_where_clauses[] = "takes_van = 0";
         }

        if (!empty($student_where_clauses)) {
            $sql_select_students .= " WHERE " . implode(" AND ", $student_where_clauses);
        }
        $sql_select_students .= " ORDER BY current_class ASC, full_name ASC";

        if ($stmt_select = mysqli_prepare($link, $sql_select_students)) {
             if (!empty($student_param_types)) {
                 call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_select, $student_param_types], refs($student_param_values)));
             }

            if (mysqli_stmt_execute($stmt_select)) {
                $result_select = mysqli_stmt_get_result($stmt_select);
                 $students_list_for_display = mysqli_fetch_all($result_select, MYSQLI_ASSOC); // Store for later display
                mysqli_free_result($result_select);

                 if (empty($students_list_for_display)) {
                      $processing_message = "No students found matching the selected filters. Cannot add fees.";
                      $toast_type = 'warning';
                 }

            } else {
                 $db_error = mysqli_stmt_error($stmt_select);
                 $processing_message = "Error fetching students for processing. Database error: " . htmlspecialchars($db_error);
                 $toast_type = 'error';
                 error_log("Add Bulk Fee student select query failed: " . $db_error);
            }
            mysqli_stmt_close($stmt_select);
        } else {
             $db_error = mysqli_error($link);
             $processing_message = "Error preparing student select statement. Database error: " . htmlspecialchars($db_error);
             $toast_type = 'error';
             error_log("Add Bulk Fee prepare student select failed: " . $db_error);
        }

        // --- Process Bulk Insert if Students Found AND No Processing Message Set ---
        if (!empty($students_list_for_display) && empty($processing_message)) {

            $added_count = 0;
            $skipped_duplicate_count = 0;
            $failed_insert_count = 0;

            mysqli_begin_transaction($link);
            $transaction_success = true;

            // NEW: Fetch all relevant fee structures if action is 'add_from_structure'
            $student_fee_structures_map = [];
            if ($action === 'add_from_structure' && !empty($selected_academic_year_filter)) {
                $student_ids_in_list = array_column($students_list_for_display, 'user_id');
                if (!empty($student_ids_in_list)) {
                    $placeholders_for_structures = implode(',', array_fill(0, count($student_ids_in_list), '?'));
                    $sql_fetch_structures = "SELECT student_id, base_monthly_fee, monthly_van_fee_component FROM student_fee_structures WHERE academic_year = ? AND student_id IN ($placeholders_for_structures)";

                    if ($stmt_fetch_structures = mysqli_prepare($link, $sql_fetch_structures)) {
                        $bind_params_structures = array_merge([$selected_academic_year_filter], $student_ids_in_list);
                        $types_structures = 's' . str_repeat('i', count($student_ids_in_list));
                        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_fetch_structures, $types_structures], refs($bind_params_structures)));

                        if (mysqli_stmt_execute($stmt_fetch_structures)) {
                            $result_structures = mysqli_stmt_get_result($stmt_fetch_structures);
                            while ($row = mysqli_fetch_assoc($result_structures)) {
                                $student_fee_structures_map[$row['student_id']] = $row;
                            }
                            mysqli_free_result($result_structures);
                        } else {
                            $db_error = mysqli_stmt_error($stmt_fetch_structures);
                            error_log("Add Bulk Fee: Error fetching fee structures: " . $db_error);
                            $transaction_success = false;
                            $processing_message = "Database error fetching fee structures: " . htmlspecialchars($db_error);
                            $toast_type = 'error';
                        }
                        mysqli_stmt_close($stmt_fetch_structures);
                    } else {
                        $db_error = mysqli_error($link);
                        error_log("Add Bulk Fee: Error preparing fee structure fetch: " . $db_error);
                        $transaction_success = false;
                        $processing_message = "Database error preparing fee structure fetch: " . htmlspecialchars($db_error);
                        $toast_type = 'error';
                    }
                }
            } // End fetch fee structures

            // Only proceed with inserts if fetching structures was successful (or not needed for manual_add)
            if ($transaction_success) {
                $sql_insert_fee = "INSERT INTO student_monthly_fees (student_id, fee_year, fee_month, base_monthly_fee, monthly_van_fee, monthly_exam_fee, monthly_electricity_fee, amount_due, amount_paid, is_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";

                if ($stmt_insert = mysqli_prepare($link, $sql_insert_fee)) {

                    foreach ($students_list_for_display as $student) {
                        $student_id = $student['user_id'];
                        $is_duplicate = false;

                        // Check for duplicate entry for this specific student, year, and month
                        $sql_check_duplicate = "SELECT id FROM student_monthly_fees WHERE student_id = ? AND fee_year = ? AND fee_month = ?";
                        if ($stmt_check = mysqli_prepare($link, $sql_check_duplicate)) {
                            mysqli_stmt_bind_param($stmt_check, "iii", $student_id, $fee_year_for_db, $fee_month_for_db);
                            mysqli_stmt_execute($stmt_check);
                            mysqli_stmt_store_result($stmt_check);
                            $is_duplicate = mysqli_stmt_num_rows($stmt_check) > 0;
                            mysqli_stmt_close($stmt_check);
                        } else {
                            $db_error = mysqli_error($link);
                            error_log("Add Bulk Fee prepare duplicate check failed for student ID " . $student_id . ": " . $db_error);
                            $failed_insert_count++;
                            $transaction_success = false;
                            continue;
                        }

                        if ($is_duplicate) {
                            $skipped_duplicate_count++;
                        } else {
                            $current_student_base_fee = 0.0;
                            $current_student_van_fee = 0.0; // This is the *applied* van fee for this monthly record

                            if ($action === 'add_from_structure') {
                                $structure_data = $student_fee_structures_map[$student_id] ?? null;
                                if ($structure_data) {
                                    $current_student_base_fee = (float)$structure_data['base_monthly_fee'];
                                    // Apply van fee from structure only if student takes van
                                    if (($student['takes_van'] ?? 0) == 1) {
                                        $current_student_van_fee = (float)$structure_data['monthly_van_fee_component'];
                                    }
                                } else {
                                    // No fee structure found for this student for the selected academic year.
                                    // Skip this student or handle as needed. Here, we'll increment a new counter.
                                    $skipped_no_structure_count++;
                                    error_log("Skipped student " . $student_id . ": No fee structure found for " . $selected_academic_year_filter);
                                    continue; // Move to the next student
                                }
                            } else { // manual_add
                                $current_student_base_fee = (float)$base_monthly_fee_for_db;
                                // Apply van fee from manual input only if student takes van
                                if (($student['takes_van'] ?? 0) == 1) {
                                    $current_student_van_fee = (float)$monthly_van_fee_for_db;
                                }
                            }

                            // Calculate total amount due for this specific monthly record
                            $current_student_amount_due = $current_student_base_fee + $current_student_van_fee +
                                                          (float)$monthly_exam_fee_for_db + (float)$monthly_electricity_fee_for_db;


                            // Bind parameters for the insert statement and execute for this student
                            $bind_types_fee = "iiiddddd";
                            // Using temporary variables to bind by reference
                            $tmp_student_id = $student_id;
                            $tmp_fee_year = $fee_year_for_db;
                            $tmp_fee_month = $fee_month_for_db;
                            $tmp_base_fee = $current_student_base_fee;
                            $tmp_van_fee = $current_student_van_fee; // This is the *applied* van fee for this monthly record
                            $tmp_exam_fee = $monthly_exam_fee_for_db;
                            $tmp_elec_fee = $monthly_electricity_fee_for_db;
                            $tmp_amount_due = $current_student_amount_due;

                            mysqli_stmt_bind_param($stmt_insert, $bind_types_fee,
                                $tmp_student_id,
                                $tmp_fee_year,
                                $tmp_fee_month,
                                $tmp_base_fee,
                                $tmp_van_fee,
                                $tmp_exam_fee,
                                $tmp_elec_fee,
                                $tmp_amount_due
                            );

                            if (mysqli_stmt_execute($stmt_insert)) {
                                $added_count++;
                            } else {
                                $db_error = mysqli_stmt_error($stmt_insert);
                                error_log("Add Bulk Fee insert failed for student ID " . $student_id . " Year " . $fee_year_for_db . " Month " . $fee_month_for_db . ": " . $db_error);
                                $failed_insert_count++;
                                $transaction_success = false;
                            }
                        } // end if !is_duplicate
                    } // end foreach student

                    mysqli_stmt_close($stmt_insert);

                    // Decide whether to commit or rollback the entire batch
                    if (!$transaction_success && ($failed_insert_count > 0 || $skipped_no_structure_count > 0)) {
                        mysqli_rollback($link);
                        $processing_message = "Bulk fee addition partially failed or encountered errors. Transaction rolled back.";
                        if ($failed_insert_count > 0) $processing_message .= " " . $failed_insert_count . " record(s) failed to insert.";
                        if ($skipped_no_structure_count > 0) $processing_message .= " " . $skipped_no_structure_count . " record(s) skipped (no fee structure).";
                        $toast_type = 'error';
                        error_log("Bulk fee addition transaction rolled back due to failures/skips.");
                    } else {
                        mysqli_commit($link);
                        $processing_message = "Bulk fee addition complete. " . $added_count . " records added.";
                        if ($skipped_duplicate_count > 0) $processing_message .= " " . $skipped_duplicate_count . " skipped (duplicate).";
                        if ($skipped_no_structure_count > 0) $processing_message .= " " . $skipped_no_structure_count . " skipped (no fee structure).";
                        if ($failed_insert_count > 0) $processing_message .= " " . $failed_insert_count . " failed to insert.";

                        // Set success/summary message for toast
                        if ($added_count > 0 && $failed_insert_count == 0 && $skipped_no_structure_count == 0) {
                            $toast_type = 'success';
                        } elseif ($added_count > 0) {
                            $toast_type = 'warning'; // Some added, but also some skipped/failed
                        } else {
                            $toast_type = 'info'; // Nothing added, maybe all skipped
                        }
                    }
                } else {
                    $db_error = mysqli_error($link);
                    $processing_message = "Error preparing fee insert statement for bulk operation. Database error: " . htmlspecialchars($db_error);
                    $toast_type = 'error';
                    error_log("Add Bulk Fee prepare insert failed: " . $db_error);
                    mysqli_rollback($link); // Rollback if prepare failed
                }
            } // End if (transaction_success) from fee structures fetch
        } // End if (!empty($students_list_for_display) && empty($processing_message))

    } elseif (!empty($fee_input_errors)) {
        $processing_message = "Please correct the errors in the fee amount fields and academic year filter (if applicable).";
        $toast_type = 'error';
    } elseif ($link === false) {
         $processing_message = "Database connection failed. Cannot process fee addition.";
         $toast_type = 'error';
    }


} // --- End POST Request Handling ---


// --- Handle GET Request OR POST with errors (Display Students List) ---
// This block runs for initial GET or if POST had validation/processing/DB errors *before* insert loop
$students_list_message = "Apply filters to see students."; // Default message when no filters are applied yet
$students_list_message_type = 'info'; // Default style for the list message

// Check if filters are applied OR if it was a POST request (even with errors, show the targeted students)
// We always try to fetch the student list if filters are set or if it's a POST request,
// unless the DB connection is down.
$is_filtered = !empty($selected_academic_year_filter) || !empty($selected_class) || !empty($selected_van_filter);
$should_fetch_list = $is_filtered || $_SERVER["REQUEST_METHOD"] == "POST";


if ($should_fetch_list && $link !== false) {

    // Build the SQL query to select students based on filters (using $selected_* variables)
    $sql_select_students_display = "SELECT user_id, virtual_id, full_name, current_class, takes_van FROM students"; // Include virtual_id for display
    $student_where_clauses_display = [];
    $student_param_types_display = "";
    $student_param_values_display = [];

    // Academic Year filter is only for context in the filter form, not student selection SQL for now
    // (but will be used to fetch fee structures when bulk adding from structure)

    if (!empty($selected_class)) {
        $student_where_clauses_display[] = "current_class = ?";
        $student_param_types_display .= "s";
        $student_param_values_display[] = $selected_class;
    }

     if ($selected_van_filter === 'yes') {
         $student_where_clauses_display[] = "takes_van = 1";
     } elseif ($selected_van_filter === 'no') {
         $student_where_clauses_display[] = "takes_van = 0";
     }

    if (!empty($student_where_clauses_display)) {
        $sql_select_students_display .= " WHERE " . implode(" AND ", $student_where_clauses_display);
    }
    $sql_select_students_display .= " ORDER BY current_class ASC, full_name ASC";

    // Prepare and execute the student select statement for display
    if ($stmt_select_display = mysqli_prepare($link, $sql_select_students_display)) {
         if (!empty($student_param_types_display)) {
             call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_select_display, $student_param_types_display], refs($student_param_values_display)));
         }

        if (mysqli_stmt_execute($stmt_select_display)) {
            $result_select_display = mysqli_stmt_get_result($stmt_select_display);
            $students_list_for_display = mysqli_fetch_all($result_select_display, MYSQLI_ASSOC);
            mysqli_free_result($result_select_display);

            if (empty($students_list_for_display)) {
                $students_list_message = "No students found matching the selected filters.";
                $students_list_message_type = 'warning';

                 if (empty($toast_message) && $_SERVER["REQUEST_METHOD"] == "GET" && $is_filtered) {
                     $toast_message = $students_list_message;
                     $toast_type = $students_list_message_type;
                 }

            } else {
                 $students_list_message = "Found " . count($students_list_for_display) . " students matching filters:";
                 $students_list_message_type = 'info';
            }

        } else {
             $db_error = mysqli_stmt_error($stmt_select_display);
             $students_list_message = "Error fetching students list. Database error: " . htmlspecialchars($db_error);
             $students_list_message_type = 'error';

             if (empty($toast_message)) {
                  $toast_message = $students_list_message;
                  $toast_type = $students_list_message_type;
             }
             error_log("Add Bulk Fee student select query failed for display: " . $db_error);
        }
        mysqli_stmt_close($stmt_select_display);
    } else {
         $db_error = mysqli_error($link);
         $students_list_message = "Error preparing student list statement. Database error: " . htmlspecialchars($db_error);
         $students_list_message_type = 'error';

          if (empty($toast_message)) {
               $toast_message = $students_list_message;
               $toast_type = $students_list_message_type;
          }
         error_log("Add Bulk Fee prepare student list failed: " . $db_error);
    }
} elseif ($link === false) {
     $students_list_message = "Database connection failed. Cannot fetch student list.";
     $students_list_message_type = 'error';
}


// Close connection
if (isset($link) && is_object($link) && mysqli_ping($link)) {
     mysqli_close($link);
}
?>

<?php
// Include the header file.
require_once "./admin_header.php";
?>

     <!-- Custom Styles -->
     <style>
         body {
             padding-top: 4.5rem; /* Space for fixed header */
             background-color: #f3f4f6;
             min-height: 100vh;
              transition: padding-left 0.3s ease; /* Smooth transition for padding when sidebar opens/closes */
         }
         body.sidebar-open {
             padding-left: 16rem; /* Adjust based on your sidebar width */
         }
         .fixed-header {
              position: fixed;
              top: 0;
              left: 0;
              right: 0;
              height: 4.5rem;
              background-color: #ffffff;
              box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
              padding: 1rem;
              display: flex;
              align-items: center;
              z-index: 10;
               transition: left 0.3s ease;
         }
          body.sidebar-open .fixed-header {
              left: 16rem;
          }
         .main-content-wrapper {
             width: 100%;
             max-width: 1280px;
             margin-left: auto;
             margin-right: auto;
             padding: 2rem 1rem; /* py-8 px-4 */
         }
          @media (min-width: 768px) { /* md breakpoint */
               .main-content-wrapper {
                   padding-left: 2rem; /* md:px-8 */
                   padding-right: 2rem; /* md:px-8 */
               }
          }

         .form-error {
            color: #dc2626; /* red-600 */
            font-size: 0.75em; /* text-xs */
            margin-top: 0.25em;
            display: block;
         }
         .form-control.is-invalid {
             border-color: #dc2626; /* red-600 */
         }
         .form-control.is-invalid:focus {
              border-color: #ef4444; /* red-500 */
              box-shadow: 0 0 0 1px #ef4444; /* ring red-500 */
         }
         input[type="number"]::placeholder {
               color: #9ca3af; /* gray-400 */
           }

         /* --- Toast Notification Styles --- */
         .toast-container {
             position: fixed; top: 1rem; right: 1rem; z-index: 100; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none; max-width: 90%;
         }
         .toast {
             background-color: #fff; color: #333; padding: 0.75rem 1.25rem; border-radius: 0.375rem; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
             opacity: 0; transform: translateX(100%); transition: opacity 0.3s ease-out, transform 0.3s ease-out;
             pointer-events: auto; min-width: 200px; max-width: 350px; display: flex; align-items: center; word-break: break-word;
         }
         .toast.show { opacity: 1; transform: translateX(0); }
         .toast-success { border-left: 5px solid #10b981; color: #065f46; }
         .toast-error { border-left: 5px solid #ef4444; color: #991b1b; }
         .toast-warning { border-left: 5px solid #f59e0b; color: #9a3412; }
         .toast-info { border-left: 5px solid #3b82f6; color: #1e40af; }
         .toast .close-button {
             margin-left: auto; background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer; padding: 0 0.25rem; line-height: 1; font-weight: bold;
         }

          /* Specific styles for student list */
          .student-list-container {
               background-color: #ffffff;
               padding: 1.5rem;
               border-radius: 0.5rem;
               box-shadow: 0 1px 3px rgba(0,0,0,0.1);
               margin-bottom: 2rem;
          }
           .student-list-container h3 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1f2937; /* Default H3 color */
                margin-bottom: 1rem;
           }
            /* Style for the message box inside the list container */
           .student-list-container .message-box {
                margin: 0; /* Remove margin to fit better */
                padding: 0.75rem 1rem; /* Adjust padding */
           }
          .student-list {
               list-style: none;
               padding: 0;
               margin: 0;
               display: flex;
               flex-wrap: wrap; /* Allow items to wrap */
               gap: 0.5rem; /* Space between list items */
          }
           .student-list li {
               background-color: #f9fafb; /* gray-50 */
               border: 1px solid #e5e7eb; /* gray-200 */
               padding: 0.4rem 0.8rem; /* py-1.5 px-3 */
               border-radius: 0.25rem; /* rounded-sm */
               font-size: 0.875rem; /* text-sm */
               color: #374151; /* gray-700 */
           }
           /* Message styles (reused for general messages and specific ones) */
            .message-box {
                padding: 1rem; border-radius: 0.5rem; border: 1px solid transparent; margin-bottom: 1.5rem; text-align: center;
            }
             .message-box.success { color: #065f46; background-color: #d1fae5; border-color: #a7f3d0; } /* green */
              .message-box.error { color: #b91c1c; background-color: #fee2e2; border-color: #fca5a5; } /* red */
              .message-box.warning { color: #b45309; background-color: #fffce0; border-color: #fde68a; } /* yellow/amber */
              .message-box.info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; } /* cyan/blue */

     </style>
     <script>
          // --- Toast Notification JS ---
         document.addEventListener('DOMContentLoaded', function() {
             const toastContainer = document.getElementById('toastContainer');
             if (!toastContainer) {
                 console.error('Toast container #toastContainer not found.');
             }

             function showToast(message, type = 'info', duration = 5000) {
                 if (!message || !toastContainer) return;

                 const toast = document.createElement('div');
                 // Use innerHTML to allow basic HTML tags like <p> if they come from session messages,
                 // but ensure the content is safe (strip_tags is used in PHP).
                 toast.innerHTML = message;
                 toast.classList.add('toast', `toast-${type}`);

                 const closeButton = document.createElement('button');
                 closeButton.classList.add('close-button');
                 closeButton.textContent = '×';
                 closeButton.onclick = () => toast.remove();
                 toast.appendChild(closeButton);

                 toastContainer.appendChild(toast);

                 requestAnimationFrame(() => {
                     toast.classList.add('show');
                 });

                 if (duration > 0) {
                     setTimeout(() => {
                         toast.classList.remove('show');
                         toast.addEventListener('transitionend', () => toast.remove(), { once: true });
                     }, duration);
                 }
             }

             // Trigger toast display on DOM load if a message exists
             const phpMessage = <?php echo json_encode($toast_message); ?>;
             const messageType = <?php echo json_encode($toast_type); ?>;

             if (phpMessage) {
                 showToast(phpMessage, messageType);
             }

             // JavaScript to handle disabling/enabling fee input fields based on button clicked
             const manualAddButton = document.getElementById('manual_add_btn');
             const addFromStructureButton = document.getElementById('add_from_structure_btn');
             const baseMonthlyFeeInput = document.getElementById('base_monthly_fee');
             const monthlyVanFeeInput = document.getElementById('monthly_van_fee');

             function toggleFeeInputs(disableManual) {
                 baseMonthlyFeeInput.disabled = disableManual;
                 monthlyVanFeeInput.disabled = disableManual;

                 if (disableManual) {
                     baseMonthlyFeeInput.value = ''; // Clear values if disabled
                     monthlyVanFeeInput.value = '';
                     baseMonthlyFeeInput.classList.remove('is-invalid');
                     monthlyVanFeeInput.classList.remove('is-invalid');
                     const errors = document.querySelectorAll('.form-error');
                     errors.forEach(error => error.textContent = ''); // Clear all error messages
                 }
             }

             if (manualAddButton && addFromStructureButton && baseMonthlyFeeInput && monthlyVanFeeInput) {
                // Initialize state based on previous POST if any, or default to manual_add enabled
                const lastAction = "<?php echo htmlspecialchars($_POST['action'] ?? ''); ?>";
                if (lastAction === 'add_from_structure') {
                    toggleFeeInputs(true);
                } else {
                    toggleFeeInputs(false);
                }


                 manualAddButton.addEventListener('click', function() {
                     toggleFeeInputs(false); // Enable manual inputs
                     this.name = 'action'; // Set name for this button to send action value
                     this.value = 'manual_add';
                     addFromStructureButton.name = ''; // Unset name for other button
                 });

                 addFromStructureButton.addEventListener('click', function() {
                     toggleFeeInputs(true); // Disable manual inputs
                     this.name = 'action'; // Set name for this button to send action value
                     this.value = 'add_from_structure';
                     manualAddButton.name = ''; // Unset name for other button
                 });
             }
         });
     </script>
</head>
<body class="bg-gray-100">

    <?php
    // Include the admin sidebar and fixed header.
    // Assumes admin_sidebar.php renders the fixed header or includes logic for it.
    $sidebar_path = "./admin_sidebar.php";
    if (file_exists($sidebar_path)) {
        require_once $sidebar_path;
    } else {
        // Fallback header if sidebar file is missing
        echo '<div class="fixed-header">';
        echo '<h1 class="text-xl md:text-2xl font-bold text-gray-800 flex-grow">Add Bulk Monthly Fee Records (Sidebar file missing!)</h1>';
        echo '<span class="ml-auto text-sm text-gray-700 hidden md:inline">Logged in as: <span class="font-semibold">' . htmlspecialchars($_SESSION['name'] ?? 'Admin') . '</span></span>';
        echo '<a href="../logout.php" class="ml-4 text-red-600 hover:text-red-800 hover:underline transition duration-150 ease-in-out text-sm font-medium hidden md:inline">Logout</a>';
        echo '</div>';
         echo '<div class="w-full max-w-screen-xl mx-auto px-4 py-8" style="margin-top: 4.5rem;">';
         echo '<div class="message-box error" role="alert">Admin sidebar file not found! Check path: `' . htmlspecialchars($sidebar_path) . '`</div>';
         echo '</div>';
    }
    ?>

    <!-- Toast Container (Positioned fixed) -->
    <div id="toastContainer" class="toast-container">
        <!-- Toasts will be dynamically added here by JS -->
    </div>

    <!-- Main content wrapper -->
    <div class="main-content-wrapper">

        <h2 class="text-2xl font-bold mb-6 text-gray-800">Add Bulk Monthly Fee Records</h2>

         <!-- Filter Form -->
         <!-- Uses GET method to update the student list without processing fees -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="bg-white p-6 rounded-lg shadow-md mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
             <!-- Academic Year Filter - Now crucial for fee structure lookup -->
             <div>
                 <label for="academic_year_filter" class="block text-sm font-medium text-gray-700">Academic Year <span class="text-red-500">*</span></label>
                 <select name="academic_year_filter" id="academic_year_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                     <option value="">Select Year</option>
                     <?php foreach ($available_academic_years as $year): ?>
                         <option value="<?php echo $year; ?>" <?php echo ($selected_academic_year_filter === $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                     <?php endforeach; ?>
                 </select>
                 <?php // Display error if filter is clicked without selecting year for structure adds
                 if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($fee_input_errors['academic_year_filter'])) {
                    echo '<span class="form-error">' . htmlspecialchars($fee_input_errors['academic_year_filter']) . '</span>';
                 }
                 ?>
             </div>
             <!-- Class Filter -->
             <div>
                 <label for="current_class" class="block text-sm font-medium text-gray-700">Filter by Class</label>
                 <select name="current_class" id="current_class" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                     <option value="">All Classes</option>
                     <?php foreach ($available_classes as $class): ?>
                         <option value="<?php echo $class; ?>" <?php echo ($selected_class === $class) ? 'selected' : ''; ?>><?php echo $class; ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>
             <!-- Van Service Filter -->
             <div>
                 <label for="van_filter" class="block text-sm font-medium text-gray-700">Filter by Van Service</label>
                 <select name="van_filter" id="van_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                     <option value="all" <?php echo ($selected_van_filter === 'all' || $selected_van_filter === '') ? 'selected' : ''; ?>>All Students</option>
                     <option value="yes" <?php echo ($selected_van_filter === 'yes') ? 'selected' : ''; ?>>Uses Van Service</option>
                     <option value="no" <?php echo ($selected_van_filter === 'no') ? 'selected' : ''; ?>>Does NOT Use Van Service</option>
                 </select>
             </div>
             <div class="md:col-span-1 text-right md:text-left"> <!-- Span across columns and align right -->
                  <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm font-medium">Apply Filters</button>
             </div>
        </form>

         <!-- Students List Preview -->
         <div class="student-list-container">
              <?php
               // Determine message style based on list message type
               $list_message_class = 'info'; // Default
               if ($students_list_message_type === 'error') $list_message_class = 'error';
               elseif ($students_list_message_type === 'warning') $list_message_class = 'warning';

              if ($students_list_message_type === 'error' || $students_list_message_type === 'warning') {
                   echo "<div class='message-box " . $list_message_class . " mb-4'>" . htmlspecialchars($students_list_message) . "</div>";
               } else {
                    echo "<h3>" . htmlspecialchars($students_list_message) . "</h3>";
               }
              ?>

               <?php if (!empty($students_list_for_display)): ?>
                    <ul class="student-list">
                         <?php foreach($students_list_for_display as $student): ?>
                             <li>
                                 <?php echo htmlspecialchars($student['full_name']) . " (Class: " . htmlspecialchars($student['current_class']) . ")"; ?>
                                 <?php
                                 if (($student['takes_van'] ?? 0) == 1) {
                                     echo ' - Van User';
                                 }
                                 ?>
                             </li>
                         <?php endforeach; ?>
                    </ul>
               <?php elseif ($should_fetch_list && $students_list_message_type !== 'error' && $students_list_message_type !== 'warning'): ?>
                    <p class="text-gray-600 italic text-sm">No students matched the selected criteria.</p>
               <?php elseif (!$should_fetch_list && $link !== false): ?>
                     <p class="text-gray-600 italic text-sm">Select criteria above and click "Apply Filters" to preview students.</p>
               <?php endif; ?>
         </div>

        <!-- Fee Input Form (only shown if students are found matching filters) -->
        <?php if (!empty($students_list_for_display) || $_SERVER["REQUEST_METHOD"] == "POST"): // Show form if students are found OR if it was a POST request (even if students became empty due to an error)?>
             <div class="bg-white p-6 rounded-lg shadow-md">
                 <h3 class="text-xl font-semibold mb-4 text-gray-800">Enter Monthly Fee Details</h3>

                 <?php
                  if (!empty($processing_message)) {
                       $message_type = 'info';
                       // Use the type explicitly set in PHP ($toast_type) if available,
                       // otherwise infer from message content.
                       if (!empty($toast_type)) {
                            $message_type = $toast_type;
                       } else {
                           $msg_lower = strtolower(strip_tags($processing_message));
                           if (strpos($msg_lower, 'success') !== false || strpos($msg_lower, 'added') !== false) {
                                $message_type = 'success';
                           } elseif (strpos($msg_lower, 'error') !== false || strpos($msg_lower, 'failed') !== false || strpos($msg_lower, 'could not') !== false) {
                                $message_type = 'error';
                           } elseif (strpos($msg_lower, 'skipped') !== false || strpos($msg_lower, 'no students found') !== false || strpos($msg_lower, 'warning') !== false) {
                                $message_type = 'warning';
                           } else {
                                $message_type = 'info';
                           }
                       }
                       echo "<div class='message-box " . htmlspecialchars($message_type) . " mb-4'>" . htmlspecialchars($processing_message) . "</div>";
                   }
                 ?>

                 <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">

                     <!-- Hidden inputs to carry filter values through POST -->
                     <input type="hidden" name="academic_year_filter" value="<?php echo htmlspecialchars($selected_academic_year_filter); ?>">
                     <input type="hidden" name="current_class" value="<?php echo htmlspecialchars($selected_class); ?>">
                     <input type="hidden" name="van_filter" value="<?php echo htmlspecialchars($selected_van_filter); ?>">


                     <!-- Month and Year for the Fee Record -->
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                             <label for="fee_month_post" class="block text-sm font-medium text-gray-700 mb-1">Month <span class="text-red-500">*</span></label>
                             <select name="fee_month" id="fee_month_post" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['fee_month'])) ? 'is-invalid' : ''; ?>">
                                 <option value="">Select Month</option>
                                 <?php
                                 $month_names_select = [
                                     1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                     5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                     9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                 ];
                                 for ($m = 1; $m <= 12; $m++) {
                                     $selected = ((int)($fee_month_display ?? 0) === $m) ? 'selected' : '';
                                     echo "<option value='" . $m . "'" . $selected . ">" . htmlspecialchars($month_names_select[$m]) . "</option>";
                                 }
                                 ?>
                             </select>
                             <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['fee_month'] ?? ''); ?></span>
                         </div>
                         <div>
                             <label for="fee_year_post" class="block text-sm font-medium text-gray-700 mb-1">Year <span class="text-red-500">*</span></label>
                             <input type="number" name="fee_year" id="fee_year_post" step="1" min="2000" max="2100" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['fee_year'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fee_year_display ?? date('Y')); ?>" placeholder="e.g., 2024">
                             <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['fee_year'] ?? ''); ?></span>
                         </div>
                     </div>

                     <!-- Fee Breakdown Inputs -->
                     <div><h4 class="text-md font-semibold text-gray-700 border-b pb-1 mb-3 mt-3">Fee Breakdown for ALL Selected Students</h4></div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                              <label for="base_monthly_fee" class="block text-sm font-medium text-gray-700 mb-1">Base Fee <span class="text-red-500">*</span></label>
                              <input type="number" name="base_monthly_fee" id="base_monthly_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['base_monthly_fee'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($base_monthly_fee_display ?? ''); ?>" placeholder="e.g., 1200.00">
                              <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['base_monthly_fee'] ?? ''); ?></span>
                              <p class="text-xs text-gray-500 mt-1">For 'Manual Input' method.</p>
                          </div>
                          <div>
                              <label for="monthly_van_fee" class="block text-sm font-medium text-gray-700 mb-1">Van Fee <span class="text-gray-500">(Only applied to students who use van service)</span></label>
                              <input type="number" name="monthly_van_fee" id="monthly_van_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['monthly_van_fee'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($monthly_van_fee_display ?? ''); ?>" placeholder="e.g., 300.00">
                              <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['monthly_van_fee'] ?? ''); ?></span>
                              <p class="text-xs text-gray-500 mt-1">For 'Manual Input' method.</p>
                          </div>
                      </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                             <label for="monthly_exam_fee" class="block text-sm font-medium text-gray-700 mb-1">Exam Fee (Optional)</label>
                             <input type="number" name="monthly_exam_fee" id="monthly_exam_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['monthly_exam_fee'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($monthly_exam_fee_display ?? ''); ?>" placeholder="e.g., 100.00">
                             <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['monthly_exam_fee'] ?? ''); ?></span>
                         </div>
                          <div>
                              <label for="monthly_electricity_fee" class="block text-sm font-medium text-gray-700 mb-1">Electricity Fee (Optional)</label>
                              <input type="number" name="monthly_electricity_fee" id="monthly_electricity_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($fee_input_errors['monthly_electricity_fee'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($monthly_electricity_fee_display ?? ''); ?>" placeholder="e.g., 50.00">
                              <span class="form-error"><?php echo htmlspecialchars($fee_input_errors['monthly_electricity_fee'] ?? ''); ?></span>
                          </div>
                     </div>


                     <div class="flex items-center justify-end gap-4 mt-6">
                          <button type="submit" id="manual_add_btn"
                                  class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                  name="action" value="manual_add"
                                  <?php echo empty($students_list_for_display) ? 'disabled' : ''; ?>
                          >Add Fee Records (Manual Input)</button>

                          <button type="submit" id="add_from_structure_btn"
                                  class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                  name="action" value="add_from_structure"
                                  <?php echo empty($students_list_for_display) ? 'disabled' : ''; ?>
                          >Add Fees from Fee Structures</button>
                          
                          <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-sm font-medium">Cancel</a>
                     </div>
                 </form>
             </div>
        <?php elseif($link === false): ?>
             <!-- This case is now handled by the students_list_message display block above, which will show the error box -->
        <?php endif; ?>


    </div> <!-- End main-content-wrapper -->

<?php
// Include the footer file.
require_once "./admin_footer.php";
?>