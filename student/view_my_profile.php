<?php
// School/student/view_my_profile.php

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

// Get the logged-in student's ID
$student_id = $_SESSION['id'];

// Set the page title
$pageTitle = "My Profile";

// Initialize variables
$student = null;
$error_message = '';

// Fetch the student's full details from the database
if ($link) {
    $sql = "SELECT user_id, virtual_id, full_name, father_name, mother_name, phone_number, whatsapp_number, current_class, previous_class, previous_school, previous_marks_percentage, student_fees, optional_fees, address, pincode, state, is_active, created_at, photo_filename FROM students WHERE user_id = ?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $student = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Your profile could not be found.";
            }
        } else {
            $error_message = "Error executing the profile query. Please try again later.";
            error_log("Profile view execute error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error preparing the profile query. Please try again later.";
        error_log("Profile view prepare error: " . mysqli_error($link));
    }
    // Close the database connection
    mysqli_close($link); 
} else {
    $error_message = "Database connection failed. Please contact administration.";
}

// Determine the correct photo URL
$photo_url = '../assets/images/default_student_avatar.png'; // Default
if ($student && !empty($student['photo_filename'])) {
    $cloudinary_url = $student['photo_filename'];
    // Check if it's a full URL (like from Cloudinary) or a relative path
    $is_full_url = strpos($cloudinary_url, 'http') === 0 || strpos($cloudinary_url, '//') === 0;
    $photo_url = $is_full_url ? $cloudinary_url : '../' . $cloudinary_url; // Assuming local path needs ../
}

// Include the header
require_once "./student_header.php";
?>

<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-8 shadow-md" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php elseif ($student): ?>
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-3xl">
            <div class="p-8 sm:p-10">
                <div class="flex flex-col sm:flex-row justify-between items-center sm:items-start mb-10 border-b-2 border-dashed border-gray-200 pb-8">
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-6 sm:mb-0 leading-tight">My Profile</h1>
                    <a href="./student_dashboard.php" class="text-indigo-700 hover:text-indigo-900 font-semibold transition duration-300 ease-in-out flex items-center gap-2 px-4 py-2 bg-indigo-50 rounded-lg shadow-sm hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Dashboard
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-x-12 gap-y-10">
                    <!-- Photo and Action Section (Left Sidebar) -->
                    <div class="md:col-span-1 lg:col-span-2 flex flex-col items-center justify-start p-8 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-2xl md:rounded-l-3xl md:rounded-r-none shadow-inner border-r-4 border-indigo-200">
                        <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Profile Photo" class="w-48 h-48 rounded-full object-cover border-6 border-white ring-4 ring-indigo-300 shadow-xl transform transition-all duration-300 ease-in-out hover:scale-105 hover:shadow-2xl">
                        
                        <div class="mt-8 text-center">
                            <h3 class="text-2xl font-extrabold text-gray-800 tracking-wide"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                            <p class="text-lg text-gray-700 mt-2">Student ID: <span class="font-mono text-indigo-800 font-semibold"><?php echo htmlspecialchars($student['user_id']); ?></span></p>
                        </div>


                        <div class="mt-10 text-center text-indigo-600 border-t border-indigo-200 pt-6">
                            <a href="./change_password.php" class="text-blue-700 hover:text-indigo-900 font-semibold transition duration-300 ease-in-out flex items-center gap-2 px-4 py-2 bg-red-300 rounded-lg shadow-sm hover:shadow-md">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        Change password
                    </a>
                            
                            <p class="text-xs text-indigo-500 mt-2">(Direct student editing is not available.)</p>
                        </div>
                    </div>

                    <!-- Details Section (Main Content) -->
                    <div class="md:col-span-3 lg:col-span-3 p-8">
                        
                        <div class="mb-10">
                            <h2 class="text-3xl font-bold text-gray-800 border-b-4 border-indigo-500 pb-4 mb-6">Personal Information</h2>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-x-10 gap-y-6 text-base">
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Full Name</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['full_name']); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Father's Name</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Mother's Name</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Registered On</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo date('F j, Y, g:i A', strtotime($student['created_at'])); ?></dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div class="mb-10">
                            <h2 class="text-3xl font-bold text-gray-800 border-b-4 border-indigo-500 pb-4 mb-6">Contact Information</h2>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-x-10 gap-y-6 text-base">
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Phone Number</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['phone_number'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">WhatsApp Number</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['whatsapp_number'] ?? 'N/A'); ?></dd>
                                </div>
                                <div class="col-span-full">
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Address</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2 break-words"><?php echo htmlspecialchars($student['address'] . ', ' . $student['state'] . ' - ' . $student['pincode']); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h2 class="text-3xl font-bold text-gray-800 border-b-4 border-indigo-500 pb-4 mb-6">Academic Information</h2>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-x-10 gap-y-6 text-base">
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Virtual ID</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2 font-mono"><?php echo htmlspecialchars($student['virtual_id'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Current Class</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['current_class']); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Previous Class</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['previous_class'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Previous School</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['previous_school'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Previous Marks</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['previous_marks_percentage'] ? $student['previous_marks_percentage'] . '%' : 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Student Fees</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['student_fees'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Optional Fees</dt>
                                    <dd class="text-gray-900 font-semibold text-lg mt-2"><?php echo htmlspecialchars($student['optional_fees'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 uppercase tracking-wider text-sm">Status</dt>
                                    <dd class="mt-2">
                                        <span class="px-4 py-1 inline-flex text-base leading-5 font-bold rounded-full <?php echo $student['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-xl relative shadow-md" role="alert">
            <strong class="font-bold">Notice:</strong>
            <span class="block sm:inline">Could not retrieve student profile data.</span>
        </div>
    <?php endif; ?>

</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>