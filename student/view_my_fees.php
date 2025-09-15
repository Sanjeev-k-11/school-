<?php
// School/student/view_my_fees.php

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

// Get the logged-in student's ID from the session
$student_id = $_SESSION['id'];
$pageTitle = "My Fee Details"; // Set the page title

// Initialize variables
$fee_history = [];
$error_message = '';
$total_due = 0;
$total_paid = 0;
$outstanding_balance = 0;

// Fetch fee history from the database
if ($link) {
    // SQL to fetch all monthly fee records for the specific student
    $sql = "SELECT id, fee_month, fee_year, amount_due, amount_paid, is_paid, payment_date 
            FROM student_monthly_fees 
            WHERE student_id = ? 
            ORDER BY fee_year DESC, fee_month DESC";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $fee_history = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // Calculate totals by iterating through the fetched records
            foreach ($fee_history as $fee) {
                $total_due += (float)($fee['amount_due'] ?? 0);
                $total_paid += (float)($fee['amount_paid'] ?? 0);
            }
            $outstanding_balance = $total_due - $total_paid;

        } else {
            $error_message = "Error fetching your fee records.";
            error_log("Fee view execute error for student ID $student_id: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error preparing the fee query.";
        error_log("Fee view prepare error: " . mysqli_error($link));
    }
    mysqli_close($link);
} else {
    $error_message = "Database connection failed. Please try again later.";
}

// Month names array for display
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];


// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Fee Details</h1>
        <a href="./student_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">An Error Occurred</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php else: ?>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg text-center transition-transform transform hover:scale-105">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Billed</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">₹<?php echo number_format($total_due, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg text-center transition-transform transform hover:scale-105">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Paid</p>
                <p class="text-3xl font-bold text-green-600 mt-1">₹<?php echo number_format($total_paid, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-lg text-center transition-transform transform hover:scale-105">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Balance Due</p>
                <p class="text-3xl font-bold <?php echo ($outstanding_balance > 0) ? 'text-red-600' : 'text-blue-600'; ?> mt-1">
                    ₹<?php echo number_format($outstanding_balance, 2); ?>
                </p>
            </div>
        </div>

        <!-- Fee History Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment History</h2>
                <?php if (empty($fee_history)): ?>
                    <div class="text-center py-12">
                         <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No Fee Records Found</h3>
                        <p class="mt-1 text-sm text-gray-500">There are currently no fee records available for your account.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Period</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Due</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($fee_history as $fee):
                                    $balance = (float)$fee['amount_due'] - (float)$fee['amount_paid'];
                                    $is_paid_flag = (bool)$fee['is_paid'];

                                    if ($is_paid_flag || ($balance <= 0 && (float)$fee['amount_due'] > 0)) {
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Paid';
                                    } elseif ($balance > 0 && (float)$fee['amount_paid'] > 0) {
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        $status_text = 'Partially Paid';
                                    } elseif ($balance > 0) {
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Unpaid';
                                    } else {
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        $status_text = 'N/A';
                                    }
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($month_names[$fee['fee_month']] . ' ' . $fee['fee_year']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">₹<?php echo number_format($fee['amount_due'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-700 text-right">₹<?php echo number_format($fee['amount_paid'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?php echo ($balance > 0) ? 'text-red-700' : 'text-blue-700'; ?> text-right">₹<?php echo number_format($balance, 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center"><?php echo $fee['payment_date'] && $fee['payment_date'] !== '0000-00-00' ? date('d-M-Y', strtotime($fee['payment_date'])) : 'N/A'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <a href="view_receipt.php?fee_id=<?php echo htmlspecialchars($fee['id']); ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">View Receipt</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
