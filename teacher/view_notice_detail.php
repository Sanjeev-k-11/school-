<?php
// School/teacher/view_notice_detail.php

session_start();
require_once "../config.php";

// Security check: Allow admin, principal, and teacher to view
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'principal', 'teacher'])) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Holiday Notice";
$holiday = null;
$error_message = '';

// Validate and fetch the specific holiday notice
$holiday_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$holiday_id) {
    $error_message = "Invalid holiday ID provided.";
} else {
    $sql = "SELECT holiday_name, start_date, end_date, description, created_by_name, created_at FROM holidays WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $holiday_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) == 1) {
            $holiday = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Holiday notice not found.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Database query failed.";
    }
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
    <style>
        @media print {
            body * { visibility: hidden; }
            #printableNotice, #printableNotice * { visibility: visible; }
            #printableNotice { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 1cm; }
            .no-print { display: none !important; }
        }
    </style>
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

<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-2xl font-bold text-gray-800">View Notice</h1>
        <a href="./view_holidays.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to All Notices</a>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php elseif ($holiday): ?>
        <div id="printableNotice" class="bg-white rounded-lg shadow-lg border border-gray-200 p-8">
            <div class="text-center border-b-2 pb-4 mb-6">
                <h2 class="text-3xl font-bold text-gray-800">Modern Public School</h2>
                <p class="text-gray-500">Madhubani, Bihar</p>
                <h3 class="text-xl font-semibold text-gray-700 mt-4 uppercase tracking-wider">Notice</h3>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mb-6">
                <span><strong>Ref No:</strong> SCH/HOL/<?php echo date('Y') . '/' . htmlspecialchars($holiday_id); ?></span>
                <span><strong>Date:</strong> <?php echo date('d F, Y', strtotime($holiday['created_at'])); ?></span>
            </div>
            <div class="text-center mb-8">
                <h4 class="text-lg font-bold underline"><?php echo htmlspecialchars($holiday['holiday_name']); ?></h4>
            </div>
            <div class="text-gray-700 space-y-4">
                <p>This is to inform all students, teachers, and staff that the school will remain closed on account of <strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong>.</p>
                <p>
                    The holiday will be observed from 
                    <strong><?php echo date('l, d F Y', strtotime($holiday['start_date'])); ?></strong>
                    <?php if (!empty($holiday['end_date']) && $holiday['end_date'] !== $holiday['start_date']): ?>
                        to <strong><?php echo date('l, d F Y', strtotime($holiday['end_date'])); ?></strong>.
                    <?php endif; ?>
                </p>
                <?php if (!empty($holiday['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($holiday['description'])); ?></p>
                <?php endif; ?>
                <p>The school will resume its normal schedule on the next working day.</p>
            </div>
            <div class="mt-16 text-right">
                <p class="font-semibold"><?php echo htmlspecialchars($holiday['created_by_name']); ?></p>
                <p class="text-sm text-gray-600">Principal</p>
            </div>
        </div>
        
        <?php if ($_SESSION['role'] === 'principal'): ?>
        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">
                Print or Download Notice
            </button>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>




<?php 
require_once "./teacher_footer.php";
?>
