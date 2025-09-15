<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our School Classes - Enhanced</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Define a custom color palette for Tailwind */
        :root {
            --primary-color: #4f46e5; /* A vibrant indigo */
            --secondary-color: #312e81; /* A deep indigo */
            --background-color: #eef2ff; /* Light, subtle background */
            --card-background: #ffffff;
            --text-color: #1f2937;
            --muted-text-color: #6b7280;
        }

        /* Custom scrollbar styles for a cleaner look */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }

        /* Base styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Classes Section Styling */
        .classes-section {
            padding: 5rem 1rem;
            position: relative;
            background-color: var(--background-color);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary-color);
            margin-bottom: 2rem;
        }

        /* Enhanced Slider Container */
        .slider-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .class-card-wrapper {
            display: flex;
            overflow-x: scroll;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
            padding: 1rem;
            gap: 2rem;
            align-items: stretch;
        }

        /* Individual Class Card */
        .class-card {
            background: var(--card-background);
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            width: 320px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Card hover effect */
        .class-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .class-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .card-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .card-content p {
            color: var(--muted-text-color);
            font-size: 0.95rem;
            line-height: 1.5;
            flex-grow: 1;
        }
        
        /* Enhanced Age Range Styling */
        .age-range {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 0.9rem;
            background-color: rgba(79, 70, 229, 0.1);
            padding: 0.3rem 0.7rem;
            border-radius: 9999px; /* Tailwind's rounded-full */
            display: inline-block;
            margin-top: 1rem;
        }

        .admission-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            margin-top: 1rem;
        }
        .admission-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Slider Navigation Buttons */
        .slider-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(79, 70, 229, 0.9);
            color: white;
            border: none;
            border-radius: 9999px;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            opacity: 0;
            visibility: hidden;
        }
        
        .slider-container:hover .slider-button {
            opacity: 1;
            visibility: visible;
        }

        .slider-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-50%) scale(1.1);
        }

        .prev-button { left: -25px; }
        .next-button { right: -25px; }

        @media (max-width: 768px) {
            .prev-button, .next-button {
                display: none; /* Hide buttons on small screens, rely on scroll */
            }
            .class-card-wrapper {
                padding-left: 2rem;
                padding-right: 2rem;
            }
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <section class="classes-section">
        <div class="slider-container">
            <h2 class="section-title">Our Classes</h2>
            <div class="class-card-wrapper no-scrollbar" id="classCardWrapper">
                <div class="class-card">
                    <img src="./image/nur.png" alt="Nursery Class">
                    <div class="card-content">
                        <h3>Nursery</h3>
                        <p>Building foundational skills through play, structured activities, and exploration, focusing on language, numbers, and basic concepts.</p>
                        <span class="age-range">Age: 3-4 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/kg1.png" alt="Junior KG Class">
                    <div class="card-content">
                        <h3>Junior KG</h3>
                        <p>Developing literacy, numeracy, and social skills through structured activities, preparing children for formal education.</p>
                        <span class="age-range">Age: 4-5 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/kg2.png" alt="Senior KG Class">
                    <div class="card-content">
                        <h3>Senior KG</h3>
                        <p>Advancing skills in reading, writing, mathematics, and social interaction, laying the groundwork for primary schooling.</p>
                        <span class="age-range">Age: 5-6 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>
                
                <div class="class-card">
                    <img src="./image/1.png" alt="Grade 1 Class">
                    <div class="card-content">
                        <h3>Grade 1</h3>
                        <p>Focus on fundamental literacy and numeracy, introducing more complex problem-solving and foundational science concepts.</p>
                        <span class="age-range">Age: 6-7 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/2.png" alt="Grade 2 Class">
                    <div class="card-content">
                        <h3>Grade 2</h3>
                        <p>Expanding on basic skills, fostering independent learning, and introducing broader subjects like history and geography.</p>
                        <span class="age-range">Age: 7-8 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/3.png" alt="Grade 3 Class">
                    <div class="card-content">
                        <h3>Grade 3</h3>
                        <p>Developing critical thinking and analytical skills, with a focus on comprehensive understanding in all core subjects.</p>
                        <span class="age-range">Age: 8-9 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/4.png" alt="Grade 4 Class">
                    <div class="card-content">
                        <h3>Grade 4</h3>
                        <p>Introduction to more advanced concepts in mathematics and science, encouraging research and collaborative projects.</p>
                        <span class="age-range">Age: 9-10 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/5.png" alt="Grade 5 Class">
                    <div class="card-content">
                        <h3>Grade 5</h3>
                        <p>Building strong academic foundations for middle school, with emphasis on independent study and problem-solving.</p>
                        <span class="age-range">Age: 10-11 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/6.png" alt="Grade 6 Class">
                    <div class="card-content">
                        <h3>Grade 6</h3>
                        <p>Transition to secondary education curriculum, focusing on deeper subject knowledge and analytical approaches.</p>
                        <span class="age-range">Age: 11-12 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/7.png" alt="Grade 7 Class">
                    <div class="card-content">
                        <h3>Grade 7</h3>
                        <p>Advanced studies in core subjects, promoting critical thinking and preparation for higher academic challenges.</p>
                        <span class="age-range">Age: 12-13 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/8.png" alt="Grade 8 Class">
                    <div class="card-content">
                        <h3>Grade 8</h3>
                        <p>Consolidating knowledge and skills across disciplines, encouraging independent research and project-based learning.</p>
                        <span class="age-range">Age: 13-14 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>

                <div class="class-card">
                    <img src="./image/9.png" alt="Grade 9 Class">
                    <div class="card-content">
                        <h3>Grade 9</h3>
                        <p>Intensive preparation for board examinations, focusing on in-depth subject mastery and career path exploration.</p>
                        <span class="age-range">Age: 14-15 Years</span>
                        <a href="#contact" class="admission-btn">Admission Query</a>
                    </div>
                </div>
            </div>
            <button class="slider-button prev-button" id="prevButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="slider-button next-button" id="nextButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('classCardWrapper');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');

            // Function to handle the scroll action
            const scrollSlider = (direction) => {
                const cardWidth = wrapper.querySelector('.class-card').offsetWidth;
                const scrollAmount = cardWidth + 32; // cardWidth + gap (2rem = 32px)
                
                if (direction === 'next') {
                    wrapper.scrollLeft += scrollAmount;
                } else {
                    wrapper.scrollLeft -= scrollAmount;
                }
            };
            
            // Event listeners for navigation buttons
            if (prevButton) {
                prevButton.addEventListener('click', () => scrollSlider('prev'));
            }
            if (nextButton) {
                nextButton.addEventListener('click', () => scrollSlider('next'));
            }

            // Optional: Hide/Show buttons at start/end
            wrapper.addEventListener('scroll', () => {
                // You can add logic here to hide the previous button if at the start
                // and hide the next button if at the end of the scroll.
            });
        });
    </script>
</body>
</html>