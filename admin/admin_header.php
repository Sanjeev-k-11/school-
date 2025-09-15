<?php

$loggedInUserName = $_SESSION['name'] ?? 'User';
$loggedInUserRole = $_SESSION['role'] ?? 'Unknown';

$schoolName = "Basic Public School";

$logoUrl = "../uploads/basic.jpeg";

$pageTitle = $pageTitle ?? 'Admin Panel';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>

        .gradient-background-blue-cyan {
            background: linear-gradient(to right, #4facfe, #00f2fe);
        }
        .gradient-background-purple-pink {
            background: linear-gradient(to right, #a18cd1, #fbc2eb);
        }
        .gradient-background-green-teal {
            background: linear-gradient(to right, #a8edea, #fed6e3);
        }
        .solid-bg-gray {
            background-color: #f3f4f6;
        }
        .solid-bg-indigo {
            background-color: #4f46e5;
        }


        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo {
            height: 40px;
            width: auto;
            margin-right: 0.5rem;
        }

        .header-school-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            flex-grow: 1;
        }


        .header-user-info {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .header-user-info span {
            color: #374151;
        }
        .header-user-info span strong {
            font-weight: 600;
        }
        .header-user-info a {
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
        }
        .header-user-info a:hover {
            text-decoration: underline;
        }

        .notification-icon-container {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            font-size: 0.75rem;
            line-height: 1;
            padding: 0.2rem 0.4rem;
            border-radius: 9999px;
        }
        
        .notification-modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .notification-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .header-user-info {
                display: none;
            }
            .fixed-header {
                gap: 0.5rem;
            }
            .header-school-name {
                font-size: 1rem;
            }
            .header-logo {
                height: 30px;
            }
        }
    </style>
    <script>
        function setBackground(className) {
            const body = document.body;
            body.classList.forEach(cls => {
                if (cls.startsWith('gradient-background-') || cls.startsWith('solid-bg-')) {
                    body.classList.remove(cls);
                }
            });
            body.classList.add(className);
            localStorage.setItem('backgroundPreference', className);
        }
        
        function fetchNotifications() {
            // Placeholder for real-time notification fetching logic.
            // In a real application, this would make an AJAX call to a server endpoint.
            // For now, we use a static array of notifications to simulate data.
            const notifications = [
                { message: 'New student enrolled: John Doe', timestamp: '2 minutes ago' },
                { message: 'Your class schedule has been updated.', timestamp: '1 hour ago' },
                { message: 'The annual report is due tomorrow.', timestamp: '3 hours ago' },
            ];
            
            const badge = document.getElementById('notification-badge');
            if (badge) {
                if (notifications.length > 0) {
                    badge.innerText = notifications.length;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }

            const notificationList = document.getElementById('notification-list');
            if (notificationList) {
                // Clear existing notifications
                notificationList.innerHTML = '';
                
                if (notifications.length === 0) {
                    notificationList.innerHTML = '<p class="text-gray-600">No new notifications.</p>';
                } else {
                    notifications.forEach(notification => {
                        const notificationItem = document.createElement('div');
                        notificationItem.className = 'p-3 border-b border-gray-200 hover:bg-gray-50';
                        notificationItem.innerHTML = `
                            <p class="text-sm text-gray-800">${htmlspecialchars(notification.message)}</p>
                            <p class="text-xs text-gray-500">${htmlspecialchars(notification.timestamp)}</p>
                        `;
                        notificationList.appendChild(notificationItem);
                    });
                }
            }
        }

        function toggleNotificationModal() {
            const modal = document.getElementById('notification-modal');
            const bell = document.getElementById('notification-bell');
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            } else {
                modal.style.display = 'block';
                // Fetch latest notifications whenever the modal is opened
                fetchNotifications();
            }
        }

        document.addEventListener('DOMContentLoaded', (event) => {
            const savedBackground = localStorage.getItem('backgroundPreference');
            if (savedBackground) {
                setBackground(savedBackground);
            }

            document.getElementById('admin-sidebar-toggle-open')?.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-open');
            });
            
            document.getElementById('notification-bell')?.addEventListener('click', function() {
                toggleNotificationModal();
            });

            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('notification-modal');
                const bell = document.getElementById('notification-bell');
                
                // Check if the click is outside the modal content and not on the bell button
                if (modal.style.display === 'block' && !modal.contains(event.target) && event.target !== bell && !bell.contains(event.target)) {
                    modal.style.display = 'none';
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth < 768) {
                    document.body.classList.remove('sidebar-open');
                }
            });

            fetchNotifications();

            setInterval(fetchNotifications, 30000);
        });

        function htmlspecialchars(str) {
            if (typeof str != 'string' && typeof str != 'number') return str ?? '';
            str = String(str);
            const map = {
                '&': '&',
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '''
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function nl2brJs(str) {
            if (typeof str != 'string') return str ?? '';
            return str.replace(/\r\n|\r|\n/g, '<br>');
        }
    </script>
</head>
<body class="min-h-screen">
    <?php
    require_once "./admin_sidebar.php";
    ?>

    <div class="fixed-header">
        <button id="admin-sidebar-toggle-open" class="focus:outline-none text-gray-600 hover:text-gray-800 mr-2 md:mr-4" aria-label="Toggle sidebar">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="School Logo" class="header-logo">
        <span class="header-school-name"><?php echo htmlspecialchars($schoolName); ?></span>

        <div class="header-user-info">
            <div class="notification-icon-container">
                <button id="notification-bell" class="focus:outline-none text-gray-600 hover:text-gray-800" aria-label="Notifications">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm-.75 0l-.36-.36" />
                    </svg>
                </button>
                <span id="notification-badge" class="notification-badge hidden">0</span>
            </div>
            <span>Welcome, <strong><?php echo htmlspecialchars($loggedInUserName); ?></strong> (<?php echo htmlspecialchars(ucfirst($loggedInUserRole)); ?>)</span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <!-- Notification Modal -->
    <div id="notification-modal" class="notification-modal">
        <div class="notification-modal-content">
            <div class="flex justify-between items-center pb-3">
                <p class="text-2xl font-bold">Notifications</p>
                <button onclick="toggleNotificationModal()" class="text-gray-500 hover:text-gray-800 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="notification-list">
               <p class="text-gray-600">No new notifications.</p>
            </div>
        </div>
    </div>

    <?php
    ?>
