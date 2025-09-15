<?php
// School/student/view_receipt.php

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
$session_student_id = $_SESSION['id'];
$pageTitle = "Fee Receipt";

// Initialize variables
$receipt_data = null;
$error_message = '';

// --- School Information (Replace with your actual details) ---
$school_name = "Basic Public School";
$school_address = " Madhubani, Bihar, 847211";
$school_phone = "+91 8877780197";
$school_logo_path = "../uploads/basic.jpeg"; // IMPORTANT: Update this path

// --- Get Fee ID from URL and Validate ---
if (!isset($_GET['fee_id']) || !filter_var($_GET['fee_id'], FILTER_VALIDATE_INT)) {
    $error_message = "Invalid or missing fee ID.";
} else {
    $fee_id = $_GET['fee_id'];
    $fee_details = null;
    $student_details = null;

    // --- Fetch Receipt Data from Database using a two-step query for robustness ---
    if ($link) {
        // 1. Fetch the fee record first
        $sql_fee = "SELECT * FROM `student_monthly_fees` WHERE `id` = ?";
        if ($stmt_fee = mysqli_prepare($link, $sql_fee)) {
            mysqli_stmt_bind_param($stmt_fee, "i", $fee_id);
            if (mysqli_stmt_execute($stmt_fee)) {
                $result_fee = mysqli_stmt_get_result($stmt_fee);
                if (mysqli_num_rows($result_fee) == 1) {
                    $fee_details = mysqli_fetch_assoc($result_fee);
                    
                    // Authorization Check: Ensure the receipt belongs to the logged-in student
                    if ($fee_details['student_id'] != $session_student_id) {
                        $fee_details = null; // Unset data if not authorized
                        $error_message = "Access Denied. You are not authorized to view this receipt.";
                    }
                } else {
                    $error_message = "No fee record found with the specified ID.";
                }
            } else {
                $error_message = "Error fetching fee details.";
                error_log("Receipt view (fee) execute error for fee ID $fee_id: " . mysqli_stmt_error($stmt_fee));
            }
            mysqli_stmt_close($stmt_fee);
        } else {
            $error_message = "Error preparing fee query.";
            error_log("Receipt view (fee) prepare error: " . mysqli_error($link));
        }

        // 2. If fee record was found and authorized, fetch student details
        if ($fee_details && empty($error_message)) {
            $student_id_from_fee = $fee_details['student_id'];
            $sql_student = "SELECT `full_name`, `father_name`, `current_class`, `roll_number` FROM `students` WHERE `user_id` = ?";
            if ($stmt_student = mysqli_prepare($link, $sql_student)) {
                mysqli_stmt_bind_param($stmt_student, "i", $student_id_from_fee);
                if (mysqli_stmt_execute($stmt_student)) {
                    $result_student = mysqli_stmt_get_result($stmt_student);
                    if (mysqli_num_rows($result_student) == 1) {
                        $student_details = mysqli_fetch_assoc($result_student);
                    } else {
                        $error_message = "Could not find linked student details for this receipt.";
                    }
                } else {
                    $error_message = "Error fetching student details.";
                    error_log("Receipt view (student) execute error for student ID $student_id_from_fee: " . mysqli_stmt_error($stmt_student));
                }
                mysqli_stmt_close($stmt_student);
            } else {
                $error_message = "Error preparing student query.";
                 error_log("Receipt view (student) prepare error: " . mysqli_error($link));
            }
        }

        // 3. Combine into a single array for the template if both queries were successful
        if ($fee_details && $student_details && empty($error_message)) {
            $receipt_data = array_merge($fee_details, $student_details);
        }

        mysqli_close($link);
    } else {
        $error_message = "Database connection failed.";
    }
}


// Month names array for display
$month_names = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

$is_paid = false;
if ($receipt_data) {
    $balance = (float)$receipt_data['amount_due'] - (float)$receipt_data['amount_paid'];
    if ($receipt_data['is_paid'] || ($balance <= 0 && (float)$receipt_data['amount_due'] > 0)) {
        $is_paid = true;
    }
}

// --- Function to convert number to words ---
if (!function_exists('convertNumberToWords')) {
    function convertNumberToWords($number) {
        $hyphen      = ' ';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $dictionary  = array(0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety', 100 => 'hundred', 1000 => 'thousand', 1000000 => 'million');
        if (!is_numeric($number)) { return false; }
        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) { trigger_error('convertNumberToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING); return false; }
        if ($number < 0) { return $negative . convertNumberToWords(abs($number)); }
        $string = $fraction = null;
        if (strpos($number, '.') !== false) { list($number, $fraction) = explode('.', $number); }
        switch (true) {
            case $number < 21: $string = $dictionary[$number]; break;
            case $number < 100: $tens   = ((int) ($number / 10)) * 10; $units  = $number % 10; $string = $dictionary[$tens]; if ($units) { $string .= $hyphen . $dictionary[$units]; } break;
            case $number < 1000: $hundreds  = $number / 100; $remainder = $number % 100; $string = $dictionary[$hundreds] . ' ' . $dictionary[100]; if ($remainder) { $string .= $conjunction . convertNumberToWords($remainder); } break;
            default: $baseUnit = pow(1000, floor(log($number, 1000))); $numBaseUnits = (int) ($number / $baseUnit); $remainder = $number % $baseUnit; $string = convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit]; if ($remainder) { $string .= $remainder < 100 ? $conjunction : $separator; $string .= convertNumberToWords($remainder); } break;
        }
        return $string;
    }
}
if (!function_exists('amountInWordsWithRupees')) {
    function amountInWordsWithRupees($amount) {
        $amount = max(0, round((float)$amount, 2));
        $words = 'Zero';
        if ($amount > 0) {
            $words = convertNumberToWords((int)$amount);
        }
        return ucwords($words) . ' Rupees Only.';
    }
}


// Include the student header
require_once "./student_header.php";
?>
<style>
    body { background-color: #f3f4f6; }
    .receipt-container { max-width: 800px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .paid-stamp {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-25deg);
        font-size: 5rem; /* Large font size */
        font-weight: 800; /* Bold */
        color: rgba(34, 197, 94, 0.15); /* Light green, semi-transparent */
        border: 10px solid rgba(34, 197, 94, 0.15);
        padding: 1rem 2rem;
        border-radius: 1rem;
        text-transform: uppercase;
        z-index: 0; /* Behind the content */
        pointer-events: none;
        user-select: none;
    }

    @media print {
        body { background-color: #fff; padding: 0; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .receipt-container { box-shadow: none; border: none; padding: 0; max-width: 100%; margin: 0; }
        .no-print { display: none !important; }
        .paid-stamp { color: rgba(0, 128, 0, 0.1) !important; border-color: rgba(0, 128, 0, 0.1) !important; }
        .bg-gray-100 { background-color: #f3f4f6 !important; }
        .text-green-600 { color: #059669 !important; }
    }
</style>

<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="./view_my_fees.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Fee History</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php elseif ($receipt_data): ?>
        <div id="receipt-container" class="bg-white rounded-lg shadow-lg overflow-hidden relative border border-gray-200">
            <?php if ($is_paid): ?>
                <div class="paid-stamp">Paid</div>
            <?php endif; ?>
            
            <div class="p-8 relative z-10">
                <!-- Header -->
                <div class="flex justify-between items-start pb-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <?php if (file_exists($school_logo_path)): ?>
                            <img src="<?php echo htmlspecialchars($school_logo_path); ?>" alt="School Logo" class="h-16 w-16 mr-4">
                        <?php endif; ?>
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-800"><?php echo htmlspecialchars($school_name); ?></h2>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($school_address); ?></p>
                            <p class="text-sm text-gray-500">Phone: <?php echo htmlspecialchars($school_phone); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h3 class="text-2xl font-bold <?php echo $is_paid ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $is_paid ? 'FEE RECEIPT' : 'PAYMENT ADVICE'; ?></h3>
                        <p class="text-sm text-gray-500"><strong>Receipt No:</strong> <?php echo htmlspecialchars(str_pad($receipt_data['id'], 6, '0', STR_PAD_LEFT)); ?></p>
                        <p class="text-sm text-gray-500"><strong>Date:</strong> <?php echo htmlspecialchars(date('d F, Y')); ?></p>
                    </div>
                </div>

                <!-- Student Details -->
                <div class="grid grid-cols-2 gap-8 mt-6">
                    <div>
                        <p class="text-sm font-semibold text-gray-500">BILL TO</p>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($receipt_data['full_name']); ?></p>
                        <p class="text-sm text-gray-600">S/O <?php echo htmlspecialchars($receipt_data['father_name']); ?></p>
                        <p class="text-sm text-gray-600">Class: <?php echo htmlspecialchars($receipt_data['current_class']); ?> | Roll: <?php echo htmlspecialchars($receipt_data['roll_number']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-500">FEE PERIOD</p>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($month_names[$receipt_data['fee_month']] . ' ' . $receipt_data['fee_year']); ?></p>
                        <?php if(!$is_paid): ?>
                           <p class="text-sm text-red-600 font-semibold">Due Date: <?php echo htmlspecialchars(date("t F, Y", mktime(0, 0, 0, $receipt_data['fee_month'], 1, $receipt_data['fee_year']))); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fee Details Table -->
                <div class="mt-8">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Particulars</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                                $item_no = 1;
                                if ((float)$receipt_data['base_monthly_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Base Monthly Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['base_monthly_fee'], 2) . "</td></tr>"; $item_no++; }
                                if ((float)$receipt_data['monthly_van_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Van Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_van_fee'], 2) . "</td></tr>"; $item_no++; }
                                if ((float)$receipt_data['monthly_exam_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Exam Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_exam_fee'], 2) . "</td></tr>"; $item_no++; }
                                if ((float)$receipt_data['monthly_electricity_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Electricity Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_electricity_fee'], 2) . "</td></tr>"; $item_no++; }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="flex justify-end mt-4">
                    <div class="w-full max-w-xs">
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Subtotal:</span>
                            <span class="font-semibold text-gray-800">₹<?php echo number_format($receipt_data['amount_due'], 2); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Paid:</span>
                            <span class="font-semibold text-gray-800">₹<?php echo number_format($receipt_data['amount_paid'], 2); ?></span>
                        </div>
                        <div class="flex justify-between py-2 <?php echo $is_paid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> px-2 rounded-md">
                            <span class="text-lg font-bold">Balance Due:</span>
                            <span class="text-lg font-bold">₹<?php echo number_format((float)$receipt_data['amount_due'] - (float)$receipt_data['amount_paid'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Notes & Footer -->
                <div class="border-t border-gray-200 mt-8 pt-6">
                    <?php if ($is_paid): ?>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-500">PAYMENT DETAILS</p>
                            <p class="text-sm text-gray-700"><strong>Amount in Words:</strong> <?php echo htmlspecialchars(amountInWordsWithRupees($receipt_data['amount_paid'])); ?></p>
                            <p class="text-sm text-gray-700"><strong>Payment Mode:</strong> <?php echo htmlspecialchars($receipt_data['payment_mode'] ?? 'N/A'); ?></p>
                            <?php if(!empty($receipt_data['transaction_id'])): ?>
                                <p class="text-sm text-gray-700"><strong>Transaction ID:</strong> <?php echo htmlspecialchars($receipt_data['transaction_id']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-8">
                            <p class="font-semibold text-lg text-green-700">Thank You For Your Payment!</p>
                            <p class="text-xs text-gray-500">This is a computer-generated receipt and does not require a signature.</p>
                        </div>
                    <?php else: ?>
                         <div class="text-center">
                            <p class="font-semibold text-lg text-red-700">Payment Required</p>
                            <p class="text-sm text-gray-500">Please contact the school office to complete the payment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 text-center no-print">
            <button onclick="window.print()" class="bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-md">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </button>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
