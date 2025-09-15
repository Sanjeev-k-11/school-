<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in as an admin.</p>";
    header("location: ../login.php");
    exit;
}

$pageTitle = "Bulk Print Fee Receipts";

$school_name = "Basic Public School";
$school_address = " Madhubani, Bihar, 847211";
$school_phone = "+91 8877780197";
$school_logo_path = "../uploads/basic.jpeg";

$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_class = isset($_GET['class']) ? $_GET['class'] : 'all';
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$all_receipts = [];

$available_classes = [];
if ($link) {
    $sql_classes = "SELECT DISTINCT current_class FROM students WHERE current_class IS NOT NULL AND current_class != '' ORDER BY current_class ASC";
    if ($result_classes = mysqli_query($link, $sql_classes)) {
        while ($row = mysqli_fetch_assoc($result_classes)) {
            $available_classes[] = $row['current_class'];
        }
    }
}

if (isset($_GET['month']) && isset($_GET['year'])) {
    
    $sql = "SELECT f.*, s.full_name, s.father_name, s.current_class, s.roll_number 
            FROM student_monthly_fees AS f
            JOIN students AS s ON f.student_id = s.user_id
            WHERE f.fee_month = ? AND f.fee_year = ?";

    if ($selected_class !== 'all') {
        $sql .= " AND s.current_class = ?";
    }

    if ($selected_status === 'paid') {
        $sql .= " AND f.is_paid = 1";
    } elseif ($selected_status === 'due') {
        $sql .= " AND f.is_paid = 0";
    }

    $sql .= " ORDER BY s.current_class, s.roll_number ASC";
    
    if ($link && $stmt = mysqli_prepare($link, $sql)) {
        if ($selected_class !== 'all') {
            mysqli_stmt_bind_param($stmt, "iis", $selected_month, $selected_year, $selected_class);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $selected_month, $selected_year);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $all_receipts = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("Bulk print execute error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Bulk print prepare error: " . mysqli_error($link));
    }
}

$month_names = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

if (!function_exists('convertNumberToWords')) {
    function convertNumberToWords($number) {
        $hyphen = ' '; $conjunction = ' and '; $separator = ', '; $negative = 'negative ';
        $dictionary = [0=>'zero', 1=>'one', 2=>'two', 3=>'three', 4=>'four', 5=>'five', 6=>'six', 7=>'seven', 8=>'eight', 9=>'nine', 10=>'ten', 11=>'eleven', 12=>'twelve', 13=>'thirteen', 14=>'fourteen', 15=>'fifteen', 16=>'sixteen', 17=>'seventeen', 18=>'eighteen', 19=>'nineteen', 20=>'twenty', 30=>'thirty', 40=>'forty', 50=>'fifty', 60=>'sixty', 70=>'seventy', 80=>'eighty', 90=>'ninety', 100=>'hundred', 1000=>'thousand', 1000000=>'million'];
        if (!is_numeric($number)) { return false; }
        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) { trigger_error('convertNumberToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING); return false; }
        if ($number < 0) { return $negative . convertNumberToWords(abs($number)); }
        $string = $fraction = null;
        if (strpos($number, '.') !== false) { list($number, $fraction) = explode('.', $number); }
        switch (true) {
            case $number < 21: $string = $dictionary[$number]; break;
            case $number < 100: $tens = ((int) ($number / 10)) * 10; $units = $number % 10; $string = $dictionary[$tens]; if ($units) { $string .= $hyphen . $dictionary[$units]; } break;
            case $number < 1000: $hundreds = $number / 100; $remainder = $number % 100; $string = $dictionary[$hundreds] . ' ' . $dictionary[100]; if ($remainder) { $string .= $conjunction . convertNumberToWords($remainder); } break;
            default: $baseUnit = pow(1000, floor(log($number, 1000))); $numBaseUnits = (int) ($number / $baseUnit); $remainder = $number % $baseUnit; $string = convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit]; if ($remainder) { $string .= $remainder < 100 ? $conjunction : $separator; $string .= convertNumberToWords($remainder); } break;
        }
        return $string;
    }
}
if (!function_exists('amountInWordsWithRupees')) {
    function amountInWordsWithRupees($amount) { return ucwords(convertNumberToWords((int)$amount)) . ' Rupees Only.';}
}

require_once "./admin_header.php";
?>

<style>
    .receipt-page { background-color: #fff; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px 0 rgba(0,0,0,.06); margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .paid-stamp { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-25deg); font-size: 5rem; font-weight: 800; color: rgba(34, 197, 94, 0.15); border: 10px solid rgba(34, 197, 94, 0.15); padding: 1rem 2rem; border-radius: 1rem; text-transform: uppercase; z-index: 0; pointer-events: none; user-select: none; }
    @media print {
        body * { visibility: hidden; }
        .printable-area, .printable-area * { visibility: visible; }
        .printable-area { position: absolute; left: 0; top: 0; width: 100%; }
        .receipt-page { box-shadow: none; border: none; margin: 0; width: 100%; page-break-after: always; }
        .receipt-page:last-child { page-break-after: auto; }
        .no-print { display: none !important; }
        body { background-color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .paid-stamp { color: rgba(0, 128, 0, 0.1) !important; border-color: rgba(0, 128, 0, 0.1) !important; }
        .bg-gray-100 { background-color: #f3f4f6 !important; }
        .bg-red-100 { background-color: #fee2e2 !important; }
        .bg-green-100 { background-color: #dcfce7 !important; }
        .text-green-600 { color: #059669 !important; }
        .text-red-600 { color: #dc2626 !important; }
    }
</style>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white p-6 rounded-lg shadow-md mb-8 no-print">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-gray-600 mb-6">Use the filters to generate receipts, then click the 'Print All' button.</p>
        <form action="" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                <select id="month" name="month" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <?php foreach ($month_names as $num => $name) echo "<option value='{$num}'" . ($num == $selected_month ? ' selected' : '') . ">{$name}</option>"; ?>
                </select>
            </div>
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                <select id="year" name="year" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--) echo "<option value='{$y}'" . ($y == $selected_year ? ' selected' : '') . ">{$y}</option>"; ?>
                </select>
            </div>
            <div>
                <label for="class" class="block text-sm font-medium text-gray-700">Class</label>
                <select id="class" name="class" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="all" <?php echo ($selected_class == 'all' ? 'selected' : ''); ?>>All Classes</option>
                    <?php foreach ($available_classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($selected_class == $class ? 'selected' : ''); ?>><?php echo htmlspecialchars($class); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="all" <?php echo ($selected_status == 'all' ? 'selected' : ''); ?>>All Statuses</option>
                    <option value="paid" <?php echo ($selected_status == 'paid' ? 'selected' : ''); ?>>Paid</option>
                    <option value="due" <?php echo ($selected_status == 'due' ? 'selected' : ''); ?>>Due</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-5 rounded-lg hover:bg-indigo-700 transition-colors duration-200">Generate Receipts</button>
        </form>
    </div>

    <div class="printable-area">
        <?php if (!empty($all_receipts)): ?>
            <div class="text-center mb-6 no-print">
                 <?php
                    $result_heading = "Generated " . count($all_receipts) . " Receipts for ";
                    if ($selected_class !== 'all') { $result_heading .= "Class " . htmlspecialchars($selected_class) . " "; }
                    $result_heading .= "in " . $month_names[$selected_month] . ' ' . $selected_year;
                    if ($selected_status !== 'all') { $result_heading .= " (Status: " . ucfirst($selected_status) . ")"; }
                 ?>
                 <h2 class="text-xl font-semibold text-gray-700"><?php echo $result_heading; ?></h2>
                 <button onclick="window.print()" class="mt-4 bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print All Receipts
                </button>
            </div>
            
            <?php foreach ($all_receipts as $receipt_data): ?>
                <?php
                    $balance = (float)$receipt_data['amount_due'] - (float)$receipt_data['amount_paid'];
                    $is_paid = $receipt_data['is_paid'] || ($balance <= 0 && (float)$receipt_data['amount_due'] > 0);
                ?>
                <div class="receipt-page overflow-hidden relative">
                    <?php if ($is_paid): ?><div class="paid-stamp">Paid</div><?php endif; ?>
                    <div class="p-8 relative z-10">
                        <div class="flex justify-between items-start pb-4 border-b border-gray-200">
                             <div class="flex items-center">
                                <?php if (file_exists($school_logo_path)): ?><img src="<?php echo htmlspecialchars($school_logo_path); ?>" alt="School Logo" class="h-16 w-16 mr-4"><?php endif; ?>
                                <div><h2 class="text-2xl font-extrabold text-gray-800"><?php echo htmlspecialchars($school_name); ?></h2><p class="text-sm text-gray-500"><?php echo htmlspecialchars($school_address); ?></p><p class="text-sm text-gray-500">Phone: <?php echo htmlspecialchars($school_phone); ?></p></div>
                            </div>
                            <div class="text-right"><h3 class="text-2xl font-bold <?php echo $is_paid ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $is_paid ? 'FEE RECEIPT' : 'PAYMENT ADVICE'; ?></h3><p class="text-sm text-gray-500"><strong>Receipt No:</strong> <?php echo htmlspecialchars(str_pad($receipt_data['id'], 6, '0', STR_PAD_LEFT)); ?></p><p class="text-sm text-gray-500"><strong>Date:</strong> <?php echo htmlspecialchars(date('d F, Y')); ?></p></div>
                        </div>
                        <div class="grid grid-cols-2 gap-8 mt-6">
                            <div><p class="text-sm font-semibold text-gray-500">BILL TO</p><p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($receipt_data['full_name']); ?></p><p class="text-sm text-gray-600">S/O <?php echo htmlspecialchars($receipt_data['father_name']); ?></p><p class="text-sm text-gray-600">Class: <?php echo htmlspecialchars($receipt_data['current_class']); ?> | Roll: <?php echo htmlspecialchars($receipt_data['roll_number']); ?></p></div>
                            <div class="text-right"><p class="text-sm font-semibold text-gray-500">FEE PERIOD</p><p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($month_names[$receipt_data['fee_month']] . ' ' . $receipt_data['fee_year']); ?></p><?php if(!$is_paid): ?><p class="text-sm text-red-600 font-semibold">Due Date: <?php echo htmlspecialchars(date("t F, Y", mktime(0, 0, 0, $receipt_data['fee_month'], 1, $receipt_data['fee_year']))); ?></p><?php endif; ?></div>
                        </div>
                        <div class="mt-8">
                            <table class="w-full">
                                <thead class="bg-gray-100"><tr><th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th><th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Particulars</th><th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php
                                        $item_no = 1;
                                        if ((float)$receipt_data['base_monthly_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Base Monthly Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['base_monthly_fee'], 2) . "</td></tr>"; $item_no++; }
                                        if (isset($receipt_data['monthly_van_fee']) && (float)$receipt_data['monthly_van_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Van Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_van_fee'], 2) . "</td></tr>"; $item_no++; }
                                        if (isset($receipt_data['monthly_exam_fee']) && (float)$receipt_data['monthly_exam_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Exam Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_exam_fee'], 2) . "</td></tr>"; $item_no++; }
                                        if (isset($receipt_data['monthly_electricity_fee']) && (float)$receipt_data['monthly_electricity_fee'] > 0) { echo "<tr><td class='px-4 py-2 text-gray-500'>{$item_no}</td><td class='px-4 py-2'>Electricity Fee</td><td class='px-4 py-2 text-right'>₹" . number_format($receipt_data['monthly_electricity_fee'], 2) . "</td></tr>"; $item_no++; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end mt-4">
                            <div class="w-full max-w-xs"><div class="flex justify-between py-2 border-b"><span class="font-semibold text-gray-600">Subtotal:</span><span class="font-semibold text-gray-800">₹<?php echo number_format($receipt_data['amount_due'], 2); ?></span></div><div class="flex justify-between py-2 border-b"><span class="font-semibold text-gray-600">Paid:</span><span class="font-semibold text-gray-800">₹<?php echo number_format($receipt_data['amount_paid'], 2); ?></span></div><div class="flex justify-between py-2 <?php echo $is_paid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> px-2 rounded-md"><span class="text-lg font-bold">Balance Due:</span><span class="text-lg font-bold">₹<?php echo number_format($balance, 2); ?></span></div></div>
                        </div>
                        <div class="border-t border-gray-200 mt-8 pt-6 text-xs text-gray-500 text-center">This is a computer-generated receipt and does not require a signature.</div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif(isset($_GET['month'])): ?>
            <div class="text-center bg-white p-8 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">No Records Found</h3>
                <p class="text-gray-500 mt-2">There are no fee records matching your selected filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once "./admin_footer.php";
?>