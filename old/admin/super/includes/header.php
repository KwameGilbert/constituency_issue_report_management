<?php
$current_page = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'MP Admin Portal' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .active-nav-link {
        background-color: rgba(0, 107, 63, 0.1);
        color: #006b3f;
        border-left: 4px solid #006b3f;
    }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start">
                    <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar"
                        aria-controls="default-sidebar" type="button" id="sidebar-toggle-button"
                        class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <span class="sr-only">Open sidebar</span>
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <a href="../dashboard/" class="flex ml-2 md:mr-24">
                        <div class="h-8 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="32" height="32">
                                <!-- Simplified Ghana Coat of Arms -->
                                <circle cx="100" cy="100" r="95" fill="#fcd116" stroke="#000" stroke-width="2" />
                                <path d="M70,50 L130,50 L140,150 L100,170 L60,150 Z" fill="#fff" stroke="#000"
                                    stroke-width="2" />
                                <path d="M100,50 L100,170 M70,110 L130,110" stroke="#000" stroke-width="3" />
                                <path d="M70,50 L100,50 L100,110 L70,110 Z" fill="#006b3f" />
                                <path d="M100,50 L130,50 L130,110 L100,110 Z" fill="#ce1126" />
                                <path d="M70,110 L100,110 L100,170 L70,110 Z" fill="#000" />
                                <path d="M100,110 L130,110 L130,150 L100,170 Z" fill="#fcd116" />
                                <path
                                    d="M100,85 L105,100 L120,100 L110,110 L115,125 L100,115 L85,125 L90,110 L80,100 L95,100 Z"
                                    fill="#000" />
                            </svg>
                        </div>
                        <span class="self-center text-xl font-semibold sm:text-xl whitespace-nowrap">MP Portal</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="../issues/?status=pending" class="relative mr-4 text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bell text-lg"></i>
                        <?php
                        // Get pending issues count
                        $pending_count_query = "SELECT COUNT(*) as count FROM issues WHERE status = 'pending'";
                        $pending_count_result = $conn->query($pending_count_query);
                        $pending_count = $pending_count_result->fetch_assoc()['count'];
                        if ($pending_count > 0):
                        ?>
                        <div
                            class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full -top-2 -right-2">
                            <?= $pending_count > 9 ? '9+' : $pending_count ?>
                        </div>
                        <?php endif; ?>
                    </a>

                    <div class="flex items-center ml-3 relative">
                        <div>
                            <button type="button"
                                class="flex text-sm bg-green-800 rounded-full focus:ring-4 focus:ring-gray-300"
                                aria-expanded="false" data-dropdown-toggle="dropdown-user" id="user-menu-button">
                                <span class="sr-only">Open user menu</span>
                                <?php
                                // Get supervisor profile pic
                                $admin_id = $_SESSION['admin_id'];
                                $profile_pic_query = "SELECT profile_pic FROM supervisors WHERE id = ?";
                                $profile_pic_stmt = $conn->prepare($profile_pic_query);
                                $profile_pic_stmt->bind_param("i", $admin_id);
                                $profile_pic_stmt->execute();
                                $profile_pic_result = $profile_pic_stmt->get_result();
                                $profile_pic = $profile_pic_result->fetch_assoc()['profile_pic'] ?? null;
                                $profile_pic_stmt->close();
                                
                                if ($profile_pic && file_exists("../../../" . $profile_pic)):
                                ?>
                                <img class="w-8 h-8 rounded-full" src="<?= '/' . $profile_pic ?>"
                                    alt="MP profile picture">
                                <?php else: ?>
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center bg-green-800 text-white">
                                    <?= strtoupper(substr($_SESSION['admin_name'] ?? 'MP', 0, 1)) ?>
                                </div>
                                <?php endif; ?>
                            </button>
                        </div>
                        <div class="z-50 hidden absolute right-0 top-full mt-2 text-base list-none bg-white divide-y divide-gray-100 rounded shadow w-48"
                            id="dropdown-user">
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-900">
                                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Member of Parliament') ?></p>
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
                                </p>
                            </div>
                            <ul class="py-1">
                                <li>
                                    <a href="../dashboard/"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                </li>
                                <li>
                                    <a href="../profile/"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                </li>
                                <li>
                                    <a href="../logout.php"
                                        class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Sign out</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Include Sidebar -->
    <?php include_once 'sidebar.php'; ?>

    <!-- User dropdown menu JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('dropdown-user');

        userMenuButton.addEventListener('click', function() {
            userMenu.classList.toggle('hidden');
            userMenuButton.setAttribute('aria-expanded', userMenu.classList.contains('hidden') ?
                'false' : 'true');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    });
    </script>