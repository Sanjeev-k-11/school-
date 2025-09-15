 
<?php
// admin_sidebar.php
// This file is included in dashboard/admin pages.
// ASSUMPTION: The variable $webroot_path is defined *before* this file is included.
// $webroot_path should be the path from the web server root to your project's root directory.
// Examples:
// - If your project is accessed via http://yourdomain.com/login.php -> $webroot_path = '/';
// - If your project is accessed via http://yourdomain.com/school/login.php -> $webroot_path = '/school/';
// - Based on your URL neoschool.42web.io/admin/admin/..., if neoschool.42web.io/admin/ is your project root
//   (meaning login.php is at neoschool.42web.io/admin/login.php), then $webroot_path = '/admin/';


// Determine display name and role for the sidebar
$sidebar_display_name = 'User'; // Default if session data isn't available
$sidebar_role_display = 'Role'; // Default if session data isn't available

// Check if session variables exist and are set
if (isset($_SESSION['role'])) {
    // Capitalize the first letter of the role for display
    $sidebar_role_display = ucfirst($_SESSION['role']);

    // Determine the display name based on available session data
    // Prioritize 'name' if available (used in your login script for staff/student)
    if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $sidebar_display_name = htmlspecialchars($_SESSION['name']);
    } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        // Fallback to username if 'name' isn't set but username is (used for admin)
        $sidebar_display_name = htmlspecialchars($_SESSION['username']);
    } else {
        // Fallback if no name/username is in session, maybe show the role?
         $sidebar_display_name = $sidebar_role_display; // Use role as display name if no name found
    }

} else {
    // If $_SESSION['role'] is not set, the user is likely not logged in or session is broken.
    // The access control check at the top of your dashboard pages should handle this.
    // Here, we'll just set a default placeholder.
    $sidebar_display_name = 'Guest';
    $sidebar_role_display = 'Not Logged In';
}

// Ensure $webroot_path is defined. If it wasn't set before inclusion, default it.
// However, it's better practice to ensure it's set correctly *before* including this file.
if (!isset($webroot_path)) {
    // Fallback - You might want to log an error here if $webroot_path is not set
    // Defaulting to '/' might or might not be correct depending on your hosting setup.
    $webroot_path = '/school/';
    error_log("Warning: \$webroot_path was not defined before including admin_sidebar.php. Defaulting to '/'. Check the including file.");
}


?>

<!-- Sidebar Overlay (appears when sidebar is open to dim content) -->
<div id="admin-sidebar-overlay" class="fixed inset-0 bg-black opacity-0 pointer-events-none transition-opacity duration-300 z-30"></div>

<!-- Sidebar -->
<div id="admin-sidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 ease-in-out z-40">
    <div class="p-6 flex flex-col h-full">
        <!-- Sidebar Header (User Info) -->
        <div class="flex items-center justify-between mb-6">
             <div>
                <div class="text-xl font-semibold"><?php echo $sidebar_display_name; // Already htmlspecialchars'd above ?></div>
                <span class="text-sm font-medium px-2 py-1 mt-1 inline-block rounded-full bg-indigo-600 text-white">
                    <?php echo htmlspecialchars($sidebar_role_display); ?>
                </span>
             </div>
             <!-- Close Button -->
            <button id="admin-sidebar-toggle-close" class="text-gray-400 hover:text-white focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow space-y-2 overflow-y-auto pb-4"> <!-- <-- MODIFIED LINE -->
            <!-- Links using absolute paths from the webroot $webroot_path -->

            <!-- Dashboard Link (Points to admin_dashboard.php in the admin module directory) -->
            <a href="<?php echo $webroot_path; ?>admin/admin_dashboard.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Dashboard</a>
            <a href="<?php echo $webroot_path; ?>admin/allstudentList.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Manage Students</a>

            <!-- Add Student Link (In admin module directory) -->
            <a href="<?php echo $webroot_path; ?>admin/create_student.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Add Student</a>
             <a href="<?php echo $webroot_path; ?>admin/student_monthly_fees_list.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Fee List</a>
            <!-- Manage Staff Link (In admin module directory) -->
            <a href="<?php echo $webroot_path; ?>admin/manage_staff.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Manage Staff</a>

             <!-- Create Staff Link (In admin module directory) -->
            <a href="<?php echo $webroot_path; ?>admin/create_staff.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Add Staff</a>
            <a href="<?php echo $webroot_path; ?>admin/create_event.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Announcement</a>
                         <a href="<?php echo $webroot_path; ?>admin/create_holiday.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">create Holiday</a>
            <a href="<?php echo $webroot_path; ?>admin/view_holidays.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">All Holiday</a>
             <a href="<?php echo $webroot_path; ?>admin/create_class_timetable.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">class timetable</a>
             <a href="<?php echo $webroot_path; ?>admin/view_all_timetables.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">class view timetable</a>

             <a href="<?php echo $webroot_path; ?>admin/all_student_results.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Result</a>
            <a href="<?php echo $webroot_path; ?>admin/student_fee_structure.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">fee structure</a>
             <a href="<?php echo $webroot_path; ?>admin/add_bulk_monthly_fee.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Add Bulk Fee</a>
             <a href="<?php echo $webroot_path; ?>admin/view_all_receipts.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Receip Fee</a>

             <a href="<?php echo $webroot_path; ?>admin/manage_students.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Fee Due</a>

             <a href="<?php echo $webroot_path; ?>admin/add_expense.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Expense</a>

             <a href="<?php echo $webroot_path; ?>admin/manage_expenses.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">All Expenses</a>
             
             <a href="<?php echo $webroot_path; ?>admin/add_income.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Income</a>
             <a href="<?php echo $webroot_path; ?>admin/manage_income.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">All Income</a>
             <a href="<?php echo $webroot_path; ?>admin/view_contact_queries.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">contact queries</a>
            <a href="<?php echo $webroot_path; ?>admin/upload_announcement_admin.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Admission Announcement</a>
            <a href="<?php echo $webroot_path; ?>admin/vacation_homework_admin.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">vacation homework</a>
             <a href="<?php echo $webroot_path; ?>admin/book_admin_panel.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">Book</a>
            <a href="<?php echo $webroot_path; ?>admin/gallery_admin.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">gallery</a>
            <a href="<?php echo $webroot_path; ?>admin/stats_panel.php" class="block px-3 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">stats</a>



            <!-- Add more admin specific links here -->

        </nav>

        <!-- Logout Link - Points to logout.php in the project root -->
         <div class="mt-auto">
            <a href="<?php echo $webroot_path; ?>logout.php" class="block px-3 py-2 rounded-md text-red-400 hover:bg-gray-700 hover:text-red-300 transition duration-200">Logout</a>
         </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Note: The "Open Sidebar" button HTML must be placed in your main page file (e.g., admin_dashboard.php)
        // It needs the ID 'admin-sidebar-toggle-open'.
        const toggleOpenBtn = document.getElementById('admin-sidebar-toggle-open');
        const toggleCloseBtn = document.getElementById('admin-sidebar-toggle-close');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('admin-sidebar-overlay');

        // Function to open the sidebar
        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-50', 'pointer-events-auto'); // Adjust opacity as needed

            // Add class to body to push content over (if you are using body padding)
             // document.body.classList.add('sidebar-open'); // UNCOMMENT if using body padding
        }

        // Function to close the sidebar
        function closeSidebar() {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('opacity-50', 'pointer-events-auto');
            overlay.classList.add('opacity-0', 'pointer-events-none');

             // Remove class from body to reset content position (if using body padding)
             // document.body.classList.remove('sidebar-open'); // UNCOMMENT if using body padding
        }

        // Add event listeners
        // Check if elements exist before adding listeners, as the open button is in the main page
        if (toggleOpenBtn) {
            toggleOpenBtn.addEventListener('click', openSidebar);
        }
        if (toggleCloseBtn) {
            toggleCloseBtn.addEventListener('click', closeSidebar);
        }
        if (overlay) {
            overlay.addEventListener('click', closeSidebar); // Close sidebar when clicking overlay
        }

        // Optional: Close sidebar on resize to desktop if it's open
        // This prevents the sidebar from staying open and covering content
        // if the user resizes from mobile to desktop width while the sidebar is open.
        window.addEventListener('resize', function() {
             clearTimeout(window.resizeTimeout);
             window.resizeTimeout = setTimeout(() => {
                 // Check if the screen is desktop size (>= md breakpoint, 768px)
                 if (window.innerWidth >= 768) {
                      // Check if the sidebar is currently open
                     if (sidebar && sidebar.classList.contains('translate-x-0')) {
                          closeSidebar();
                     }
                     // Ensure overlay is hidden and inactive on desktop regardless of state
                     if (overlay) {
                         overlay.classList.remove('opacity-50', 'pointer-events-auto');
                         overlay.classList.add('opacity-0', 'pointer-events-none');
                     }
                      // Also ensure body padding is removed on desktop if sidebar is open
                      // if (document.body.classList.contains('sidebar-open')) { // UNCOMMENT if using body padding
                      //     document.body.classList.remove('sidebar-open'); // UNCOMMENT if using body padding
                      // } // UNCOMMENT if using body padding
                 } else {
                      // On smaller screens, if the sidebar is closed, ensure overlay is off
                      if (sidebar && sidebar.classList.contains('-translate-x-full')) {
                           if (overlay) {
                              overlay.classList.remove('opacity-50', 'pointer-events-auto');
                              overlay.classList.add('opacity-0', 'pointer-events-none');
                           }
                      }
                      // On smaller screens, ensure body padding is off if sidebar is open (it shouldn't push content, overlay covers it)
                       // if (document.body.classList.contains('sidebar-open')) { // UNCOMMENT if using body padding
                       //     document.body.classList.remove('sidebar-open'); // UNCOMMENT if using body padding
                       // } // UNCOMMENT if using body padding
                 }
             }, 250); // Adjust delay as needed
        });
    });
</script>