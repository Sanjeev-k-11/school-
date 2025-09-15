<?php
// School/student/view_school_details.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php";

// Check if the user is logged in and is a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in to view this page.</p>";
    header("location: ../login.php");
    exit;
}

// Set the page title
$pageTitle = "School Information";

// --- School Information (This can be fetched from a settings table in a real application) ---
$school_name = "Basic Public School";
$school_logo_path = "../uploads/basic.jpeg"; // IMPORTANT: Update this path
$school_address = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$school_email = "vc74295@gmail.com";
$school_phone = "+91 88777 80197";
$principal_name = "  ";
$owner_name = "Mr. Vishal Kumar";
$school_motto = "Excellence in Education";
$establishment_year = "1998";


// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">About Our School</h1>
        <a href="./student_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Dashboard</a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-8">
            <!-- Main School Info -->
            <div class="flex flex-col md:flex-row items-center text-center md:text-left">
                <?php if (file_exists($school_logo_path)): ?>
                    <img src="<?php echo htmlspecialchars($school_logo_path); ?>" alt="School Logo" class="h-32 w-32 rounded-full object-cover border-4 border-indigo-200 flex-shrink-0 mb-6 md:mb-0 md:mr-8">
                <?php endif; ?>
                <div class="flex-grow">
                    <h2 class="text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($school_name); ?></h2>
                    <p class="text-xl text-indigo-600 font-semibold mt-1">"<?php echo htmlspecialchars($school_motto); ?>"</p>
                    <p class="text-md text-gray-600 mt-2">Established in <?php echo htmlspecialchars($establishment_year); ?></p>
                </div>
            </div>

            <div class="border-t border-gray-200 my-8"></div>

            <!-- Detailed Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Key Personnel -->
                <div>
                    <h3 class="text-xl font-bold text-gray-700 mb-4">Key Personnel</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div class="ml-4">
                                
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($principal_name); ?></p>
                                <p class="text-sm text-gray-500">Principal</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                             <div class="flex-shrink-0 h-12 w-12 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($owner_name); ?></p>
                                <p class="text-sm text-gray-500">School Dean</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-xl font-bold text-gray-700 mb-4">Contact Information</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-lg font-semibold text-gray-900">Address</p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($school_address); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-lg font-semibold text-gray-900">Email</p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($school_email); ?></p>
                            </div>
                        </div>
                         <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-lg font-semibold text-gray-900">Phone</p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($school_phone); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
