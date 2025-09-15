<?php
// School/admin/create_class_timetable.php

// Start the session
session_start();

require_once "../config.php";

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can create or edit class timetables.</p>";
    header("location: ./admin_dashboard.php");
    exit;
}

// Set the page title
$pageTitle = "Create/Edit Class Timetable";

// Initialize variables
$classes = [];
$teachers = [];
$selected_class = "";
$timetable_data = [];
$operation_message = "";
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$max_periods = 8;

// --- Fetch all distinct classes and teachers ---
if ($link) {
    // Fetch classes
    $sql_classes = "SELECT DISTINCT current_class FROM students ORDER BY current_class ASC";
    if ($result_classes = mysqli_query($link, $sql_classes)) {
        while ($row = mysqli_fetch_assoc($result_classes)) {
            $classes[] = $row['current_class'];
        }
    } else {
        $operation_message .= "<p class='text-red-600'>Error fetching class list.</p>";
    }

    // Fetch teachers
    $sql_teachers = "SELECT staff_name FROM staff WHERE role = 'teacher' ORDER BY staff_name ASC";
    if ($result_teachers = mysqli_query($link, $sql_teachers)) {
        while ($row = mysqli_fetch_assoc($result_teachers)) {
            $teachers[] = $row['staff_name'];
        }
    } else {
        $operation_message .= "<p class='text-red-600'>Error fetching teacher list.</p>";
    }
} else {
    $operation_message = "<p class='text-red-600'>Database connection failed.</p>";
}

// --- Handle form submission (both for fetching and saving) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Handle class selection to view existing timetable ---
    if (isset($_POST['select_class'])) {
        $selected_class = trim($_POST['class_name']);
        if (!empty($selected_class)) {
            $sql_fetch_timetable = "SELECT day_of_week, period_number, subject_name, start_time, end_time, teacher_name 
                                    FROM class_timetables 
                                    WHERE class_name = ? 
                                    ORDER BY period_number ASC";
            if ($stmt_fetch = mysqli_prepare($link, $sql_fetch_timetable)) {
                mysqli_stmt_bind_param($stmt_fetch, "s", $selected_class);
                mysqli_stmt_execute($stmt_fetch);
                $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                $raw_data = mysqli_fetch_all($result_fetch, MYSQLI_ASSOC);
                
                // Structure the data for easy form population
                foreach ($raw_data as $period) {
                    $timetable_data[$period['day_of_week']][$period['period_number']] = $period;
                }
                mysqli_stmt_close($stmt_fetch);
            }
        }
    }

    // --- Handle saving the timetable ---
    if (isset($_POST['save_timetable'])) {
        $selected_class = trim($_POST['class_name']);
        $timetable_input = $_POST['timetable'] ?? [];

        if (empty($selected_class)) {
            $operation_message = "<p class='text-red-600'>Error: Class name was not submitted. Cannot save timetable.</p>";
        } else {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for efficiency
            // *** MODIFIED SQL: Explicitly set updated_at = NOW() to force timestamp update ***
            $sql_save = "INSERT INTO class_timetables (class_name, day_of_week, period_number, subject_name, start_time, end_time, teacher_name) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         subject_name = VALUES(subject_name), 
                         start_time = VALUES(start_time), 
                         end_time = VALUES(end_time), 
                         teacher_name = VALUES(teacher_name),
                         updated_at = NOW()";
            
            if ($stmt_save = mysqli_prepare($link, $sql_save)) {
                $success_count = 0;
                $error_count = 0;

                foreach ($days_of_week as $day) {
                    for ($period = 1; $period <= $max_periods; $period++) {
                        $subject = trim($timetable_input[$day][$period]['subject_name'] ?? '');
                        $start_time = trim($timetable_input[$day][$period]['start_time'] ?? '');
                        $end_time = trim($timetable_input[$day][$period]['end_time'] ?? '');
                        $teacher = trim($timetable_input[$day][$period]['teacher_name'] ?? '');

                        // Only insert/update if a subject name is provided
                        if (!empty($subject)) {
                            mysqli_stmt_bind_param($stmt_save, "ssissss", $selected_class, $day, $period, $subject, $start_time, $end_time, $teacher);
                            if (mysqli_stmt_execute($stmt_save)) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                    }
                }
                
                if ($error_count > 0) {
                    $operation_message = "<p class='text-red-600'>Timetable saved with {$error_count} errors. {$success_count} periods were saved successfully.</p>";
                } else {
                     $_SESSION['operation_message'] = "<p class='text-green-600'>Timetable for class '{$selected_class}' saved successfully. {$success_count} periods recorded.</p>";
                     header("location: ./admin_dashboard.php");
                     exit();
                }
                mysqli_stmt_close($stmt_save);
            } else {
                $operation_message = "<p class='text-red-600'>Error preparing the save query.</p>";
            }
        }
    }
}

// Include the header file
require_once "./admin_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Manage Class Timetable</h1>

    <?php if (!empty($operation_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <!-- Class Selection Form -->
    <div class="bg-white p-6 rounded-2xl shadow-lg mb-8">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="class_name" class="block text-lg font-semibold text-gray-700 mb-2">Select a Class to View/Edit</label>
            <div class="flex items-center space-x-4">
                <select name="class_name" id="class_name" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($selected_class == $class) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="select_class" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Load Timetable
                </button>
            </div>
        </form>
    </div>

    <!-- Timetable Entry Form -->
    <?php if (!empty($selected_class)): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($selected_class); ?>">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Editing Timetable for Class: <?php echo htmlspecialchars($selected_class); ?></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Day / Period</th>
                            <?php for ($i = 1; $i <= $max_periods; $i++): ?>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Period <?php echo $i; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($days_of_week as $day): ?>
                            <tr>
                                <td class="px-4 py-4 text-sm font-bold text-gray-800 bg-gray-50"><?php echo $day; ?></td>
                                <?php for ($i = 1; $i <= $max_periods; $i++): 
                                    $period_data = $timetable_data[$day][$i] ?? [];
                                ?>
                                    <td class="p-2 text-sm text-center">
                                        <input type="text" name="timetable[<?php echo $day; ?>][<?php echo $i; ?>][subject_name]" placeholder="Subject" class="w-full mb-1 text-sm border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($period_data['subject_name'] ?? ''); ?>">
                                        <input type="time" name="timetable[<?php echo $day; ?>][<?php echo $i; ?>][start_time]" class="w-full mb-1 text-xs border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($period_data['start_time'] ?? ''); ?>">
                                        <input type="time" name="timetable[<?php echo $day; ?>][<?php echo $i; ?>][end_time]" class="w-full mb-1 text-xs border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($period_data['end_time'] ?? ''); ?>">
                                        
                                        <select name="timetable[<?php echo $day; ?>][<?php echo $i; ?>][teacher_name]" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                                            <option value="">-- Teacher --</option>
                                            <?php foreach ($teachers as $teacher_name): ?>
                                                <option value="<?php echo htmlspecialchars($teacher_name); ?>" <?php echo (isset($period_data['teacher_name']) && $period_data['teacher_name'] == $teacher_name) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($teacher_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 bg-gray-50 text-right">
                <button type="submit" name="save_timetable" class="px-8 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Save Timetable
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php
// Include the footer file
require_once "./admin_footer.php";
?>
