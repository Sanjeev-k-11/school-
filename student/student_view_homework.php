<?php
// School/student/student_view_homework.php

session_start();
require_once "../config.php";

// Security check: Only students can view homework
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "My Homework";
$student_id = $_SESSION['id'];

// Check for database connection error
if ($link->connect_error) {
    die("ERROR: Could not connect. " . $link->connect_error);
}

// Fetch the student's class from the 'current_class' column
$student_class = '';
$sql_get_class = "SELECT current_class FROM students WHERE user_id = ?"; // Corrected column name and user_id
if ($stmt_class = mysqli_prepare($link, $sql_get_class)) {
    mysqli_stmt_bind_param($stmt_class, "i", $student_id);
    if (mysqli_stmt_execute($stmt_class)) {
        $result_class = mysqli_stmt_get_result($stmt_class);
        if ($row_class = mysqli_fetch_assoc($result_class)) {
            $student_class = $row_class['current_class'];
        }
    }
    mysqli_stmt_close($stmt_class);
}

// Fetch homework assignments for the specific student's class
$sql_get_homework = "SELECT * FROM homework WHERE class_name = ? ORDER BY due_date ASC";
$homework_assignments = [];

if (!empty($student_class)) {
    if ($stmt = mysqli_prepare($link, $sql_get_homework)) {
        mysqli_stmt_bind_param($stmt, "s", $student_class);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $homework_assignments[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
// Include the student header
require_once "./student_header.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .hidden-mobile {
            display: none;
        }
        @media (min-width: 768px) {
            .hidden-mobile {
                display: block;
            }
        }
        .block-mobile {
            display: block;
        }
        @media (min-width: 768px) {
            .block-mobile {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">



<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Homework for Class: <?php echo htmlspecialchars($student_class); ?></h1>
    <?php if (isset($_SESSION['operation_message'])) { echo $_SESSION['operation_message']; unset($_SESSION['operation_message']); } ?>

    <?php if (empty($homework_assignments)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">No homework assignments have been posted yet for your class.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attachments</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($homework_assignments as $homework): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($homework['teacher_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($homework['subject_name']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($homework['title']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo nl2br(htmlspecialchars($homework['description'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y', strtotime($homework['due_date']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if (!empty($homework['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($homework['file_path']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">View File</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    });
</script>

</body>
</html>

<?php
// Include the footer
require_once "./student_footer.php";
?>
