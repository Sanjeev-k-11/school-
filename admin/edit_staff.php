<?php
session_start();

require_once "../config.php";
require_once "./cloudinary_upload_handler.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can edit staff records.</p>";
    header("location: ../login.php");
    exit;
}

$staff_id = null;
$staff_name = $mobile_number = $unique_id = $email = $role = $salary = $subject_taught = $classes_taught = "";
$photo_filename = '';

$staff_name_err = $mobile_number_err = $unique_id_err = $email_err = $role_err = $salary_err = "";
$photo_err = '';

$edit_message = "";
$staff_full_name = "";

$allowed_roles = ['teacher', 'principal', ];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $staff_id = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
    $staff_full_name = trim($_POST['staff_full_name'] ?? '');

    $photo_filename_to_retain = $_POST['current_photo_filename'] ?? '';


    if ($staff_id === false || $staff_id <= 0) {
        $edit_message = "<p class='text-red-600'>Invalid staff ID provided for update.</p>";
        $staff_name = trim($_POST["staff_name"] ?? '');
        $mobile_number = trim($_POST["mobile_number"] ?? '');
        $unique_id = trim($_POST["unique_id"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $role = trim($_POST["role"] ?? '');
        $salary = $_POST["salary"] ?? '';
        $subject_taught = trim($_POST["subject_taught"] ?? '');
        $classes_taught = trim($_POST["classes_taught"] ?? '');
        $photo_filename = $photo_filename_to_retain;

    } else {
        if (empty(trim($_POST["staff_name"] ?? ''))) {
            $staff_name_err = "Please enter the staff name.";
        } else {
            $staff_name = trim($_POST["staff_name"] ?? '');
        }

        if (empty(trim($_POST["mobile_number"] ?? ''))) {
            $mobile_number_err = "Please enter mobile number.";
        } else {
            $mobile_number = trim($_POST["mobile_number"] ?? '');
        }

        if (empty(trim($_POST["unique_id"] ?? ''))) {
            $unique_id_err = "Please enter the unique ID.";
        } else {
            $unique_id = trim($_POST["unique_id"] ?? '');
            if (!preg_match("/^\d{4}$/", $unique_id)) {
                $unique_id_err = "Unique ID must be exactly 4 digits.";
            } else {
                $sql_check_unique_id = "SELECT staff_id FROM staff WHERE unique_id = ? AND staff_id != ?";
                if ($stmt_check = mysqli_prepare($link, $sql_check_unique_id)) {
                    mysqli_stmt_bind_param($stmt_check, "si", $unique_id, $staff_id);
                    if (mysqli_stmt_execute($stmt_check)) {
                        mysqli_stmt_store_result($stmt_check);
                        if (mysqli_stmt_num_rows($stmt_check) > 0) {
                            $unique_id_err = "This unique ID is already taken by another staff member.";
                        }
                    } else {
                         $edit_message .= "<p class='text-red-600'>Error checking unique ID availability. Please try again.</p>";
                    }
                    mysqli_stmt_close($stmt_check);
                } else {
                     $edit_message .= "<p class='text-red-600'>Error preparing unique ID check statement.</p>";
                }
            }
        }

        if (empty(trim($_POST["email"] ?? ''))) {
            $email_err = "Please enter the email.";
        } else {
            $email = trim($_POST["email"] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email_err = "Please enter a valid email format.";
            } else {
                $sql_check_email = "SELECT staff_id FROM staff WHERE email = ? AND staff_id != ?";
                 if ($stmt_check = mysqli_prepare($link, $sql_check_email)) {
                    mysqli_stmt_bind_param($stmt_check, "si", $email, $staff_id);
                    if (mysqli_stmt_execute($stmt_check)) {
                        mysqli_stmt_store_result($stmt_check);
                        if (mysqli_stmt_num_rows($stmt_check) > 0) {
                            $email_err = "This email is already registered by another staff member.";
                        }
                    } else {
                         $edit_message .= "<p class='text-red-600'>Error checking email availability. Please try again.</p>";
                    }
                    mysqli_stmt_close($stmt_check);
                } else {
                     $edit_message .= "<p class='text-red-600'>Error preparing email check statement.</p>";
                }
            }
        }

        $role = trim($_POST["role"] ?? '');
         if (empty($role)) {
             $role_err = "Please select a role.";
         } elseif (!in_array($role, $allowed_roles)) {
             $role_err = "Invalid role selected.";
         }

        $salary_input = trim($_POST['salary'] ?? '');
        if (empty($salary_input)) {
             $salary_err = "Please enter salary.";
             $salary = $salary_input;
        } else {
            $salary_filtered = filter_var($salary_input, FILTER_VALIDATE_FLOAT);
            if ($salary_filtered === false || $salary_filtered < 0) {
                $salary_err = "Please enter a valid positive number for salary.";
                $salary = $salary_input;
            } else {
                $salary = $salary_filtered;
            }
        }

        $subject_taught = trim($_POST["subject_taught"] ?? '');

        $classes_taught = trim($_POST["classes_taught"] ?? '');

        $new_photo_filename_for_db = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024;

            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                $photo_err = "Only JPG, PNG, and GIF images are allowed.";
            } elseif ($_FILES['photo']['size'] > $max_size) {
                $photo_err = "File size must be less than 2MB.";
            } else {
                $upload_result = uploadToCloudinary($_FILES['photo'], 'staff_photos');

                if ($upload_result && isset($upload_result['secure_url'])) {
                    $new_photo_filename_for_db = $upload_result['secure_url'];
                } else {
                    $photo_err = "Photo upload failed: " . ($upload_result['error'] ?? 'Unknown Cloudinary error.');
                    error_log("Cloudinary upload failed for staff ID " . $staff_id . ": " . ($upload_result['error'] ?? 'Unknown error.'));
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
            error_log("PHP file upload error for staff ID " . $staff_id . ": " . $photo_err . " (Error Code: " . $_FILES['photo']['error'] . ")");
        }

        $photo_filename_db = empty($photo_err) ? ($new_photo_filename_for_db ?? $photo_filename_to_retain) : $photo_filename_to_retain;
        if ($photo_filename_db === '') {
            $photo_filename_db = null;
        }


        $subject_taught_db = ($subject_taught === '') ? null : $subject_taught;
        $classes_taught_db = ($classes_taught === '') ? null : $classes_taught;
        $salary_db = empty($salary_err) ? $salary : 0.00;


        if (empty($staff_name_err) && empty($mobile_number_err) && empty($unique_id_err) && empty($email_err) && empty($role_err) && empty($salary_err) && empty($photo_err)) {

            $sql_update = "UPDATE staff SET staff_name=?, mobile_number=?, unique_id=?, email=?, role=?, salary=?, subject_taught=?, classes_taught=?, photo_filename=? WHERE staff_id=?";

            if ($link === false) {
                 $edit_message = "<p class='text-red-600'>Database connection error. Could not save changes.</p>";
                 error_log("Edit Staff DB connection failed: " . mysqli_connect_error());
            } elseif ($stmt_update = mysqli_prepare($link, $sql_update)) {
                // CORRECTED: The type string now has 10 characters ('sssssdsssi') matching the 10 parameters.
                mysqli_stmt_bind_param($stmt_update, "sssssdsssi",
                    $staff_name,
                    $mobile_number,
                    $unique_id,
                    $email,
                    $role,
                    $salary_db,
                    $subject_taught_db,
                    $classes_taught_db,
                    $photo_filename_db,
                    $staff_id
                );

                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['operation_message'] = "<p class='text-green-600'>Staff record for " . htmlspecialchars($staff_full_name) . " updated successfully.</p>";
                    header("location: manage_staff.php");
                    exit();
                } else {
                     $edit_message = "<p class='text-red-600'>Error: Could not update staff record. " . mysqli_stmt_error($stmt_update) . "</p>";
                }

                mysqli_stmt_close($stmt_update);
            } else {
                 $edit_message = "<p class='text-red-600'>Error: Could not prepare update statement. " . mysqli_error($link) . "</p>";
            }
        } else {
             $edit_message = "<p class='text-yellow-600'>Please correct the errors below.</p>";
        }
        $staff_name = trim($_POST["staff_name"] ?? '');
        $mobile_number = trim($_POST["mobile_number"] ?? '');
        $unique_id = trim($_POST["unique_id"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $role = trim($_POST["role"] ?? '');
        $salary = $_POST["salary"] ?? '';
        $subject_taught = trim($_POST["subject_taught"] ?? '');
        $classes_taught = trim($_POST["classes_taught"] ?? '');
        $photo_filename = $photo_filename_to_retain;
    }

} else {

    if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
        $staff_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($staff_id === false || $staff_id <= 0) {
            $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid staff ID provided for editing.</p>";
            header("location: manage_staff.php");
            exit();
        } else {
            $sql_fetch = "SELECT staff_id, staff_name, mobile_number, unique_id, email, role, salary, subject_taught, classes_taught, photo_filename FROM staff WHERE staff_id = ?";

            if ($link === false) {
                $edit_message = "<p class='text-red-600'>Database connection error. Could not load staff data.</p>";
                 error_log("Edit Staff fetch DB connection failed: " . mysqli_connect_error());
            } elseif ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
                mysqli_stmt_bind_param($stmt_fetch, "i", $staff_id);

                if (mysqli_stmt_execute($stmt_fetch)) {
                    $result_fetch = mysqli_stmt_get_result($stmt_fetch);

                    if (mysqli_num_rows($result_fetch) == 1) {
                        $staff = mysqli_fetch_assoc($result_fetch);

                        $staff_name = $staff["staff_name"];
                        $mobile_number = $staff["mobile_number"];
                        $unique_id = $staff["unique_id"];
                        $email = $staff["email"];
                        $role = $staff["role"];
                        $salary = $staff["salary"];
                        $subject_taught = $staff["subject_taught"];
                        $classes_taught = $staff["classes_taught"];
                        $photo_filename = $staff["photo_filename"] ?? '';

                         $staff_full_name = $staff['staff_name'];

                    } else {
                        $_SESSION['operation_message'] = "<p class='text-red-600'>Staff record not found.</p>";
                        header("location: manage_staff.php");
                        exit();
                    }
                } else {
                    $edit_message = "<p class='text-red-600'>Oops! Something went wrong. Could not fetch staff data. Please try again later.</p>";
                     error_log("Edit Staff fetch query failed: " . mysqli_stmt_error($stmt_fetch));
                }

                mysqli_stmt_close($stmt_fetch);
            } else {
                 $edit_message = "<p class='text-red-600'>Oops! Something went wrong. Could not prepare fetch statement. Please try again later.</p>";
                 error_log("Edit Staff prepare fetch statement failed: " . mysqli_error($link));
            }
        }
    } else {
        $_SESSION['operation_message'] = "<p class='text-red-600'>No staff ID provided for editing.</p>";
        header("location: manage_staff.php");
        exit();
    }
}

if (isset($link) && is_object($link)) {
     mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Edit Staff Data</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
     <style>
        .form-error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25em;
        }
         .form-control.is-invalid {
             border-color: #dc3545;
         }
          .alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.25rem; }
           .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
           .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
            .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
            .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }

         .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
         }
         body {
             padding-top: 4rem;
         }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center">
    <?php
    require_once "./admin_sidebar.php";
    ?>

    <div class="fixed-header w-full flex items-center">
         <button id="admin-sidebar-toggle-open" class="focus:outline-none mr-4 text-gray-600 hover:text-gray-800">
             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
             </svg>
         </button>
         <h1 class="text-xl font-bold text-gray-800">Edit Staff</h1>
          <span class="ml-auto text-sm text-gray-700 hidden md:inline">Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['display_name'] ?? 'Admin'); ?> (<?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Unknown')); ?>)</span>
    </div>


     <div class="w-full max-w-lg flex flex-col items-center px-4 py-8">

         <?php
         if (!empty($edit_message)) {
             $alert_class = 'alert-warning';
             if (strpos(strip_tags($edit_message), 'successfully') !== false) {
                 $alert_class = 'alert-success';
             } elseif (strpos(strip_tags($edit_message), 'Error') !== false || strpos(strip_tags($edit_message), 'correct the errors') !== false) {
                  $alert_class = 'alert-danger';
             }
            echo "<div class='mb-4 text-center alert " . $alert_class . "' role='alert'>" . $edit_message . "</div>";
        }
        ?>

        <?php if ($staff_id !== null || ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['staff_id']))): ?>

        <p class="text-center text-gray-600 mb-4">Editing record for: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($staff_full_name); ?></span> (ID: <?php echo htmlspecialchars($staff_id); ?>)</p>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4 w-full" enctype="multipart/form-data">

            <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($staff_id); ?>">
            <input type="hidden" name="staff_full_name" value="<?php echo htmlspecialchars($staff_full_name); ?>">
            <input type="hidden" name="current_photo_filename" value="<?php echo htmlspecialchars($photo_filename); ?>">


            <div>
                <label for="display_staff_id" class="block text-sm font-medium text-gray-700">Staff ID</label>
                <input type="text" id="display_staff_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm" value="<?php echo htmlspecialchars($staff_id); ?>" readonly>
            </div>

             <!-- Photo Upload Section -->
             <div class="mt-4">
                 <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Staff Photo</h3>
                 <div class="flex items-center space-x-4 mb-4">
                     <?php if (!empty($photo_filename)): ?>
                         <div>
                             <img src="<?php echo htmlspecialchars($photo_filename); ?>" alt="Staff Photo" class="h-24 w-24 object-cover rounded-full border border-gray-300">
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
                         <span class="form-error"><?php echo htmlspecialchars($photo_err); ?></span>
                         <p class="text-xs text-gray-500 mt-1">Max 2MB. JPG, PNG, GIF.</p>
                     </div>
                 </div>
             </div>
             <!-- End Photo Upload Section -->

             <div>
                <label for="staff_name" class="block text-sm font-medium text-gray-700">Staff Name <span class="text-red-500">*</span></label>
                <input type="text" name="staff_name" id="staff_name" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($staff_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($staff_name); ?>">
                <span class="form-error"><?php echo $staff_name_err; ?></span>
            </div>

            <div>
                <label for="mobile_number" class="block text-sm font-medium text-gray-700">Mobile Number <span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" id="mobile_number" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($mobile_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($mobile_number); ?>">
                <span class="form-error"><?php echo $mobile_number_err; ?></span>
            </div>

             <div>
                <label for="unique_id" class="block text-sm font-medium text-gray-700">Unique ID (4 Digits) <span class="text-red-500">*</span></label>
                <input type="text" name="unique_id" id="unique_id" maxlength="4" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($unique_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($unique_id); ?>">
                <span class="form-error"><?php echo $unique_id_err; ?></span>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                <span class="form-error"><?php echo $email_err; ?></span>
            </div>


            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">-- Select Role --</option>
                     <?php
                        foreach ($allowed_roles as $allowed_role) {
                             echo '<option value="' . htmlspecialchars($allowed_role) . '"' . (($role === $allowed_role) ? ' selected' : '') . '>' . htmlspecialchars(ucfirst($allowed_role)) . '</option>';
                        }
                     ?>
                </select>
                <span class="form-error"><?php echo $role_err; ?></span>
            </div>


            <div>
                <label for="salary" class="block text-sm font-medium text-gray-700">Salary <span class="text-red-500">*</span></label>
                <input type="number" name="salary" id="salary" step="0.01" min="0" class="form-control mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo (!empty($salary_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($salary); ?>">
                 <span class="form-error"><?php echo $salary_err; ?></span>
            </div>

            <div>
                <label for="subject_taught" class="block text-sm font-medium text-gray-700">Subject Taught</label>
                <input type="text" name="subject_taught" id="subject_taught" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($subject_taught ?? ''); ?>">
            </div>

             <div>
                <label for="classes_taught" class="block text-sm font-medium text-gray-700">Classes Taught (e.g., "Nursery, Class 1, Class 5")</label>
                <textarea name="classes_taught" id="classes_taught" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($classes_taught ?? ''); ?></textarea>
            </div>


            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Save Changes</button>
                 <a href="manage_staff.php" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</a>
            </div>
        </form>

         <?php else: ?>
             <div class="mt-6 text-center">
                 <a href="manage_staff.php" class="text-blue-600 hover:underline">Back to Manage Staff</a>
             </div>
         <?php endif; ?>

    </div>

</body>
</html>