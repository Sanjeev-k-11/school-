<?php
// School/student/view_announcements.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php";

// Check if the user is logged in and is a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in as a student to view this page.</p>";
    header("location: ../login.php");
    exit;
}

// Set the page title
$pageTitle = "Announcements & Events";

// Initialize variables
$announcements = [];
$error_message = '';

// Fetch all events/announcements from the database
if ($link) {
    // Fetch events, ordering by the event date in descending order (newest first)
    $sql = "SELECT event_name, event_description, event_date_time, created_by_name, created_at FROM events ORDER BY event_date_time DESC";
    
    if ($result = mysqli_query($link, $sql)) {
        $announcements = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    } else {
        $error_message = "Error fetching announcements.";
        error_log("Announcements view error: " . mysqli_error($link));
    }
    mysqli_close($link);
} else {
    $error_message = "Database connection failed.";
}

// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Announcements & Events</h1>
        <a href="./student_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">An Error Occurred</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php elseif (empty($announcements)): ?>
        <div class="text-center bg-white p-12 rounded-2xl shadow-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Announcements Yet</h3>
            <p class="mt-1 text-sm text-gray-500">There are currently no new events or announcements. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($announcements as $event): 
                $event_timestamp = strtotime($event['event_date_time']);
                $is_past = $event_timestamp < time();
            ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transition-transform transform hover:scale-105 <?php echo $is_past ? 'opacity-70' : ''; ?>">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($event['event_name']); ?></h2>
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $is_past ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo $is_past ? 'Completed' : 'Upcoming'; ?>
                            </span>
                        </div>
                        <div class="flex items-center text-sm text-gray-500 mt-2">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span><?php echo htmlspecialchars(date('l, F j, Y \a\t g:i A', $event_timestamp)); ?></span>
                        </div>
                        
                        <?php if (!empty($event['event_description'])): ?>
                            <p class="text-gray-600 mt-4">
                                <?php echo nl2br(htmlspecialchars($event['event_description'])); ?>
                            </p>
                        <?php endif; ?>

                        <div class="text-right text-xs text-gray-400 mt-4 border-t pt-2">
                            Announced by <?php echo htmlspecialchars($event['created_by_name']); ?> on <?php echo htmlspecialchars(date('d-M-Y', strtotime($event['created_at']))); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
