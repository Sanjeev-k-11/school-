<?php
// School/teacher/create_quiz.php

session_start();
require_once "../config.php";

// Security check: Only teachers can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Create Quiz";
$teacher_id = $_SESSION['id'];
$teacher_name = $_SESSION['name'];

$assigned_classes = [];
$operation_message = "";

// Fetch the classes assigned to the logged-in teacher
$sql_get_classes = "SELECT classes_taught FROM staff WHERE staff_id = ?";
if ($stmt_classes = mysqli_prepare($link, $sql_get_classes)) {
    mysqli_stmt_bind_param($stmt_classes, "i", $teacher_id);
    if (mysqli_stmt_execute($stmt_classes)) {
        $result_classes = mysqli_stmt_get_result($stmt_classes);
        if ($row = mysqli_fetch_assoc($result_classes)) {
            if (!empty($row['classes_taught'])) {
                $assigned_classes = array_map('trim', explode(',', $row['classes_taught']));
            }
        }
    }
    mysqli_stmt_close($stmt_classes);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quiz_class = $_POST['quiz_class'] ?? '';
    $quiz_subject = $_POST['quiz_subject'] ?? '';
    $quiz_title = $_POST['quiz_title'] ?? '';
    $questions_data = $_POST['questions'] ?? [];

    if (empty($quiz_class) || empty($quiz_subject) || empty($quiz_title)) {
        $operation_message = "<p class='text-red-600'>Please fill in all required fields (Class, Subject, Title).</p>";
    } elseif (empty($questions_data)) {
        $operation_message = "<p class='text-red-600'>You must add at least one question.</p>";
    } else {
        $quiz_data_json = json_encode($questions_data);
        
        $sql_insert = "INSERT INTO quizzes (class_name, subject_name, title, teacher_id, teacher_name, quiz_data) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "sssiis", $quiz_class, $quiz_subject, $quiz_title, $teacher_id, $teacher_name, $quiz_data_json);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Quiz created successfully!</p>";
                header("location: staff_dashboard.php");
                exit();
            } else {
                $operation_message = "<p class='text-red-600'>Something went wrong. Please try again later.</p>";
            }
            mysqli_stmt_close($stmt);
        }
    }
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
        .draggable {
            cursor: grab;
        }
        .dragging {
            opacity: 0.5;
            border: 2px dashed #3182CE;
        }
        .placeholder {
            border: 2px dashed #9AE6B4;
            background-color: #F0FFF4;
            height: 50px;
            margin: 10px 0;
        }
        .option-draggable {
            cursor: grab;
        }
        .option-dragging {
            opacity: 0.5;
            border: 1px dashed #3182CE;
        }
    </style>
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
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Create New Quiz</h1>
 <a href="view_quizzes.php" class="inline-flex mt-3 sm:mt-0 justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        View My Quizzes
    </a>

    <?php if(!empty($operation_message)) echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>{$operation_message}</div>"; ?>
    <?php if(!empty($_SESSION['operation_message'])) { echo "<div class='p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . $_SESSION['operation_message'] . "</div>"; unset($_SESSION['operation_message']); } ?>

    <div class="bg-white p-8 rounded-2xl shadow-lg">
        <form id="quiz-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="quiz_class" class="block text-sm font-medium text-gray-700">Class</label>
                    <select id="quiz_class" name="quiz_class" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Select a class</option>
                        <?php foreach($assigned_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="quiz_subject" class="block text-sm font-medium text-gray-700">Subject</label>
                    <input type="text" name="quiz_subject" id="quiz_subject" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="quiz_title" class="block text-sm font-medium text-gray-700">Quiz Title</label>
                    <input type="text" name="quiz_title" id="quiz_title" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div id="questions-container" class="space-y-6">
                <!-- Question templates will be dynamically added here -->
            </div>

            <div class="mt-8 flex justify-between items-center pt-5 border-t border-gray-200">
                <button type="button" id="add-question-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Question
                </button>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Save Quiz
                </button>
            </div>
        </form>
    </div>
</div>

<script src="create_quiz_handler.js"></script>
</body>
</html>


<?php
// Include the footer
require_once "./teacher_footer.php";
?>
