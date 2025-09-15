<?php
// School/teacher/view_assigned_homework.php

session_start();
require_once "../config.php";

// Security check: Only teachers can view and manage homework
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'teacher') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "View Assigned Homework";
$teacher_id = $_SESSION['id'];
$class_name_filter = $_GET['class_name'] ?? '';
$subject_name_filter = $_GET['subject_name'] ?? '';
$due_date_filter = $_GET['due_date'] ?? '';

// --- Homework Deletion Logic ---
// Automatically delete homework older than one month
$one_month_ago = date('Y-m-d', strtotime('-1 month'));
$sql_delete_old_homework = "DELETE FROM homework WHERE due_date < ?";
if ($stmt_delete = mysqli_prepare($link, $sql_delete_old_homework)) {
    mysqli_stmt_bind_param($stmt_delete, "s", $one_month_ago);
    mysqli_stmt_execute($stmt_delete);
    mysqli_stmt_close($stmt_delete);
}

// Handle delete action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        $sql_delete = "DELETE FROM homework WHERE id = ? AND teacher_id = ?";
        if ($stmt = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt, "ii", $delete_id, $teacher_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Homework assignment deleted successfully!</p>";
            } else {
                $_SESSION['operation_message'] = "<p class='text-red-600'>Error deleting assignment. Please try again.</p>";
            }
            mysqli_stmt_close($stmt);
            header("location: view_assigned_homework.php");
            exit;
        }
    }
}

// --- Fetching Logic with Filters ---
$sql_get_homework = "SELECT * FROM homework WHERE teacher_id = ?";
$params = [$teacher_id];
$param_types = "i";

if (!empty($class_name_filter)) {
    $sql_get_homework .= " AND class_name = ?";
    $param_types .= "s";
    $params[] = $class_name_filter;
}
if (!empty($subject_name_filter)) {
    $sql_get_homework .= " AND subject_name LIKE ?";
    $param_types .= "s";
    $params[] = "%" . $subject_name_filter . "%";
}
if (!empty($due_date_filter)) {
    $sql_get_homework .= " AND due_date = ?";
    $param_types .= "s";
    $params[] = $due_date_filter;
}

$sql_get_homework .= " ORDER BY due_date DESC";
$homework_assignments = [];

if ($stmt = mysqli_prepare($link, $sql_get_homework)) {
    // Create an array of references for bind_param
    $bind_params = [];
    $bind_params[] = $param_types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_params[] = &$params[$i];
    }
    
    // Dynamically call mysqli_stmt_bind_param using the array of references
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bind_params));
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $homework_assignments[] = $row;
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
    <style>
        /* CSS for responsive navigation */
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
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Your Assigned Homework</h1>
    <?php if (isset($_SESSION['operation_message'])): ?>
        <div class='p-4 mb-4 text-sm bg-green-100 text-green-700 rounded-lg'>
            <?php echo $_SESSION['operation_message']; unset($_SESSION['operation_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <form action="view_assigned_homework.php" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700">Filter by Class</label>
                <input type="text" name="class_name" id="class_name" value="<?php echo htmlspecialchars($class_name_filter); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="subject_name" class="block text-sm font-medium text-gray-700">Filter by Subject</label>
                <input type="text" name="subject_name" id="subject_name" value="<?php echo htmlspecialchars($subject_name_filter); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700">Filter by Due Date</label>
                <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($due_date_filter); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Filter</button>
                <a href="view_assigned_homework.php" class="w-full px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    <?php if (empty($homework_assignments)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">No homework assignments found that match your criteria.</p>
            <a href="assign_homework.php" class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Assign New Homework</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attachments</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($homework_assignments as $homework): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($homework['class_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($homework['subject_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($homework['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y', strtotime($homework['due_date']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if (!empty($homework['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($homework['file_path']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">View File</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="edit_homework.php?id=<?php echo $homework['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                            <form method="post" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this homework?');">
                                <input type="hidden" name="delete_id" value="<?php echo $homework['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
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
require_once "./teacher_footer.php";
?>
