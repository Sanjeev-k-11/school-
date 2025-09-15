<?php
// Start the session
session_start();

// Adjust path to config.php based on directory structure
require_once "../config.php"; // Path to config.php

// --- ACCESS CONTROL ---
$allowed_staff_roles_dashboard = ['teacher', 'principal', 'staff'];
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], $allowed_staff_roles_dashboard)) {
    $_SESSION['operation_message'] = "Access denied. Please log in with appropriate staff credentials.";
    header("location: ./login.php");
    exit;
}

// --- RETRIEVE STAFF INFORMATION FROM SESSION ---
$staff_id = $_SESSION['id'] ?? null;
$staff_display_name = $_SESSION['name'] ?? 'Staff Member';
$staff_role = $_SESSION['role'] ?? 'Staff';

$staff_data = null;
$fetch_staff_error = "";

// --- Fetch Staff Profile Data from DB ---
if ($staff_id !== null && $link !== false) {
    $sql_fetch_staff = "SELECT staff_id, staff_name, mobile_number, unique_id, email, role, salary, subject_taught, classes_taught, created_at FROM staff WHERE staff_id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch_staff)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $staff_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            if ($result_fetch && mysqli_num_rows($result_fetch) == 1) {
                $staff_data = mysqli_fetch_assoc($result_fetch);
                $staff_display_name = $staff_data['staff_name'];
                $staff_role = $staff_data['role'];
                $_SESSION['name'] = $staff_data['staff_name'];
                $_SESSION['role'] = $staff_data['role'];
            } else {
                $fetch_staff_error = "Your staff profile could not be found in the database.";
            }
            if ($result_fetch) mysqli_free_result($result_fetch);
        } else {
            $fetch_staff_error = "Oops! Something went wrong while fetching your profile data.";
        }
        if ($stmt_fetch) mysqli_stmt_close($stmt_fetch);
    } else {
         $fetch_staff_error = "Oops! Something went wrong. Could not prepare profile fetch statement.";
    }
} else if ($link === false) {
     $fetch_staff_error = "Database connection error. Could not load staff profile.";
} else {
    $fetch_staff_error = "Staff ID not found in session. Please try logging in again.";
}


// --- Fetch Today's Staff Timetable Data ---
$today_timetable_entries = [];
$fetch_timetable_message = "";
$can_fetch_timetable = ($staff_data !== null && isset($staff_data['staff_name']) && $link !== false);

if ($can_fetch_timetable) {
     $today_day = date('l'); // Full day name
     $sql_fetch_today_timetable = "SELECT day_of_week, class_name AS class_taught, subject_name AS subject_taught, CONCAT(TIME_FORMAT(start_time, '%h:%i %p'), ' - ', TIME_FORMAT(end_time, '%h:%i %p')) AS time_slot FROM class_timetables WHERE teacher_name = ? AND day_of_week = ? ORDER BY start_time ASC";
    if ($stmt_today_timetable = mysqli_prepare($link, $sql_fetch_today_timetable)) {
        mysqli_stmt_bind_param($stmt_today_timetable, "ss", $staff_data['staff_name'], $today_day);
        if (mysqli_stmt_execute($stmt_today_timetable)) {
            $result_today_timetable = mysqli_stmt_get_result($stmt_today_timetable);
            if ($result_today_timetable) {
                $today_timetable_entries = mysqli_fetch_all($result_today_timetable, MYSQLI_ASSOC);
                if (empty($today_timetable_entries)) {
                     $fetch_timetable_message = "You have no classes scheduled for today (" . htmlspecialchars($today_day) . ").";
                }
            } else {
                 $fetch_timetable_message = "Could not retrieve your timetable entries for today.";
            }
            if ($result_today_timetable) mysqli_free_result($result_today_timetable);
        } else {
            $fetch_timetable_message = "Error fetching your timetable for today.";
        }
        if ($stmt_today_timetable) mysqli_stmt_close($stmt_today_timetable);
    } else {
         $fetch_timetable_message = "Error preparing timetable fetch statement.";
    }
} else if ($link === false) {
    $fetch_timetable_message = "Database connection error. Could not load your timetable.";
} else if ($staff_data === null || !isset($staff_data['staff_name'])) {
    $fetch_timetable_message = "Your profile could not be loaded, so the timetable is unavailable.";
}


// --- Fetch Student Data for Table and Stats (LIMITED TO 5 LATEST) ---
$students = [];
$fetch_students_message = "";
$can_fetch_students = ($staff_data !== null && $link !== false);

if ($can_fetch_students) {
    $sql_select_students_base = "SELECT user_id, photo_filename, full_name, phone_number, whatsapp_number, current_class, previous_class, previous_school, current_marks, created_at, virtual_id, 'Active' AS status FROM students";
    $sql_students_order_limit = " ORDER BY created_at DESC LIMIT 5";
    $where_clauses_students = [];
    $params_students = [];
    $param_types_students = "";
    
    if (isset($staff_data['role']) && $staff_data['role'] === 'teacher') {
         $classes_taught_string = $staff_data['classes_taught'] ?? '';
         $classes_array = array_filter(array_map('trim', explode(',', $classes_taught_string)));
         if (!empty($classes_array)) {
             $placeholder_string = implode(', ', array_fill(0, count($classes_array), '?'));
             $where_clauses_students[] = "current_class IN (" . $placeholder_string . ")";
             $params_students = $classes_array;
             $param_types_students = str_repeat('s', count($classes_array));
             $fetch_students_message = "Displaying the <strong>5 latest</strong> students in your assigned classes.";
         } else {
             $can_fetch_students = false;
             $fetch_students_message = "You have no classes assigned, so no students are listed.";
         }
    } else {
          $fetch_students_message = "Displaying the <strong>5 latest</strong> student records.";
    }

    if ($can_fetch_students) {
        $sql_students = $sql_select_students_base;
        if (!empty($where_clauses_students)) {
            $sql_students .= " WHERE " . implode(" AND ", $where_clauses_students);
        }
        $sql_students .= $sql_students_order_limit;

        if ($stmt_students = mysqli_prepare($link, $sql_students)) {
            if (!empty($params_students)) {
                 mysqli_stmt_bind_param($stmt_students, $param_types_students, ...$params_students);
            }
            if (mysqli_stmt_execute($stmt_students)) {
                $result_students = mysqli_stmt_get_result($stmt_students);
                if ($result_students) {
                    $students = mysqli_fetch_all($result_students, MYSQLI_ASSOC);
                    if(empty($students)) $fetch_students_message = "No student records found.";
                }
                if ($result_students) mysqli_free_result($result_students);
            } else {
                 $fetch_students_message = "Error fetching student data.";
            }
            if ($stmt_students) mysqli_stmt_close($stmt_students);
        } else {
             $fetch_students_message = "Error preparing student fetch statement.";
        }
    }
}

// --- Calculate Overall Class Score (Average Marks) ---
$total_marks = 0;
$student_count_with_marks = 0;
$formatted_average_marks = "N/A";
if (!empty($students)) {
    foreach ($students as $student) {
        if (isset($student['current_marks']) && is_numeric($student['current_marks'])) {
            $total_marks += (float) $student['current_marks'];
            $student_count_with_marks++;
        }
    }
    if ($student_count_with_marks > 0) {
        $average_marks = $total_marks / $student_count_with_marks;
        $formatted_average_marks = number_format($average_marks, 2);
    }
}

// Check for and display messages from operations
$operation_message_session = $_SESSION['operation_message'] ?? "";
if ($operation_message_session) {
    unset($_SESSION['operation_message']);
}

// Close database connection
if (isset($link) && is_object($link)) {
     mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(ucfirst($staff_role)); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 4rem;
            color: #374151;
            background: linear-gradient(-45deg, #b5e2ff, #d9f4ff, #b5e2ff, #d9f4ff);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            background-attachment: fixed;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .content-card {
             background-color: #ffffff;
             padding: 1.5rem;
             border-radius: 0.75rem;
             box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
             border: 1px solid #e5e7eb;
        }
        .alert {
             border-left-width: 4px; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem;
             box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); display: flex; align-items: center; gap: 0.75rem;
        }
        .alert-icon { flex-shrink: 0; width: 1.5rem; height: 1.5rem; }
        .alert p { margin: 0; }
        .alert-info { background-color: #e0f7fa; border-color: #0891b2; color: #0e7490; }
        .alert-success { background-color: #dcfce7; border-color: #22c55e; color: #15803d; }
        .alert-warning { background-color: #fff7ed; border-color: #f97316; color: #ea580c; }
        .alert-danger { background-color: #fee2e2; border-color: #ef4444; color: #b91c1c; }

        /* --- STYLES FOR HOVER EFFECT --- */
        #stats-grid {
            position: relative; /* Parent must be relative for absolute child */
        }
        #hover-effect-bg {
            position: absolute;
            z-index: 0; /* Behind the card content */
            background-color: rgba(40, 62, 92, 0.8); /* light-blue-50 with opacity */
            border-radius: 0.75rem; /* Match card border-radius */
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Smooth sliding transition */
            pointer-events: none; /* Make it non-interactive */
            opacity: 0; /* Initially hidden */
        }
        /* --- END HOVER EFFECT STYLES --- */

        .dashboard-stats-card {
             background-color: #ffffff; padding: 1.5rem; border-radius: 0.75rem;
             box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px 0 rgba(0, 0, 0, 0.04);
             border: 1px solid #e5e7eb; display: flex; flex-direction: column; justify-content: space-between;
             position: relative; /* Needed to keep content above the hover effect */
             z-index: 1; /* Ensure content is on top of the sliding background */
        }
        .dashboard-stats-card h3 { font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 0.75rem; }
        .dashboard-stats-card .card-value { font-size: 2.25rem; font-weight: 700; line-height: 1; }
        .dashboard-stats-card .card-description { font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem; }
        .stats-card-icon { width: 3.5rem; height: 3.5rem; flex-shrink: 0; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
        .student-table, .timetable-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .student-table th, .student-table td, .timetable-table th, .timetable-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .student-table th, .timetable-table th { background-color: #f9fafb; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .student-table td, .timetable-table td { font-size: 0.875rem; color: #4b5563; }
        .student-table tbody tr:hover, .timetable-table tbody tr:hover { background-color: #f3f4f6; }
        .photo-cell { width: 40px; padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .photo-container { width: 2.5rem; height: 2.5rem; border-radius: 9999px; background-color: #d1d5db; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .photo-container img { width: 100%; height: 100%; object-fit: cover; }
        .photo-container span { color: #ffffff; font-size: 0.875rem; font-weight: 500; }
        .action-links a { text-decoration: none; font-size: 0.8125rem; font-weight: 500; margin-right: 0.5rem; transition: color 0.1s ease-in-out; }
        .action-links a.edit-link { color: #059669; }
        .action-links a:hover { text-decoration: underline; }
        .btn-secondary { color: #374151; background-color: #ffffff; border: 1px solid #d1d5db; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .btn-secondary:hover { background-color: #f9fafb; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; font-size: 1rem; font-weight: 500; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s ease-in-out; }
        .form-input { display: block; width: 100%; padding: 0.625rem 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
        .time-slot-squares { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; scrollbar-width: none; -ms-overflow-style: none; }
        .time-slot-squares::-webkit-scrollbar { display: none; }
        .time-slot-square { flex: 0 0 200px; height: 70px; background-color: #e0f2f7; border: 1px solid #06b6d4; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center; text-align: center; font-size: 0.875rem; font-weight: 600; color: #0e7490; padding: 0.5rem; line-height: 1.3; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: transform 0.1s ease-in-out, box-shadow 0.1s ease-in-out; }
        .time-slot-square:hover { transform: translateY(-2px); box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        .time-slot-square .time { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; color: #0891b2; }
    </style>
</head>
<body>

    <?php
    $navbar_path = "./staff_navbar.php";
    if (file_exists($navbar_path)) {
        require_once $navbar_path;
    } else {
        echo '<div class="alert alert-danger" role="alert"><strong>Error:</strong> Staff navbar file not found!</div>';
    }
    ?>

    <main class="w-full max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-8">

        <?php if (!empty($operation_message_session)): ?>
            <div class='alert alert-success' role='alert'>
                <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <p><?php echo htmlspecialchars($operation_message_session); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($fetch_staff_error)): ?>
            <div class='alert alert-warning' role='alert'>
                 <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.246 3.01-1.881 3.01H4.558c-1.636 0-2.636-1.676-1.88-3.01l5.58-9.92zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 112 0v4a1 1 0 11-2 0V5z" clip-rule="evenodd"></path></svg>
                <p><?php echo htmlspecialchars($fetch_staff_error); ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-1">
                 <?php echo htmlspecialchars(ucfirst($staff_role)); ?> Dashboard
            </h1>
            <?php if ($staff_data): ?>
                 <p class="text-lg text-gray-700">
                     Welcome back, <strong class="font-semibold text-gray-800"><?php echo htmlspecialchars($staff_display_name); ?>!</strong>
                 </p>
             <?php endif; ?>
        </div>

        <!-- Dashboard Stats Section with Hover Effect -->
        <div id="stats-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- NEW: This is the sliding background element -->
            <div id="hover-effect-bg"></div>

            <!-- Card 1: Staff Profile/Info -->
            <?php if ($staff_data): ?>
            <div class="dashboard-stats-card">
                 <h3 class="text-lg font-semibold text-gray-800">Your Information</h3>
                 <div class="text-sm text-gray-700 space-y-2 mt-4">
                      <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($staff_role)); ?></p>
                      <p><strong>Unique ID:</strong> <?php echo htmlspecialchars($staff_data['unique_id'] ?? 'N/A'); ?></p>
                      <p><strong>Email:</strong> <?php echo htmlspecialchars($staff_data['email'] ?? 'N/A'); ?></p>
                      <?php if (!empty($staff_data['classes_taught'])): ?>
                      <p><strong>Classes Taught:</strong> <?php echo htmlspecialchars($staff_data['classes_taught']); ?></p>
                      <?php endif; ?>
                 </div>
            </div>
            <?php endif; ?>

            <!-- Card 2: Latest Student Average Score -->
            <div class="dashboard-stats-card">
                <h3 class="text-lg font-semibold text-green-800">Latest Student Avg. Score</h3>
                <div class="flex items-center justify-between flex-grow mt-4">
                    <p class="card-value text-green-700"><?php echo htmlspecialchars($formatted_average_marks); ?>%</p>
                    <svg class="stats-card-icon text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <p class="card-description text-green-700 mt-2">Average from <?php echo $student_count_with_marks; ?> students displayed.</p>
            </div>

            <!-- Card 3: Students Displayed -->
            <div class="dashboard-stats-card">
                <h3 class="text-lg font-semibold text-blue-800">Students Displayed</h3>
                 <div class="flex items-center justify-between flex-grow mt-4">
                    <p class="card-value text-blue-700"><?php echo count($students); ?></p>
                    <svg class="stats-card-icon text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M12 20.646v-1.646m0 0V14m0 0a2 2 0 100 4m0-4a2 2 0 110 4m-8-2a2 2 0 11-4 0 2 2 0 014 0zM14 15a2 2 0 10-4 0 2 2 0 014 0zm0 0v2.5a2.5 2.5 0 002.5 2.5h1a2.5 2.5 0 002.5-2.5v-1l1.42-1.42a2 2 0 000-2.84L19 9l1.42-1.42a2 2 0 000-2.84L18 3l-1.42 1.42a2 2 0 00-2.84 0L13 6l-1.42-1.42a2 2 0 00-2.84 0L8 6z"></path></svg>
                 </div>
                 <p class="card-description text-blue-700 mt-2">Showing the latest student records.</p>
            </div>
        </div>

        <!-- Today's Timetable Section -->
        <section class="content-card mt-8">
            <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-4 text-center">Today's Timetable (<?php echo htmlspecialchars(date('l')); ?>)</h2>
            <?php if (!empty($fetch_timetable_message)): ?>
                <div class='alert alert-info'><p><?php echo htmlspecialchars($fetch_timetable_message); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($today_timetable_entries)): ?>
                 <div class="time-slot-squares mb-6">
                     <?php foreach ($today_timetable_entries as $entry): ?>
                         <div class="time-slot-square">
                             <div class="time"><?php echo htmlspecialchars($entry['time_slot']); ?></div>
                             <div><?php echo htmlspecialchars($entry['class_taught']); ?></div>
                             <div><?php echo htmlspecialchars($entry['subject_taught']); ?></div>
                         </div>
                     <?php endforeach; ?>
                 </div>
                 <div class="table-responsive">
                     <table class="timetable-table">
                         <thead><tr><th>Time Slot</th><th>Class</th><th>Subject</th></tr></thead>
                         <tbody>
                             <?php foreach ($today_timetable_entries as $entry): ?>
                                 <tr>
                                     <td><?php echo htmlspecialchars($entry['time_slot']); ?></td>
                                     <td><?php echo htmlspecialchars($entry['class_taught']); ?></td>
                                     <td><?php echo htmlspecialchars($entry['subject_taught']); ?></td>
                                 </tr>
                             <?php endforeach; ?>
                         </tbody>
                     </table>
                 </div>
            <?php endif; ?>
             <div class="mt-6 text-center">
                 <a href="./view_my_timetables.php" class="btn btn-secondary">
                     <i class="fas fa-calendar-alt mr-2"></i> View Full Timetable
                 </a>
             </div>
        </section>

        <!-- Student Records Section -->
        <section class="content-card mt-8">
            <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-6 text-center">Latest 5 Student Records</h2>
            <div class="mb-6"><input type="text" id="search" class="form-input" placeholder="Search students..."></div>
            <div class="alert alert-info mb-4"><p><?php echo $fetch_students_message; ?></p></div>
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Photo</th><th>User ID</th><th>Name</th><th>Phone</th><th>Class</th><th>Marks (%)</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="photo-cell">
                                         <div class="photo-container">
                                             <?php if (!empty($student['photo_filename'])): ?>
                                                 <img src="./uploads/student_photos/<?php echo htmlspecialchars($student['photo_filename']); ?>" alt="Photo">
                                             <?php else: ?>
                                                 <span><?php echo htmlspecialchars(substr($student['full_name'] ?? 'S', 0, 1)); ?></span>
                                             <?php endif; ?>
                                         </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['current_class'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['current_marks'] ?? 'N/A'); ?></td>
                                    <td class="action-links">
                                        <a href="teacher/edit_student_teacher.php?id=<?php echo htmlspecialchars($student['user_id']); ?>" class="edit-link">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if (count($students) >= 5 && in_array($staff_role, ['principal', 'staff'])): ?>
                <div class="mt-6 text-center">
                    <a href="./view_all_students.php" class="btn btn-secondary"><i class="fas fa-users mr-2"></i> View All Students</a>
                </div>
            <?php endif; ?>
        </section>

         <p class="mt-8 text-center text-gray-600">
            <a href="./logout.php" class="text-red-600 hover:underline font-medium">Logout</a>
         </p>
    </main>

    <!-- NEW: JAVASCRIPT FOR HOVER EFFECT & SEARCH -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Sliding Hover Effect for Stat Cards ---
        const statsGrid = document.getElementById('stats-grid');
        const hoverBg = document.getElementById('hover-effect-bg');
        const cards = statsGrid.querySelectorAll('.dashboard-stats-card');

        if (statsGrid && hoverBg && cards.length > 0) {
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    const cardRect = card.getBoundingClientRect();
                    const gridRect = statsGrid.getBoundingClientRect();

                    // Calculate position relative to the grid container
                    const top = cardRect.top - gridRect.top;
                    const left = cardRect.left - gridRect.left;

                    // Apply styles to the background element
                    hoverBg.style.width = `${cardRect.width}px`;
                    hoverBg.style.height = `${cardRect.height}px`;
                    hoverBg.style.transform = `translate(${left}px, ${top}px)`;
                    hoverBg.style.opacity = '1';
                });
            });

            // Hide the effect when mouse leaves the entire grid area
            statsGrid.addEventListener('mouseleave', () => {
                hoverBg.style.opacity = '0';
            });
        }


        // --- Simple Client-Side Search Functionality ---
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = this.value.toUpperCase();
                let table = document.querySelector('.student-table');
                if (!table) return;

                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header
                    let row = tr[i];
                    let cells = row.getElementsByTagName('td');
                    let match = false;
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j] && cells[j].innerText.toUpperCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                    row.style.display = match ? "" : "none";
                }
            });
        }

    });
    </script>
</body>
</html>

<?php require_once './teacher_footer.php'; ?>