<?php
// School/student/student_footer.php

// --- School Information (This should be consistent across the site) ---
// In a real application, this would come from a global config file or a database settings table.
$school_name = "Basic Public School";
$school_address = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$school_email = "vc74295@gmail.com";
$school_phone = "+91 88777 80197";
// $principal_name = "";
$owner_name = "Mr. Vishal Kumar";

?>
    </main> <!-- This closes the <main> tag opened in student_header.php -->

    <footer class="bg-gray-800 text-gray-300 pt-12 pb-8 mt-12">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- School Details Column -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-white uppercase tracking-wider">Contact Us</h3>
                    <p class="text-sm leading-relaxed"><strong><?php echo htmlspecialchars($school_name); ?></strong></p>
                    <p class="text-sm leading-relaxed"><?php echo htmlspecialchars($school_address); ?></p>
                    <p class="text-sm"><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($school_email); ?>" class="hover:underline text-indigo-400"><?php echo htmlspecialchars($school_email); ?></a></p>
                    <p class="text-sm"><strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($school_phone); ?>" class="hover:underline text-indigo-400"><?php echo htmlspecialchars($school_phone); ?></a></p>
                </div>

                <!-- Key Personnel & Quick Links Column -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-white uppercase tracking-wider">Key Information</h3>
                    <!-- <p class="text-sm"><strong>Principal:</strong> <?php echo htmlspecialchars($principal_name); ?></p> -->
                    <p class="text-sm"><strong>Dean:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
                    <div class="mt-4 space-y-2">
                        <a href="view_announcements.php" class="block text-sm hover:underline text-indigo-400">Announcements</a>
                        <a href="view_my_timetable.php" class="block text-sm hover:underline text-indigo-400">My Timetable</a>
                        <a href="view_school_details.php" class="block text-sm hover:underline text-indigo-400">About the School</a>
                    </div>
                </div>

                <!-- Social Media Column -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-white uppercase tracking-wider">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><span class="sr-only">Facebook</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><span class="sr-only">Twitter</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.71v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><span class="sr-only">Instagram</span><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.345 2.525c.636-.247 1.363-.416 2.427-.465C9.793 2.013 10.147 2 12.315 2zm-1.161 1.043c-1.06.048-1.693.212-2.228.42a3.917 3.917 0 00-1.423.923c-.428.428-.746.94-.923 1.423-.208.535-.373 1.168-.42 2.228-.048 1.047-.06 1.378-.06 3.808s.012 2.76.06 3.808c.048 1.06.212 1.693.42 2.228a3.917 3.917 0 00.923 1.423c.428.428.94.746 1.423.923.535.208 1.168.373 2.228.42 1.047.048 1.378.06 3.808.06s2.76-.012 3.808-.06c1.06-.048 1.693-.212 2.228-.42a3.917 3.917 0 001.423-.923c.428-.428.746-.94.923-1.423.208-.535.373-1.168.42-2.228.048-1.047.06-1.378.06-3.808s-.012-2.76-.06-3.808c-.048-1.06-.212-1.693-.42-2.228a3.917 3.917 0 00-.923-1.423c-.428-.428-.94-.746-1.423-.923-.535-.208-1.168-.373-2.228-.42-1.047-.048-1.378-.06-3.808-.06zM12 8.118c-2.193 0-3.972 1.779-3.972 3.972s1.779 3.972 3.972 3.972 3.972-1.779 3.972-3.972S14.193 8.118 12 8.118zm0 6.658c-1.486 0-2.686-1.2-2.686-2.686s1.2-2.686 2.686-2.686 2.686 1.2 2.686 2.686-1.2 2.686-2.686 2.686zM17.648 6.118a1.21 1.21 0 100 2.42 1.21 1.21 0 000-2.42z" clip-rule="evenodd" /></svg></a>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-700 pt-8 text-center">
                <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($school_name); ?>. All rights reserved.</p>
                <p class="text-xs mt-1 text-gray-400">Designed with <a href="https://tailwindcss.com/" target="_blank" class="hover:underline text-indigo-400">Tailwind CSS</a></p>
            </div>
        </div>
    </footer>

    <!-- Any global JS scripts can go here -->
    <script>
        // Optional: Common utility functions for student dashboard can be added here
        if (typeof htmlspecialchars === 'undefined') {
            function htmlspecialchars(text) {
                if (text === null || typeof text === 'undefined') return '';
                let map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        }
    </script>
</body>
</html>