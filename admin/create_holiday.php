<?php
// School/admin/create_holiday.php

// Start the session
session_start();

require_once "../config.php";

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can create holiday notices.</p>";
    header("location: ./admin_dashboard.php");
    exit;
}

// Set the page title
$pageTitle = "Create Holiday Notice";

// Initialize variables
$holiday_name = $start_date = $end_date = $description = "";
$holiday_name_err = $start_date_err = $end_date_err = "";
$operation_message = "";

// Handle form submission
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

    // Description is optional, no validation needed unless you have specific rules
    $description = trim($_POST["description"]);

    // Check input errors before inserting into database
    if (empty($holiday_name_err) && empty($start_date_err) && empty($end_date_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO holidays (holiday_name, start_date, end_date, description, created_by_name) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            // Note: end_date can be NULL
            mysqli_stmt_bind_param($stmt, "sssss", $param_name, $param_start, $param_end, $param_desc, $param_created_by);

            // Set parameters
            $param_name = $holiday_name;
            $param_start = $start_date;
            $param_end = !empty($end_date) ? $end_date : NULL; // Set to NULL if empty
            $param_desc = $description;
            $param_created_by = $_SESSION['name'] ?? 'Admin';

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['operation_message'] = "<p class='text-green-600'>Holiday notice created successfully.</p>";
                header("location: ./admin_dashboard.php");
                exit();
            } else {
                error_log("Holiday creation failed: " . mysqli_stmt_error($stmt));
                $operation_message = "<p class='text-red-600'>Error creating holiday notice. Please try again.</p>";
            }

            mysqli_stmt_close($stmt);
        } else {
            error_log("Holiday create prepare failed: " . mysqli_error($link));
            $operation_message = "<p class='text-red-600'>Database error. Could not prepare the statement.</p>";
        }
    } else {
        $operation_message = "<p class='text-yellow-600'>Please correct the errors and try again.</p>";
    }
    mysqli_close($link);
}

// Include the header file
require_once "./admin_header.php";
?>

<div class="w-full max-w-screen-md mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Create Holiday Notice</h1>

    <?php if (!empty($operation_message)): ?>
        <div class="p-4 mb-6 text-sm rounded-lg <?php echo strpos($operation_message, 'red') ? 'bg-red-100 text-red-800' : (strpos($operation_message, 'yellow') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'); ?>" role="alert">
            <?php echo $operation_message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="holiday_name" class="block text-gray-700 text-sm font-bold mb-2">Holiday/Festival Name:</label>
                <input type="text" name="holiday_name" id="holiday_name" class="shadow appearance-none border <?php echo !empty($holiday_name_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($holiday_name); ?>" required>
                <span class="text-red-500 text-xs italic"><?php echo $holiday_name_err; ?></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" class="shadow appearance-none border <?php echo !empty($start_date_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $start_date_err; ?></span>
                </div>
                <div>
                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">End Date (Optional):</label>
                    <input type="date" name="end_date" id="end_date" class="shadow appearance-none border <?php echo !empty($end_date_err) ? 'border-red-500' : 'border-gray-300'; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($end_date); ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $end_date_err; ?></span>
                </div>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description (Optional):</label>
                <textarea name="description" id="description" rows="4" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Add any additional details about the holiday..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline">
                    Create Notice
                </button>
                <a href="./admin_dashboard.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include the footer file
require_once "./admin_footer.php";
?>
