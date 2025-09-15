<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_directory = '/School';
$current_staff_role = $_SESSION['role'] ?? 'guest';
$display_name = $_SESSION['display_name'] ?? 'Post';

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (str_starts_with($current_path, $base_directory)) {
    $current_path = substr($current_path, strlen($base_directory));
}
$current_path = empty($current_path) ? '/' : $current_path;

$role_config = [
    'teacher' => ['text' => 'text-blue-400', 'bg' => 'bg-blue-600'],
    'principal' => ['text' => 'text-green-400', 'bg' => 'bg-green-600'],
    'staff' => ['text' => 'text-purple-400', 'bg' => 'bg-purple-600'],
    'guest' => ['text' => 'text-gray-400', 'bg' => 'bg-gray-500'],
];
$role_colors = $role_config[$current_staff_role] ?? $role_config['guest'];
$role_link_color_class = $role_colors['text'];
$role_badge_bg_class = $role_colors['bg'];

$nav_links = [
    ['text' => 'Dashboard', 'path' => 'teacher/staff_dashboard.php', 'roles' => ['all']],
    ['text' => 'Holidays', 'path' => 'teacher/view_holidays.php', 'roles' => ['all']],
    ['text' => 'Chat', 'path' => 'teacher/group_chat.php', 'roles' => ['all']],
    ['text' => 'My Students', 'path' => 'teacher/student-list.php', 'roles' => ['teacher']],
    ['text' => 'Timetable', 'path' => 'teacher/view_my_timetables.php', 'roles' => ['teacher']],
    ['text' => 'Create Quiz', 'path' => 'teacher/create_quiz.php', 'roles' => ['teacher']],
    ['text' => 'Create Homework', 'path' => 'teacher/assign_homework.php', 'roles' => ['teacher']],
    ['text' => 'Manage Students', 'path' => 'teacher/student-list.php', 'roles' => ['principal']],
    ['text' => 'Manage Staff', 'path' => 'teacher/manage_principles.php', 'roles' => ['principal']],
    ['text' => 'Student List', 'path' => 'teacher/student-list.php', 'roles' => ['staff']],
];

function generate_nav_link($link_text, $relative_path, $current_path, $base_directory, $color_class, $is_dropdown_item = false) {
    $full_href = rtrim($base_directory, '/') . '/' . ltrim($relative_path, '/');
    $normalized_relative_path = '/' . ltrim($relative_path, '/');

    $is_active = ($current_path === $normalized_relative_path);
    if (($normalized_relative_path === '/teacher/staff_dashboard.php' || $normalized_relative_path === '/index.php') && $current_path === '/') {
        $is_active = true;
    }

    if ($is_dropdown_item) {
        $base_classes = "block px-4 py-2 text-sm text-gray-200 hover:bg-slate-700 hover:text-white w-full text-left";
        $active_class = $is_active ? "bg-slate-700 font-semibold" : "";
    } else {
        $base_classes = "relative px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200";
        $active_class = $is_active ? "bg-slate-700 text-white" : "text-gray-300 hover:bg-slate-700/50 hover:text-white";
    }
    
    if ($link_text === 'Logout') {
        $color_class = 'text-red-400 hover:text-red-300';
        if($is_dropdown_item) $base_classes = "block px-4 py-2 text-sm hover:bg-red-900/50 w-full text-left";
    }

    $combined_classes = trim("$base_classes $active_class $color_class");

    return sprintf(
        '<a href="%s" class="%s" aria-current="%s">%s</a>',
        htmlspecialchars($full_href),
        htmlspecialchars($combined_classes),
        $is_active ? 'page' : 'false',
        htmlspecialchars($link_text)
    );
}

?>

<header class="sticky top-4 z-50 mx-auto max-w-screen-xl">
<nav class="mx-4 rounded-xl border border-white bg-gray-500 p-4 shadow-lg backdrop-blur-lg" aria-label="Main navigation">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
    <a  class="flex items-center gap-2 text-white text-lg font-bold">
        <!-- Logo Image -->
        <img src="<?= $base_directory ?>./uploads/basic.jpeg" alt="School Logo" class="h-8 w-8 rounded-full object-cover">

        <!-- Optional SVG Icon (fixed) -->
       

        <!-- School Name -->
        Basic Public School
    </a>

    <div class="hidden md:flex items-center gap-4">
        <?php
        foreach ($nav_links as $link) {
            if (in_array($current_staff_role, $link['roles']) || in_array('all', $link['roles'])) {
                echo generate_nav_link($link['text'], $link['path'], $current_path, $base_directory, '');
            }
        }
        ?>
    </div>
</div>


            <div class="flex items-center gap-4">
                <div class="relative">
                    <button id="user-menu-button" type="button" class="flex items-center gap-2 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-800" aria-expanded="false" aria-haspopup="true">
                        <span class="sr-only">Open user menu</span>
                        <div class="text-right hidden sm:block">
                            <div class="font-medium text-white"><?= htmlspecialchars($display_name) ?></div>
                            <div class="text-xs <?= htmlspecialchars($role_link_color_class) ?>"><?= htmlspecialchars(ucfirst($current_staff_role)) ?></div>
                        </div>
                        <div class="h-10 w-10 flex-shrink-0 rounded-full flex items-center justify-center text-white font-bold <?= htmlspecialchars($role_badge_bg_class) ?>">
                             <?= htmlspecialchars(substr($display_name, 0, 1)) ?>
                        </div>
                    </button>
                    <div id="user-menu-panel" class="absolute right-0 mt-2 w-48 bg-gray-500 origin-top-right rounded-md bg-slate-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <div class="py-1" role="none">
                            <?= generate_nav_link("My Profile", "teacher/staffProfile.php", $current_path, $base_directory, '', true) ?>
                            <?= generate_nav_link("Logout", "logout.php", $current_path, $base_directory, '', true) ?>
                        </div>
                    </div>
                </div>

                <div class="md:hidden">
                    <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="mobile-menu-panel" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg id="icon-open" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <svg id="icon-close" class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-menu-panel" class="md:hidden hidden">
            <div class="space-y-1 px-2 pt-2 pb-3 sm:px-3">
                <?php
                foreach ($nav_links as $link) {
                    if (in_array($current_staff_role, $link['roles']) || in_array('all', $link['roles'])) {
                        echo generate_nav_link($link['text'], $link['path'], $current_path, $base_directory, 'block');
                    }
                }
                ?>
            </div>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const setupDropdown = (buttonId, panelId, options = {}) => {
        const button = document.getElementById(buttonId);
        const panel = document.getElementById(panelId);
        const openIcon = options.openIconId ? document.getElementById(options.openIconId) : null;
        const closeIcon = options.closeIconId ? document.getElementById(options.closeIconId) : null;

        if (!button || !panel) {
            console.error(`Dropdown elements not found for: ${buttonId}, ${panelId}`);
            return;
        }

        const toggleMenu = (forceClose = false) => {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            
            if (forceClose || isExpanded) {
                button.setAttribute('aria-expanded', 'false');
                panel.classList.add('hidden');
                if(openIcon && closeIcon) {
                    openIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            } else {
                button.setAttribute('aria-expanded', 'true');
                panel.classList.remove('hidden');
                 if(openIcon && closeIcon) {
                    openIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                }
            }
        };

        button.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && !button.contains(e.target)) {
                if (button.getAttribute('aria-expanded') === 'true') {
                    toggleMenu(true);
                }
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
                toggleMenu(true);
            }
        });

        panel.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                 if (window.innerWidth < 768) {
                    toggleMenu(true);
                 }
            });
        });
    };

    setupDropdown('mobile-menu-button', 'mobile-menu-panel', {
        openIconId: 'icon-open',
        closeIconId: 'icon-close'
    });

    setupDropdown('user-menu-button', 'user-menu-panel');

     window.matchMedia('(min-width: 768px)').addEventListener('change', e => {
        if (e.matches) {
            const mobilePanel = document.getElementById('mobile-menu-panel');
            const mobileButton = document.getElementById('mobile-menu-button');
            if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
                 mobilePanel.classList.add('hidden');
                 mobileButton.setAttribute('aria-expanded', 'false');
                 document.getElementById('icon-open').classList.remove('hidden');
                 document.getElementById('icon-close').classList.add('hidden');
            }
        }
    });
});
</script>