<?php
// School/admin/view_holidays.php

// Start the session
session_start();

require_once "../config.php";

// Check if user is logged in and is ADMIN or Principal
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'principal'])) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. You do not have permission to view this page.</p>";
    header("location: ./admin_dashboard.php");
    exit;
}

$pageTitle = "View Holiday Notices";
$operation_message = "";

// --- AUTO-DELETE OLD HOLIDAYS (OLDER THAN 7 DAYS) ---
// This runs every time the page is loaded by an authorized user.
if ($link) {
    // Deletes entries where the end_date is more than 7 days ago.
    // It uses COALESCE to handle single-day holidays where end_date might be NULL.
    $sql_auto_delete = "DELETE FROM holidays WHERE COALESCE(end_date, start_date) < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    if (!mysqli_query($link, $sql_auto_delete)) {
        // Log an error if the auto-delete fails, but don't stop the page from loading.
        error_log("Failed to auto-delete old holidays: " . mysqli_error($link));
    }
}


// Handle Manual Delete Request
if (isset($_GET['delete_id']) && filter_var($_GET['delete_id'], FILTER_VALIDATE_INT)) {
    // Admins only can delete
    if ($_SESSION['role'] !== 'admin') {
         $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can delete holiday notices.</p>";
    } else {
        $delete_id = $_GET['delete_id'];
        $sql_delete = "DELETE FROM holidays WHERE id = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $delete_id);
            if (mysqli_stmt_execute($stmt_delete)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Holiday notice deleted successfully.</p>";
            } else {
                $_SESSION['operation_message'] = "<p class='text-red-600'>Error deleting holiday notice.</p>";
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            $_SESSION['operation_message'] = "<p class='text-red-600'>Error preparing delete statement.</p>";
        }
    }
    header("location: ./view_holidays.php");
    exit();
}


// Check for session messages
if (isset($_SESSION['operation_message'])) {
    $operation_message = $_SESSION['operation_message'];
    unset($_SESSION['operation_message']);
}

// Fetch all remaining holidays from the database
$holidays = [];
$sql_fetch = "SELECT id, holiday_name, start_date, end_date, description, created_by_name, created_at FROM holidays ORDER BY start_date DESC";
if ($result = mysqli_query($link, $sql_fetch)) {
    $holidays = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $operation_message = "<p class='text-red-600'>Error fetching holiday data from the database.</p>";
}

mysqli_close($link);

// Include the header file
require_once "./admin_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Holiday & Event Notices</h1>
        <a href="./create_holiday.php" class="px-4 mt-11 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
            + Create New Notice
        </a>
    </div>

    <?php if (!empty($operation_message)): ?>
        <div class="p-4 mb-6 text-sm rounded-lg <?php echo strpos($operation_message, 'red') ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <?php if (empty($holidays)): ?>
                <p class="text-center text-gray-500 py-12">No holiday notices have been created yet.</p>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Holiday Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date(s)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Created By</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($holiday['created_by_name']); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo date('d-m-Y', strtotime($holiday['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="./view_holiday_notice.php?id=<?php echo $holiday['id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <a href="./edit_holiday.php?id=<?php echo $holiday['id']; ?>" class="text-indigo-600 hover:text-indigo-900 ml-4">Edit</a>
                                        <a href="?delete_id=<?php echo $holiday['id']; ?>" class="text-red-600 hover:text-red-900 ml-4" onclick="return confirm('Are you sure you want to delete this notice?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include the footer file
require_once "./admin_footer.php";
?>
