<?php
// School/admin/view_all_timetables.php

// Start the session
session_start();

require_once "../config.php";

// Check if user is logged in and is ADMIN or Principal
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'principal'])) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. You do not have permission to view timetables.</p>";
    header("location: ./admin_dashboard.php");
    exit;
}

// Set the page title
$pageTitle = "View All Timetables";

// Initialize variables
$classes = [];
$teachers = [];
$selected_class = "";
$selected_teacher = "";
$timetable_data = []; // Holds the final structured data for display
$operation_message = "";
$view_by = $_POST['view_by'] ?? 'class'; // Default view, persists on POST
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$max_periods = 8;

// --- Fetch all distinct classes and teachers ---
if ($link) {
    // Fetch classes
    $sql_classes = "SELECT DISTINCT current_class FROM students WHERE current_class IS NOT NULL AND current_class != '' ORDER BY current_class ASC";
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

// --- Handle form submission for filtering ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_timetable'])) {
    if ($view_by === 'class') {
        $selected_class = trim($_POST['class_name'] ?? '');
        if (!empty($selected_class)) {
            $sql_fetch = "SELECT day_of_week, period_number, subject_name, start_time, end_time, teacher_name FROM class_timetables WHERE class_name = ? ORDER BY period_number ASC";
            if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
                mysqli_stmt_bind_param($stmt_fetch, "s", $selected_class);
                mysqli_stmt_execute($stmt_fetch);
                $result = mysqli_stmt_get_result($stmt_fetch);
                $raw_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
                foreach ($raw_data as $period) {
                    $timetable_data[$period['day_of_week']][$period['period_number']] = $period;
                }
                mysqli_stmt_close($stmt_fetch);
            }
        }
    } elseif ($view_by === 'teacher') {
        $selected_teacher = trim($_POST['teacher_name'] ?? '');
        if (!empty($selected_teacher)) {
            $sql_fetch = "SELECT day_of_week, period_number, subject_name, start_time, end_time, class_name FROM class_timetables WHERE teacher_name = ? ORDER BY period_number ASC";
             if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
                mysqli_stmt_bind_param($stmt_fetch, "s", $selected_teacher);
                mysqli_stmt_execute($stmt_fetch);
                $result = mysqli_stmt_get_result($stmt_fetch);
                $raw_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
                foreach ($raw_data as $period) {
                    $timetable_data[$period['day_of_week']][$period['period_number']] = $period;
                }
                mysqli_stmt_close($stmt_fetch);
            }
        }
    }
}

// Include the header file
require_once "./admin_header.php";
?>
<style>
    @media print {
        body * { visibility: hidden; }
        #printableArea, #printableArea * { visibility: visible; }
        #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewByRadios = document.querySelectorAll('input[name="view_by"]');
    const classSelector = document.getElementById('class_selector_div');
    const teacherSelector = document.getElementById('teacher_selector_div');

    function toggleSelectors() {
        if (document.querySelector('input[name="view_by"]:checked').value === 'teacher') {
            classSelector.style.display = 'none';
            teacherSelector.style.display = 'block';
        } else {
            classSelector.style.display = 'block';
            teacherSelector.style.display = 'none';
        }
    }
    viewByRadios.forEach(radio => radio.addEventListener('change', toggleSelectors));
    toggleSelectors(); // Initial check
});
</script>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">View Timetables</h1>

    <?php if (!empty($operation_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><?php echo $operation_message; ?></div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="bg-white p-6 rounded-2xl shadow-lg mb-8 no-print">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label class="block text-lg font-semibold text-gray-700 mb-2">Filter Timetable By</label>
            <div class="flex items-center space-x-6 mb-4">
                <label class="flex items-center"><input type="radio" name="view_by" value="class" class="form-radio" <?php echo ($view_by === 'class') ? 'checked' : ''; ?>> <span class="ml-2">Class</span></label>
                <label class="flex items-center"><input type="radio" name="view_by" value="teacher" class="form-radio" <?php echo ($view_by === 'teacher') ? 'checked' : ''; ?>> <span class="ml-2">Teacher</span></label>
            </div>

            <div id="class_selector_div">
                <select name="class_name" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $class): ?><option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($selected_class == $class) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class); ?></option><?php endforeach; ?>
                </select>
            </div>
            
            <div id="teacher_selector_div">
                 <select name="teacher_name" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Select Teacher --</option>
                    <?php foreach ($teachers as $teacher): ?><option value="<?php echo htmlspecialchars($teacher); ?>" <?php echo ($selected_teacher == $teacher) ? 'selected' : ''; ?>><?php echo htmlspecialchars($teacher); ?></option><?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="filter_timetable" class="mt-4 px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">View Timetable</button>
        </form>
    </div>

    <!-- Timetable Display Area -->
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_timetable'])): ?>
        <div id="printableArea">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-800">
                        <?php 
                        if ($view_by === 'class' && !empty($selected_class)) echo "Timetable for Class: " . htmlspecialchars($selected_class);
                        if ($view_by === 'teacher' && !empty($selected_teacher)) echo "Timetable for Teacher: " . htmlspecialchars($selected_teacher);
                        ?>
                    </h2>
                </div>
                <?php if (empty($timetable_data)): ?>
                    <p class="p-6 text-center text-gray-500">No timetable has been set for the selected <?php echo $view_by; ?>.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Day</th>
                                <?php for ($i = 1; $i <= $max_periods; $i++): ?>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Period <?php echo $i; ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($days_of_week as $day): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-800"><?php echo $day; ?></td>
                                    <?php for ($i = 1; $i <= $max_periods; $i++): ?>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                            <?php if (isset($timetable_data[$day][$i])): $period = $timetable_data[$day][$i]; ?>
                                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($period['subject_name']); ?></div>
                                                <div class="text-xs text-gray-600"><?php echo ($view_by === 'class') ? htmlspecialchars($period['teacher_name']) : htmlspecialchars($period['class_name']); ?></div>
                                                <div class="text-xs text-indigo-600 mt-1"><?php echo htmlspecialchars(date('g:i A', strtotime($period['start_time']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($period['end_time']))); ?></div>
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
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($timetable_data)): ?>
        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">
                Print Timetable
            </button>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Include the footer file
require_once "./admin_footer.php";
?>
