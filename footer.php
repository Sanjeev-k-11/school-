<?php
// footer.php - Reusable Footer for the School Website
?>
<footer class="bg-gray-800 text-white py-10 px-6">
    <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
        <div>
            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($schoolName ?? 'School Name'); ?></h3>
            <p class="text-gray-400"><?php echo htmlspecialchars($schoolAddress ?? 'School Address'); ?></p>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-2">Quick Links</h3>
            <ul class="space-y-2">
                <li><a href="index.php#about" class="hover:text-gray-300">About Us</a></li>
                <li><a href="admission_announcement.php" class="hover:text-gray-300">Admissions</a></li>
                <li><a href="vacation_homework.php" class="hover:text-gray-300">Vacation Homework</a></li>
                <li><a href="book.php" class="hover:text-gray-300">Book List</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-2">Contact Us</h3>
            <p class="text-gray-400">Phone: <a href="tel:<?php echo htmlspecialchars($schoolPhone ?? ''); ?>" class="hover:underline"><?php echo htmlspecialchars($schoolPhone ?? 'N/A'); ?></a></p>
            <p class="text-gray-400">Email: <a href="mailto:<?php echo htmlspecialchars($schoolEmail ?? ''); ?>" class="hover:underline"><?php echo htmlspecialchars($schoolEmail ?? 'N/A'); ?></a></p>
           <a href="./uploads/Basic.apk"
   download
   class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 px-8 rounded-full shadow-lg transition duration-300 transform hover:scale-105 mt-8">
    Download Our App
</a>

        </div>
    </div>

    <!-- Bottom Section -->
    <div class="text-center text-gray-500 border-t border-gray-700 mt-8 pt-6 space-y-2">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($schoolName ?? 'School Name'); ?>. All rights reserved.</p>

        <!-- Creator Credit -->
        <p class="text-sm text-gray-400">
            Website crafted with ❤️ by
            <a href="https://potfolio.mednova.store/" target="_blank" class="text-blue-400 hover:underline font-semibold">
                Sanjeev Kumar
            </a>
            &nbsp;|&nbsp;
           <a href="mailto:sy781405@gmail.com" class="text-blue-400 hover:underline font-semibold">
    📧 Email: sy781405@gmail.com
</a>

        </p>

    </div>
</footer>

</body>

</html>