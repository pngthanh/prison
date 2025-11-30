<?php
$role = $_SESSION['role'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="apple-touch-icon" sizes="57x57" href="./img/ico/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="./img/ico/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="./img/ico/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="./img/ico/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="./img/ico/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="./img/ico/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./img/ico/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="./img/ico/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./img/ico/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="./img/ico/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./img/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="./img/ico/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./img/ico/favicon-16x16.png">
    <link rel="manifest" href="./img/ico/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="./img/ico/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <!-- Boxicons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/sidebar.css">
</head>

<body>
    <aside class="sidebar">
        <!-- Header -->
        <header class="sidebar-header">
            <img src="img/logo1.png" alt="Ngôi Nhà Tình Thương">
            <h1>NGÔI NHÀ TÌNH THƯƠNG</h1>
        </header>

        <!-- Content -->
        <div class="sidebar-content">
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="trangchu.php" class="menu-link">
                        <span class="icon"><i class="fa-regular fa-house"></i></span>
                        <span class="label">Trang Chủ</span>
                    </a>
                </li>

                <!-- Ktra role -->
                <?php if ($role === 'Admin'): ?>
                    <li class="menu-item">
                        <a href="staff.php" class="menu-link">
                            <span class="icon"><i class="fa-solid fa-user-shield"></i></span>
                            <span class="label">Quản Lý Cán Bộ</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="menu-item">
                    <a href="prisoners.php" class="menu-link">
                        <span class="icon"><i class="fa-regular fa-user"></i></span>
                        <span class="label">Quản Lý Tù Nhân</span>
                    </a>
                </li>
                <li class="menu-item">
                    <?php if ($role === 'Admin' || $role === 'Mod'): ?>
                        <a href="cells.php" class="menu-link">
                            <span class="icon"><i class="fa-regular fa-building"></i></span>
                            <span class="label">Quản Lý Buồng Giam</span>
                        </a>
                    <?php else:
                    ?>
                        <a href="cells_user.php" class="menu-link">
                            <span class="icon"><i class="fa-regular fa-building"></i></span>
                            <span class="label">Quản Lý Buồng Giam</span>
                        </a>
                    <?php endif; ?>
                </li>
                <li class="menu-item">
                    <a href="thongke.php" class="menu-link">
                        <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
                        <span class="label">Thống Kê</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="rule.php" class="menu-link">
                        <span class="icon"><i class="fa-regular fa-newspaper"></i></span>
                        <span class="label">Nội Quy</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="events.php" class="menu-link">
                        <span class="icon"><i class="fa-regular fa-calendar-days"></i></span>
                        <span class="label">Hoạt Động</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="amnesty.php" class="menu-link">
                        <span class="icon"><i class="fa-solid fa-dove"></i></span>
                        <span class="label">Ân xá</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="visits.php" class="menu-link">
                        <span class="icon"><i class="fa-solid fa-comments"></i></span>
                        <span class="label">Thăm nom</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php" class="menu-link">
                <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span class="label">Đăng Xuất</span>
            </a>
        </div>
    </aside>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const currentLocation = window.location.pathname.split("/").pop();
            const menuItems = document.querySelectorAll(".menu-item a");
            menuItems.forEach(link => {
                if (link.getAttribute("href") === currentLocation) {
                    link.parentElement.classList.add("active");
                }
            });
        });
    </script>
</body>

</html>