<?php
// School/student/student_header.php

// Ensure session is started, though it should be by dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$loggedInUsername = $_SESSION['username'] ?? 'Guest';
$loggedInUserRole = $_SESSION['role'] ?? 'guest';

// Get current script name to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);

// Determine the base path for assets. Adjust if your directory structure changes.
$base_path = '../'; // From 'student' directory to 'School/'
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : "Student Portal"; ?> - School Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Optional: Add custom CSS if needed -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/styles.css">
    <style>
        /* General layout adjustments for fixed header and sidebar */
        body {
            padding-top: 4.5rem;
            /* Adjust based on your header's height */
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 50;
            /* Tailwind z-50 */
        }

        .nav-link.active {
            font-weight: 600;
            /* semi-bold */
            color: #3b82f6;
            /* blue-500 */
            border-b-2 border-blue-500;
        }
    </style>
</head>

<body class="bg-gray-100">

    <header class="header bg-white shadow-md">
        <nav class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">

            <div class="flex items-center">
                <img src="../uploads/basic.jpeg" alt="Logo" class="h-10 w-10 mr-2">

                <a href="./student_dashboard.php" class="text-xl font-bold text-gray-800 mr-6">Student Portal</a>
                <div class="hidden md:flex space-x-4">
                    <a href="./student_dashboard.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'student_dashboard.php' ? 'active' : ''); ?>">Dashboard</a>
                    <a href="./group_chat.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'group_chat.php' ? 'active' : ''); ?>">group_chat</a>

                    <a href="./view_my_profile.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_my_profile.php' ? 'active' : ''); ?>">My Profile</a>
                    <a href="./view_my_fees.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_my_fees.php' ? 'active' : ''); ?>">Fees</a>
                    <a href="./view_my_exam_results.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_my_exam_results.php' ? 'active' : ''); ?>">Exam Results</a>
                    <a href="./view_my_timetable.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_my_timetable.php' ? 'active' : ''); ?>">Timetable</a>
                    <a href="./view_announcements.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_announcements.php' ? 'active' : ''); ?>">Announcements</a>
                    <a href="./view_holidays.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_holidays.php' ? 'active' : ''); ?>">Holiday</a>
                    <a href="./student_view_homework.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'student_view_homework.php' ? 'active' : ''); ?>">Homework</a>
                    <a href="./student_view_quizzes.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'student_view_quizzes.php' ? 'active' : ''); ?>">Quize</a>

                    <a href="./view_school_details.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_school_details.php' ? 'active' : ''); ?>">About</a>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-gray-700 text-sm mr-4 hidden sm:inline">Welcome, <?php echo htmlspecialchars($loggedInUsername); ?> (Student)</span>
                <a href="<?php echo $base_path; ?>logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">Logout</a>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-900 focus:outline-none focus:text-gray-900">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Mobile menu (hidden by default) -->
        <div id="mobile-menu" class="md:hidden bg-white shadow-lg py-2">
            <a href="./student_dashboard.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'student_dashboard.php' ? 'active' : ''); ?>">Dashboard</a>
            
            <a href="./view_my_profile.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'view_my_profile.php' ? 'active' : ''); ?>">My Profile</a>
            <a href="./view_my_fees.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'view_my_fees.php' ? 'active' : ''); ?>">Fees</a>
            <a href="./view_my_exam_results.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'view_my_exam_results.php' ? 'active' : ''); ?>">Exam Results</a>
            <a href="./view_my_timetable.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'view_my_timetable.php' ? 'active' : ''); ?>">Timetable</a>
            <a href="./view_announcements.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'view_announcements.php' ? 'active' : ''); ?>">Announcements</a>
            <a href="./view_holidays.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_holidays.php' ? 'active' : ''); ?>">Holiday</a>
            <a href="./student_view_homework.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'student_view_homework.php' ? 'active' : ''); ?>">Homework</a>
            <a href="./student_view_quizzes.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'student_view_quizzes.php' ? 'active' : ''); ?>">Quize</a>
            <a href="./group_chat.php" class="block text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium <?php echo ($current_page == 'group_chat.php' ? 'active' : ''); ?>">Chat</a>

            <a href="./view_school_details.php" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium <?php echo ($current_page == 'view_school_details.php' ? 'active' : ''); ?>">About</a>

        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenu.style.display = 'none'; // Ensure it's hidden on load

                mobileMenuButton.addEventListener('click', function() {
                    if (mobileMenu.style.display === 'none') {
                        mobileMenu.style.display = 'block';
                    } else {
                        mobileMenu.style.display = 'none';
                    }
                });

                // Close mobile menu if window is resized above md breakpoint
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768) { // md breakpoint
                        mobileMenu.style.display = 'none';
                    }
                });
            }
        });
    </script>

    <main class="flex-1">
        <!-- Main content will be inserted here by the specific page -->