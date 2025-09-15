<?php
// Start the session
session_start();

// Check if the user is already logged in, if yes then redirect to respective dashboard
// This prevents logged-in users from seeing the login form
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // IMPORTANT: Redirect based on the role stored in the SESSION
    switch ($_SESSION['role']) {
        case 'admin':
            header("location: admin/admin_dashboard.php");
            break;
        // Redirect 'teacher', 'principal', and generic 'staff' roles to the staff dashboard
        case 'staff':
        case 'teacher':
        case 'principal':
            header("location: teacher/staff_dashboard.php"); // Assuming staff dashboard is in 'teacher' subdirectory
            break;
        case 'student':
            header("location: student/student_dashboard.php"); // Assuming student dashboard is in root
            break;
        default:
            // If role is somehow invalid, maybe just log them out or redirect to a generic page
            session_unset();
            session_destroy();
            $_SESSION['operation_message'] = "<p class='text-red-600'>Invalid session role. Please log in again.</p>";
            header("location: login.php"); // Redirect back to login
            break;
    }
    exit;
}

// Include config file - Make sure config.php is in the same directory as login.php
// Make sure your config.php establishes a valid $link connection
require_once "config.php";

// Define variables and initialize with empty values
$identifier = $password = $role = ""; // $role is from the form input
$identifier_err = $password_err = $login_err = $role_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate role - Check if 'role' key exists AND is not empty after trim
    if (!isset($_POST["role"]) || empty(trim($_POST["role"]))) {
        $role_err = "Please select a role.";
        // Preserve selected role if it was set before the error
        if (isset($_POST["role"])) {
            $role = trim($_POST["role"]);
        }
    } else {
        $role = trim($_POST["role"]); // $role holds the value from the form dropdown
    }

    // Validate identifier - Check if 'identifier' key exists AND is not empty after trim
    // The image shows "Username", but the PHP uses "Identifier" which is more generic for different roles
    if (!isset($_POST["identifier"]) || empty(trim($_POST["identifier"]))) {
        $identifier_err = "Please enter your identifier.";
        // Preserve entered identifier if it was set before the error
        if (isset($_POST["identifier"])) {
            $identifier = trim($_POST["identifier"]);
        }
    } else {
        $identifier = trim($_POST["identifier"]);
    }

    // Validate password - Check if 'password' key exists AND is not empty after trim
    if (!isset($_POST["password"]) || empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
        // We don't preserve password for security
    } else {
        $password = trim($_POST["password"]);
    }

    // If no errors in basic input validation, proceed with login attempt
    if (empty($identifier_err) && empty($password_err) && empty($role_err)) {
        $sql = ""; // SQL query will depend on the role
        $id_column_where = ""; // Column name used in the WHERE clause (username, email, virtual_id)
        $user_id_column_select = ""; // Column name for the primary ID fetched (id, staff_id, user_id)
        $name_column_select = ""; // Column name for the display name fetched
        $db_role_column_select = ""; // Column name for the role fetched (will be 'role' for staff)
        $table_name = ""; // Store table name for logging
        $stmt = false; // Initialize statement variable

        // Determine the SQL query, ID column, and name/role columns based on the FORM role
        switch ($role) { // Use the form role ($role) to decide WHICH table/query to use
            case 'admin':
                // Based on your schema assumption for 'users' table (only admins)
                $sql = "SELECT id, username, password FROM users WHERE username = ?";
                $id_column_where = "username";
                $user_id_column_select = "id"; // Primary key column in 'users' table
                $name_column_select = "username"; // Using username as display name for admin
                // Admins don't have a 'role' column in this specific table based on the SQL you had
                // We'll hardcode their role later if needed, or add 'role' to the 'users' table
                $db_role_column_select = null; // No role column in DB for admin based on this SELECT
                $table_name = "users";
                break;
            case 'staff':
                // --- CORRECTED: Include the 'role' column in the SELECT statement ---
                $sql = "SELECT staff_id, email, password, staff_name, role FROM staff WHERE email = ?";
                $id_column_where = "email";
                $user_id_column_select = "staff_id";
                $name_column_select = "staff_name";
                $db_role_column_select = "role"; // The name of the role column in the staff table
                $table_name = "staff";
                break;
            case 'student':
                // Based on your schema assumption for 'students' table
                $sql = "SELECT user_id, virtual_id, password, full_name FROM students WHERE virtual_id = ?";
                $id_column_where = "virtual_id";
                $user_id_column_select = "user_id";
                $name_column_select = "full_name";
                // Students don't have a 'role' column in this specific table based on the SQL you had
                $db_role_column_select = null; // No role column in DB for student based on this SELECT
                $table_name = "students";
                break;
            default:
                // This case should ideally not be hit if role validation works
                $login_err = "Invalid role selected during processing.";
                break;
        }

        // Proceed only if a valid role and SQL query were determined and DB link is valid
        // Check if $link is set and is a valid MySQLi link
        if (!empty($sql) && isset($link) && is_object($link) && mysqli_ping($link)) {

            // Prepare statement
            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                // Always bind the identifier (username, email, or virtual_id) as a string (s)
                mysqli_stmt_bind_param($stmt, "s", $param_identifier);

                // Set parameters
                $param_identifier = $identifier;

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // Attempt to get the result set
                    $result = mysqli_stmt_get_result($stmt);

                    // *** CHECK if getting the result was successful ***
                    if ($result === false) {
                        // Handle the error if getting the result failed
                        $login_err = "Oops! Could not retrieve results. Please try again later.";
                        error_log("Login get_result failed for role: $role, identifier: " . htmlspecialchars($identifier) . ", table: $table_name. Error: " . mysqli_stmt_error($stmt));
                    } elseif (mysqli_num_rows($result) == 1) { // We expect exactly one row
                        // Check if identifier exists and is unique

                        // Fetch the user row as an associative array
                        if ($user = mysqli_fetch_assoc($result)) {

                            // Fetch password from the 'password' column (assuming this column name exists in all tables)
                            // Check if 'password' key exists before accessing it
                            if (isset($user['password'])) {
                                $hashed_password = $user['password'];
                            } else {
                                // Handle case where 'password' column is missing in SELECT - critical error
                                error_log("Critical Error: 'password' column missing from fetched data for role: $role, table: $table_name. SQL error likely.");
                                $login_err = "An internal error occurred during login. Please try again.";
                                // Skip password verification and session setting, fall through to error display
                                $hashed_password = null; // Set to null to fail password_verify
                            }


                            // Verify the password ONLY if hashed_password was successfully retrieved
                            if (isset($hashed_password) && password_verify($password, $hashed_password)) {
                                // Password is correct

                                // --- Determine the ACTUAL role based on the database row and table queried ---
                                $actual_role = '';
                                switch ($role) { // Use the form role to know WHICH table's row is in $user
                                    case 'admin':
                                        // Assume admin role if logged in via the admin path
                                        $actual_role = 'admin';
                                        // You might want to check if the user has an 'is_admin' flag in the 'users' table if it exists
                                        break;
                                    case 'staff':
                                        // --- CORRECTED: Get the actual role from the 'role' column fetched from the staff table ---
                                        if (isset($user[$db_role_column_select])) { // Check if the role column was selected and exists
                                            $actual_role = $user[$db_role_column_select]; // Use the value from the DB!
                                        } else {
                                            // Fallback or Error: 'role' column wasn't selected for staff.
                                            // This should ideally not happen if SQL was correct. Log and error.
                                            error_log("Critical Error: Staff 'role' column missing from fetched data for ID: " . ($user[$user_id_column_select] ?? 'N/A') . ". SQL error likely.");
                                            $login_err = "An internal error occurred during login. Please try again.";
                                            // Skip session setting and redirect, fall through to error display
                                            $actual_role = ''; // Ensure actual_role is empty to skip session/redirect
                                        }
                                        break;
                                    case 'student':
                                        // Assume student role if logged in via the student path
                                        $actual_role = 'student';
                                        break;
                                        // default case handled earlier
                                }

                                // Only set session and redirect if we successfully determined an actual role
                                // Also ensure the user ID and name columns exist in the fetched data
                                if (!empty($actual_role) && isset($user[$user_id_column_select]) && isset($user[$name_column_select])) {
                                    // Store data in session variables
                                    $_SESSION["loggedin"] = true;
                                    // Use the correct ID column name ($user_id_column_select) determined earlier
                                    $_SESSION["id"] = $user[$user_id_column_select];
                                    // --- Store the ACTUAL role determined above ---
                                    $_SESSION["role"] = $actual_role;
                                    // Use the correct name column name ($name_column_select) determined earlier
                                    $_SESSION["name"] = $user[$name_column_select];

                                    // Store a welcome message in session for the dashboard
                                    $_SESSION['operation_message'] = "<p class='text-green-600'>Welcome, " . htmlspecialchars($user[$name_column_select]) . "!</p>";

                                    // Redirect user to appropriate dashboard page based on the ACTUAL role
                                    switch ($actual_role) {
                                        case 'admin':
                                            header("location: admin/admin_dashboard.php");
                                            break;
                                        // Redirect all specific staff roles ('teacher', 'principal', 'staff') to the staff dashboard
                                        case 'staff':
                                        case 'teacher':
                                        case 'principal':
                                            header("location: teacher/staff_dashboard.php"); // Assuming staff dashboard is in 'teacher' subdirectory
                                            break;
                                        case 'student':
                                            header("location: student/student_dashboard.php"); // Assuming student dashboard is in root
                                            break;
                                        default:
                                            // Handle unexpected roles after successful login - should not happen with valid actual_role
                                            session_unset();
                                            session_destroy();
                                            $_SESSION['operation_message'] = "<p class='text-red-600'>Login failed: Unknown user role after authentication.</p>"; // More specific error
                                            header("location: login.php");
                                            break;
                                    }
                                    exit; // Always exit after a header redirect
                                } else if (empty($actual_role)) {
                                    // Error message already set in the switch case
                                } else {
                                    // Error if ID or name column was missing despite successful fetch
                                    error_log("Critical Error: User ID or Name column missing from fetched data for role: $role, identifier: " . htmlspecialchars($identifier) . ". SQL error likely.");
                                    $login_err = "An internal error occurred during login. Please try again.";
                                }
                                // else: actual_role was empty, meaning an internal error occurred, $login_err is set, fall through to display error


                            } else {
                                // Password is not valid or hashed_password was null (internal error)
                                $login_err = "Invalid identifier or password.";
                            }
                        } else {
                            // This should ideally not happen if num_rows was 1 and get_result was successful
                            $login_err = "An unexpected error occurred during data retrieval. Please try again.";
                            error_log("Login fetch assoc failed after get_result/num_rows=1 for role: $role, identifier: " . htmlspecialchars($identifier));
                        }
                        // Free result set if it was successfully retrieved
                        if ($result !== false) { // Check if $result is valid before freeing
                            mysqli_free_result($result);
                        }
                    } else { // mysqli_num_rows is not 1 (0 or > 1)
                        // Identifier doesn't exist (num_rows is 0) OR there's a duplicate (num_rows > 1)
                        $login_err = "Invalid identifier or password.";
                        if ($result !== false && mysqli_num_rows($result) > 1) {
                            error_log("Duplicate identifier found for role: $role, identifier: " . htmlspecialchars($identifier) . " in table: $table_name");
                        }
                        // Free result set even if 0 or >1 rows were returned
                        if ($result !== false) {
                            mysqli_free_result($result);
                        }
                    }
                } else {
                    // Handle the error if executing the statement failed
                    $login_err = "Oops! Something went wrong with query execution. Please try again later.";
                    error_log("Login execute query failed for role: $role, identifier: " . htmlspecialchars($identifier) . ", table: $table_name. Error: " . mysqli_stmt_error($stmt));
                }

                // Close statement now if $stmt was successfully prepared
                if ($stmt !== false) {
                    mysqli_stmt_close($stmt);
                    $stmt = false; // Reset $stmt after closing
                }
            } else {
                // Handle the error if preparing the statement failed
                $login_err = "Oops! Could not prepare statement. Please try again later.";
                error_log("Login prepare statement failed for role: $role, table: $table_name. Error: " . mysqli_error($link));
            }
        } else if (empty($sql)) {
            // This specific error should be caught by the role validation above, but good for fallback
            $login_err = "Invalid role selected during processing.";
        } else if (!isset($link) || !is_object($link) || !mysqli_ping($link)) {
            // Database connection failed or is not valid
            $login_err = "Database connection failed during processing. Please check config.";
            error_log("Database connection was invalid or missing before login query prepare.");
        }
    }

    // Close connection if it was opened successfully and is still active and valid
    if (isset($link) && is_object($link) && mysqli_ping($link)) {
        mysqli_close($link);
        $link = null; // Set link to null after closing
    }
}

// Ensure connection is closed if it was opened and still valid, even if not submitting form (e.g., first page load)
// This is defensive; config.php might handle this, but good to be sure if $link is global/included.
if (isset($link) && is_object($link) && mysqli_ping($link)) {
    mysqli_close($link);
    $link = null;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Full-screen background image */
        body {
            background-image: url('uploads/7605994.jpg');
            /* Path to your image */
            background-size: cover;
            /* Cover the entire viewport */
            background-position: center;
            /* Center the image */
            background-repeat: no-repeat;
            min-height: 100vh;
            /* Ensure body takes at least viewport height */
            font-family: sans-serif;
            padding: 1rem;
            /* Add some padding for smaller screens */
            margin: 0;
            /* Remove default body margin */
            display: flex;
            /* Use flexbox to center the container */
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
            overflow: auto;
            /* Allow scrolling if content overflows vertically */
        }

        .main-login-container {
            /* Removed flexbox properties as it's now a single column */
            background-color: #ffffff;
            border-radius: 15px;
            /* More rounded like the image */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2), 0 5px 15px rgba(0, 0, 0, 0.1);
            /* Stronger shadow */
            overflow: hidden;
            /* Keep children within rounded corners */
            max-width: 450px;
            /* Set max width for the form container */
            width: 100%;
            /* Allow to shrink */
            padding: 3rem 2rem;
            /* Padding inside the container */
            box-sizing: border-box;
            /* Include padding in width */
            /* Centering is now handled by body flexbox */
        }

        /* Remove the illustration section styles */
        /* .login-illustration-section, .illustration-placeholder styles are removed below */


        .form-group {
            margin-bottom: 1.5rem;
            /* mb-6 */
            text-align: left;
        }

        label {
            display: block;
            color: #555;
            /* Darker gray */
            font-size: 0.9rem;
            /* Slightly larger text */
            font-weight: 600;
            /* font-semibold */
            margin-bottom: 0.6rem;
            /* mb-2 */
        }

        /* Style for inputs and select dropdown */
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.9rem 1.2rem;
            /* More padding */
            border: 1px solid #ccc;
            /* Light gray border */
            border-radius: 8px;
            /* Rounded corners like the image */
            font-size: 1rem;
            /* text-base */
            color: #333;
            /* Dark text color */
            background-color: #f8f8f8;
            /* Light gray background like the image */
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            /* subtle inset shadow */
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #6a5dee;
            /* Match a color from the gradient */
            box-shadow: 0 0 0 3px rgba(106, 93, 238, 0.25);
            /* Highlight color with opacity */
            background-color: #fff;
            /* White background on focus */
        }

        /* Style for the SUBMIT button */
        button[type="submit"] {
            width: 100%;
            padding: 1rem;
            /* More padding */
            background-color: #ff6d3a;
            /* Orange color from the image */
            color: white;
            font-weight: 700;
            /* font-bold */
            border-radius: 8px;
            /* Rounded corners like the image */
            transition: background-color 0.2s ease-in-out;
            cursor: pointer;
            border: none;
            font-size: 1.1rem;
            /* Slightly larger text */
            letter-spacing: 0.05em;
            /* Slight letter spacing */
            text-transform: uppercase;
            /* Uppercase like the image */
        }

        button[type="submit"]:hover {
            background-color: #f5844c;
            /* Darker orange on hover */
        }

        /* Error messages */
        .alert-danger {
            color: #c53030;
            /* red-700 */
            background-color: #fed7d7;
            /* red-200 */
            border: 1px solid #fc8181;
            /* red-400 */
            padding: 0.75rem;
            /* p-3 */
            border-radius: 0.375rem;
            /* rounded-md */
            margin-bottom: 1.5rem;
            /* mb-6 */
            font-size: 0.875rem;
            /* text-sm */
            text-align: left;
            word-break: break-word;
            /* Prevent overflow */
        }

        .text-danger {
            color: #c53030;
            /* red-700 */
            font-size: 0.75rem;
            /* text-xs */
            margin-top: 0.4rem;
            /* mt-1 */
            display: block;
            /* Ensures it's on its own line below input */
            text-align: left;
        }

        /* Title styling */
        .main-login-container h2 {
            /* Changed selector from .login-form-section h2 */
            color: #ff6d3a;
            /* Orange color for the title */
            font-size: 1.8rem;
            /* Larger title */
            font-weight: 700;
            margin-bottom: 0.5rem;
            /* Space below title */
            text-align: center;
            /* Center the title */
        }

        .main-login-container p:first-of-type {
            /* Selects the first paragraph after h2 */
            text-align: center;
            /* Center the description */
            color: #666;
            /* Gray text */
            margin-bottom: 2rem;
            /* More space below description */
            font-size: 0.9rem;
        }

        /* Style for the "Forgot Password?" link */
        .forgot-password-link {
            text-align: right;
            /* Align link to the right */
            margin-top: -0.5rem;
            /* Pull it up slightly */
            margin-bottom: 1.5rem;
            /* Space below the link */
            font-size: 0.875rem;
            /* Small text */
        }

        .forgot-password-link a {
            color: #6a5dee;
            /* Purple/Blue color */
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }

        .forgot-password-link a:hover {
            color: #4c44cf;
            /* Darker color on hover */
            text-decoration: underline;
        }

        /* Removed styles for .login-illustration-section and .illustration-placeholder */
    </style>
</head>

<body>

    <!-- Main container now only holds the form and is centered on the background image -->
    <div class="main-login-container">


        <!-- Content (Form and related elements) -->
        <!-- Removed the login-form-section wrapper, content is directly in main-login-container -->

        <h2>User Login</h2>
        <p>Please select your role and enter your credentials.</p>

        <?php
        // Display login error messages
        if (!empty($login_err)) {
            echo '<div class="alert-danger">' . $login_err . '</div>';
        }
        // Display messages from session (like access denied from dashboard)
        if (isset($_SESSION['operation_message'])) {
            // Add some basic styling to session messages if needed
            echo '<div class="alert-danger">' . $_SESSION['operation_message'] . '</div>'; // Use alert-danger for all messages for simplicity, or create separate classes
            unset($_SESSION['operation_message']); // Clear it after displaying
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate> <!-- Added novalidate to control HTML5 validation -->

            <div class="form-group">
                <label for="role">Login As:</label>
                <select name="role" id="role" class="<?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="staff" <?php echo ($role == 'staff') ? 'selected' : ''; ?>>Staff (Teacher/Principal)</option>
                    <option value="student" <?php echo ($role == 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
                <?php if (!empty($role_err)): ?><span class="text-danger"><?php echo $role_err; ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <!-- Label changed to "Identifier" to match PHP variable -->
                <label for="identifier">Identifier (Username/Email/Virtual ID):</label>
                <input type="text" name="identifier" id="identifier" class="<?php echo (!empty($identifier_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($identifier); ?>" placeholder="Enter your identifier" required>
                <?php if (!empty($identifier_err)): ?><span class="text-danger"><?php echo $identifier_err; ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" class="<?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" placeholder="Enter your password" required>
                <?php if (!empty($password_err)): ?><span class="text-danger"><?php echo $password_err; ?></span><?php endif; ?>
            </div>

            <!-- "Forgot Password?" link aligned to the right -->
            <div class="forgot-password-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <div class="form-group">
                <!-- Button text changed to "Login" but styled like "SUBMIT" -->
                <button type="submit">Login</button>
            </div>
            <a href="index.php" style="display: inline-block; padding: 10px 20px; color: blue; text-decoration: none; border-radius: 5px;"
                onmouseover="this.style.textDecoration='underline'"
                onmouseout="this.style.textDecoration='none'">
                ← Back to Home
            </a>


            <!-- Optional: Add links for registration if applicable -->
            <!-- <p class="text-gray-600 text-sm mt-4 text-center">Don't have an account? <a href="register.php" class="text-indigo-600 hover:underline">Sign up now</a>.</p> -->

        </form>

        <!-- Removed the login-illustration-section -->

    </div>

</body>

</html>