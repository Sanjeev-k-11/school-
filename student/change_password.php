<?php
// change_password.php
session_start(); // Start the session (essential for getting user_id from session)

// Include database connection
// Make sure this path is correct for your project structure
// If change_password.php is in 'your_project/pages/' and config.php is in 'your_project/'
// then '../config.php' is correct.
require_once '../config.php';

// Initialize message variables
$message = "";
$message_type = ""; // 'success' or 'error'

// !!! CRITICAL SECURITY NOTICE !!!
// In a real application, you MUST get the user_id from the session after they log in.
// The hardcoded user_id below is for demonstration ONLY and is INSECURE for production.
// --- REPLACE THIS BLOCK IN PRODUCTION ---
$user_id_to_change = 14; // Example user ID. DO NOT USE IN PRODUCTION.
// For example, if you have a login system, it would be:
/*
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect them to the login page.
    header("location: login.php"); // Replace 'login.php' with your actual login page
    exit;
}
$user_id_to_change = $_SESSION['user_id'];
// Example for displaying username in header (assuming it's stored in session)
$username = $_SESSION['username'] ?? 'Guest';
*/
// For this standalone demo, we'll ensure username is defined:
$username = $_SESSION['username'] ?? 'John Doe'; // Default for demo if not logged in
// --- END OF DEMO-ONLY CODE ---


// Process form submission when the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Use null coalescing operator for safety
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // --- Input Validation ---
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $message = "Please fill in all password fields.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_new_password) {
        $message = "Your new password and confirmation password do not match.";
        $message_type = "error";
    } elseif (strlen($new_password) < 8) { // Enforce a minimum password length
        $message = "New password must be at least 8 characters long.";
        $message_type = "error";
    } else {
        // --- Database Interaction using Prepared Statements to Prevent SQL Injection ---
        $sql_select = "SELECT password FROM students WHERE user_id = ?";

        if ($stmt_select = mysqli_prepare($link, $sql_select)) {
            mysqli_stmt_bind_param($stmt_select, "i", $user_id_to_change);

            if (mysqli_stmt_execute($stmt_select)) {
                mysqli_stmt_store_result($stmt_select);

                // Check if the user exists
                if (mysqli_stmt_num_rows($stmt_select) == 1) {
                    mysqli_stmt_bind_result($stmt_select, $stored_hashed_password);
                    mysqli_stmt_fetch($stmt_select);

                    // --- Verify Current Password ---
                    // Use password_verify() to securely compare the submitted password against the stored hash
                    if (password_verify($current_password, $stored_hashed_password)) {
                        
                        // --- Hash New Password and Update ---
                        // Use PASSWORD_DEFAULT for a strong, modern hashing algorithm (currently bcrypt)
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Added updated_at to track last password change
                        $sql_update = "UPDATE students SET password = ?, updated_at = NOW() WHERE user_id = ?"; 
                        
                        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
                            mysqli_stmt_bind_param($stmt_update, "si", $hashed_new_password, $user_id_to_change);

                            if (mysqli_stmt_execute($stmt_update)) {
                                $message = "Password updated successfully!";
                                $message_type = "success";
                                // Clear fields on successful update for security/freshness
                                $_POST['current_password'] = '';
                                $_POST['new_password'] = '';
                                $_POST['confirm_new_password'] = '';
                            } else {
                                $message = "Oops! Something went wrong while updating. Please try again later. Error: " . mysqli_error($link); // More specific error for debug
                                $message_type = "error";
                            }
                            mysqli_stmt_close($stmt_update);
                        } else {
                             $message = "Database error: Could not prepare the update statement. Error: " . mysqli_error($link); // More specific error for debug
                             $message_type = "error";
                        }
                    } else {
                        $message = "The current password you entered is incorrect.";
                        $message_type = "error";
                    }
                } else {
                    $message = "User account not found. Please contact support.";
                    $message_type = "error";
                }
            } else {
                $message = "Oops! Something went wrong while fetching your data. Error: " . mysqli_error($link); // More specific error for debug
                $message_type = "error";
            }
            mysqli_stmt_close($stmt_select);
        } else {
            $message = "Database error: Could not prepare the select statement. Error: " . mysqli_error($link); // More specific error for debug
            $message_type = "error";
        }
    }
}

// Close the database connection
if(isset($link)) {
    mysqli_close($link);
}
require_once "./student_header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Basic Public School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for eye icon and header logo -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Variables for easy theme customization */
        :root {
            --primary-color: #6a11cb; /* Deep purple */
            --secondary-color: #2575fc; /* Vibrant blue */
            --background-color: #f0f2f5; /* Light grey background */
            --form-background: #ffffff; /* White form background */
            --text-color: #333;
            --label-color: #555;
            --border-color: #e0e0e0; /* Lighter border */
            --input-focus-shadow: rgba(106, 17, 203, 0.2); /* Soft shadow for focus */

            --success-bg: #d1e7dd; /* Light green */
            --success-text: #0f5132; /* Dark green */
            --error-bg: #f8d7da; /* Light red */
            --error-text: #842029; /* Dark red */

            /* Password Strength Colors */
            --strength-weak: #dc3545; /* Red */
            --strength-medium: #ffc107; /* Orange */
            --strength-strong: #28a745; /* Green */
            --strength-very-strong: #007bff; /* Blue */
        }

        /* --- Global / Body Styles --- */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh; /* Ensure body takes full viewport height */
            display: flex; /* Use flexbox for overall layout */
            flex-direction: column; /* Stack header, main, footer vertically */
            box-sizing: border-box; /* Include padding in height calculation */
        }

        /* --- Header Navigation Bar --- */
        .header-nav {
            background-color: #fff;
            padding: 10px 30px; /* Adjust padding to match image */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            position: fixed; /* Fix header to top */
            top: 0;
            left: 0;
            z-index: 1000; /* Ensure it's above other content */
            height: 60px; /* Explicit height for consistency and padding calculation */
        }

        .header-left {
            display: flex;
            align-items: center;
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .logo-icon {
            font-size: 1.8em; /* Adjust icon size */
            color: var(--primary-color); /* Match theme color */
            margin-right: 10px;
        }

        .header-nav nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px; /* Space between nav items */
            flex-wrap: wrap; /* Allow navigation items to wrap on smaller screens */
            justify-content: center; /* Center nav items if they wrap */
        }

        .header-nav nav ul li a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 5px 0;
            transition: color 0.3s;
            white-space: nowrap; /* Prevent text wrapping within nav items */
        }

        .header-nav nav ul li a:hover {
            color: var(--primary-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .header-right span {
            color: #666;
            font-size: 0.9em; /* Slightly smaller text */
        }

        .header-right .logout-btn {
            background-color: #dc3545; /* Red color for logout */
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .header-right .logout-btn:hover {
            background-color: #c82333;
        }

        /* --- Main Content Area --- */
        .main-content {
            flex-grow: 1; /* Allows main content to take up remaining space */
            display: flex; /* Use flexbox to center the form within it */
            justify-content: center; /* Center form horizontally */
            align-items: center; /* Center form vertically */
            padding: 80px 20px 20px 20px; /* Top padding to clear fixed header, plus general padding */
            box-sizing: border-box;
            width: 100%; /* Ensure it takes full width */
        }

        .form-container {
            background-color: var(--form-background);
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 480px;
            box-sizing: border-box;
            border-top: 5px solid var(--primary-color);
            transform: scale(1);
            transition: transform 0.3s ease-in-out;
            /* margin-top/bottom are no longer strictly needed here due to main-content centering */
            margin-top: 0; 
            margin-bottom: 0; 
        }
        .form-container:hover {
            transform: scale(1.01);
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .form-header p {
            font-size: 1.1em;
            color: var(--label-color);
            margin: 0;
            letter-spacing: 0.2px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--label-color);
            font-weight: 600;
            font-size: 0.95em;
        }

        .password-wrapper {
            position: relative;
        }

        input[type="password"],
        input[type="text"] /* For when password type is toggled */ {
            width: 100%;
            padding: 12px 40px 12px 15px; /* Right padding for icon */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 4px var(--input-focus-shadow);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }

        /* Password Strength Meter */
        .strength-meter {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 10px;
            display: flex;
            overflow: hidden; /* Ensures bar stays within bounds */
        }
        .strength-bar {
            height: 100%;
            width: 0; /* Initial width */
            transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
            border-radius: 4px; /* Maintain rounded corners */
        }
        #strength-text {
            margin-top: 5px;
            font-size: 0.85em;
            text-align: right;
            font-weight: 600;
            height: 1.2em; /* Reserve space to prevent layout shift */
            color: #6c757d; /* Default text color */
        }
        
        /* Password Suggestion Button */
        #suggest-password {
            font-size: 0.85em;
            font-weight: 600;
            color: var(--primary-color);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 0;
            float: right;
            transition: color 0.3s;
            text-decoration: none; /* Remove default underline */
        }
        #suggest-password:hover {
            text-decoration: underline;
            color: var(--secondary-color);
        }
        #suggest-password:active {
            transform: translateY(1px);
        }


        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s, background 0.3s;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.4);
        }
        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        /* Message Styling & Animation */
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            display: none; /* Hidden by default, will be set to block by JS 'show' class */
            opacity: 0; /* For fade-in animation */
            transform: translateY(-10px); /* For slight slide-down animation */
            transition: opacity 0.5s ease-out, transform 0.5s ease-out; /* For auto-hide transition */
        }
        .message.success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #b7dbca; /* Manually set darker border for success */
        }
        .message.error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #ebcccd; /* Manually set darker border for error */
        }
        .message.show {
            display: block; /* Make it visible for animation */
            opacity: 1;
            transform: translateY(0);
        }

        /* --- Footer Styles --- */
        .footer {
            background-color: #333;
            color: #f8f8f8;
            padding: 20px 30px;
            text-align: center;
            font-size: 0.9em;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
            margin-top: auto; /* Pushes footer to the bottom in a flex column layout */
        }
        .footer p {
            margin: 0;
        }

        /* --- Responsive adjustments --- */
        @media (max-width: 1024px) {
            .header-nav nav ul {
                gap: 15px; /* Reduce gap for smaller screens */
            }
        }
        @media (max-width: 768px) {
            .header-nav {
                flex-direction: column; /* Stack header items vertically */
                align-items: flex-start; /* Align items to the start */
                height: auto; /* Auto height for stacked content */
                padding: 10px 20px;
                position: relative; /* Make it relative to stack, not fixed */
                box-shadow: none; /* Remove shadow if it looks bad stacked */
            }
            .header-left {
                margin-bottom: 10px;
            }
            .header-nav nav ul {
                width: 100%; /* Full width for nav links */
                justify-content: space-around; /* Distribute links */
                margin-bottom: 10px;
            }
            .header-right {
                width: 100%;
                justify-content: space-between; /* Space between welcome text and logout */
            }
            /* Adjust body padding and main-content padding to account for header no longer being fixed */
            body {
                padding-top: 0; /* No fixed header, no body top padding */
            }
            .main-content {
                padding-top: 20px; /* Adjust padding for non-fixed header. Add some general top padding. */
            }
        }
        @media (max-width: 480px) {
            .header-nav nav ul {
                flex-direction: column; /* Stack nav links on very small screens */
                align-items: center;
            }
            .header-nav nav ul li {
                width: 100%;
                text-align: center;
            }
            .form-container {
                padding: 30px 20px; /* Reduce padding for smaller screens */
            }
            .main-content {
                padding: 15px; /* More overall padding on very small screens */
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation Bar -->
     

    <main class="main-content">
        <div class="form-container">
            <div class="form-header">
                <div class="logo">Basic Public School</div>
                <p>Update Your Password</p>
            </div>

            <?php if (!empty($message)): ?>
                <!-- Added htmlspecialchars for XSS prevention -->
                <div id="statusMessage" class="message <?php echo $message_type; ?> show">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="current_password" name="current_password" 
                               value="<?php echo htmlspecialchars($_POST['current_password'] ?? ''); ?>" required autocomplete="current-password">
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <button type="button" id="suggest-password">Suggest Strong Password</button>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" 
                               value="<?php echo htmlspecialchars($_POST['new_password'] ?? ''); ?>" required autocomplete="new-password">
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                    <div class="strength-meter">
                        <div id="strength-bar" class="strength-bar"></div>
                    </div>
                    <div id="strength-text"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_new_password" name="confirm_new_password" 
                               value="<?php echo htmlspecialchars($_POST['confirm_new_password'] ?? ''); ?>" required autocomplete="new-password">
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>
                
                <button type="submit">Change Password</button>
            </form>
        </div>
    </main>
 

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. SHOW/HIDE PASSWORD FUNCTIONALITY ---
        // Select all eye icons to make all password fields toggleable
        const togglePasswordIcons = document.querySelectorAll('.toggle-password');
        togglePasswordIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling; // The input field is the previous sibling
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                // Toggle the eye icon between open and closed eye
                this.classList.toggle('fa-eye-slash');
            });
        });

        // --- 2. PASSWORD STRENGTH METER ---
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        newPasswordInput.addEventListener('input', updatePasswordStrength);
        
        // Initial check if there's any pre-filled value (e.g., after failed submission)
        if (newPasswordInput.value.length > 0) {
            updatePasswordStrength();
        }

        function updatePasswordStrength() {
            const password = newPasswordInput.value;
            const strength = checkPasswordStrength(password); // Get score from function
            
            let strengthValue = 0;
            let barColor = '';
            let text = '';

            // Map score to percentage and color
            switch (strength.score) {
                case 0: strengthValue = 0; text = ''; break;
                case 1: strengthValue = 25; barColor = 'var(--strength-weak)'; text = 'Weak'; break;
                case 2: strengthValue = 50; barColor = 'var(--strength-medium)'; text = 'Medium'; break;
                case 3: strengthValue = 75; barColor = 'var(--strength-strong)'; text = 'Strong'; break;
                case 4: strengthValue = 100; barColor = 'var(--strength-very-strong)'; text = 'Very Strong'; break;
            }
            
            strengthBar.style.width = strengthValue + '%';
            strengthBar.style.backgroundColor = barColor;
            strengthText.textContent = text;
            strengthText.style.color = barColor;
        }

        function checkPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++; // Good length
            if (/[A-Z]/.test(password)) score++; // Contains uppercase
            if (/[0-9]/.test(password)) score++; // Contains numbers
            if (/[^A-Za-z0-9]/.test(password)) score++; // Contains special characters
            
            // Adjust score for very short passwords even if they have other complexities
            if (password.length < 6 && password.length > 0) score = Math.min(score, 1);
            if (password.length === 0) score = 0;

            return { score: score };
        }

        // --- 3. STRONG PASSWORD SUGGESTION ---
        const suggestBtn = document.getElementById('suggest-password');
        const confirmPasswordInput = document.getElementById('confirm_new_password');

        suggestBtn.addEventListener('click', function() {
            const strongPassword = generateStrongPassword();
            newPasswordInput.value = strongPassword;
            confirmPasswordInput.value = strongPassword;
            
            // Trigger the input event to update the strength meter
            newPasswordInput.dispatchEvent(new Event('input'));
        });

        function generateStrongPassword() {
            const length = 16; // Recommended length for strong password
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
            let password = "";
            // Use window.crypto.getRandomValues for cryptographically secure random numbers
            const randomValues = new Uint32Array(length);
            window.crypto.getRandomValues(randomValues); // Fill the array with random numbers

            for (let i = 0; i < length; i++) {
                // Get a random character from the charset using modulo operator
                password += charset.charAt(randomValues[i] % charset.length);
            }
            return password;
        }

        // --- 4. MESSAGE AUTO-HIDE AND FADE EFFECT ---
        const statusMessage = document.getElementById('statusMessage');
        if (statusMessage) {
            // Message is already shown by PHP, now set a timeout to hide it
            setTimeout(() => {
                // Apply transition for opacity and transform
                statusMessage.style.transition = 'opacity 0.7s ease, transform 0.7s ease';
                statusMessage.style.opacity = '0';
                statusMessage.style.transform = 'translateY(-20px)'; // Slide up slightly as it fades out

                // Remove the element from the DOM after the transition is complete
                statusMessage.addEventListener('transitionend', function handler() {
                    statusMessage.style.display = 'none';
                    // Remove the event listener to prevent it from firing multiple times
                    statusMessage.removeEventListener('transitionend', handler);
                });
            }, 5000); // Message starts fading out after 5 seconds
        }
    });
    </script>
</body>
</html>

<?php
require_once "./student_footer.php";
?>