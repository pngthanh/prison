<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? 'User';

$total_prisoners = $conn->query("SELECT COUNT(*) AS total FROM prisoners WHERE Trang_thai = 'Dang_giam_giu'")->fetch_assoc()['total'];
$total_staff = $conn->query("SELECT COUNT(*) AS total FROM staff WHERE Trang_thai = 'Kich_hoat'")->fetch_assoc()['total'];
$total_cells_empty = $conn->query("SELECT COUNT(*) AS total FROM cells WHERE Trang_thai = 'Con_trong'")->fetch_assoc()['total'];
$total_visits_pending = $conn->query("SELECT COUNT(*) AS total FROM visits WHERE Trang_thai = 'Dang_cho_duyet'")->fetch_assoc()['total'];
$total_amnesty_pending = $conn->query("SELECT COUNT(*) AS total FROM amnesty WHERE status = 'pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thống Kê</title>

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

    <link rel="stylesheet" href="css/thongke.css" />
    <link rel="stylesheet" href="css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h1 class="page-title">Thống Kê</h1>
        <!-- KPI TỔNG QUAN -->
        <div class="kpi-summary-box">
            <div class="kpi-container">
                <div class="kpi-card">
                    <i class="fas fa-users kpi-icon" style="color: #3b82f6;"></i>
                    <div class="kpi-info">
                        <h3>Tù nhân (đang giam)</h3>
                        <p><?= $total_prisoners ?></p>
                    </div>
                </div>
                <div class="kpi-card">
                    <i class="fas fa-user-shield kpi-icon" style="color: #10b981;"></i>
                    <div class="kpi-info">
                        <h3>Cán bộ (công tác)</h3>
                        <p><?= $total_staff ?></p>
                    </div>
                </div>
                <div class="kpi-card">
                    <i class="fas fa-door-open kpi-icon" style="color: #f59e0b;"></i>
                    <div class="kpi-info">
                        <h3>Buồng còn trống</h3>
                        <p><?= $total_cells_empty ?></p>
                    </div>
                </div>
                <div class="kpi-card">
                    <i class="fas fa-comments kpi-icon" style="color: #8b5cf6;"></i>
                    <div class="kpi-info">
                        <h3>Thăm nom (chờ duyệt)</h3>
                        <p><?= $total_visits_pending ?></p>
                    </div>
                </div>
                <div class="kpi-card">
                    <i class="fas fa-dove kpi-icon" style="color: #ef4444;"></i>
                    <div class="kpi-info">
                        <h3>Ân xá (chờ duyệt)</h3>
                        <p><?= $total_amnesty_pending ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <!-- Các tab lựa chọn -->
            <nav class="stats-options">
                <button class="stats-option-btn active" data-section="prisoners">
                    <i class="fas fa-users"></i> Tù Nhân
                </button>
                <button class="stats-option-btn" data-section="cells">
                    <i class="fas fa-building"></i> Buồng Giam
                </button>
                <button class="stats-option-btn" data-section="visits">
                    <i class="fas fa-comments"></i> Thăm Nom
                </button>
                <button class="stats-option-btn" data-section="amnesty">
                    <i class="fas fa-dove"></i> Ân Xá
                </button>
                <button class="stats-option-btn" data-section="staff">
                    <i class="fas fa-user-shield"></i> Cán Bộ
                </button>
                <button class="stats-option-btn" data-section="events">
                    <i class="fas fa-calendar-days"></i> Hoạt Động
                </button>
            </nav>

            <!-- Bộ lọc -->
            <div class="stats-filters">
                <div class="filter-group" id="filter-group-time">
                    <label for="time-filter">Thống kê theo:</label>
                    <select id="time-filter">
                        <option value="year">Năm Hiện Tại</option>
                        <option value="all_years">Tất Cả</option>
                    </select>
                </div>
            </div>

            <!-- biểu đồ -->
            <div class="charts-area" id="charts-area">



                <div class="chart-loading" id="chart-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/thongke.js"></script>
</body>

</html>