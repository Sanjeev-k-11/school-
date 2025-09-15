<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Facilities For Kids</title>
    <!-- Google Fonts for 'Poppins' (similar to font used in image) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for the scroll-to-top icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- CSS Variables for easy color management --- */
        :root {
            --red-title: #E74C3C;
            --text-dark: #333;
            --text-medium: #555;

            /* Colors for the numbered circles */
            --circle-01-bg: #FFB988;   /* Light orange */
            --circle-01-border: #F79F6C; /* Darker orange */

            --circle-02-bg: #FAD7C8;   /* Light peach */
            --circle-02-border: #F4B39F; /* Darker peach */

            --circle-03-bg: #BFE7E7;   /* Light cyan */
            --circle-03-border: #99D9D9; /* Darker cyan */

            --circle-04-bg: #F3B4CB;   /* Light pink */
            --circle-04-border: #EF96B4; /* Darker pink */

            --circle-05-bg: #F9E8BB;   /* Light yellow */
            --circle-05-border: #F4DB9B; /* Darker yellow */

            --circle-06-bg: #D2C4E3;   /* Light purple */
            --circle-06-border: #B39AD3; /* Darker purple */

            --scroll-btn-bg: #5C2B7A; /* Dark purple for scroll button */
        }

        /* --- Basic Reset and Body Styles --- */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8; /* A very light grey background */
            line-height: 1.6;
            -webkit-font-smoothing: antialiased; /* Smoother fonts */
            -moz-osx-font-smoothing: grayscale;
        }

        /* --- Main Section Container --- */
        .facilities-section {
            position: relative; /* Crucial for positioning internal elements */
            width: 100%;
            max-width: 1400px; /* Max width of the overall section */
            margin: 40px auto; /* Center the section with vertical margin */
            padding: 40px 20px 80px 20px; /* Padding inside the section */
            background-color: #fff; /* White background for the section */
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Subtle shadow */
            overflow: hidden; /* Important to contain absolutely positioned images */
        }

        /* --- Section Title --- */
        .section-title {
            text-align: center;
            color: var(--red-title);
            font-size: 2.8em;
            font-weight: 700;
            margin-bottom: 60px;
            padding-top: 20px;
        }

        /* --- Features Grid Layout --- */
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two equal columns */
            gap: 50px 100px; /* Vertical and horizontal gaps between items */
            max-width: 1000px; /* Max width for the grid content */
            margin: 0 auto; /* Center the grid */
            position: relative;
            z-index: 1; /* Ensure text is above background images */
        }

        /* --- Individual Feature Item --- */
        .feature-item {
            display: flex;
            align-items: flex-start; /* Align number and text to the top */
            gap: 20px; /* Space between number circle and text content */
        }

        /* --- Numbered Circles Styling --- */
        .number-circle {
            min-width: 60px; /* Fixed width to keep circle shape */
            height: 60px;
            border-radius: 50%; /* Makes it a circle */
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600; /* Semi-bold for the number */
            font-size: 1.5em; /* Size of the number */
            color: var(--text-dark);
            border-width: 2px;
            border-style: solid;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Subtle shadow for depth */
        }

        /* Specific colors for each circle */
        .circle-01 { background-color: var(--circle-01-bg); border-color: var(--circle-01-border); }
        .circle-02 { background-color: var(--circle-02-bg); border-color: var(--circle-02-border); }
        .circle-03 { background-color: var(--circle-03-bg); border-color: var(--circle-03-border); }
        .circle-04 { background-color: var(--circle-04-bg); border-color: var(--circle-04-border); }
        .circle-05 { background-color: var(--circle-05-bg); border-color: var(--circle-05-border); }
        .circle-06 { background-color: var(--circle-06-bg); border-color: var(--circle-06-border); }

        /* --- Feature Item Text Content --- */
        .feature-item h3 {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0; /* Remove default margin */
            padding-top: 5px; /* Adjust to visually align with number */
        }

        .feature-item p {
            font-size: 0.95em;
            color: var(--text-medium);
            margin-top: 5px; /* Space below title */
        }

        /* --- Decorative Images --- */
        /* All decorative images are absolutely positioned within .facilities-section */
        .facilities-section img {
            position: absolute;
            z-index: 0; /* Ensures they are behind the text content */
            pointer-events: none; /* Prevents them from being clickable or interfering with text selection */
        }

        .deco-sun {
            top: 20px;
            left: 50px;
            width: 100px;
            height: auto;
        }

        .deco-pencil {
            bottom: 30px;
            left: 50px;
            width: 120px;
            height: auto;
            transform: rotate(-15deg); /* Slight rotation */
        }

        .deco-kids-numbers {
            bottom: 0; /* Aligned to the bottom of the section */
            left: 50%;
            transform: translateX(-50%); /* Centers horizontally */
            width: 500px;
            height: auto;
            z-index: 0;
        }

        .deco-happy-face {
            top: 50px;
            right: 80px;
            width: 70px;
            height: auto;
        }

        .deco-puzzle {
            top: 38%; /* Vertical position */
            right: 150px;
            width: 80px;
            height: auto;
            transform: rotate(15deg); /* Slight rotation */
        }

        .deco-paint-brush {
            bottom: 50px;
            right: 100px;
            width: 80px;
            height: auto;
            transform: rotate(-20deg); /* Slight rotation */
        }

        .deco-plant {
            bottom: 0px;
            right: 0px;
            width: 150px;
            height: auto;
            z-index: 0;
        }

        /* --- Scroll to Top Button --- */
        .scroll-to-top-button {
            position: absolute; /* Positioned relative to .facilities-section */
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--scroll-btn-bg);
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5em;
            text-decoration: none; /* Remove underline */
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease; /* Smooth hover effect */
            z-index: 10; /* Ensures it's on top of other elements */
        }

        .scroll-to-top-button:hover {
            background-color: #7a3a9a; /* Darker shade on hover */
        }

        /* --- Responsive Adjustments --- */
        @media (max-width: 992px) {
            .section-title {
                font-size: 2.2em;
                margin-bottom: 40px;
            }
            .features-grid {
                grid-template-columns: 1fr; /* Stack columns into a single column */
                gap: 30px;
                max-width: 600px; /* Adjust grid width for single column */
            }
            .feature-item h3 {
                font-size: 1.1em;
            }
            .feature-item p {
                font-size: 0.9em;
            }

            /* Adjust image positions/sizes for medium screens */
            .deco-sun { top: 10px; left: 20px; width: 70px; }
            .deco-pencil { bottom: 20px; left: 20px; width: 90px; }
            .deco-kids-numbers { width: 350px; }
            .deco-happy-face { top: 20px; right: 20px; width: 50px; }
            .deco-puzzle { top: 25%; right: 50px; width: 60px; }
            .deco-paint-brush { bottom: 30px; right: 50px; width: 60px; }
            .deco-plant { width: 100px; }

            .scroll-to-top-button {
                width: 40px;
                height: 40px;
                font-size: 1.2em;
            }
        }

        @media (max-width: 768px) {
             .facilities-section {
                padding: 20px 15px 60px 15px;
                margin: 20px auto;
            }
            .section-title {
                font-size: 1.8em;
                margin-bottom: 30px;
            }
            /* Hide some decorative elements on smaller screens to avoid clutter */
            .deco-puzzle, .deco-paint-brush, .deco-plant {
                display: none;
            }
        }

        @media (max-width: 480px) {
            /* Hide most decorative elements on very small screens */
            .deco-kids-numbers, .deco-sun, .deco-pencil, .deco-happy-face {
                display: none;
            }
            .features-grid {
                padding: 0 10px; /* Add some horizontal padding to the grid items */
            }
        }
    </style>
</head>
<body>
    <section class="facilities-section">
        <!-- Main title of the section -->
        <h2 class="section-title">Best Facilities For Kids</h2>

        <!-- Grid container for the feature items -->
        <div class="features-grid">
            <!-- Feature Item 1 -->
            <div class="feature-item">
                <div class="number-circle circle-01">01</div>
                <div>
                    <h3>Safe and secure outdoor play area</h3>
                    <p>A fenced and monitored space with age-appropriate equipment ensuring children can play freely under supervision.</p>
                </div>
            </div>
            <!-- Feature Item 4 (placed next for grid layout) -->
            <div class="feature-item">
                <div class="number-circle circle-04">04</div>
                <div>
                    <h3>Interactive learning tools and educational toys</h3>
                    <p>Various hands-on materials and toys that engage children while promoting cognitive and motor skill development.</p>
                </div>
            </div>
            <!-- Feature Item 2 -->
            <div class="feature-item">
                <div class="number-circle circle-02">02</div>
                <div>
                    <h3>Colorful and stimulating classrooms</h3>
                    <p>Vibrant, well-organized rooms with visually appealing decor that encourages learning and creativity.</p>
                </div>
            </div>
            <!-- Feature Item 5 -->
            <div class="feature-item">
                <div class="number-circle circle-05">05</div>
                <div>
                    <h3>Art and creativity corner with diverse materials</h3>
                    <p>An area stocked with art supplies to encourage self-expression and creativity through various art mediums.</p>
                </div>
            </div>
            <!-- Feature Item 3 -->
            <div class="feature-item">
                <div class="number-circle circle-03">03</div>
                <div>
                    <h3>Indoor play spaces for physical activities</h3>
                    <p>Areas designed for physical play, promoting gross motor skills through activities like climbing, jumping, and balancing.</p>
                </div>
            </div>
            <!-- Feature Item 6 -->
            <div class="feature-item">
                <div class="number-circle circle-06">06</div>
                <div>
                    <h3>Multimedia and technology integration for learning</h3>
                    <p>Access to age-appropriate technology, such as computers or tablets, used as a supplementary tool to enhance learning experiences.</p>
                </div>
            </div>
        </div>

        <!-- Decorative Images -->
        <!-- These image paths are directly linked to Imgur for demonstration.
             For a real project, you would replace these with paths to your local image files.
             Ideally, use SVG for these illustrations for better scalability and smaller file sizes. -->
        <img src="./uploads/206.jpg" alt="Sun illustration" class="deco-sun">
        <img src="./uploads/O4YINS0.jpg" alt="Pencil illustration" class="deco-pencil">
        <img src="./uploads/image.png" alt="Kids with numbers illustration" class="deco-kids-numbers">
        <img src="./uploads/1.png" alt="Happy face illustration" class="deco-happy-face">
        <img src="./uploads/2.png" alt="Puzzle piece illustration" class="deco-puzzle">
        <img src="./uploads/p.png" alt="Paint brush illustration" class="deco-paint-brush">
        <img src="./uploads/pl.png" alt="Plant illustration" class="deco-plant">

        <!-- Scroll to Top Button -->
        <a href="#top" class="scroll-to-top-button" aria-label="Scroll to top of page">
            <i class="fas fa-arrow-up"></i>
        </a>
    </section>

    <script>
        // JavaScript for smooth scrolling to the top when the button is clicked
        document.querySelector('.scroll-to-top-button').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor link behavior
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Smooth scroll effect
            });
        });
    </script>
</body>
</html>