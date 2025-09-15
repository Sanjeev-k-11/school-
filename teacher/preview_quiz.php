<?php
// School/teacher/preview_quiz.php

session_start();
require_once "../config.php";

// Security check: Only teachers can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Quiz Preview";
$teacher_id = $_SESSION['id'];
$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid quiz ID.</p>";
    header("location: view_quizzes.php");
    exit;
}

$sql_get_quiz = "SELECT * FROM quizzes WHERE id = ? AND teacher_id = ?";
$quiz_details = null;

if ($stmt = mysqli_prepare($link, $sql_get_quiz)) {
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $teacher_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $quiz_details = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$quiz_details) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Quiz not found or you do not have permission to view it.</p>";
    header("location: view_quizzes.php");
    exit;
}

$quiz_data = json_decode($quiz_details['quiz_data'], true);
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
    // INCLUDE NAVBAR
    // IMPORTANT: Verify the path to your staff_navbar.php file
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



<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Quiz: <?php echo htmlspecialchars($quiz_details['title']); ?></h1>
    <p class="text-gray-600 mb-6">Subject: <?php echo htmlspecialchars($quiz_details['subject_name']); ?> | Class: <?php echo htmlspecialchars($quiz_details['class_name']); ?></p>

    <div class="space-y-6">
        <?php foreach ($quiz_data as $index => $question_item): ?>
            <div class="p-6 bg-white rounded-lg shadow-md">
                <p class="text-lg font-bold text-gray-800 mb-4">Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars($question_item['question_text']); ?></p>
                <div class="space-y-2">
                    <?php foreach ($question_item['options'] as $option_index => $option_text): ?>
                        <div class="flex items-start">
                            <span class="mr-2 text-sm font-medium text-gray-600"><?php echo chr(65 + $option_index); ?>.</span>
                            <span class="text-gray-800 flex-grow"><?php echo htmlspecialchars($option_text); ?></span>
                            <?php if ($option_index == $question_item['correct_answer']): ?>
                                <span class="ml-auto text-green-500 font-bold">Correct Answer</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-8 flex justify-end">
        <a href="view_quizzes.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300">Back to Quizzes</a>
    </div>
</div>

</body>
</html>

<?php
// Include the footer
require_once "./teacher_footer.php";
?>
