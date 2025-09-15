<?php
// School/student/student_view_quizzes.php

session_start();
require_once "../config.php";

// Security check: Only students can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "My Quizzes";
$student_id = $_SESSION['id'];

// Check for database connection error
if ($link->connect_error) {
    die("ERROR: Could not connect. " . $link->connect_error);
}

// Fetch the student's class from the 'current_class' column
$student_class = '';
$sql_get_class = "SELECT current_class FROM students WHERE user_id = ?";
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

// Fetch all quizzes for the student's class
$sql_get_quizzes = "SELECT id, subject_name, title, teacher_name, created_at FROM quizzes WHERE class_name = ? ORDER BY created_at DESC";
$quizzes = [];

if (!empty($student_class)) {
    if ($stmt = mysqli_prepare($link, $sql_get_quizzes)) {
        mysqli_stmt_bind_param($stmt, "s", $student_class);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $quizzes[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
// Include the header
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
<body class="bg-gray-100 font-sans">

    <!-- Mobile menu, hidden by default -->
    <div id="mobile-menu" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="student_dashboard.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
            <a href="student_view_homework.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">My Homework</a>
            <a href="student_view_quizzes.php" class="text-white bg-gray-900 block px-3 py-2 rounded-md text-base font-medium" aria-current="page">My Quizzes</a>
            <a href="../logout.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Logout</a>
        </div>
    </div>
</nav>

<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Quizzes for Class: <?php echo htmlspecialchars($student_class); ?></h1>

    <?php if (empty($quizzes)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">No quizzes have been assigned to your class yet.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($quizzes as $quiz): 
                        // Check if the student has already taken this quiz
                        $score = null;
                        $sql_check_submission = "SELECT score FROM quiz_submissions WHERE quiz_id = ? AND student_id = ?";
                        if ($stmt_check = mysqli_prepare($link, $sql_check_submission)) {
                            mysqli_stmt_bind_param($stmt_check, "ii", $quiz['id'], $student_id);
                            if (mysqli_stmt_execute($stmt_check)) {
                                $result_check = mysqli_stmt_get_result($stmt_check);
                                if ($row_check = mysqli_fetch_assoc($result_check)) {
                                    $score = $row_check['score'];
                                }
                            }
                            mysqli_stmt_close($stmt_check);
                        }
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quiz['teacher_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($score !== null): ?>
                                <span class="inline-block px-4 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-md">Score: <?php echo $score; ?></span>
                            <?php else: ?>
                                <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Take Quiz</a>
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
