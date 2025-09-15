<?php
// index.php - School Landing Page

// --- PHPMailer Integration ---
// Use Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start the session to handle form feedback messages
session_start();

// --- DATABASE CONNECTION ---
require_once "./config.php"; // Assuming config.php is in the same directory

// Define school details
$schoolName = "Basic Public School";
$schoolTagline = "Excellence in Education in Madhubani";
$schoolAddress = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$schoolEmail = "vc74295@gmail.com";
$schoolPhone = "+91 88777 80197";

// --- Contact Form Submission Handling ---
$form_message = "";
$form_message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['contact_message'] = "Please fill all fields correctly.";
        $_SESSION['contact_message_type'] = "error";
    } else {
        // --- 1. SAVE TO DATABASE ---
        $sql_save_message = "INSERT INTO contact_form_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_save_message)) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);

            if (mysqli_stmt_execute($stmt)) {
                // --- 2. SEND EMAIL (only if DB save was successful) ---
                require './mail_config.php'; // Your SMTP settings
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_ENCRYPTION;
                    $mail->Port       = SMTP_PORT;

                    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                    $mail->addAddress($schoolEmail, $schoolName);
                    $mail->addReplyTo($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = " New Contact Form Message: " . htmlspecialchars($subject);

                    $mail->Body = '
<div style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #2c3e50;">New Contact Form Submission</h2>
    <p style="font-size: 15px;">You have received a new message from the <strong>school website</strong> contact form.</p>

    <table cellpadding="8" cellspacing="0" border="0" style="width: 100%; background-color: #ffffff; border-radius: 6px; border: 1px solid #ccc;">
        <tr>
            <td style="width: 150px; font-weight: bold;">Name:</td>
            <td>' . htmlspecialchars($name) . '</td>
        </tr>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold;">Email:</td>
            <td>' . htmlspecialchars($email) . '</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Subject:</td>
            <td>' . htmlspecialchars($subject) . '</td>
        </tr>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold; vertical-align: top;">Message:</td>
            <td>' . nl2br(htmlspecialchars($message)) . '</td>
        </tr>
    </table>

    <p style="margin-top: 20px; font-size: 14px; color: #666;">This message was sent from the contact form on your website.</p>
</div>';

                    $mail->AltBody =
                        "New Contact Form Submission\n\n" .
                        "Name: " . htmlspecialchars($name) . "\n" .
                        "Email: " . htmlspecialchars($email) . "\n" .
                        "Subject: " . htmlspecialchars($subject) . "\n\n" .
                        "Message:\n" . htmlspecialchars($message);

                    $mail->send();
                    $_SESSION['contact_message'] = 'Your message has been sent successfully. Thank you!';
                    $_SESSION['contact_message_type'] = 'success';
                } catch (Exception $e) {
                    $_SESSION['contact_message'] = "Your message was saved, but could not be sent by email. Please contact the school directly. Mailer Error: {$mail->ErrorInfo}";
                    $_SESSION['contact_message_type'] = 'error';
                }
            } else {
                $_SESSION['contact_message'] = "Error: Could not save your message to the database.";
                $_SESSION['contact_message_type'] = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['contact_message'] = "Error: Database query could not be prepared.";
            $_SESSION['contact_message_type'] = 'error';
        }
    }
    // Redirect back to the contact section to show the message
    header("Location: index.php#contact");
    exit();
}

// Check for session message to display
if (isset($_SESSION['contact_message'])) {
    $form_message = $_SESSION['contact_message'];
    $form_message_type = $_SESSION['contact_message_type'];
    unset($_SESSION['contact_message'], $_SESSION['contact_message_type']);
}

// Split the school name and tagline into words for animation
$schoolNameWords = explode(' ', $schoolName);
$schoolTaglineWords = explode(' ', $schoolTagline);

// Path to school-related background images (WEB PATHS)
$backgroundImagePaths = [
    "./uploads/10513496.jpg",
    "./uploads/7605994.jpg",
    "./uploads/11159076.jpg",
];

// Filter out non-existing local paths
$validBackgroundImages = array_filter($backgroundImagePaths, function ($webPath) {
    $serverPath = __DIR__ . '/' . ltrim($webPath, './');
    return file_exists($serverPath);
});

// Use placeholders if no valid local images are found
if (empty($validBackgroundImages)) {
    $validBackgroundImages = [
        "https://via.placeholder.com/1920x1080?text=Modern+Classroom",
        "https://via.placeholder.com/1920x1080?text=School+Campus",
        "https://via.placeholder.com/1920x1080?text=Library+and+Learning",
    ];
}

// Content for the page sections
$schoolDescription = "Welcome to {$schoolName}, where we are dedicated to fostering a nurturing and stimulating environment for students to grow academically, socially, and personally. We believe in providing quality education that prepares students for a bright future.";
$aboutUsContent = "Our school has a rich history of academic excellence and community involvement. We offer a wide range of programs, extracurricular activities, and support services designed to meet the diverse needs of our students. Our experienced faculty and staff are committed to creating a positive and engaging learning experience.";
$ourMission = "To empower students with the knowledge, skills, and values needed to thrive in a dynamic world. We aim to cultivate critical thinking, creativity, and a lifelong passion for learning.";
$ourVision = "To be a leading educational institution recognized for our commitment to innovation, academic excellence, and the holistic development of every student.";

// --- Fetch latest positive feedback for index.php (using $link) ---
$positive_feedback_data_index = [];
$sql_fetch_positive_feedback_index = "SELECT name, rating, feedback_text, submission_date
                                        FROM feedback_submissions
                                        WHERE rating >= 4
                                        ORDER BY submission_date DESC
                                        LIMIT 3";
if ($result_index = mysqli_query($link, $sql_fetch_positive_feedback_index)) {
    while ($row_index = mysqli_fetch_assoc($result_index)) {
        $positive_feedback_data_index[] = $row_index;
    }
    mysqli_free_result($result_index);
} else {
    error_log("Error fetching positive feedback for index.php: " . mysqli_error($link));
}

// Ensure the database connection is closed after all queries
// In a typical application, you'd close the connection only once at the very end of the script
// but since this is part of index.php, it will be closed after the script finishes execution.
// If you uncommented mysqli_close($link) in feedback.php, you might need to re-open it here if needed later in index.php
// For simplicity and to avoid issues, I've just corrected the variable.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?php echo htmlspecialchars($schoolName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .full-page-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-size: cover;
            background-position: center;
            transition: background-image 1s ease-in-out;
            z-index: -1;
            filter: brightness(50%);
        }

        .hero-overlay {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 20px;
        }

        .animated-word {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            display: inline-block;
            margin: 0 2px;
        }

        .main-content {
            background-color: #fff;
            position: relative;
            z-index: 1;
        }
        /* Specific styles for testimonial cards on index.php */
        .testimonial-card-index {
            border-radius: 12px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fdfdfd;
        }
        .testimonial-card-index:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.12);
        }
        .testimonial-card-index .star-rating-index span {
            color: #ffc107;
        }
    </style>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "EducationalOrganization",
            "name": "Basic Public School",
            "url": "https://school.mednova.store",
            "logo": "./uploads/basic.jpeg",
            "description": "Basic Public School is dedicated to academic excellence, character building, and holistic development.",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "XYZ Road",
                "addressLocality": "Madhubani",
                "addressRegion": "Bihar",
                "postalCode": "847211",
                "addressCountry": "IN"
            },
            "telephone": "+91-9876543210",
            "email": "info@basicpublicschool.in",
            "sameAs": [
                "https://www.facebook.com/basicpublicschool",
                "https://www.instagram.com/basicpublicschool",
                "https://www.youtube.com/@basicpublicschool"
            ],
            "founder": {
                "@type": "Person",
                "name": "Mr. Vishal Kumar"
            },
            "foundingDate": "2005-06-15",
            "openingHours": "Mo-Fr 08:00-15:00"
        }
    </script>

</head>

<body class="bg-gray-50">

    <!-- Full-page background container -->
    <div id="hero-background" class="full-page-background"></div>

    <?php
    require_once "./header.php";
    ?>

    <!-- Hero Section -->
    <section id="home" class="hero-overlay">
        <h1 id="school-name-animated" class="text-4xl md:text-6xl font-bold mb-4">
            <?php foreach ($schoolNameWords as $word) {
                echo '<span class="animated-word">' . htmlspecialchars($word) . '</span> ';
            } ?>
        </h1>
        <p id="school-tagline-animated" class="text-lg md:text-xl opacity-90">
            <?php foreach ($schoolTaglineWords as $word) {
                echo '<span class="animated-word">' . htmlspecialchars($word) . '</span> ';
            } ?>
        </p>
    </section>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- About Us Section -->
        <section id="about" class="py-20 px-6">
            <div class="container mx-auto text-center">
                <h2 class="text-3xl font-bold mb-4">About Our School</h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-12"><?php echo htmlspecialchars($aboutUsContent); ?></p>
                <div class="grid md:grid-cols-2 gap-12 text-left">
                    <div class="bg-gray-100 p-8 rounded-lg">
                        <h3 class="text-2xl font-semibold mb-3 text-indigo-600">Our Mission</h3>
                        <p class="text-gray-700"><?php echo htmlspecialchars($ourMission); ?></p>
                    </div>
                    <div class="bg-gray-100 p-8 rounded-lg">
                        <h3 class="text-2xl font-semibold mb-3 text-indigo-600">Our Vision</h3>
                        <p class="text-gray-700"><?php echo htmlspecialchars($ourVision); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Key Features Section -->
              <section id="features" class="py-20 px-6 bg-gradient-to-b from-indigo-50 to-blue-100 rounded-lg shadow-inner">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl font-bold mb-12 text-indigo-900">Why Parents & Students Love <span class="text-indigo-600">Basic Public School</span></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Caring & Experienced Teachers -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-yellow-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm6 0h-6m6 0v-1a6 6 0 00-9-5.197" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Caring & Experienced Teachers</h3>
                <p class="text-gray-600">Loving guidance from trained educators who nurture every child’s potential — from Nursery to Class 9.</p>
            </div>

            <!-- Fun & Interactive Learning -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-green-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Fun & Interactive Learning</h3>
                <p class="text-gray-600"> creative activities, and hands-on projects make learning exciting and engaging.</p>
            </div>

            <!-- Modern & Safe Campus -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-blue-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9 5 9-5v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Modern & Safe Campus</h3>
                <p class="text-gray-600">Bright classrooms, safe playgrounds, and a secure environment where parents can feel confident.</p>
            </div>

            <!-- Arts, Music & Sports -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-pink-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6h13v13H9z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Arts, Music & Sports</h3>
                <p class="text-gray-600">From painting and singing to cricket and football — we encourage creativity and physical growth.</p>
            </div>

            <!-- Individual Attention -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-purple-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Individual Attention</h3>
                <p class="text-gray-600">Small class sizes so every child gets the focus, guidance, and encouragement they deserve.</p>
            </div>

            <!-- Vibrant School Community -->
            <div class="bg-white p-8 rounded-lg shadow-lg transition transform hover:scale-105 duration-300 hover:shadow-xl">
                <div class="text-red-500 mb-4">
                    <svg class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h2a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2h2" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-gray-800">Vibrant School Community</h3>
                <p class="text-gray-600">A friendly and inclusive environment where students build lifelong friendships and values.</p>
            </div>
        </div>
    </div>
</section>

        <?php require_once 'our.php'; ?>
        <?php require_once 'best.php'; ?>
        <?php require_once 'gallery.php'; ?>

        <!-- Latest Positive Feedback Section on Home Page -->
       <section id="home-feedback-showcase" class="relative py-16 px-6 bg-gray-50 overflow-hidden">
    <div class="container mx-auto max-w-7xl">
        <h2 class="text-4xl font-extrabold text-gray-900 text-center mb-16">What Our Parents Are Saying</h2>

        <?php if (!empty($positive_feedback_data_index)): ?>
            <div class="relative">
                <div id="testimonial-carousel" class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory space-x-8 px-4 py-8 -mx-4 hide-scrollbar">
                    <?php foreach ($positive_feedback_data_index as $feedback): ?>
                        <div class="testimonial-card-index flex-none w-[90%] md:w-[45%] lg:w-[30%] snap-center p-8 bg-white rounded-xl shadow-xl border-t-4 border-indigo-500 transform transition-transform duration-300 hover:scale-[1.02] relative">
                            <div class="absolute top-4 right-4 text-indigo-100 text-7xl font-serif">
                                &#8220;
                            </div>
                            
                            <div class="star-rating-index mb-4 text-2xl text-yellow-400">
                                <?php for ($i = 0; $i < $feedback['rating']; $i++): ?>
                                    <span>&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            
                            <p class="text-gray-700 italic text-lg mb-6 z-10 leading-relaxed">"<?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>"</p>
                            
                            <div class="mt-auto">
                                <p class="font-bold text-xl text-indigo-600">- <?php echo htmlspecialchars($feedback['name']); ?></p>
                                <p class="text-sm text-gray-500 mt-1"><?php echo date("M j, Y", strtotime($feedback['submission_date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button id="carousel-prev" class="absolute top-1/2 -left-4 md:-left-8 transform -translate-y-1/2 p-3 bg-white text-indigo-600 rounded-full shadow-lg hover:bg-indigo-600 hover:text-white transition-colors duration-300 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button id="carousel-next" class="absolute top-1/2 -right-4 md:-right-8 transform -translate-y-1/2 p-3 bg-white text-indigo-600 rounded-full shadow-lg hover:bg-indigo-600 hover:text-white transition-colors duration-300 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
            
            <div class="mt-16 text-center">
                <a href="feedback.php" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 px-10 rounded-full shadow-lg transition duration-300 transform hover:-translate-y-1">
                    View All Feedback
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600 text-lg">No positive feedback to display yet.</p>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('testimonial-carousel');
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');

        // Function to scroll the carousel
        const scrollCarousel = (direction) => {
            const cardWidth = carousel.querySelector('.testimonial-card-index').offsetWidth;
            const scrollAmount = direction === 'next' ? cardWidth + 32 : -(cardWidth + 32); // 32px is the gap-x-8
            carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        };

        // Event listeners for the buttons
        prevBtn.addEventListener('click', () => scrollCarousel('prev'));
        nextBtn.addEventListener('click', () => scrollCarousel('next'));
    });
</script>

<style>
    /* Hide scrollbar for the carousel on Webkit browsers (Chrome, Safari) */
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for other browsers */
    .hide-scrollbar {
        -ms-overflow-style: none;  /* Internet Explorer 10+ */
        scrollbar-width: none;  /* Firefox */
    }
</style>

        <!-- Contact Section -->
        <section id="contact" class="py-20 px-6">
            <div class="container mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-3xl text-black font-bold">Contact</h2>
                    <p class="text-lg text-gray-600 mt-2">We are here to help. If you have any questions, concerns, or need assistance, feel free to contact us.</p>
                </div>

                <?php if (!empty($form_message)): ?>
                    <div class="max-w-4xl mx-auto mb-6 p-4 rounded-md <?php echo $form_message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($form_message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Left Column: Details & Map -->
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="text-teal-500 mt-1"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg></div>
                                <div>
                                    <h4 class="font-semibold text-lg">Address</h4>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($schoolAddress); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="text-teal-500 mt-1"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg></div>
                                <div>
                                    <h4 class="font-semibold text-lg">Call Us</h4>
                                    <p class="text-gray-600"><a href="tel:<?php echo htmlspecialchars($schoolPhone); ?>" class="hover:underline"><?php echo htmlspecialchars($schoolPhone); ?></a></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="text-teal-500 mt-1"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg></div>
                                <div>
                                    <h4 class="font-semibold text-lg">Email Us</h4>
                                    <p class="text-gray-600"><a href="mailto:<?php echo htmlspecialchars($schoolEmail); ?>" class="hover:underline"><?php echo htmlspecialchars($schoolEmail); ?></a></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 rounded-lg overflow-hidden">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3574.333333333333!2d86.08111111111111!3d26.38027777777778!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ec55a62f1a60b9%3A0x8f7069094858e42!2sMaharaj%20Ganj%2C%20Madhubani%2C%20Bihar%20847211!5e0!3m2!1sen!2sin!4v1678886400000!5m2!1sen!2sin" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                    <!-- Right Column: Contact Form -->
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <form action="index.php#contact" method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                                    <input type="text" name="name" id="name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Your Email</label>
                                    <input type="email" name="email" id="email" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                                <input type="text" name="subject" id="subject" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                            </div>
                            <div class="mb-4">
                                <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                                <textarea name="message" id="message" rows="5" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500"></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="inline-block bg-teal-500 hover:bg-teal-600 bg-red-300 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition duration-300">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Image Carousel ---
            const backgroundElement = document.getElementById('hero-background');
            const imageUrls = <?php echo json_encode(array_values($validBackgroundImages)); ?>;
            let currentImageIndex = 0;

            function changeBackground() {
                if (imageUrls.length === 0) return;
                backgroundElement.style.backgroundImage = `url('${imageUrls[currentImageIndex]}')`;
                currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
            }
            if (imageUrls.length > 0) {
                changeBackground();
                setInterval(changeBackground, 5000);
            }

            // --- Text Animation ---
            const nameWords = document.querySelectorAll('#school-name-animated .animated-word');
            const taglineWords = document.querySelectorAll('#school-tagline-animated .animated-word');

            function animateElements(elements) {
                let delay = 0;
                elements.forEach(word => {
                    setTimeout(() => {
                        word.style.opacity = 1;
                    }, delay);
                    delay += 150;
                });
            }
            animateElements(nameWords);
            setTimeout(() => animateElements(taglineWords), nameWords.length * 150);
        });
    </script>

</body>

</html>
<?php
require_once "footer.php"
?>
