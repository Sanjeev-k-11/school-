<?php
// School/student/take_quiz.php

session_start();
require_once "../config.php";

// Security check: Only students can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Take Quiz";
$student_id = $_SESSION['id'];
$student_name = $_SESSION['name'];

// Get quiz ID from URL
$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid quiz ID.</p>";
    header("location: student_view_quizzes.php");
    exit;
}

// Check if student has already submitted this quiz
$sql_check_submission = "SELECT id FROM quiz_submissions WHERE quiz_id = ? AND student_id = ?";
if ($stmt_check = mysqli_prepare($link, $sql_check_submission)) {
    mysqli_stmt_bind_param($stmt_check, "ii", $quiz_id, $student_id);
    if (mysqli_stmt_execute($stmt_check)) {
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $_SESSION['operation_message'] = "<p class='text-red-600'>You have already submitted this quiz.</p>";
            header("location: student_view_quizzes.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt_check);
}

// Handle quiz submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_answers = $_POST['answers'] ?? [];
    $score = 0;

    // Fetch the correct answers from the database
    $sql_get_correct_answers = "SELECT quiz_data FROM quizzes WHERE id = ?";
    if ($stmt_answers = mysqli_prepare($link, $sql_get_correct_answers)) {
        mysqli_stmt_bind_param($stmt_answers, "i", $quiz_id);
        if (mysqli_stmt_execute($stmt_answers)) {
            $result_answers = mysqli_stmt_get_result($stmt_answers);
            if ($quiz_row = mysqli_fetch_assoc($result_answers)) {
                $quiz_data = json_decode($quiz_row['quiz_data'], true);
                // Loop through quiz data and compare with submitted answers
                foreach ($quiz_data as $question_key => $question) {
                    $correct_answer_index = $question['correct_answer'];
                    $submitted_answer_index = $submitted_answers[$question['id']] ?? null;

                    if ($submitted_answer_index !== null && $submitted_answer_index == $correct_answer_index) {
                        $score++;
                    }
                }
            }
        }
        mysqli_stmt_close($stmt_answers);
    }
    
    // Save the submission to the quiz_submissions table
    $sql_insert_submission = "INSERT INTO quiz_submissions (quiz_id, student_id, student_name, score) VALUES (?, ?, ?, ?)";
    if ($stmt_insert = mysqli_prepare($link, $sql_insert_submission)) {
        mysqli_stmt_bind_param($stmt_insert, "iisi", $quiz_id, $student_id, $student_name, $score);
        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['operation_message'] = "<p class='text-green-600'>Quiz submitted successfully! Your score is: {$score}.</p>";
            header("location: student_view_quizzes.php");
            exit;
        } else {
            $_SESSION['operation_message'] = "<p class='text-red-600'>Error submitting quiz. Please try again.</p>";
            header("location: student_view_quizzes.php");
            exit;
        }
        mysqli_stmt_close($stmt_insert);
    }
}

// Fetch quiz details for display
$sql_get_quiz = "SELECT id, title, subject_name, quiz_data FROM quizzes WHERE id = ?";
$quiz_details = null;
if ($stmt = mysqli_prepare($link, $sql_get_quiz)) {
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $quiz_details = $row;
            $quiz_data = json_decode($row['quiz_data'], true);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$quiz_details) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Quiz not found.</p>";
    header("location: student_view_quizzes.php");
    exit;
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
</head>
<body class="bg-gray-100 font-sans">

 

<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Quiz: <?php echo htmlspecialchars($quiz_details['title']); ?></h1>
    <p class="text-gray-600 mb-6">Subject: <?php echo htmlspecialchars($quiz_details['subject_name']); ?></p>

    <div class="bg-white p-8 rounded-2xl shadow-lg">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo htmlspecialchars($quiz_id); ?>" method="post">
            <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">
            
            <div class="space-y-8">
                <?php foreach ($quiz_data as $index => $question_item): ?>
                    <div class="p-6 bg-gray-50 rounded-lg shadow-inner">
                        <p class="text-lg font-bold text-gray-800 mb-4">Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars($question_item['question_text']); ?></p>
                        <div class="space-y-2">
                            <?php foreach ($question_item['options'] as $option_index => $option_text): ?>
                                <div class="flex items-center space-x-2">
                                    <input type="radio" name="answers[<?php echo $question_item['id']; ?>]" id="q-<?php echo $question_item['id']; ?>-opt-<?php echo $option_index; ?>" value="<?php echo $option_index; ?>" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" required>
                                    <label for="q-<?php echo $question_item['id']; ?>-opt-<?php echo $option_index; ?>" class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($option_text); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-8 pt-5 border-t border-gray-200">
                <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Submit Quiz</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>



<?php
// Include the footer
require_once "./student_footer.php";
?>
