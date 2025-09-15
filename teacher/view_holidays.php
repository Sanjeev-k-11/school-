<?php
// School/teacher/view_holidays.php

session_start();
require_once "../config.php";

// Security check: Allow teachers and principals to view
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['teacher', 'principal'])) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Holiday Notices";
$operation_message = "";

// Check for session messages
if (isset($_SESSION['operation_message'])) {
    $operation_message = $_SESSION['operation_message'];
    unset($_SESSION['operation_message']);
}

// Fetch all holidays from the database
$holidays = [];
$sql_fetch = "SELECT id, holiday_name, start_date, end_date, description, created_at FROM holidays ORDER BY start_date DESC";
if ($result = mysqli_query($link, $sql_fetch)) {
    $holidays = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $operation_message = "<p class='text-red-600'>Error fetching holiday data from the database.</p>";
}

mysqli_close($link);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - School Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
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

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Holiday & Event Notices</h1>

    <?php if (!empty($operation_message)): ?>
        <div class="p-4 mb-6 text-sm rounded-lg <?php echo strpos($operation_message, 'red') ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <?php if (empty($holidays)): ?>
                <p class="text-center text-gray-500 py-12">No holiday notices have been published yet.</p>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Holiday Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date(s)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($holiday['holiday_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <?php 
                                            echo date('d M, Y', strtotime($holiday['start_date'])); 
                                            if (!empty($holiday['end_date']) && $holiday['end_date'] !== $holiday['start_date']) {
                                                echo " to " . date('d M, Y', strtotime($holiday['end_date']));
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600 max-w-sm truncate" title="<?php echo htmlspecialchars($holiday['description']); ?>">
                                        <?php echo htmlspecialchars($holiday['description'] ?? 'N/A'); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="./view_notice_detail.php?id=<?php echo $holiday['id']; ?>" class="text-indigo-600 hover:text-indigo-900">View Notice</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>




<?php 
require_once "./teacher_footer.php";
?>
