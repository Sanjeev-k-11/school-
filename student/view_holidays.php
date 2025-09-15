<?php
// School/student/view_holidays.php

session_start();
require_once "../config.php";

// Security check: Allow only students to view
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
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

// Include the header file
require_once "./student_header.php";
?>
<style>
    body {
        background-color: #EBF8FF; /* Light blue background */
    }
</style>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Holiday & Event Notices</h1>

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="text" id="holidaySearch" class="w-full p-3 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="Search for a holiday by name...">
        </div>
    </div>

    <?php if (!empty($operation_message)): ?>
        <div class="p-4 mb-6 text-sm rounded-lg <?php echo strpos($operation_message, 'red') ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="holidayGrid">
        <?php if (empty($holidays)): ?>
            <p class="text-center text-gray-500 py-12 col-span-full">No holiday notices have been published yet.</p>
        <?php else: ?>
            <?php 
            $today = date('Y-m-d');
            foreach ($holidays as $holiday): 
                $start_date = $holiday['start_date'];
                $end_date = $holiday['end_date'] ?? $start_date;
                $status = '';
                $status_class = '';

                if ($today < $start_date) {
                    $status = 'Upcoming';
                    $status_class = 'bg-blue-100 text-blue-800';
                } elseif ($today >= $start_date && $today <= $end_date) {
                    $status = 'Ongoing';
                    $status_class = 'bg-green-100 text-green-800';
                } else {
                    $status = 'Past';
                    $status_class = 'bg-gray-100 text-gray-800';
                }
            ?>
                <div class="holiday-card bg-white rounded-xl shadow-lg overflow-hidden flex flex-col transition-transform transform hover:-translate-y-1" data-title="<?php echo htmlspecialchars(strtolower($holiday['holiday_name'])); ?>">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold text-gray-900 pr-2"><?php echo htmlspecialchars($holiday['holiday_name']); ?></h3>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?php echo $status_class; ?> flex-shrink-0"><?php echo $status; ?></span>
                        </div>
                        <p class="text-sm font-semibold text-indigo-600 mt-2 flex items-center">
                             <svg class="h-4 w-4 mr-1.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <?php 
                                echo date('d M, Y', strtotime($holiday['start_date'])); 
                                if (!empty($holiday['end_date']) && $holiday['end_date'] !== $holiday['start_date']) {
                                    echo " to " . date('d M, Y', strtotime($holiday['end_date']));
                                }
                            ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-3 flex-grow">
                            <?php echo htmlspecialchars($holiday['description'] ?? 'No description provided.'); ?>
                        </p>
                    </div>
                    <div class="bg-gray-50 p-4 border-t flex items-center justify-end">
                        <a href="./view_notice_detail.php?id=<?php echo $holiday['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View Notice &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('holidaySearch');
    const holidayGrid = document.getElementById('holidayGrid');
    const holidayCards = holidayGrid.querySelectorAll('.holiday-card');

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        holidayCards.forEach(card => {
            const title = card.dataset.title;
            if (title.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php
// Include the footer file
require_once "./student_footer.php";
?>
