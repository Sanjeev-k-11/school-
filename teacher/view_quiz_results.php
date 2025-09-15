<?php
// School/teacher/view_quiz_results.php

session_start();
require_once "../config.php";

// Security check: Only teachers can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Quiz Results";
$teacher_id = $_SESSION['id'];
$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid quiz ID.</p>";
    header("location: view_quizzes.php");
    exit;
}

// Fetch quiz details to get title, class, and total questions
$sql_get_quiz_details = "SELECT title, class_name, quiz_data FROM quizzes WHERE id = ? AND teacher_id = ?";
$quiz_details = null;
$total_questions = 0;

if ($stmt = mysqli_prepare($link, $sql_get_quiz_details)) {
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $teacher_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $quiz_details = $row;
            $quiz_data = json_decode($row['quiz_data'], true);
            $total_questions = count($quiz_data);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$quiz_details) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Quiz not found or you do not have permission to view results.</p>";
    header("location: view_quizzes.php");
    exit;
}

// Fetch all submissions for this quiz
$sql_get_submissions = "SELECT student_id, student_name, score, submitted_at FROM quiz_submissions WHERE quiz_id = ? ORDER BY submitted_at DESC";
$submissions = [];

if ($stmt = mysqli_prepare($link, $sql_get_submissions)) {
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $submissions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="mt-12">
    
    <?php 
    $navbar_path = "./staff_navbar.php"; // Assuming it's in the same directory as staff_dashboard.php (School/)

    if (file_exists($navbar_path)) {
        require_once $navbar_path;
    } else {
        // Basic fallback if navbar file is missing
        echo '<div class="alert alert-danger" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"> Staff navbar file not found! Please check the path: <code>' . htmlspecialchars($navbar_path) . '</code></span>
              </div>';
    }
    ?>
</div>



<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Results for: <?php echo htmlspecialchars($quiz_details['title']); ?></h1>
            <p class="text-gray-600">Class: <?php echo htmlspecialchars($quiz_details['class_name']); ?> | Total Questions: <?php echo $total_questions; ?></p>
        </div>
        <a href="view_quizzes.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300">Back to Quizzes</a>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">No students have submitted this quiz yet.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($submission['student_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($submission['student_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($submission['score']); ?> / <?php echo $total_questions; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($submission['submitted_at']))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

<?php
// Include the footer
require_once "./teacher_footer.php";
?>
