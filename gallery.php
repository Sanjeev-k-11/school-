<?php
// PHP to fetch images from a database
// This assumes you have a database connection in a file named "config.php"

require_once 'config.php'; // Include your database connection file

// Fetch all image URLs from the gallery_images table
$sql = "SELECT secure_url FROM gallery_images ORDER BY uploaded_at DESC";
$result = mysqli_query($link, $sql);

$imageUrls = [];
if ($result) { // Check if query was successful
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $imageUrls[] = $row['secure_url'];
        }
    }
    mysqli_free_result($result); // Free result set
} else {
    // Handle query error gracefully in a production environment
    // For this example, we'll just log it to the server error log
    error_log("Database query failed: " . mysqli_error($link));
    $imageUrls = []; // Ensure it's an empty array if query fails
}

// Close the database connection
mysqli_close($link);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our School Gallery - Moments Captured</title>
    <!-- Link to Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Optional: Add Google Fonts for a nicer font (e.g., Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Custom styles for better typography and scrollbar */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Tailwind's slate-50 */
            scroll-behavior: smooth; /* Smooth scrolling for anchor links */
        }
        /* Custom scrollbar for better aesthetics - Note: This is for general body scroll, not the hidden gallery scroll */
        body::-webkit-scrollbar {
            width: 8px;
        }
        body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        body::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* Tailwind gray-300 */
            border-radius: 10px;
        }
        body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* Tailwind gray-400 */
        }

        /* --- Custom styles for hidden horizontal scrollbar --- */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;  /* Chrome, Safari, Opera*/
        }
        /* --- End hidden scrollbar styles --- */

        /* Ensure images fill their container correctly while maintaining aspect ratio */
        /* For horizontal scroll, we'll set fixed width/height on the parent flex-item */
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* This is crucial for consistent image sizes */
            display: block; /* Removes extra space below image */
        }

        /* Lightbox specific styles (unchanged from previous version) */
        .lightbox-modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000; /* High z-index to be on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed for content */
            background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .lightbox-modal.show {
            display: flex; /* Show when active */
            opacity: 1;
        }

        .lightbox-content {
            position: relative;
            background-color: #fefefe;
            margin: auto;
            padding: 0;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            overflow: hidden; /* For rounded corners on image */
            box-shadow: 0 0 20px rgba(0,0,0,0.5); /* Add a subtle shadow */
        }

        .lightbox-content img {
            width: auto; /* Allow image to scale down */
            height: auto;
            max-width: 100%;
            max-height: calc(100vh - 120px); /* Adjust based on caption/close button height */
            display: block;
            margin: auto;
            object-fit: contain; /* Ensures entire image is visible */
            border-radius: 8px 8px 0 0; /* Match modal corners */
        }

        .lightbox-caption {
            padding: 15px 20px;
            text-align: center;
            color: #333;
            background-color: #fff;
            font-size: 1.1em;
            font-weight: 600;
        }

        .lightbox-close {
            color: #fff;
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            text-shadow: 0 0 5px rgba(0,0,0,0.5); /* Make close button more visible */
        }

        .lightbox-close:hover,
        .lightbox-close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Disable body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body class="antialiased">

 

    <!-- Main Gallery Section -->
    <div class="container mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-center mb-12 text-5xl md:text-6xl font-extrabold text-gray-800 tracking-tight leading-tight">
            Our School <span class="text-indigo-600">Gallery</span>
            <p class="text-xl md:text-2xl font-light text-gray-500 mt-4 max-w-2xl mx-auto">
                Discover vibrant moments from our school life. <br class="hidden sm:inline"> Scroll horizontally to explore more!
            </p>
        </h1>

        <?php
        // Check if any images were found
        if (empty($imageUrls)) {
            echo '<div class="flex items-center justify-center min-h-[400px] bg-white rounded-lg shadow-md border border-gray-200 p-8">';
            echo '<p class="text-center text-gray-600 italic text-xl md:text-2xl">';
            echo 'No beautiful moments captured in the gallery yet. Check back soon for new additions!';
            echo '</p>';
            echo '</div>';
        } else {
            // Updated structure for horizontal scroll
            echo '<div class="flex overflow-x-scroll scrolling-touch hide-scrollbar pb-6 space-x-6 lg:space-x-8">'; // pb-6 adds space for the hidden scrollbar area
            // Loop through the fetched image URLs
            foreach ($imageUrls as $url) {
                $filename = basename($url);
                // Create a cleaner alt text from the filename
                $alt_text = htmlspecialchars(ucwords(str_replace(['-', '_', '.jpg', '.jpeg', '.png', '.gif'], [' ', ' ', '', '', '', ''], pathinfo($filename, PATHINFO_FILENAME))));

                // Added flex-shrink-0 and fixed w-72 h-72 for consistent size in horizontal scroll
                echo '<div class="gallery-item cursor-pointer flex-shrink-0 relative overflow-hidden rounded-lg shadow-lg bg-white transform transition-transform duration-300 ease-in-out hover:scale-105 hover:shadow-xl group w-64 h-64 sm:w-72 sm:h-72" data-src="' . htmlspecialchars($url) . '" data-alt="' . $alt_text . '">';
                // Removed the aspect-ratio div, as w/h directly control size now
                echo '<img src="' . htmlspecialchars($url) . '" alt="' . $alt_text . '" class="rounded-lg">';

                // Optional: Image caption overlay on hover
                echo '<div class="absolute inset-0 bg-black bg-opacity-50 flex items-end p-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">';
                echo '<span class="text-white text-sm font-semibold truncate w-full text-center">' . $alt_text . '</span>';
                echo '</div>'; // Close overlay

                echo '</div>'; // Close gallery-item
            }
            echo '</div>'; // Close horizontal scroll container
        }
        ?>
    </div>

   
    <!-- Lightbox Modal Structure (unchanged from previous version) -->
    <div id="lightboxModal" class="lightbox-modal">
        <span class="lightbox-close" id="closeLightbox">&times;</span>
        <div class="lightbox-content">
            <img id="lightboxImage" src="" alt="">
            <div id="lightboxCaption" class="lightbox-caption"></div>
        </div>
    </div>

    <!-- JavaScript for Lightbox (unchanged from previous version) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            const lightboxModal = document.getElementById('lightboxModal');
            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxCaption = document.getElementById('lightboxCaption');
            const closeLightbox = document.getElementById('closeLightbox');
            const body = document.body;

            galleryItems.forEach(item => {
                item.addEventListener('click', function() {
                    const imgSrc = this.getAttribute('data-src');
                    const imgAlt = this.getAttribute('data-alt');

                    lightboxImage.src = imgSrc;
                    lightboxImage.alt = imgAlt;
                    lightboxCaption.textContent = imgAlt;
                    lightboxModal.classList.add('show');
                    body.classList.add('modal-open'); // Prevent body scroll
                });
            });

            // Close lightbox when clicking the close button
            closeLightbox.addEventListener('click', function() {
                lightboxModal.classList.remove('show');
                body.classList.remove('modal-open'); // Re-enable body scroll
            });

            // Close lightbox when clicking outside the image content
            lightboxModal.addEventListener('click', function(e) {
                if (e.target === lightboxModal) {
                    lightboxModal.classList.remove('show');
                    body.classList.remove('modal-open');
                }
            });

            // Close lightbox with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightboxModal.classList.contains('show')) {
                    lightboxModal.classList.remove('show');
                    body.classList.remove('modal-open');
                }
            });
        });
    </script>

</body>
</html>