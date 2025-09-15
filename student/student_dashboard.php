<?php
// School/student/student_dashboard.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php";

// Check if the user is logged in and is a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in as a student to view this page.</p>";
    header("location: ../login.php");
    exit;
}

// Get the logged-in student's ID from the session
$student_id = $_SESSION['id'];
$student_name = $_SESSION['full_name'] ?? 'Student'; // Fallback name

// Set the page title
$pageTitle = "Student Dashboard";

// --- Variables for Messages ---
$operation_message = "";
if (isset($_SESSION['operation_message'])) {
    $operation_message = $_SESSION['operation_message'];
    unset($_SESSION['operation_message']);
}

// --- Initialize Data Arrays and Variables ---
$student_details = null;
$total_fees_due = 0;
$student_class = 'N/A';
$exam_summary = [];
$events = [];
$todays_timetable = [];
$fees_to_display = [];
$today = date('l'); // Get current day name, e.g., "Monday"

// --- Fetch Data from Database ---
if ($link === false) {
    $operation_message = "<p class='text-red-600'>Database connection error. Could not load dashboard data.</p>";
    error_log("Student Dashboard DB connection failed: " . mysqli_connect_error());
} else {
    // 1. Fetch Student's Personal Details
    $sql_student_details = "SELECT user_id, virtual_id, full_name, current_class, photo_filename FROM students WHERE user_id = ? AND is_active = 1";
    if ($stmt_details = mysqli_prepare($link, $sql_student_details)) {
        mysqli_stmt_bind_param($stmt_details, "i", $student_id);
        if (mysqli_stmt_execute($stmt_details)) {
            $result_details = mysqli_stmt_get_result($stmt_details);
            if ($student_details = mysqli_fetch_assoc($result_details)) {
                $student_class = $student_details['current_class'] ?? 'N/A';
            } else {
                $_SESSION['operation_message'] = "<p class='text-red-600'>Your account could not be found or is inactive.</p>";
                session_unset(); session_destroy(); header("location: ../login.php"); exit;
            }
        }
        mysqli_stmt_close($stmt_details);
    }

    // 2. Fetch Student's Fee History and Calculate Total Due
    $sql_fees = "SELECT fee_month, fee_year, amount_due, amount_paid, payment_date FROM student_monthly_fees WHERE student_id = ? ORDER BY fee_year DESC, fee_month DESC";
    if ($stmt_fees = mysqli_prepare($link, $sql_fees)) {
        mysqli_stmt_bind_param($stmt_fees, "i", $student_id);
        if (mysqli_stmt_execute($stmt_fees)) {
            $result_fees = mysqli_stmt_get_result($stmt_fees);
            $fee_history = mysqli_fetch_all($result_fees, MYSQLI_ASSOC);
            
            $current_month = date('n');
            $current_year = date('Y');

            foreach ($fee_history as $fee) {
                $balance = (float)$fee['amount_due'] - (float)$fee['amount_paid'];
                if ($balance > 0) { 
                    $total_fees_due += $balance;
                    $fees_to_display[] = $fee; // Add to display if there's a balance
                } elseif ($fee['fee_month'] == $current_month && $fee['fee_year'] == $current_year) {
                    $fees_to_display[] = $fee; // Also add to display if it's the current month, even if paid
                }
            }
        }
        mysqli_stmt_close($stmt_fees);
    }

    // 3. Fetch and Process Student's Exam Results for Summary
    $sql_exams = "SELECT exam_name, marks_obtained, max_marks FROM student_exam_results WHERE student_id = ?";
    if ($stmt_exams = mysqli_prepare($link, $sql_exams)) {
        mysqli_stmt_bind_param($stmt_exams, "i", $student_id);
        if (mysqli_stmt_execute($stmt_exams)) {
            $result_exams = mysqli_stmt_get_result($stmt_exams);
            $raw_results = mysqli_fetch_all($result_exams, MYSQLI_ASSOC);
            
            $grouped_results = [];
            foreach ($raw_results as $result) {
                $exam_name = $result['exam_name'];
                if (!isset($grouped_results[$exam_name])) {
                    $grouped_results[$exam_name] = ['total_marks' => 0, 'max_marks' => 0];
                }
                $grouped_results[$exam_name]['total_marks'] += (float)$result['marks_obtained'];
                $grouped_results[$exam_name]['max_marks'] += (float)$result['max_marks'];
            }

            foreach($grouped_results as $exam => $data) {
                if ($data['max_marks'] > 0) {
                    $percentage = ($data['total_marks'] / $data['max_marks']) * 100;
                    $exam_summary[$exam] = round($percentage, 2);
                }
            }
        }
        mysqli_stmt_close($stmt_exams);
    }

    // 4. Fetch Upcoming Events (Limit to 2)
    $sql_events = "SELECT event_name, event_description, event_date_time FROM events WHERE event_date_time >= NOW() ORDER BY event_date_time ASC LIMIT 2";
    if ($result_events = mysqli_query($link, $sql_events)) {
        $events = mysqli_fetch_all($result_events, MYSQLI_ASSOC);
    }

    // 5. Fetch Today's Timetable
    if ($student_class !== 'N/A') {
        $sql_timetable = "SELECT period_number, subject_name, start_time, end_time, teacher_name FROM class_timetables WHERE class_name = ? AND day_of_week = ? ORDER BY period_number ASC";
        if ($stmt_timetable = mysqli_prepare($link, $sql_timetable)) {
            mysqli_stmt_bind_param($stmt_timetable, "ss", $student_class, $today);
            if (mysqli_stmt_execute($stmt_timetable)) {
                $result_timetable = mysqli_stmt_get_result($stmt_timetable);
                $todays_timetable = mysqli_fetch_all($result_timetable, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_timetable);
        }
    }

    mysqli_close($link);
}

// Default paths for avatars
$default_student_avatar_path = '../assets/images/default_student_avatar.png';
$photo_url = $student_details['photo_filename'] ?? $default_student_avatar_path;

// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if (!empty($operation_message)):
        $is_error = (strpos(strtolower(strip_tags($operation_message)), 'denied') !== false || strpos(strtolower(strip_tags($operation_message)), 'error') !== false);
        $message_class = $is_error ? 'bg-red-100 border-red-300 text-red-800' : 'bg-green-100 border-green-300 text-green-800';
    ?>
        <div class="p-3 rounded-md border text-center text-sm mb-6 <?php echo $message_class; ?>" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <!-- Welcome Header -->
    <div class="bg-white p-6 rounded-xl shadow-md mb-8 flex items-center space-x-4">
        <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Your Photo" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($student_name); ?>!</h1>
            <p class="text-gray-600">Here is your dashboard for today, <?php echo date('F j, Y'); ?>.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="p-6 rounded-xl shadow-md border-b-4 border-blue-500 bg-white">
            <h3 class="text-base font-medium text-gray-600">My Class</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($student_class); ?></p>
        </div>
        <div class="p-6 rounded-xl shadow-md border-b-4 <?php echo ($total_fees_due > 0) ? 'border-red-500' : 'border-green-500'; ?> bg-white">
            <h3 class="text-base font-medium text-gray-600">Outstanding Fees</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1">₹ <?php echo number_format($total_fees_due, 2); ?></p>
        </div>
        <div class="p-6 rounded-xl shadow-md border-b-4 border-purple-500 bg-white">
            <h3 class="text-base font-medium text-gray-600">Virtual ID</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($student_details['virtual_id'] ?? 'N/A'); ?></p>
        </div>
        <div class="p-6 rounded-xl shadow-md border-b-4 border-amber-500 bg-white">
            <h3 class="text-base font-medium text-gray-600">Upcoming Events</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo count($events); ?></p>
        </div>
    </div>
    
    <div class="flex flex-wrap lg:flex-nowrap gap-8">
        <!-- Left Column -->
        <div class="flex-1 w-full lg:w-2/3 min-w-0 flex flex-col gap-8">
            <!-- Today's Timetable -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Today's Timetable (<?php echo htmlspecialchars($today); ?>)</h2>
                <?php if (!empty($todays_timetable)): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach($todays_timetable as $period): ?>
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($period['subject_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($period['teacher_name']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-indigo-600"><?php echo htmlspecialchars(date('g:i A', strtotime($period['start_time']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($period['end_time']))); ?></p>
                                <p class="text-xs text-gray-400">Period <?php echo htmlspecialchars($period['period_number']); ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-gray-500 p-4 border rounded-md bg-gray-50">No classes scheduled for today.</p>
                <?php endif; ?>
                 <div class="mt-4 text-right">
                    <a href="./view_my_timetable.php" class="text-indigo-600 hover:underline text-sm font-medium">View Full Timetable &rarr;</a>
                </div>
            </div>

             <!-- Exam Result Summary -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Exam Result Summary</h2>
                <?php if (!empty($exam_summary)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach($exam_summary as $exam => $percentage): ?>
                        <div class="p-4 rounded-lg bg-gray-50 border">
                            <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($exam); ?></p>
                            <p class="text-2xl font-bold <?php echo $percentage >= 40 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo htmlspecialchars($percentage); ?>%</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-500 p-4 border rounded-md bg-gray-50">No exam results available yet.</p>
                <?php endif; ?>
                 <div class="mt-4 text-right">
                    <a href="./view_my_exam_results.php" class="text-indigo-600 hover:underline text-sm font-medium">View Detailed Results &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="w-full lg:w-1/3 min-w-[250px] max-w-md flex flex-col gap-8">
            <!-- Upcoming Events -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col h-full">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Events & Announcements</h2>
                <?php if (!empty($events)): ?>
                    <ul class="list-none p-0 m-0 divide-y divide-gray-200">
                        <?php foreach ($events as $event): ?>
                            <li class="py-3">
                                <h4 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                <div class="text-xs text-gray-600 mb-2">
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full font-medium">
                                        <?php echo htmlspecialchars(date('D, M j, Y, g:i A', strtotime($event['event_date_time']))); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 leading-normal break-words"><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-gray-500 p-4 border rounded-md bg-gray-50">No upcoming events or announcements.</p>
                <?php endif; ?>
                 <div class="mt-auto pt-4 text-right">
                    <a href="./view_announcements.php" class="text-indigo-600 hover:underline text-sm font-medium">View All &rarr;</a>
                </div>
            </div>
            
            <!-- Fee Payment History -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Fee Status</h2>
                <?php if (!empty($fees_to_display)): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($fees_to_display as $fee):
                            $balance = (float)$fee['amount_due'] - (float)$fee['amount_paid'];
                        ?>
                        <li class="py-3">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-700"><?php echo htmlspecialchars(date("F Y", mktime(0, 0, 0, $fee['fee_month'], 1, $fee['fee_year']))); ?></p>
                                <?php if($balance > 0): ?>
                                    <span class="px-2 py-1 text-xs font-bold text-red-800 bg-red-100 rounded-full">DUE</span>
                                <?php else: ?>
                                     <span class="px-2 py-1 text-xs font-bold text-green-800 bg-green-100 rounded-full">PAID</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Balance: <span class="font-semibold <?php echo $balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">₹ <?php echo number_format($balance, 2); ?></span></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-gray-500 p-4 border rounded-md bg-gray-50">No outstanding or recent fees.</p>
                <?php endif; ?>
                <div class="mt-4 text-right">
                    <a href="./view_my_fees.php" class="text-indigo-600 hover:underline text-sm font-medium">View Full History &rarr;</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once "./student_footer.php";
?>
