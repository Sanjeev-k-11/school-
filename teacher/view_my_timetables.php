<?php
// School/teacher/view_my_timetable.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php";

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in as a teacher to view this page.</p>";
    header("location: ../login.php");
    exit;
}

// Set the page title for the navbar
$pageTitle = "My Timetable";

// Initialize variables
$teacher_name = $_SESSION['name'] ?? 'Teacher'; // Get teacher's name from session
$timetable_data = [];
$error_message = '';
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$max_periods = 8; // Assuming max 8 periods
$current_day = date('l'); // Get the current day name, e.g., "Friday"

// Fetch the timetable for the logged-in teacher
if ($link) {
    $sql_timetable = "SELECT day_of_week, period_number, subject_name, class_name, start_time, end_time 
                      FROM class_timetables 
                      WHERE teacher_name = ? 
                      ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period_number ASC";
    
    if ($stmt_timetable = mysqli_prepare($link, $sql_timetable)) {
        mysqli_stmt_bind_param($stmt_timetable, "s", $teacher_name);
        
        if (mysqli_stmt_execute($stmt_timetable)) {
            $result_timetable = mysqli_stmt_get_result($stmt_timetable);
            $raw_timetable = mysqli_fetch_all($result_timetable, MYSQLI_ASSOC);

            // Process data into a structured array: [Day][Period] = Data
            foreach ($raw_timetable as $period) {
                $timetable_data[$period['day_of_week']][$period['period_number']] = $period;
            }
        } else {
            $error_message = "Error fetching your timetable.";
            error_log("Teacher timetable view error: " . mysqli_stmt_error($stmt_timetable));
        }
        mysqli_stmt_close($stmt_timetable);
    } else {
        $error_message = "Error preparing the timetable query.";
    }
    mysqli_close($link);
} else {
    $error_message = "Database connection failed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printableArea, #printableArea * {
                visibility: visible;
            }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .timetable-cell {
                border: 1px solid #ccc !important;
                background-color: #fff !important;
            }
        }
    </style>
</head>
<body class="min-h-screen">
<div class="mt-14">
<?php
// Include the shared staff navigation bar
$navbar_path = "./staff_navbar.php";
 if (file_exists($navbar_path)) {
        require_once $navbar_path;
    } else {
        echo '<div class="alert alert-danger" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"> Staff navbar file not found! Check path: `' . htmlspecialchars($navbar_path) . '`</span>
              </div>';
        // In a real application, you might halt execution here or provide a fallback navbar
    }
?>
</div>
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div id="printableArea">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Weekly Timetable</h1>
                <p class="text-lg text-gray-600">Teacher: <span class="font-semibold"><?php echo htmlspecialchars($teacher_name); ?></span></p>
            </div>
            <a href="./teacher_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium no-print">&larr; Back to Dashboard</a>
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
                <h3 class="mt-2 text-lg font-medium text-gray-900">Timetable Not Assigned</h3>
                <p class="mt-1 text-sm text-gray-500">Your weekly schedule has not been assigned yet. Please check back later or contact the administration.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider border border-gray-200">Day</th>
                                <?php for ($i = 1; $i <= $max_periods; $i++): ?>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider border border-gray-200">Period <?php echo $i; ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php foreach ($days_of_week as $day): 
                                $is_current_day = ($day === $current_day);
                            ?>
                                <tr class="<?php echo $is_current_day ? 'bg-indigo-50' : ''; ?>">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-800 border border-gray-200"><?php echo $day; ?></td>
                                    <?php for ($i = 1; $i <= $max_periods; $i++): ?>
                                        <td class="p-2 whitespace-nowrap text-sm text-center border border-gray-200 timetable-cell">
                                            <?php if (isset($timetable_data[$day][$i])): 
                                                $period = $timetable_data[$day][$i];
                                            ?>
                                                <div class="p-2 rounded-lg <?php echo $is_current_day ? 'bg-white shadow' : 'bg-gray-50'; ?>">
                                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($period['class_name']); ?></div>
                                                    <div class="text-xs text-gray-600"><?php echo htmlspecialchars($period['subject_name']); ?></div>
                                                    <div class="text-xs text-indigo-600 mt-1 font-medium"><?php echo htmlspecialchars(date('g:i A', strtotime($period['start_time']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($period['end_time']))); ?></div>
                                                </div>
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
    <?php if (!empty($timetable_data)): ?>
    <div class="mt-6 text-center no-print">
        <button onclick="window.print()" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Print Timetable
        </button>
    </div>
    <?php endif; ?>
</div>

<?php
require_once "./teacher_footer.php";
?>
