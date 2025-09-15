<?php
// --- Configuration Inclusion ---
// This line includes your database configuration from config.php.
// IMPORTANT: Make sure you have a 'config.php' file in the same directory
// with your database credentials defined using 'define()'.
require_once "./config.php";

// Create database connection using the defined constants from config.php
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset for the connection
if (!$conn->set_charset("utf8mb4")) {
    // This part handles error if setting charset fails, which is important for proper character display
    error_log(sprintf("Error loading character set utf8mb4: %s\n", $conn->error));
}

// --- SQL Query to Fetch Events for the Next Year ---
// This query selects all holidays where:
// 1. The start_date is within the next 365 days from today OR
// 2. The end_date (if it exists) is within the next 365 days from today AND
// 3. The holiday has not already ended (end_date is NULL or >= TODAY)
//    This prevents showing past holidays that might have a start_date in the range but already ended.
// We order them by start_date for a clear chronological view.

$sql = "SELECT id, holiday_name, start_date, end_date, description
        FROM holidays
        WHERE (start_date <= CURDATE() + INTERVAL 1 YEAR AND (end_date IS NULL OR end_date >= CURDATE()))
        ORDER BY start_date ASC";

$result = $conn->query($sql);

// Include the header file for consistent page structure
require_once './header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Holidays</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to right, #e0e7ff, #eff6ff); /* Soft gradient background */
            color: #334155;
            min-height: 100vh; /* Ensure full height for background */
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1; /* Allows main content to take available space */
            max-width: 95%; /* Slightly wider container */
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 16px; /* More rounded corners */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); /* More prominent shadow */
            transition: all 0.3s ease-in-out;
        }
        .main-content:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        h1 {
            color: #1e3a8a; /* Darker blue for emphasis */
            font-size: 2.5rem; /* Larger title */
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }
        p.intro-text {
            color: #475569;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden; /* Ensures rounded corners on table */
            border-radius: 12px;
        }
        th, td {
            padding: 1.25rem 1rem; /* More padding */
            text-align: left;
            border-bottom: 1px solid #d1e3f8; /* Lighter border for separation */
        }
        th {
            background-color: #3b82f6; /* Stronger blue for headers */
            font-weight: 700;
            color: #ffffff; /* White text for headers */
            text-transform: uppercase;
            font-size: 0.95rem;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0; /* Make headers sticky for long tables */
            z-index: 10;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tbody tr:nth-child(even) {
            background-color: #f8faff; /* Light stripe for readability */
        }
        tbody tr:hover {
            background-color: #e0f2fe; /* Highlight on hover */
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease-in-out;
        }
        .no-holidays-message {
            padding: 3rem;
            background-color: #fefcbf; /* Light yellow background */
            border-radius: 8px;
            text-align: center;
            font-size: 1.25rem;
            color: #854d0c; /* Darker yellow/brown text */
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        }
        /* Specific rounded corners for the top-most header row */
        table thead tr th:first-child { border-top-left-radius: 12px; }
        table thead tr th:last-child { border-top-right-radius: 12px; }

        /* Specific rounded corners for the bottom-most body row */
        table tbody tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        table tbody tr:last-child td:last-child { border-bottom-right-radius: 12px; }
    </style>
</head>
<body class="p-4">
    <div class="main-content">
        <h1 class="text-center font-extrabold">Upcoming Holiday Events ✨</h1>

        <?php
        if ($result->num_rows > 0) {
             echo '<div class="overflow-x-auto rounded-lg shadow-lg">'; /* Added shadow-lg for the table container */
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Holiday Name</th>';
            echo '<th>Start Date</th>';
            echo '<th>End Date</th>';
            echo '<th>Description</th>'; 
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            // Output data of each row
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
                echo '<td>' . htmlspecialchars($row["holiday_name"]) . '</td>';
                echo '<td>' . htmlspecialchars($row["start_date"]) . '</td>';
                echo '<td>' . (isset($row["end_date"]) && $row["end_date"] !== NULL ? htmlspecialchars($row["end_date"]) : '<span class="text-gray-500 italic">Single Day</span>') . '</td>';
                echo '<td>' . htmlspecialchars($row["description"]) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // Close overflow-x-auto
        } else {
            echo '<div class="no-holidays-message">';
            echo '<p class="font-semibold">Oops! No upcoming holidays found for the next year. 🎉</p>';
            echo '<p class="text-sm mt-2">Check back later or add new events to the database!</p>';
            echo '</div>';
        }
        ?>
    </div>

    <?php
    // Close database connection
    $conn->close();
    // Include the footer file for consistent page structure
    require_once './footer.php';
    ?>
</body>
</html>
