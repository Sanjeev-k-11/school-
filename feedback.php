<?php
// feedback.php - School Feedback Page

session_start();

// --- DATABASE CONNECTION ---
require_once "./config.php"; // Assuming config.php is in the same directory

$schoolName = "Basic Public School"; // Re-using school name for display

$form_message = "";
$form_message_type = "";

// Handle Feedback Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1, "max_range" => 5)));
    $feedback_text = trim($_POST['feedback_text']);

    // Basic validation
    if (empty($name) || empty($feedback_text) || $rating === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['feedback_message'] = "Please fill all fields correctly, including a valid rating (1-5) and email.";
        $_SESSION['feedback_message_type'] = "error";
    } else {
        // Sanitize inputs
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $feedback_text = htmlspecialchars($feedback_text, ENT_QUOTES, 'UTF-8');

        // Insert into database
        $sql_insert_feedback = "INSERT INTO feedback_submissions (name, email, rating, feedback_text) VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql_insert_feedback)) {
            mysqli_stmt_bind_param($stmt, "ssis", $name, $email, $rating, $feedback_text);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback_message'] = 'Thank you for your valuable feedback!';
                $_SESSION['feedback_message_type'] = 'success';
            } else {
                $_SESSION['feedback_message'] = "Error: Could not save your feedback to the database.";
                $_SESSION['feedback_message_type'] = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['feedback_message'] = "Error: Database query could not be prepared.";
            $_SESSION['feedback_message_type'] = 'error';
        }
    }
    header("Location: feedback.php"); // Redirect to prevent re-submission
    exit();
}

// Check for session message to display
if (isset($_SESSION['feedback_message'])) {
    $form_message = $_SESSION['feedback_message'];
    $form_message_type = $_SESSION['feedback_message_type'];
    unset($_SESSION['feedback_message'], $_SESSION['feedback_message_type']);
}

// --- Fetch existing feedback and calculate average rating ---
$feedback_data = [];
$total_rating = 0;
$feedback_count = 0;

$sql_fetch_feedback = "SELECT name, rating, feedback_text, submission_date FROM feedback_submissions ORDER BY submission_date DESC";
if ($result = mysqli_query($link, $sql_fetch_feedback)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $feedback_data[] = $row;
        $total_rating += $row['rating'];
        $feedback_count++;
    }
    mysqli_free_result($result);
} else {
    // Handle error if feedback cannot be fetched
    error_log("Error fetching feedback: " . mysqli_error($link));
}

$average_rating = $feedback_count > 0 ? round($total_rating / $feedback_count, 1) : 0;

// Close database connection
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        /* Custom styles for star rating */
        .stars input[type="radio"] {
            display: none;
        }
        .stars label {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input[type="radio"]:checked ~ label {
            color: #ffc107; /* Gold color for selected stars */
        }
        /* Reverse order for star selection logic */
        .stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }
        .stars > label {
            padding: 0 5px;
        }

        /* Styles for average rating stars */
        .avg-stars span {
            font-size: 1.5rem;
            color: #ffc107;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <?php require_once "./header.php"; ?>

    <main class="flex-grow container mx-auto px-4 py-8 md:py-12">
        <section id="feedback-form" class="bg-white p-8 rounded-lg shadow-md mb-12 max-w-2xl mx-auto">
            <h2 class="text-3xl text-black font-bold text-center mb-6">Share Your Feedback</h2>
            <p class="text-lg text-gray-600 text-center mb-8">We appreciate your thoughts! Please leave your rating and feedback below.</p>

            <?php if (!empty($form_message)): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $form_message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
            <?php endif; ?>

            <form action="feedback.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input type="text" name="name" id="name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                        <input type="email" name="email" id="email" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-center">Your Rating</label>
                    <div class="stars">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">&#9733;</label>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="feedback_text" class="block text-sm font-medium text-gray-700 mb-1">Your Feedback</label>
                    <textarea name="feedback_text" id="feedback_text" rows="6" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" name="submit_feedback" class="inline-block bg-red-300 hover:bg-red-400 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition duration-300">Submit Feedback</button>
                </div>
            </form>
        </section>

        <section id="recent-feedback" class="bg-white p-8 rounded-lg shadow-md max-w-3xl mx-auto">
            <h2 class="text-3xl text-black font-bold text-center mb-6">What Others Say</h2>
            <?php if ($feedback_count > 0): ?>
                <div class="text-center mb-8">
                    <p class="text-xl text-gray-700 mb-2">Average Rating: <span class="font-bold text-teal-600"><?php echo $average_rating; ?> / 5</span></p>
                    <div class="avg-stars">
                        <?php
                        // Display average stars based on calculated rating
                        $full_stars = floor($average_rating);
                        $half_star = ($average_rating - $full_stars) >= 0.5;
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<span>&#9733;</span>'; // Full star
                        }
                        if ($half_star) {
                            echo '<span style="color: #ffc107; position: relative; display: inline-block;">&#9733;<span style="position: absolute; left: 0; width: 50%; overflow: hidden;">&#9734;</span></span>'; // Half star (visual approximation)
                        }
                        for ($i = 0; $i < (5 - $full_stars - ($half_star ? 1 : 0)); $i++) {
                            echo '<span style="color: #ccc;">&#9733;</span>'; // Empty star
                        }
                        ?>
                    </div>
                </div>

                <div class="space-y-6">
                    <?php foreach ($feedback_data as $feedback): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($feedback['name']); ?></h3>
                                <div class="flex items-center">
                                    <?php
                                    for ($i = 0; $i < $feedback['rating']; $i++) {
                                        echo '<span class="text-yellow-500">&#9733;</span>';
                                    }
                                    for ($i = 0; $i < (5 - $feedback['rating']); $i++) {
                                        echo '<span class="text-gray-300">&#9733;</span>';
                                    }
                                    ?>
                                    <span class="text-sm text-gray-500 ml-2"><?php echo htmlspecialchars($feedback['rating']); ?>/5</span>
                                </div>
                            </div>
                            <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
                            <p class="text-sm text-gray-500 text-right">Submitted on: <?php echo date("F j, Y, g:i a", strtotime($feedback['submission_date'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-600">No feedback submitted yet. Be the first to share your thoughts!</p>
            <?php endif; ?>
        </section>
    </main>

    <?php require_once "./footer.php"; ?>

</body>
</html>
