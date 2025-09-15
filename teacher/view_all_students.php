<?php
// Start the session
session_start();

// Adjust path to config.php based on directory structure
require_once "../config.php";

// --- ACCESS CONTROL ---
$allowed_roles = ['principal', 'staff'];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['operation_message'] = "Access denied. You must be a Principal or authorized staff to view all student records.";
    header("location: ./login.php");
    exit;
}

// --- RETRIEVE STAFF INFORMATION FROM SESSION ---
$staff_id = $_SESSION['id'] ?? null;
$staff_display_name = $_SESSION['name'] ?? 'Staff Member';
$staff_role = $_SESSION['role'] ?? 'Staff';

$fetch_staff_error = "";
$staff_data = null;

if ($staff_id !== null && $link !== false) {
    $sql_fetch_staff = "SELECT staff_name, role FROM staff WHERE staff_id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch_staff)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $staff_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            if ($result_fetch && mysqli_num_rows($result_fetch) == 1) {
                $staff_data = mysqli_fetch_assoc($result_fetch);
                $staff_display_name = $staff_data['staff_name'];
                $staff_role = $staff_data['role'];
            } else {
                $fetch_staff_error = "Your staff profile could not be found. Please contact an administrator.";
                error_log("View All Students: Staff profile (ID: $staff_id) not found in DB.");
            }
            if ($result_fetch) mysqli_free_result($result_fetch);
        } else {
            $fetch_staff_error = "Error fetching your profile data. Please try again later.";
            error_log("View All Students: Staff profile fetch query failed: " . mysqli_stmt_error($stmt_fetch));
        }
        if ($stmt_fetch) mysqli_stmt_close($stmt_fetch);
    } else {
        $fetch_staff_error = "Error preparing profile fetch statement: " . mysqli_error($link);
    }
} else if ($link === false) {
    $fetch_staff_error = "Database connection error. Could not load staff profile.";
}

// --- FETCH ALL STUDENT DATA with SEARCH functionality ---
$students = [];
$fetch_students_message = "";
$can_fetch_students = ($staff_data !== null && $link !== false);
$search_term = $_GET['search'] ?? ''; // Get search term from URL

if ($can_fetch_students) {
    // Base SQL query
    $sql_select_students = "SELECT user_id, photo_filename, full_name, phone_number, whatsapp_number, current_class, previous_class, previous_school, current_marks, created_at, virtual_id, is_active FROM students";
    
    $where_clause = "";
    $params = [];
    $param_types = "";
    
    // Add WHERE clause for search if a search term is provided
    if (!empty($search_term)) {
        $where_clause = " WHERE full_name LIKE ? OR virtual_id LIKE ? OR current_class LIKE ? OR phone_number LIKE ? OR user_id LIKE ?";
        $like_term = "%" . $search_term . "%";
        $params = [$like_term, $like_term, $like_term, $like_term, $like_term];
        $param_types = "sssss";
    }

    $sql_select_students .= $where_clause . " ORDER BY created_at DESC";

    if ($stmt_students = mysqli_prepare($link, $sql_select_students)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt_students, $param_types, ...$params);
        }

        if (mysqli_stmt_execute($stmt_students)) {
            $result_students = mysqli_stmt_get_result($stmt_students);
            if ($result_students) {
                $students = mysqli_fetch_all($result_students, MYSQLI_ASSOC);
                $student_count = count($students);
                if ($student_count > 0) {
                    $fetch_students_message = "Displaying " . $student_count . " student record(s).";
                } else {
                    $fetch_students_message = "No student records found matching your search term.";
                }
            } else {
                $fetch_students_message = "Could not retrieve student records.";
                error_log("View All Students: Student list get_result failed: " . mysqli_stmt_error($stmt_students));
            }
            if ($result_students) mysqli_free_result($result_students);
        } else {
            $fetch_students_message = "Error fetching student data: " . mysqli_stmt_error($stmt_students);
            error_log("View All Students: Student fetch query failed: " . mysqli_stmt_error($stmt_students));
        }
        if ($stmt_students) mysqli_stmt_close($stmt_students);
    } else {
        $fetch_students_message = "Error preparing student fetch statement: " . mysqli_error($link);
        error_log("View All Students: Could not prepare student fetch statement: " . mysqli_error($link));
    }
} else {
    $fetch_students_message = "Could not fetch staff profile data, student list unavailable.";
}


// Check for and display messages from operations
$operation_message_session = "";
if (isset($_SESSION['operation_message'])) {
    $operation_message_session = $_SESSION['operation_message'];
    unset($_SESSION['operation_message']);
}

// Close database connection at the very end
if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Student Records - <?php echo htmlspecialchars(ucfirst($staff_role)); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* General body and container styles */
        body { font-family: 'Inter', sans-serif; background: linear-gradient(-45deg, #b5e2ff, #d9f4ff, #b5e2ff, #d9f4ff); background-size: 400% 400%; animation: gradientAnimation 15s ease infinite; background-attachment: fixed; }
        @keyframes gradientAnimation { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .content-card { background-color: #ffffff; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Alert styling */
        .alert { border-left-width: 4px; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); display: flex; align-items: center; gap: 0.75rem; line-height: 1.5; }
        .alert-icon { flex-shrink: 0; width: 1.5rem; height: 1.5rem; }
        .alert-info { background-color: #e0f7fa; border-color: #0891b2; color: #0e7490; }
        .alert-success { background-color: #dcfce7; border-color: #22c55e; color: #15803d; }
        .alert-danger { background-color: #fee2e2; border-color: #ef4444; color: #b91c1c; }
        .alert-warning { background-color: #fff7ed; border-color: #f97316; color: #ea580c; }

        /* Table specific styles */
        .student-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .student-table th, .student-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .student-table th { background-color: #f9fafb; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .student-table td { font-size: 0.875rem; color: #4b5563; line-height: 1.4; }
        .student-table tbody tr:hover { background-color: #f3f4f6; }
        .photo-container { width: 2.5rem; height: 2.5rem; border-radius: 9999px; background-color: #d1d5db; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .photo-container img { width: 100%; height: 100%; object-fit: cover; }
        .photo-container span { color: #ffffff; font-size: 0.875rem; font-weight: 500; }
        .status-active { color: #10b981; }
        .status-inactive { color: #ef4444; }
        
        /* Action Links Styling */
        .action-links a { text-decoration: none; font-size: 0.8125rem; font-weight: 500; margin-right: 0.5rem; transition: color 0.1s ease-in-out; }
        .action-links a.view-link { color: #2563eb; }
        .action-links a.edit-link { color: #059669; }
        .action-links a.deactivate-link { color: #f97316; }
        .action-links a.delete-link { color: #dc2626; }
        .action-links a:hover { text-decoration: underline; }

        /* Button Styling */
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; font-size: 1rem; font-weight: 500; line-height: 1.5; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s ease-in-out; text-decoration: none; }
        .btn-primary { color: #ffffff; background-color: #10b981; border: 1px solid #10b981; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .btn-primary:hover { background-color: #059669; border-color: #059669; }
        .btn-secondary { color: #374151; background-color: #ffffff; border-color: #d1d5db; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .btn-secondary:hover { background-color: #f9fafb; }

        /* Form Element Styling (Search Input) */
        .form-input { display: block; width: 100%; padding: 0.625rem 1rem; font-size: 1rem; line-height: 1.5; color: #4b5563; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075); transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
        .form-input:focus { border-color: #60a5fa; outline: 0; box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25); }
    </style>
</head>
<body>

    <?php require_once "./staff_navbar.php"; ?>

    <main class="w-full max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-8">

        <?php
        // Display messages from session
        if (!empty($operation_message_session)) {
            $alert_class = 'alert-info';
            $icon_svg = '<svg class="alert-icon text-cyan-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
            if (strpos($operation_message_session, 'successfully') !== false || strpos($operation_message_session, 'Welcome') !== false) {
                $alert_class = 'alert-success';
                $icon_svg = '<svg class="alert-icon text-green-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
            } elseif (strpos($operation_message_session, 'denied') !== false || strpos($operation_message_session, 'Error') !== false || strpos($operation_message_session, 'Failed') !== false) {
                $alert_class = 'alert-danger';
                $icon_svg = '<svg class="alert-icon text-red-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
            }
            echo "<div class='alert " . $alert_class . "' role='alert'>" . $icon_svg;
            echo "<p>" . nl2br(htmlspecialchars($operation_message_session)) . "</p>";
            echo "</div>";
        }
        ?>

        <?php if (!empty($fetch_staff_error)): ?>
            <div class='alert alert-warning' role='alert'>
                <svg class="alert-icon text-orange-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.246 3.01-1.881 3.01H4.558c-1.636 0-2.636-1.676-1.88-3.01l5.58-9.92zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 112 0v4a1 1 0 11-2 0V5z" clip-rule="evenodd"></path></svg>
                <p><?php echo nl2br(htmlspecialchars($fetch_staff_error)); ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-1">
                All Student Records
            </h1>
            <p class="text-lg text-gray-700">
                Logged in as <strong class="font-semibold text-gray-800"><?php echo htmlspecialchars($staff_display_name); ?></strong> (<?php echo htmlspecialchars(ucfirst($staff_role)); ?>)
            </p>
        </div>

        <section id="all-student-records" class="content-card mt-8">

            <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-6 text-center">Complete Student List</h2>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <div class="w-full sm:w-auto sm:flex-grow">
                    <label for="search" class="form-label sr-only">Search Student Records:</label>
                    <input type="text" id="search" name="search" class="form-input" placeholder="Search students..." value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">
                        <i class="fas fa-undo mr-2"></i> Reset
                    </a>
                </div>
            </form>

            <div class="alert alert-info mb-4">
                <svg class="alert-icon text-cyan-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                <p><?php echo nl2br(htmlspecialchars($fetch_students_message)); ?></p>
            </div>

            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>PHOTO</th>
                                <th>USER ID</th>
                                <th>VIRTUAL ID</th>
                                <th>FULL NAME</th>
                                <th>PHONE</th>
                                <th>CURRENT CLASS</th>
                                <th>PREVIOUS CLASS</th>
                                <th>PREVIOUS SCHOOL</th>
                                <th>CURRENT MARKS (%)</th>
                                <th>STATUS</th>
                                <th>CREATED AT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="photo-cell">
                                        <div class="photo-container">
                                            <?php if (!empty($student['photo_filename'])): ?>
                                                <img src="<?php echo htmlspecialchars($student['photo_filename']); ?>" alt="<?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?> Photo">
                                            <?php else: ?>
                                                <span><?php echo htmlspecialchars(substr($student['full_name'] ?? 'S', 0, 1)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['virtual_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['current_class'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['previous_class'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['previous_school'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['current_marks'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        // Dynamic status based on is_active column
                                        $is_active = $student['is_active'] ?? 0;
                                        $status_text = ($is_active == 1) ? 'Active' : 'Inactive';
                                        $status_class = ($is_active == 1) ? 'status-active' : 'status-inactive';
                                        ?>
                                        <span class="status-text <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['created_at']); ?></td>
                                    <td class="action-links">
                                        <a href="./edit_student_principal.php?id=<?php echo htmlspecialchars($student['user_id']); ?>" class="edit-link">Edit</a>
                                        <?php if ($is_active == 1): ?>
                                            <a href="./deactivate_student.php?id=<?php echo htmlspecialchars($student['user_id']); ?>" class="deactivate-link" onclick="return confirm('Are you sure you want to deactivate this student?');">Deactivate</a>
                                        <?php else: ?>
                                            <a href="./activate_student.php?id=<?php echo htmlspecialchars($student['user_id']); ?>" class="view-link" onclick="return confirm('Are you sure you want to activate this student?');">Activate</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mt-8 text-center">
                <a href="./staff_dashboard.php" class="btn btn-secondary inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>

        </section>

    </main>

</body>
</html>