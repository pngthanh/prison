<?php
session_start();
include 'connect.php';

// Chỉ cho User vào
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header("Location: login.php");
    exit();
}

// Lấy user_id từ session trước
$user_id = $_SESSION['user_id'];

// Lấy thông tin cán bộ đang đăng nhập
$sql_staff = "SELECT * FROM staff WHERE ID = ?";
$stmt_staff = $conn->prepare($sql_staff);
$stmt_staff->bind_param("i", $user_id);
$stmt_staff->execute();
$staff = $stmt_staff->get_result()->fetch_assoc();

// Lấy buồng giam dựa vào cán bộ đăng nhập
$sql_cell = "SELECT * FROM cells WHERE Can_bo_phu_trach = ?";
$stmt = $conn->prepare($sql_cell);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cell = $stmt->get_result()->fetch_assoc();

if (!$cell) {
    die("Bạn chưa được phân công quản lý buồng giam nào.");
}

$cell_id = $cell['ID'];

// Danh sách tù nhân
$sql_prisoners = "SELECT * FROM prisoners WHERE Buong_giam = ?";
$stmt_prisoners = $conn->prepare($sql_prisoners);
$stmt_prisoners->bind_param("i", $cell_id);
$stmt_prisoners->execute();
$result_prisoners = $stmt_prisoners->get_result();
$prisoners = $result_prisoners->fetch_all(MYSQLI_ASSOC);

$count_prisoners = count($prisoners);

// Lấy lịch sử hoạt động của buồng
$sql_history = "SELECT ch.*, s.Ho_ten AS Nguoi_thuc_hien FROM cell_history ch LEFT JOIN staff s ON ch.Performed_by = s.ID WHERE ch.Cell_id = ? ORDER BY ch.Performed_at DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $cell_id);
$stmt_history->execute();
$history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);

// Hàm ánh xạ hình phạt
function hinhPhatText($value)
{
    $mapping = [
        'Tu_hinh' => 'Tử Hình',
        'Chung_than' => 'Chung Thân',
        'Co_han' => 'Có hạn'
    ];
    return $mapping[$value] ?? $value;
}

// Hàm ánh xạ trạng thái
function trangThaiText($value)
{
    $mapping = [
        'Dang_giam_giu' => 'Đang Giam Giữ',
        'Da_ra_tu' => 'Đã Ra Tù'
    ];
    return $mapping[$value] ?? $value;
}
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <title>Chi tiết Buồng <?= htmlspecialchars($cell['Ten_buong']) ?></title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/cells_detail.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include "sidebar.php"; ?>

    <div class="content">
        <div class="header-top">
            <!-- Cán bộ -->
            <div class="staff-box">
                <div class="staff-img">
                    <?php if ($staff && $staff['Anh']): ?>
                        <img src="<?= $staff['Anh'] ?>" alt="Cán bộ">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/100x100?text=CB" alt="Cán bộ">
                    <?php endif; ?>
                </div>
                <div class="staff-info">
                    <p><b><?= $staff ? $staff['Ho_ten'] : "Chưa có cán bộ" ?></b></p>
                    <p><?= $staff ? $staff['Chuc_vu'] : "" ?></p>
                </div>
            </div>

            <!-- Thông tin buồng (giữa) -->
            <div class="cell-middle">
                <h2>Buồng <?= htmlspecialchars($cell['Ten_buong']) ?></h2>
                <p><?= $count_prisoners ?>/<?= $cell['Suc_chua'] ?> tù nhân</p>
            </div>

            <!-- Nút điều khiển (phải) -->
            <div class="cell-actions">
                <button class="btn-history" id="btn-history">Xem lịch sử</button>
            </div>
        </div>

        <div class="prisoner-section">
            <h3>Danh sách tù nhân</h3>
            <div class="table-wrapper">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên</th>
                            <th>Ngày sinh</th>
                            <th>Quê quán</th>
                            <th>Tội danh</th>
                            <th>Ngày nhập trại</th>
                            <th>Ngày ra trại</th>
                            <th>Hình phạt</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prisoners as $index => $p): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><a href="prisoners_detail.php?id=<?= $p['ID'] ?>"
                                        class="staff-name-link"><?= htmlspecialchars($p['Ho_ten']) ?></a></td>
                                <td><?= htmlspecialchars($p['Ngay_sinh']) ?></td>
                                <td><?= htmlspecialchars($p['Que_quan']) ?></td>
                                <td><?= htmlspecialchars($p['Toi_danh']) ?></td>
                                <td><?= htmlspecialchars($p['Ngay_nhap_trai']) ?></td>
                                <td><?= htmlspecialchars($p['Ngay_ra_trai'] ?: "-") ?></td>
                                <td><?= htmlspecialchars(hinhPhatText($p['Hinh_phat'])) ?></td>
                                <td><?= htmlspecialchars(trangThaiText($p['Trang_thai'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal lịch sử -->
        <div id="historyModal" class="modal">
            <div class="modal-content">
                <h3>Lịch sử hoạt động của buồng</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên hành động</th>
                            <th>Thời gian</th>
                            <th>Người thực hiện</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <?php if ($history && count($history) > 0): ?>
                            <?php foreach ($history as $index => $h): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($h['Action']) ?></td>
                                    <td><?= htmlspecialchars($h['Performed_at']) ?></td>
                                    <td><?= htmlspecialchars($h['Nguoi_thuc_hien']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">Không có dữ liệu lịch sử.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="modal-buttons">
                    <button class="btn-secondary">Đóng</button>
                </div>
            </div>
        </div>

        <div id="toast-container"></div>
        <script>
            // Lấy các phần tử
            const historyBtn = document.getElementById('btn-history');
            const historyModal = document.getElementById('historyModal');
            const closeBtn = historyModal.querySelector('.btn-secondary');

            // Hiển thị modal khi nhấp vào nút
            historyBtn.addEventListener('click', function() {
                historyModal.style.display = 'block';
            });

            // Ẩn modal khi nhấp vào nút đóng
            closeBtn.addEventListener('click', function() {
                historyModal.style.display = 'none';
            });

            // Ẩn modal khi nhấp ra ngoài
            window.addEventListener('click', function(event) {
                if (event.target == historyModal) {
                    historyModal.style.display = 'none';
                }
            });
        </script>
        <script src="./js/cells_detail.js"></script>
    </div>
</body>

</html>