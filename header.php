<?php
// header.php - Enhanced Reusable Header for the School Website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($schoolName ?? 'School Website') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer>
        // Toggle mobile menu
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
            const icon = document.getElementById('menuIcon');
            icon.classList.toggle('hidden');
            const closeIcon = document.getElementById('closeIcon');
            closeIcon.classList.toggle('hidden');
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Arial', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <header class="bg-gradient-to-r from-blue-900 to-blue-700 text-white sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto flex items-center justify-between p-4 md:p-6">
            <!-- Logo / School Name -->
            <a href="index.php" class="flex items-center space-x-2 transform hover:scale-105 transition-transform duration-300">
                <!-- Placeholder Logo Image -->
                <img src="./uploads//basic.jpeg" alt="<?= htmlspecialchars($schoolName ?? 'My School') ?> Logo" class="h-10 w-10 md:h-10 md:w-12 rounded-full shadow-md">
                <span class="text-3xl font-extrabold tracking-tight">
                    <?= htmlspecialchars($schoolName ?? 'My School') ?>
                </span>
            </a>

            <!-- Desktop Navigation and Login Button -->
            <div class="hidden md:flex flex-1 justify-center">
                <nav class="flex space-x-8 text-lg font-medium">
                    <a href="index.php#home" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Home</a>
                    <a href="admission_announcement.php" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Admissions</a>
                    <a href="holidayall.php" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Holiday</a>
                    <a href="vacation_homework.php" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Homework</a>
                    <a href="book.php" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Book List</a>
                    <a href="index.php#contact" class="hover:text-blue-200 transition-colors duration-200 border-b-2 border-transparent hover:border-blue-200 pb-1">Contact</a>
                </nav>
            </div>

            <div class="hidden md:flex">
                <a href="login.php" class="bg-white text-blue-800 font-bold py-2 px-6 rounded-full shadow-md hover:bg-gray-100 transform hover:scale-105 transition-transform duration-300">Login</a>
            </div>

            <!-- Mobile Buttons (Menu & Login) -->
            <div class="md:hidden flex items-center space-x-4">
                <a href="login.php" class="bg-white text-blue-800 font-semibold py-1.5 px-4 rounded-full shadow-md text-sm hover:bg-gray-100 transition-colors duration-300">Login</a>
                <button class="focus:outline-none" onclick="toggleMenu()">
                    <svg id="menuIcon" class="w-7 h-7 text-white transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="closeIcon" class="w-7 h-7 text-white hidden transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation (hidden by default) -->
        <div id="mobileMenu" class="md:hidden hidden bg-blue-800 text-white px-6 py-4 space-y-4 shadow-inner">
            <a href="index.php#home" class="block hover:text-blue-200 transition-colors duration-200">Home</a>
            <a href="admission_announcement.php" class="block hover:text-blue-200 transition-colors duration-200">Admissions</a>
            <a href="holidayall.php" class="block hover:text-blue-200 transition-colors duration-200">Holiday</a>
            <a href="vacation_homework.php" class="block hover:text-blue-200 transition-colors duration-200">Homework</a>
            <a href="book.php" class="block hover:text-blue-200 transition-colors duration-200">Book List</a>
            <a href="index.php#contact" class="block hover:text-blue-200 transition-colors duration-200">Contact</a>
        </div>
    </header>