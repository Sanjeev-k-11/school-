<?php
// School/teacher/view_quizzes.php

session_start();
require_once "../config.php";

// Security check: Only teachers can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "View Quizzes";
$teacher_id = $_SESSION['id'];

$sql_get_quizzes = "SELECT id, class_name, subject_name, title, created_at FROM quizzes WHERE teacher_id = ? ORDER BY created_at DESC";
$quizzes = [];

if ($stmt = mysqli_prepare($link, $sql_get_quizzes)) {
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $quizzes[] = $row;
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



<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Your Quizzes</h1>

    <?php if (isset($_SESSION['operation_message'])): ?>
        <div class='p-4 mb-4 text-sm bg-green-100 text-green-700 rounded-lg'>
            <?php echo $_SESSION['operation_message']; unset($_SESSION['operation_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($quizzes)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">You have not created any quizzes yet.</p>
            <a href="create_quiz.php" class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Create a New Quiz</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created On</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quiz['class_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y', strtotime($quiz['created_at']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="preview_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">View</a>
                            <a href="view_quiz_results.php?id=<?php echo $quiz['id']; ?>" class="text-green-600 hover:text-green-900">View Results</a>
                        </td>
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
