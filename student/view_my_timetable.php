<?php
// School/student/view_my_timetable.php

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

// Set the page title
$pageTitle = "My Class Timetable";

// Initialize variables
$student_class = '';
$timetable_data = [];
$error_message = '';
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; // Define the order of days

// Fetch the student's current class first
if ($link) {
    $student_id = $_SESSION['id'];
    $sql_get_class = "SELECT current_class FROM students WHERE user_id = ?";
    
    if ($stmt_get_class = mysqli_prepare($link, $sql_get_class)) {
        mysqli_stmt_bind_param($stmt_get_class, "i", $student_id);
        if (mysqli_stmt_execute($stmt_get_class)) {
            $result_class = mysqli_stmt_get_result($stmt_get_class);
            if ($row_class = mysqli_fetch_assoc($result_class)) {
                $student_class = $row_class['current_class'];
            } else {
                $error_message = "Could not find your student record.";
            }
        } else {
            $error_message = "Error fetching your class details.";
        }
        mysqli_stmt_close($stmt_get_class);
    } else {
        $error_message = "Error preparing to fetch your class.";
    }

    // If we have the class, fetch the timetable
    if (!empty($student_class) && empty($error_message)) {
        $sql_timetable = "SELECT day_of_week, period_number, subject_name, start_time, end_time, teacher_name 
                          FROM class_timetables 
                          WHERE class_name = ? 
                          ORDER BY day_of_week, period_number ASC";
        
        if ($stmt_timetable = mysqli_prepare($link, $sql_timetable)) {
            mysqli_stmt_bind_param($stmt_timetable, "s", $student_class);
            if (mysqli_stmt_execute($stmt_timetable)) {
                $result_timetable = mysqli_stmt_get_result($stmt_timetable);
                $raw_timetable = mysqli_fetch_all($result_timetable, MYSQLI_ASSOC);

                // Process data into a structured array: [Day][Period] = Data
                foreach ($raw_timetable as $period) {
                    $timetable_data[$period['day_of_week']][$period['period_number']] = $period;
                }

            } else {
                $error_message = "Error fetching the timetable for your class.";
                error_log("Timetable view error: " . mysqli_stmt_error($stmt_timetable));
            }
            mysqli_stmt_close($stmt_timetable);
        } else {
            $error_message = "Error preparing the timetable query.";
        }
    }

    mysqli_close($link);
} else {
    $error_message = "Database connection failed.";
}

// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Class Timetable</h1>
            <?php if(!empty($student_class)): ?>
                <p class="text-lg text-gray-600">Class: <span class="font-semibold"><?php echo htmlspecialchars($student_class); ?></span></p>
            <?php endif; ?>
        </div>
        <a href="./student_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">An Error Occurred</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php elseif (empty($timetable_data)): ?>
        <div class="text-center bg-white p-12 rounded-2xl shadow-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Timetable Not Available</h3>
            <p class="mt-1 text-sm text-gray-500">The timetable for your class has not been uploaded yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Day</th>
                            <?php for ($i = 1; $i <= 8; $i++): // Assuming max 8 periods ?>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Period <?php echo $i; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($days_of_week as $day): ?>
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-800"><?php echo $day; ?></td>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if (isset($timetable_data[$day][$i])): 
                                            $period = $timetable_data[$day][$i];
                                        ?>
                                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($period['subject_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars(date('g:i A', strtotime($period['start_time']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($period['end_time']))); ?></div>
                                            <?php if(!empty($period['teacher_name'])): ?>
                                                <div class="text-xs text-indigo-600 mt-1"><?php echo htmlspecialchars($period['teacher_name']); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
