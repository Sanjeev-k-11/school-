<?php
// School/admin/edit_holiday.php

// Start the session
session_start();

require_once "../config.php";

// Security check: Only admins can edit
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can edit notices.</p>";
    header("location: ./view_holidays.php");
    exit;
}

$pageTitle = "Edit Holiday Notice";

// Initialize variables
$holiday_id = null;
$holiday_name = $start_date = $end_date = $description = "";
$holiday_name_err = $start_date_err = $end_date_err = "";
$operation_message = "";
$holiday = null; // To store fetched holiday data for printing

// --- Validate ID from URL and Fetch Existing Data ---
$holiday_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$holiday_id) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid Holiday ID provided.</p>";
    header("location: ./view_holidays.php");
    exit;
}

// Fetch the holiday data to populate the form
$sql_fetch = "SELECT * FROM holidays WHERE id = ?";
if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $holiday_id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    if ($holiday = mysqli_fetch_assoc($result)) {
        $holiday_name = $holiday['holiday_name'];
        $start_date = $holiday['start_date'];
        $end_date = $holiday['end_date'];
        $description = $holiday['description'];
    } else {
        $_SESSION['operation_message'] = "<p class='text-red-600'>Holiday notice not found.</p>";
        header("location: ./view_holidays.php");
        exit;
    }
    mysqli_stmt_close($stmt_fetch);
}

// --- Handle Form Submission for Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate holiday name
    if (empty(trim($_POST["holiday_name"]))) {
        $holiday_name_err = "Please enter a name for the holiday.";
    } else {
        $holiday_name = trim($_POST["holiday_name"]);
    }

    // Validate start date
    if (empty(trim($_POST["start_date"]))) {
        $start_date_err = "Please select a start date.";
    } else {
        $start_date = trim($_POST["start_date"]);
    }

    // Validate end date (optional)
    $end_date = trim($_POST["end_date"]);
    if (!empty($end_date) && strtotime($end_date) < strtotime($start_date)) {
        $end_date_err = "End date cannot be before the start date.";
    }

    $description = trim($_POST["description"]);

    // If no validation errors, proceed to update
    if (empty($holiday_name_err) && empty($start_date_err) && empty($end_date_err)) {
        $sql_update = "UPDATE holidays SET holiday_name = ?, start_date = ?, end_date = ?, description = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            $param_end = !empty($end_date) ? $end_date : NULL;
            mysqli_stmt_bind_param($stmt_update, "ssssi", $holiday_name, $start_date, $param_end, $description, $holiday_id);

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Holiday notice updated successfully.</p>";
                header("location: ./view_holidays.php");
                exit();
            } else {
                $operation_message = "<p class='text-red-600'>Error updating notice. Please try again.</p>";
            }
            mysqli_stmt_close($stmt_update);
        }
    } else {
        $operation_message = "<p class='text-yellow-600'>Please correct the errors and try again.</p>";
    }
}

mysqli_close($link);

// Include the header file
require_once "./admin_header.php";
?>
<style>
    /* This class visually hides the element but keeps it available for screen readers and printing */
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    @media print {
        body * { visibility: hidden; }
        .printable-area, .printable-area * { visibility: visible; }
        .printable-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 1cm; }
        .no-print { display: none !important; }
        .visually-hidden {
            position: static;
            width: auto;
            height: auto;
            padding: 0;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }
    }
</style>

<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-2xl font-bold text-gray-800">Edit Holiday Notice</h1>
        <a href="./view_holidays.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to All Notices</a>
    </div>

    <!-- Form for Editing -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 no-print">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $holiday_id; ?>" method="post">
            <?php if (!empty($operation_message)): ?>
                <div class="p-4 mb-6 text-sm rounded-lg <?php echo strpos($operation_message, 'red') ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>" role="alert">
                    <?php echo $operation_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="mb-4">
                <label for="holiday_name" class="block text-gray-700 text-sm font-bold mb-2">Holiday/Festival Name:</label>
                <input type="text" name="holiday_name" id="holiday_name" class="shadow appearance-none border <?php echo !empty($holiday_name_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($holiday_name); ?>" required>
                <span class="text-red-500 text-xs italic"><?php echo $holiday_name_err; ?></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" class="shadow appearance-none border <?php echo !empty($start_date_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $start_date_err; ?></span>
                </div>
                <div>
                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">End Date (Optional):</label>
                    <input type="date" name="end_date" id="end_date" class="shadow appearance-none border <?php echo !empty($end_date_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($end_date); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $end_date_err; ?></span>
                </div>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description (Optional):</label>
                <textarea name="description" id="description" rows="4" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700" placeholder="Add any additional details..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg">Update Notice</button>
                <button type="button" onclick="window.print()" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">Print Notice</button>
            </div>
        </form>
    </div>

    <!-- Printable Notice Area - Visually hidden on screen, visible for print -->
    <div id="printableNotice" class="visually-hidden printable-area bg-white p-8">
        <div class="text-center border-b-2 pb-4 mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Modern Public School</h2>
            <p class="text-gray-500">Madhubani, Bihar</p>
            <h3 class="text-xl font-semibold text-gray-700 mt-4 uppercase tracking-wider">Notice</h3>
        </div>
        <div class="flex justify-between text-sm text-gray-600 mb-6">
            <span><strong>Ref No:</strong> SCH/HOL/<?php echo date('Y') . '/' . htmlspecialchars($holiday_id); ?></span>
            <span><strong>Date:</strong> <?php echo date('d F, Y', strtotime($holiday['created_at'])); ?></span>
        </div>
        <div class="text-center mb-8">
            <h4 class="text-lg font-bold underline"><?php echo htmlspecialchars($holiday['holiday_name']); ?></h4>
        </div>
        <div class="text-gray-700 space-y-4">
            <p>This is to inform all students, teachers, and staff that the school will remain closed on account of <strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong>.</p>
            <p>
                The holiday will be observed from 
                <strong><?php echo date('l, d F Y', strtotime($holiday['start_date'])); ?></strong>
                <?php if (!empty($holiday['end_date']) && $holiday['end_date'] !== $holiday['start_date']): ?>
                    to <strong><?php echo date('l, d F Y', strtotime($holiday['end_date'])); ?></strong>.
                <?php endif; ?>
            </p>
            <?php if (!empty($holiday['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($holiday['description'])); ?></p>
            <?php endif; ?>
            <p>The school will resume its normal schedule on the next working day.</p>
        </div>
        <div class="mt-16 text-right">
            <p class="font-semibold"><?php echo htmlspecialchars($holiday['created_by_name']); ?></p>
            <p class="text-sm text-gray-600">Principal</p>
        </div>
    </div>
</div>

<?php
// Include the footer file
require_once "./admin_footer.php";
?>
