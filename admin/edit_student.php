<?php

session_start();

require_once "../config.php";
require_once "./cloudinary_upload_handler.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'principal')) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins and Principals can access this page.</p>";
    header("location: ../login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Permission denied. Only Admins can edit student records.</p>";
    if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) > 0) {
        header("location: view_student.php?id=" . htmlspecialchars($_GET['id']));
    } else {
        header("location: admin_dashboard.php");
    }
    exit;
}


$user_id = null;
$virtual_id = '';
$full_name = $father_name = $mother_name = $phone_number = $whatsapp_number = "";
$current_class = $previous_class = $previous_school = "";
$previous_marks_percentage_display = '';
$current_marks_display = '';
$roll_number = '';
$village = '';
$date_of_birth_display = '';

$takes_van_display = '';

$address = $pincode = $state = "";
$photo_filename = '';


$monthly_fee_records = [];

$new_fee_month_display = '';
$new_fee_year_display = '';
$new_base_monthly_fee_display = '';
$new_monthly_van_fee_display = '';
$new_monthly_exam_fee_display = '';
$new_monthly_electricity_fee_display = '';


$full_name_err = $father_name_err = $mother_name_err = $phone_number_err = "";
$current_class_err = "";
$virtual_id_err = "";
$roll_number_err = "";
$village_err = "";
$date_of_birth_err = "";
$photo_err = '';


$new_fee_month_err = '';
$new_fee_year_err = '';
$new_base_monthly_fee_err = '';
$new_monthly_van_fee_err = '';
$new_monthly_exam_fee_err = '';
$new_monthly_electricity_fee_err = '';
$new_monthly_fee_general_err = '';


$toast_message = '';
$toast_type = '';


$student_id_to_edit = null;


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $form_type = $_POST['form_type'] ?? '';

    $student_id_to_edit = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ??
        filter_input(INPUT_POST, 'student_id_for_fee', FILTER_VALIDATE_INT);


    if ($student_id_to_edit === false || $student_id_to_edit <= 0) {
        $toast_message = "Invalid student ID provided for processing.";
        $toast_type = 'error';
        $student_id_to_edit = null;
    }


    if ($student_id_to_edit !== null) {

        if ($form_type === 'student_details') {

            $full_name = trim($_POST["full_name"] ?? '');
            $father_name = trim($_POST["father_name"] ?? '');
            $mother_name = trim($_POST["mother_name"] ?? '');
            $phone_number = trim($_POST["phone_number"] ?? '');
            $whatsapp_number = trim($_POST["whatsapp_number"] ?? '');
            $current_class = trim($_POST["current_class"] ?? '');
            $previous_class = trim($_POST["previous_class"] ?? '');
            $previous_school = trim($_POST["previous_school"] ?? '');
            $roll_number = trim($_POST["roll_number"] ?? '');
            $village = trim($_POST["village"] ?? '');

            $date_of_birth_display = trim($_POST["date_of_birth"] ?? '');
            $date_of_birth_for_db = null;
            if (!empty($date_of_birth_display)) {
                $dob_datetime = DateTime::createFromFormat('Y-m-d', $date_of_birth_display);
                if ($dob_datetime && $dob_datetime->format('Y-m-d') === $date_of_birth_display) {
                    $date_of_birth_for_db = $dob_datetime->format('Y-m-d');
                } else {
                    $date_of_birth_err = "Invalid date format. Please use YYYY-MM-DD or date picker.";
                }
            }

            $previous_marks_percentage_input = trim($_POST['previous_marks_percentage'] ?? '');
            $previous_marks_percentage_for_db = null;
            $previous_marks_percentage_display = $previous_marks_percentage_input;
            if ($previous_marks_percentage_input !== '') {
                $filtered_marks = filter_var($previous_marks_percentage_input, FILTER_VALIDATE_FLOAT);
                if ($filtered_marks === false || $filtered_marks < 0 || $filtered_marks > 100) {
                    $previous_marks_percentage_for_db = null;
                } else {
                    $previous_marks_percentage_for_db = $filtered_marks;
                }
            } else {
                $previous_marks_percentage_display = '';
            }


            $current_marks_input = trim($_POST['current_marks'] ?? '');
            $current_marks_for_db = null;
            $current_marks_display = $current_marks_input;
            if ($current_marks_input !== '') {
                $filtered_marks = filter_var($current_marks_input, FILTER_VALIDATE_FLOAT);
                if ($filtered_marks === false || $filtered_marks < 0 || $filtered_marks > 100) {
                    $current_marks_for_db = null;
                } else {
                    $current_marks_for_db = $filtered_marks;
                }
            } else {
                $current_marks_display = '';
            }
            $takes_van_display = $_POST['takes_van'] ?? '';  // Get string value or empty
            $takes_van_for_db = ($takes_van_display === 'on');  // True if checked


            $address = trim($_POST['address'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');
            $state = trim($_POST['state'] ?? '');

            $new_photo_filename_for_db = null;
            $photo_filename_to_retain = $photo_filename;

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;

                if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                    $photo_err = "Only JPG, PNG, and GIF images are allowed.";
                } elseif ($_FILES['photo']['size'] > $max_size) {
                    $photo_err = "File size must be less than 2MB.";
                } else {
                    $upload_result = uploadToCloudinary($_FILES['photo'], 'student_photos');

                    if ($upload_result && isset($upload_result['secure_url'])) {
                        $new_photo_filename_for_db = $upload_result['secure_url'];
                    } else {
                        $photo_err = "Photo upload failed: " . ($upload_result['error'] ?? 'Unknown Cloudinary error.');
                        error_log("Cloudinary upload failed for student ID " . $student_id_to_edit . ": " . ($upload_result['error'] ?? 'Unknown error.'));
                    }
                }
            } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {
                $php_upload_errors = [
                    UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the maximum allowed size (php.ini).",
                    UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
                    UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder for upload.",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
                ];
                $photo_err = $php_upload_errors[$_FILES['photo']['error']] ?? "Unknown file upload error.";
                error_log("PHP file upload error for student ID " . $student_id_to_edit . ": " . $photo_err . " (Error Code: " . $_FILES['photo']['error'] . ")");
            }

            $photo_filename_db = empty($photo_err) ? ($new_photo_filename_for_db ?? $photo_filename_to_retain) : $photo_filename_to_retain;
            if ($photo_filename_db === '') {
                $photo_filename_db = null;
            }


            if (empty($full_name)) $full_name_err = "Full name is required.";
            if (empty($father_name)) $father_name_err = "Father's name is required.";
            if (empty($mother_name)) $mother_name_err = "Mother's name is required.";
            if (empty($phone_number)) {
                $phone_number_err = "Phone number is required.";
            } elseif (!preg_match("/^\d{7,15}$/", $phone_number)) {
                $phone_number_err = "Please enter a valid phone number (7-15 digits).";
            }
            if (empty($current_class)) $current_class_err = "Current class is required.";


            $has_errors = !empty($full_name_err) || !empty($father_name_err) || !empty($mother_name_err) || !empty($phone_number_err) ||
                !empty($current_class_err) || !empty($date_of_birth_err) ||
                !empty($roll_number_err) || !empty($village_err) || !empty($photo_err);


            if (!$has_errors) {
                $whatsapp_number_db = ($whatsapp_number === '') ? null : $whatsapp_number;
                $previous_class_db = ($previous_class === '') ? null : $previous_class;
                $previous_school_db = ($previous_school === '') ? null : $previous_school;
                $roll_number_db = ($roll_number === '') ? null : $roll_number;
                $village_db = ($village === '') ? null : $village;
                $address_db = ($address === '') ? null : $address;
                $pincode_db = ($pincode === '') ? null : $pincode;
                $state_db = ($state === '') ? null : $state;

                $sql_update = "UPDATE students SET full_name=?, father_name=?, mother_name=?, phone_number=?, whatsapp_number=?, current_class=?, previous_class=?, previous_school=?, previous_marks_percentage=?, current_marks=?, takes_van=?, address=?, pincode=?, state=?, roll_number=?, village=?, date_of_birth=?, photo_filename=? WHERE user_id=?";


                if ($link === false) {
                    $toast_message = "Database connection error. Could not save changes.";
                    $toast_type = 'error';
                    error_log("Edit Student DB connection failed (POST - Student Details): " . mysqli_connect_error());
                } elseif ($stmt_update = mysqli_prepare($link, $sql_update)) {

                    $bind_types = "ssssssssddisssssssi";

                    $bind_args = [];
                    $bind_args[] = $stmt_update;
                    $bind_args[] = $bind_types;
                    $bind_args[] = &$full_name;
                    $bind_args[] = &$father_name;
                    $bind_args[] = &$mother_name;
                    $bind_args[] = &$phone_number;
                    $bind_args[] = &$whatsapp_number_db;
                    $bind_args[] = &$current_class;
                    $bind_args[] = &$previous_class_db;
                    $bind_args[] = &$previous_school_db;
                    $bind_args[] = &$previous_marks_percentage_for_db;
                    $bind_args[] = &$current_marks_for_db;
                    $bind_args[] = &$takes_van_for_db;
                    $bind_args[] = &$address_db;
                    $bind_args[] = &$pincode_db;
                    $bind_args[] = &$state_db;
                    $bind_args[] = &$roll_number_db;
                    $bind_args[] = &$village_db;
                    $bind_args[] = &$date_of_birth_for_db;
                    $bind_args[] = &$photo_filename_db;
                    $bind_args[] = &$student_id_to_edit;

                    if (call_user_func_array('mysqli_stmt_bind_param', $bind_args)) {

                        if (mysqli_stmt_execute($stmt_update)) {
                            mysqli_stmt_close($stmt_update);

                            $toast_message = "Student details updated successfully.";
                            $toast_type = 'success';
                        } else {
                            $toast_message = "Error: Could not update student record.";
                            $toast_type = 'error';
                            error_log("Edit Student update query failed for ID " . $student_id_to_edit . ": " . mysqli_stmt_error($stmt_update));
                            mysqli_stmt_close($stmt_update);
                        }
                    } else {
                        $toast_message = "Error: Could not bind parameters for update statement.";
                        $toast_type = 'error';
                        error_log("Edit Student bind_param failed for ID " . $student_id_to_edit . ": " . mysqli_stmt_error($stmt_update));
                        mysqli_stmt_close($stmt_update);
                    }
                } else {
                    $toast_message = "Error: Could not prepare update statement.";
                    $toast_type = 'error';
                    error_log("Edit Student prepare update failed: " . mysqli_error($link));
                }
            } else {
                $toast_message = "Validation errors found. Please check the student details form.";
                $toast_type = 'error';
            }
        } elseif ($form_type === 'add_monthly_fee') {

            $new_fee_month_display = trim($_POST['new_fee_month'] ?? '');
            $new_fee_year_display = trim($_POST['new_fee_year'] ?? '');
            $new_base_monthly_fee_display = trim($_POST['new_base_monthly_fee'] ?? '');
            $new_monthly_van_fee_display = trim($_POST['new_monthly_van_fee'] ?? '');
            $new_monthly_exam_fee_display = trim($_POST['new_monthly_exam_fee'] ?? '');
            $new_monthly_electricity_fee_display = trim($_POST['new_monthly_electricity_fee'] ?? '');

            $new_fee_month_for_db = null;
            $new_fee_year_for_db = null;
            $new_base_monthly_fee_for_db = null;
            $new_monthly_van_fee_for_db = null;
            $new_monthly_exam_fee_for_db = null;
            $new_monthly_electricity_fee_for_db = null;
            $new_amount_due_calculated = 0.0;

            if (empty($new_fee_month_display)) {
                $new_fee_month_err = "Please select a month.";
            } else {
                $month_int = filter_var($new_fee_month_display, FILTER_VALIDATE_INT);
                if ($month_int === false || $month_int < 1 || $month_int > 12) {
                    $new_fee_month_err = "Invalid month selected.";
                } else {
                    $new_fee_month_for_db = $month_int;
                }
            }

            if (empty($new_fee_year_display)) {
                $new_fee_year_err = "Please enter a year.";
            } else {
                $year_int = filter_var($new_fee_year_display, FILTER_VALIDATE_INT);
                if ($year_int === false || $year_int < 2000 || $year_int > 2100) {
                    $new_fee_year_err = "Invalid year (e.g., 2000-2100).";
                } else {
                    $new_fee_year_for_db = $year_int;
                }
            }

            if (empty($new_base_monthly_fee_display)) {
                $new_base_monthly_fee_err = "Base fee is required.";
            } else {
                $base_fee_float = filter_var($new_base_monthly_fee_display, FILTER_VALIDATE_FLOAT);
                if ($base_fee_float === false || $base_fee_float < 0) {
                    $new_base_monthly_fee_err = "Please enter a valid non-negative number for base fee.";
                } else {
                    $new_base_monthly_fee_for_db = $base_fee_float;
                }
            }

            if (!empty($new_monthly_van_fee_display)) {
                $van_fee_float = filter_var($new_monthly_van_fee_display, FILTER_VALIDATE_FLOAT);
                if ($van_fee_float === false || $van_fee_float < 0) {
                    $new_monthly_van_fee_err = "Invalid Van fee.";
                } else {
                    $new_monthly_van_fee_for_db = $van_fee_float;
                }
            } else {
                $new_monthly_van_fee_for_db = 0.0;
            }

            if (!empty($new_monthly_exam_fee_display)) {
                $exam_fee_float = filter_var($new_monthly_exam_fee_display, FILTER_VALIDATE_FLOAT);
                if ($exam_fee_float === false || $exam_fee_float < 0) {
                    $new_monthly_exam_fee_err = "Invalid Exam fee.";
                } else {
                    $new_monthly_exam_fee_for_db = $exam_fee_float;
                }
            } else {
                $new_monthly_exam_fee_for_db = 0.0;
            }

            if (!empty($new_monthly_electricity_fee_display)) {
                $elec_fee_float = filter_var($new_monthly_electricity_fee_display, FILTER_VALIDATE_FLOAT);
                if ($elec_fee_float === false || $elec_fee_float < 0) {
                    $new_monthly_electricity_fee_err = "Invalid Electricity fee.";
                } else {
                    $new_monthly_electricity_fee_for_db = $elec_fee_float;
                }
            } else {
                $new_monthly_electricity_fee_for_db = 0.0;
            }

            if (empty($new_base_monthly_fee_err)) {
                $new_amount_due_calculated = ($new_base_monthly_fee_for_db ?? 0.0) +
                    ($new_monthly_van_fee_for_db ?? 0.0) +
                    ($new_monthly_exam_fee_for_db ?? 0.0) +
                    ($new_monthly_electricity_fee_for_db ?? 0.0);
            }


            $has_fee_errors = !empty($new_fee_month_err) || !empty($new_fee_year_err) ||
                !empty($new_base_monthly_fee_err) || !empty($new_monthly_van_fee_err) ||
                !empty($new_monthly_exam_fee_err) || !empty($new_monthly_electricity_fee_err);


            if (!$has_fee_errors && $student_id_to_edit > 0) {

                $sql_check_duplicate = "SELECT id FROM student_monthly_fees WHERE student_id = ? AND fee_year = ? AND fee_month = ?";
                if ($link === false) {
                    $new_monthly_fee_general_err = "Database connection error during duplicate check.";
                    $toast_type = 'error';
                } elseif ($stmt_check = mysqli_prepare($link, $sql_check_duplicate)) {
                    mysqli_stmt_bind_param($stmt_check, "iii", $student_id_to_edit, $new_fee_year_for_db, $new_fee_month_for_db);
                    if (mysqli_stmt_execute($stmt_check)) {
                        mysqli_stmt_store_result($stmt_check);
                        if (mysqli_stmt_num_rows($stmt_check) > 0) {
                            $new_monthly_fee_general_err = "A fee record for this month and year already exists for this student.";
                            $toast_type = 'warning';
                        }
                    } else {
                        $new_monthly_fee_general_err = "Database error during duplicate check.";
                        $toast_type = 'error';
                        error_log("Edit Student monthly fee duplicate check failed: " . mysqli_stmt_error($stmt_check));
                    }
                    mysqli_stmt_close($stmt_check);
                } else {
                    $new_monthly_fee_general_err = "Database error preparing duplicate check.";
                    $toast_type = 'error';
                    error_log("Edit Student prepare duplicate check failed: " . mysqli_error($link));
                }

                if (empty($new_monthly_fee_general_err)) {
                    $sql_insert_fee = "INSERT INTO student_monthly_fees (student_id, fee_year, fee_month, base_monthly_fee, monthly_van_fee, monthly_exam_fee, monthly_electricity_fee, amount_due, amount_paid, is_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";

                    if ($link === false) {
                        $new_monthly_fee_general_err = "Database connection error. Could not add fee record.";
                        $toast_type = 'error';
                    } elseif ($stmt_insert = mysqli_prepare($link, $sql_insert_fee)) {
                        $bind_types_fee = "iiiddddd";
                        $bind_args_fee = [];
                        $bind_args_fee[] = $stmt_insert;
                        $bind_args_fee[] = $bind_types_fee;
                        $bind_args_fee[] = &$student_id_to_edit;
                        $bind_args_fee[] = &$new_fee_year_for_db;
                        $bind_args_fee[] = &$new_fee_month_for_db;
                        $bind_args_fee[] = &$new_base_monthly_fee_for_db;
                        $bind_args_fee[] = &$new_monthly_van_fee_for_db;
                        $bind_args_fee[] = &$new_monthly_exam_fee_for_db;
                        $bind_args_fee[] = &$new_monthly_electricity_fee_for_db;
                        $bind_args_fee[] = &$new_amount_due_calculated;

                        if (call_user_func_array('mysqli_stmt_bind_param', $bind_args_fee)) {
                            if (mysqli_stmt_execute($stmt_insert)) {
                                mysqli_stmt_close($stmt_insert);
                                $toast_message = "Monthly fee record added successfully.";
                                $toast_type = 'success';

                                $new_fee_month_display = '';
                                $new_fee_year_display = date('Y');
                                $new_base_monthly_fee_display = '';
                                $new_monthly_van_fee_display = '';
                                $new_monthly_exam_fee_display = '';
                                $new_monthly_electricity_fee_display = '';

                                $new_fee_month_err = '';
                                $new_fee_year_err = '';
                                $new_base_monthly_fee_err = '';
                                $new_monthly_van_fee_err = '';
                                $new_monthly_exam_fee_err = '';
                                $new_monthly_electricity_fee_err = '';
                                $new_monthly_fee_general_err = '';
                            } else {
                                $new_monthly_fee_general_err = "Error: Could not add monthly fee record.";
                                $toast_type = 'error';
                                error_log("Edit Student monthly fee insert failed for student ID " . $student_id_to_edit . ": " . mysqli_stmt_error($stmt_insert));
                                mysqli_stmt_close($stmt_insert);
                            }
                        } else {
                            $new_monthly_fee_general_err = "Error: Could not bind parameters for fee insert statement.";
                            $toast_type = 'error';
                            error_log("Edit Student monthly fee bind_param failed for student ID " . $student_id_to_edit . ": " . mysqli_stmt_error($stmt_insert));
                            mysqli_stmt_close($stmt_insert);
                        }
                    } else {
                        $new_monthly_fee_general_err = "Error: Could not prepare fee insert statement.";
                        $toast_type = 'error';
                        error_log("Edit Student prepare fee insert failed: " . mysqli_error($link));
                    }
                }
            } else {
                if ($student_id_to_edit <= 0) {
                    $new_monthly_fee_general_err = "Invalid student ID provided for fee submission.";
                    $toast_type = 'error';
                } else {
                    $new_monthly_fee_general_err = "Validation errors found. Please check the monthly fee form.";
                    $toast_type = 'error';
                }
            }
            if (!empty($new_monthly_fee_general_err) && empty($toast_message)) {
                $toast_message = $new_monthly_fee_general_err;
                if (empty($toast_type)) $toast_type = 'error';
            }
        }


        if ($student_id_to_edit > 0) {
            $sql_fetch = "SELECT user_id, virtual_id, full_name, father_name, mother_name, phone_number, whatsapp_number, current_class, previous_class, previous_school, previous_marks_percentage, current_marks, takes_van, address, pincode, state, roll_number, village, date_of_birth, photo_filename FROM students WHERE user_id = ?";

            if ($link === false) {
            } elseif ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
                mysqli_stmt_bind_param($stmt_fetch, "i", $student_id_to_edit);

                if (mysqli_stmt_execute($stmt_fetch)) {
                    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                    if (mysqli_num_rows($result_fetch) == 1) {
                        $student = mysqli_fetch_assoc($result_fetch);

                        $user_id = $student["user_id"];
                        $virtual_id = $student["virtual_id"];
                        $full_name = $student["full_name"];
                        $father_name = $student["father_name"];
                        $mother_name = $student["mother_name"];
                        $phone_number = $student["phone_number"];
                        $whatsapp_number = $student["whatsapp_number"];
                        $current_class = $student["current_class"];
                        $previous_class = $student["previous_class"];
                        $previous_school = $student["previous_school"];
                        $roll_number = $student["roll_number"];
                        $village = $student["village"];

                        $date_of_birth_display = (!empty($student["date_of_birth"]) && $student["date_of_birth"] !== '0000-00-00') ? $student["date_of_birth"] : '';

                        $previous_marks_percentage_display = empty($previous_marks_percentage_err) ? ($student["previous_marks_percentage"] ?? '') : $previous_marks_percentage_display;
                        $current_marks_display = empty($current_marks_err) ? ($student["current_marks"] ?? '') : $current_marks_display;

                        $takes_van_display = (($student["takes_van"] ?? 0) == 1) ? 'on' : '';

                        $address = empty($address_err) ? ($student["address"] ?? '') : $address;
                        $pincode = empty($pincode_err) ? ($student["pincode"] ?? '') : $pincode;
                        $state = empty($state_err) ? ($student["state"] ?? '') : $state;

                        $photo_filename = empty($photo_err) ? ($student["photo_filename"] ?? '') : $photo_filename;
                    } else {
                        $toast_message .= "Could not refetch student data after form submission.";
                        $toast_type = 'error';
                    }
                    mysqli_free_result($result_fetch);
                } else {
                    error_log("Edit Student refetch query failed after form submit: " . mysqli_stmt_error($stmt_fetch));
                    if (empty($toast_message)) {
                        $toast_message = "Error refetching student data.";
                        $toast_type = 'error';
                    }
                }
                mysqli_stmt_close($stmt_fetch);
            } elseif ($link !== false) {
                error_log("Edit Student prepare refetch failed after form submit: " . mysqli_error($link));
                if (empty($toast_message)) {
                    $toast_message = "Error preparing to refetch student data.";
                    $toast_type = 'error';
                }
            }


            $sql_monthly_fees = "SELECT id, student_id, fee_year, fee_month, amount_due, amount_paid, is_paid, payment_date, notes, base_monthly_fee, monthly_van_fee, monthly_exam_fee, monthly_electricity_fee FROM student_monthly_fees WHERE student_id = ? ORDER BY fee_year ASC, fee_month ASC";

            if ($link === false) {
            } elseif ($stmt_monthly = mysqli_prepare($link, $sql_monthly_fees)) {
                mysqli_stmt_bind_param($stmt_monthly, "i", $student_id_to_edit);

                if (mysqli_stmt_execute($stmt_monthly)) {
                    $result_monthly = mysqli_stmt_get_result($stmt_monthly);
                    while ($row = mysqli_fetch_assoc($result_monthly)) {
                        $monthly_fee_records[] = $row;
                    }
                    mysqli_free_result($result_monthly);
                } else {
                    error_log("Edit Student monthly fees query failed during POST (refetch) for student ID " . $student_id_to_edit . ": " . mysqli_stmt_error($stmt_monthly));
                    if (empty($toast_message)) {
                        $toast_message = "Error fetching monthly fee records.";
                        $toast_type = 'error';
                    }
                }
                mysqli_stmt_close($stmt_monthly);
            } elseif ($link !== false) {
                error_log("Edit Student prepare monthly fees failed during POST (refetch): " . mysqli_error($link));
                if (empty($toast_message)) {
                    $toast_message = "Error preparing to fetch monthly fee records.";
                    $toast_type = 'error';
                }
            }
        }
    } // THIS IS THE CULPRIT: The closing brace for `if ($student_id_to_edit !== null)`
    // The "comment removed" step effectively put the `}` and `else {` on the same line,
    // which PHP interprets as an invalid `else` if the previous `if` chain is already closed.

} else { // THIS LINE (previously line 530/532 in original code, where the error was conceptualized)

    if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
        $student_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($student_id_to_edit === false || $student_id_to_edit <= 0) {
            $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid student ID.</p>";
            header("location: admin_dashboard.php");
            exit();
        } else {
            $sql_fetch = "SELECT user_id, virtual_id, full_name, father_name, mother_name, phone_number, whatsapp_number, current_class, previous_class, previous_school, previous_marks_percentage, current_marks, takes_van, address, pincode, state, roll_number, village, date_of_birth, photo_filename FROM students WHERE user_id = ?";

            if ($link === false) {
                $toast_message = "Database connection error. Could not load student data.";
                $toast_type = 'error';
                error_log("Edit Student fetch DB connection failed (GET): " . mysqli_connect_error());
            } elseif ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
                mysqli_stmt_bind_param($stmt_fetch, "i", $student_id_to_edit);

                if (mysqli_stmt_execute($stmt_fetch)) {
                    $result_fetch = mysqli_stmt_get_result($stmt_fetch);

                    if (mysqli_num_rows($result_fetch) == 1) {
                        $student = mysqli_fetch_assoc($result_fetch);

                        $user_id = $student["user_id"];
                        $virtual_id = $student["virtual_id"];
                        $full_name = $student["full_name"];
                        $father_name = $student["father_name"];
                        $mother_name = $student["mother_name"];
                        $phone_number = $student["phone_number"];
                        $whatsapp_number = $student["whatsapp_number"];
                        $current_class = $student["current_class"];
                        $previous_class = $student["previous_class"];
                        $previous_school = $student["previous_school"];
                        $roll_number = $student["roll_number"];
                        $village = $student["village"];

                        $date_of_birth_display = (!empty($student["date_of_birth"]) && $student["date_of_birth"] !== '0000-00-00') ? $student["date_of_birth"] : '';

                        $previous_marks_percentage_display = $student["previous_marks_percentage"];
                        $current_marks_display = $student["current_marks"];

                        $takes_van_display = (($student["takes_van"] ?? 0) == 1) ? 'on' : '';

                        $address = $student["address"];
                        $pincode = $student["pincode"];
                        $state = $student["state"];

                        $photo_filename = $student["photo_filename"] ?? '';

                        $sql_monthly_fees = "SELECT id, student_id, fee_year, fee_month, amount_due, amount_paid, is_paid, payment_date, notes, base_monthly_fee, monthly_van_fee, monthly_exam_fee, monthly_electricity_fee FROM student_monthly_fees WHERE student_id = ? ORDER BY fee_year ASC, fee_month ASC";

                        if ($link === false) {
                        } elseif ($stmt_monthly = mysqli_prepare($link, $sql_monthly_fees)) {
                            mysqli_stmt_bind_param($stmt_monthly, "i", $user_id);

                            if (mysqli_stmt_execute($stmt_monthly)) {
                                $result_monthly = mysqli_stmt_get_result($stmt_monthly);
                                while ($row = mysqli_fetch_assoc($result_monthly)) {
                                    $monthly_fee_records[] = $row;
                                }
                                mysqli_free_result($result_monthly);
                            } else {
                                error_log("Edit Student monthly fees query failed during GET for student ID " . $user_id . ": " . mysqli_stmt_error($stmt_monthly));
                                if (empty($toast_message)) {
                                    $toast_message = "Error fetching monthly fee records.";
                                    $toast_type = 'error';
                                }
                            }
                            mysqli_stmt_close($stmt_monthly);
                        } elseif ($link !== false) {
                            error_log("Edit Student prepare monthly fees failed during GET: " . mysqli_error($link));
                            if (empty($toast_message)) {
                                $toast_message = "Error preparing to fetch monthly fee records.";
                                $toast_type = 'error';
                            }
                        }
                    } else {
                        $_SESSION['operation_message'] = "<p class='text-red-600'>Student record not found.</p>";
                        header("location: admin_dashboard.php");
                        exit();
                    }
                    mysqli_free_result($result_fetch);
                } else {
                    $toast_message = "Oops! Something went wrong. Could not fetch record. Please try again later.";
                    $toast_type = 'error';
                    error_log("Edit Student fetch query failed: " . mysqli_stmt_error($stmt_fetch));
                }
                mysqli_stmt_close($stmt_fetch);
            } else {
                $toast_message = "Oops! Something went wrong. Could not prepare fetch statement. Please try again later.";
                $toast_type = 'error';
                error_log("Edit Student prepare fetch statement failed: " . mysqli_error($link));
            }
        }
    } else {
        $_SESSION['operation_message'] = "<p class='text-red-600'>No student ID provided for editing.</p>";
        header("location: admin_dashboard.php");
        exit();
    }
}


if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Record</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
        }

        .form-error {
            color: #dc3545;
            font-size: 0.75em;
            margin-top: 0.25em;
            display: block;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-invalid:focus {
            border-color: #dc2626;
            ring-color: #f87171;
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        input[type="number"]::placeholder {
            color: #9ca3af;
        }

        input[type="date"]::placeholder {
            color: #9ca3af;
        }

        .monthly-fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .monthly-fee-table th,
        .monthly-fee-table td {
            padding: 0.75rem 0.5rem;
            border: 1px solid #e5e7eb;
            text-align: left;
        }

        .monthly-fee-table th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #4b5563;
        }

        .monthly-fee-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .monthly-fee-table td {
            color: #1f2937;
        }

        .status-paid {
            color: #065f46;
            font-weight: 600;
        }

        .status-due {
            color: #b91c1c;
            font-weight: 600;
        }

        .monthly-fee-table .action-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .monthly-fee-table .action-link:hover {
            text-decoration: underline;
            color: #4338ca;
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none;
        }

        .toast {
            background-color: #fff;
            color: #333;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            pointer-events: auto;
            min-width: 200px;
            max-width: 300px;
            display: flex;
            align-items: center;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-success {
            border-left: 5px solid #10b981;
            color: #065f46;
        }

        .toast-error {
            border-left: 5px solid #ef4444;
            color: #991b1b;
        }

        .toast-warning {
            border-left: 5px solid #f59e0b;
            color: #9a3412;
        }

        .toast-info {
            border-left: 5px solid #3b82f6;
            color: #1e40af;
        }

        .toast .close-button {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0 0.25rem;
            line-height: 1;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const takesVanCheckbox = document.getElementById('takes_van');
            if (takesVanCheckbox) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'takes_van';
                hiddenInput.value = '';

                takesVanCheckbox.parentNode.insertBefore(hiddenInput, takesVanCheckbox);

                takesVanCheckbox.addEventListener('change', function() {
                    hiddenInput.disabled = this.checked;
                });

                hiddenInput.disabled = takesVanCheckbox.checked;
            }

            const toggleButton = document.getElementById('toggleAddFeeForm');
            const addFeeFormContainer = document.getElementById('addMonthlyFeeFormContainer');

            if (toggleButton && addFeeFormContainer) {
                const hasFeeErrors = addFeeFormContainer.querySelectorAll('.form-error:not(:empty)').length > 0;
                if (hasFeeErrors) {
                    addFeeFormContainer.classList.remove('hidden');
                    toggleButton.textContent = 'Hide Add Monthly Fee Form';
                    addFeeFormContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    addFeeFormContainer.classList.add('hidden');
                    toggleButton.textContent = 'Add New Monthly Fee Record';
                }

                toggleButton.addEventListener('click', function() {
                    addFeeFormContainer.classList.toggle('hidden');

                    if (addFeeFormContainer.classList.contains('hidden')) {
                        toggleButton.textContent = 'Add New Monthly Fee Record';
                        addFeeFormContainer.querySelector('form').reset();
                        addFeeFormContainer.querySelectorAll('.form-error').forEach(span => span.textContent = '');
                        addFeeFormContainer.querySelectorAll('.form-control.is-invalid').forEach(input => input.classList.remove('is-invalid'));

                    } else {
                        toggleButton.textContent = 'Hide Add Monthly Fee Form';
                        addFeeFormContainer.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });

                const cancelAddFeeButton = addFeeFormContainer.querySelector('.cancel-add-fee');
                if (cancelAddFeeButton) {
                    cancelAddFeeButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        addFeeFormContainer.classList.add('hidden');
                        toggleButton.textContent = 'Add New Monthly Fee Record';
                        addFeeFormContainer.querySelector('form').reset();
                        addFeeFormContainer.querySelectorAll('.form-error').forEach(span => span.textContent = '');
                        addFeeFormContainer.querySelectorAll('.form-control.is-invalid').forEach(input => input.classList.remove('is-invalid'));
                    });
                }
            }

            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                console.error('Toast container #toastContainer not found.');
            }

            function showToast(message, type = 'info', duration = 5000) {
                if (!message || !toastContainer) return;

                const toast = document.createElement('div');
                toast.classList.add('toast', `toast-${type}`);
                toast.textContent = message;

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
                        toast.addEventListener('transitionend', () => toast.remove(), {
                            once: true
                        });
                    }, duration);
                }
            }

            const phpMessage = <?php echo json_encode($toast_message); ?>;
            const messageType = <?php echo json_encode($toast_type); ?>;

            if (phpMessage) {
                showToast(phpMessage, messageType);
            }
        });
    </script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center py-8 px-4">

    <div id="toastContainer" class="toast-container">
    </div>

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl">
        <h2 class="text-xl font-semibold mb-6 text-center">Edit Student Record</h2>

        <div class="mb-6 text-left">
            <a href="admin_dashboard.php" class="text-indigo-600 hover:text-indigo-800 hover:underline text-sm font-medium">← Back to Dashboard</a>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6" enctype="multipart/form-data">

            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($student_id_to_edit ?? ''); ?>">
            <input type="hidden" name="form_type" value="student_details">

            <div>
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Personal Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="display_user_id" class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                    <input type="text" id="display_user_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm" value="<?php echo htmlspecialchars($student_id_to_edit ?? ''); ?>" readonly>
                </div>
                <div>
                    <label for="virtual_id" class="block text-sm font-medium text-gray-700 mb-1">Virtual ID</label>
                    <input type="text" name="virtual_id" id="virtual_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm" value="<?php echo htmlspecialchars($virtual_id ?? ''); ?>" readonly>
                    <span class="text-gray-500 text-xs italic">Virtual ID cannot be changed here.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($full_name_err ?? ''); ?></span>
                </div>

                <div>
                    <label for="father_name" class="block text-sm font-medium text-gray-700 mb-1">Father's Name <span class="text-red-500">*</span></label>
                    <input type="text" name="father_name" id="father_name" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($father_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($father_name ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($father_name_err ?? ''); ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mother_name" class="block text-sm font-medium text-gray-700 mb-1">Mother's Name <span class="text-red-500">*</span></label>
                    <input type="text" name="mother_name" id="mother_name" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($mother_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($mother_name ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($mother_name_err ?? ''); ?></span>
                </div>
                <div>
                    <label for="village" class="block text-sm font-medium text-gray-700 mb-1">Village (Optional)</label>
                    <input type="text" name="village" id="village" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($village ?? ''); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth (Optional)</label>
                    <?php
                    $date_of_birth_input_value = null;
                    if (!empty($date_of_birth_display) && $date_of_birth_display !== '0000-00-00') {
                        $date_obj = DateTime::createFromFormat('Y-m-d', $date_of_birth_display);
                        if ($date_obj) {
                            $date_of_birth_input_value = $date_obj->format('Y-m-d');
                        } else {
                            $date_of_birth_input_value = '';
                        }
                    }
                    ?>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date_of_birth_input_value ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($date_of_birth_err ?? ''); ?></span>
                </div>
            </div>

            <div class="mt-4">
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Student Photo</h3>
                <div class="flex items-center space-x-4 mb-4">
                    <?php if (!empty($photo_filename)): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($photo_filename); ?>" alt="Student Photo" class="h-24 w-24 object-cover rounded-full border border-gray-300">
                            <p class="text-sm text-gray-500 mt-1">Current Photo</p>
                        </div>
                    <?php else: ?>
                        <div class="h-24 w-24 flex items-center justify-center bg-gray-200 text-gray-500 rounded-full border border-gray-300 text-xs text-center">
                            No Photo<br>Uploaded
                        </div>
                    <?php endif; ?>
                    <div>
                        <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Upload New Photo (Optional)</label>
                        <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif" class="mt-1 block w-full text-sm text-gray-500
                             file:mr-4 file:py-2 file:px-4
                             file:rounded-md file:border-0
                             file:text-sm file:font-semibold
                             file:bg-indigo-50 file:text-indigo-700
                             hover:file:bg-indigo-100">
                        <span class="form-error"><?php echo htmlspecialchars($photo_err ?? ''); ?></span>
                        <p class="text-xs text-gray-500 mt-1">Max 2MB. JPG, PNG, GIF.</p>
                    </div>
                </div>
            </div>


            <div>
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4 mt-4">Contact Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone_number" id="phone_number" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone_number ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($phone_number_err ?? ''); ?></span>
                </div>

                <div>
                    <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number (Optional)</label>
                    <input type="text" name="whatsapp_number" id="whatsapp_number" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($whatsapp_number ?? ''); ?>">
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address (Optional)</label>
                <textarea name="address" id="address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="pincode" class="block text-sm font-medium text-gray-700 mb-1">Pincode (Optional)</label>
                    <input type="text" name="pincode" id="pincode" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($pincode ?? ''); ?>">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State (Optional)</label>
                    <input type="text" name="state" id="state" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($state ?? ''); ?>">
                </div>
            </div>


            <div>
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4 mt-4">Academic Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="current_class" class="block text-sm font-medium text-gray-700 mb-1">Current Class <span class="text-red-500">*</span></label>
                    <input type="text" name="current_class" id="current_class" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($current_class_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($current_class ?? ''); ?>">
                    <span class="form-error"><?php echo htmlspecialchars($current_class_err ?? ''); ?></span>
                </div>
                <div>
                    <label for="roll_number" class="block text-sm font-medium text-gray-700 mb-1">Roll Number (Optional)</label>
                    <input type="text" name="roll_number" id="roll_number" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($roll_number ?? ''); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="previous_class" class="block text-sm font-medium text-gray-700 mb-1">Previous Class (Optional)</label>
                    <input type="text" name="previous_class" id="previous_class" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($previous_class ?? ''); ?>">
                </div>

                <div>
                    <label for="previous_school" class="block text-sm font-medium text-gray-700 mb-1">Previous School (Optional)</label>
                    <input type="text" name="previous_school" id="previous_school" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($previous_school ?? ''); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="previous_marks_percentage" class="block text-sm font-medium text-gray-700 mb-1">Previous Marks (%) (Optional)</label>
                    <input type="number" name="previous_marks_percentage" id="previous_marks_percentage" step="0.01" min="0" max="100" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($previous_marks_percentage_display ?? ''); ?>">
                </div>

                <div>
                    <label for="current_marks" class="block text-sm font-medium text-gray-700 mb-1">Current Marks (%) (Optional)</label>
                    <input type="number" name="current_marks" id="current_marks" step="0.01" min="0" max="100" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($current_marks_display ?? ''); ?>">
                </div>
            </div>


            <div>
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4 mt-4">Fee Structure (Defaults - Editing these does NOT change past monthly dues)</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="form-check flex items-center pt-2">
                        <input type="checkbox" name="takes_van" id="takes_van" value="on" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out border-gray-300 rounded focus:ring-indigo-500"
                            <?php echo ($takes_van_display === 'on') ? 'checked' : ''; ?>>
                        <label for="takes_van" class="ml-2 block text-sm font-medium text-gray-700">Student takes Van Service</label>
                    </div>
                </div>
                <div></div>
            </div>


            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Update Student Details</button>
                <?php if ($student_id_to_edit): ?>
                    <a href="view_student.php?id=<?php echo htmlspecialchars($student_id_to_edit); ?>" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</a>
                <?php else: ?>
                    <a href="admin_dashboard.php" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Back to Dashboard</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Monthly Fee Status & Payments</h3>

            <?php if ($student_id_to_edit): ?>
                <button id="toggleAddFeeForm" type="button" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm font-medium">
                    Add New Monthly Fee Record
                </button>
            <?php else: ?>
                <p class="text-yellow-600 text-sm italic">Save student details first to add monthly fees.</p>
            <?php endif; ?>


            <div id="addMonthlyFeeFormContainer" class="hidden bg-gray-50 p-6 rounded-md mt-4 shadow-inner">
                <h4 class="text-md font-semibold text-gray-700 mb-4">Add New Monthly Fee Record</h4>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">
                    <input type="hidden" name="student_id_for_fee" value="<?php echo htmlspecialchars($student_id_to_edit ?? ''); ?>">
                    <input type="hidden" name="form_type" value="add_monthly_fee">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="new_fee_month" class="block text-sm font-medium text-gray-700 mb-1">Month <span class="text-red-500">*</span></label>
                            <select name="new_fee_month" id="new_fee_month" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_fee_month_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Month</option>
                                <?php
                                $month_names_select = [
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December'
                                ];
                                for ($m = 1; $m <= 12; $m++) {
                                    $selected = ((int)($new_fee_month_display ?? 0) === $m) ? 'selected' : '';
                                    echo "<option value='" . $m . "'" . $selected . ">" . htmlspecialchars($month_names_select[$m]) . "</option>";
                                }
                                ?>
                            </select>
                            <span class="form-error"><?php echo htmlspecialchars($new_fee_month_err ?? ''); ?></span>
                        </div>
                        <div>
                            <label for="new_fee_year" class="block text-sm font-medium text-gray-700 mb-1">Year <span class="text-red-500">*</span></label>
                            <input type="number" name="new_fee_year" id="new_fee_year" step="1" min="2000" max="2100" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_fee_year_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_fee_year_display ?? date('Y')); ?>" placeholder="e.g., 2024">
                            <span class="form-error"><?php echo htmlspecialchars($new_fee_year_err ?? ''); ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_base_monthly_fee" class="block text-sm font-medium text-gray-700 mb-1">Base Monthly Fee <span class="text-red-500">*</span></label>
                            <input type="number" name="new_base_monthly_fee" id="new_base_monthly_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_base_monthly_fee_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_base_monthly_fee_display ?? ''); ?>" placeholder="e.g., 1200.00">
                            <span class="form-error"><?php echo htmlspecialchars($new_base_monthly_fee_err ?? ''); ?></span>
                        </div>
                        <div>
                            <label for="new_monthly_van_fee" class="block text-sm font-medium text-gray-700 mb-1">Van Fee (Optional)</label>
                            <input type="number" name="new_monthly_van_fee" id="new_monthly_van_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_monthly_van_fee_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_monthly_van_fee_display ?? ''); ?>" placeholder="e.g., 300.00">
                            <span class="form-error"><?php echo htmlspecialchars($new_monthly_van_fee_err ?? ''); ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_monthly_exam_fee" class="block text-sm font-medium text-gray-700 mb-1">Exam Fee (Optional)</label>
                            <input type="number" name="new_monthly_exam_fee" id="new_monthly_exam_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_monthly_exam_fee_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_monthly_exam_fee_display ?? ''); ?>" placeholder="e.g., 100.00">
                            <span class="form-error"><?php echo htmlspecialchars($new_monthly_exam_fee_err ?? ''); ?></span>
                        </div>
                        <div>
                            <label for="new_monthly_electricity_fee" class="block text-sm font-medium text-gray-700 mb-1">Electricity Fee (Optional)</label>
                            <input type="number" name="new_monthly_electricity_fee" id="new_monthly_electricity_fee" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($new_monthly_electricity_fee_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_monthly_electricity_fee_display ?? ''); ?>" placeholder="e.g., 50.00">
                            <span class="form-error"><?php echo htmlspecialchars($new_monthly_electricity_fee_err ?? ''); ?></span>
                        </div>
                    </div>


                    <div class="flex items-center justify-end gap-4 mt-4">
                        <?php if ($student_id_to_edit): ?>
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 text-sm font-medium">Add Fee Record</button>
                            <button type="button" class="cancel-add-fee px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-sm font-medium">Cancel</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>


            <?php if (!empty($monthly_fee_records)): ?>
                <div class="overflow-x-auto mt-6">
                    <table class="monthly-fee-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Base Fee</th>
                                <th>Van Fee</th>
                                <th>Exam Fee</th>
                                <th>Electricity Fee</th>
                                <th>Total Due</th>
                                <th>Amount Paid</th>
                                <th>Amount Due</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $month_names_display = [
                                1 => 'Jan',
                                2 => 'Feb',
                                3 => 'Mar',
                                4 => 'Apr',
                                5 => 'May',
                                6 => 'June',
                                7 => 'Jul',
                                8 => 'Aug',
                                9 => 'Sep',
                                10 => 'Oct',
                                11 => 'Nov',
                                12 => 'Dec'
                            ];
                            foreach ($monthly_fee_records as $record):
                                $amount_due_display = ($record['amount_due'] ?? 0.00) - ($record['amount_paid'] ?? 0.00);
                                $amount_due_formatted = number_format($record['amount_due'] ?? 0.00, 2);
                                $amount_paid_formatted = number_format($record['amount_paid'] ?? 0.00, 2);
                                $amount_remaining_formatted = number_format($amount_due_display, 2);

                                $base_fee_formatted = number_format($record['base_monthly_fee'] ?? 0.00, 2);
                                $van_fee_formatted = number_format($record['monthly_van_fee'] ?? 0.00, 2);
                                $exam_fee_formatted = number_format($record['monthly_exam_fee'] ?? 0.00, 2);
                                $electricity_fee_formatted = number_format($record['monthly_electricity_fee'] ?? 0.00, 2);

                                $status_class = ($record['is_paid'] == 1) ? 'status-paid' : 'status-due';
                                $status_text = ($record['is_paid'] == 1) ? 'Paid' : 'Due';

                                $payment_date_display = (!empty($record['payment_date']) && $record['payment_date'] !== '0000-00-00') ? date("Y-m-d", strtotime($record['payment_date'])) : 'N/A';

                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($month_names_display[$record['fee_month']] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['fee_year'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($base_fee_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($van_fee_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($exam_fee_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($electricity_fee_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($amount_due_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($amount_paid_formatted); ?></td>
                                    <td><?php echo htmlspecialchars($amount_remaining_formatted); ?></td>
                                    <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                    <td><?php echo htmlspecialchars($payment_date_display); ?></td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($record['is_paid'] == 0): ?>
                                            <a href="record_payment.php?id=<?php echo htmlspecialchars($record['id']); ?>" class="action-link mr-2">Record Payment</a>
                                        <?php endif; ?>
                                        <a href="edit_monthly_fee.php?id=<?php echo htmlspecialchars($record['id']); ?>" class="action-link">Edit Record</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 italic mt-4">No monthly fee records found for this student.</p>
            <?php endif; ?>

        </div>


    </div>

</body>

</html>