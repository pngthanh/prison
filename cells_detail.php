<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Mod'])) {
    header("Location: login.php");
    exit();
}

$cell_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($cell_id <= 0) {
    header("Location: cells.php");
    exit();
}

// Thông tin buồng
$sql_cell = "SELECT * FROM cells WHERE ID = ?";
$stmt = $conn->prepare($sql_cell);
$stmt->bind_param("i", $cell_id);
$stmt->execute();
$cell = $stmt->get_result()->fetch_assoc();
if (!$cell) {
    die("Không tìm thấy buồng giam.");
}

// Cán bộ phụ trách
$staff = null;
if (!empty($cell['Can_bo_phu_trach'])) {
    $sql_staff = "SELECT * FROM staff WHERE ID = ?";
    $stmt_staff = $conn->prepare($sql_staff);
    $stmt_staff->bind_param("i", $cell['Can_bo_phu_trach']);
    $stmt_staff->execute();
    $staff = $stmt_staff->get_result()->fetch_assoc();
}

// Danh sách tù nhân
$sql_prisoners = "SELECT * FROM prisoners WHERE Buong_giam = ?";
$stmt_prisoners = $conn->prepare($sql_prisoners);
$stmt_prisoners->bind_param("i", $cell_id);
$stmt_prisoners->execute();
$result_prisoners = $stmt_prisoners->get_result();
$prisoners = $result_prisoners->fetch_all(MYSQLI_ASSOC);

$count_prisoners = count($prisoners);

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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="./img/ico/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

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
                <button class="btn-secondary" onclick="window.location.href='cells.php'">Trở về</button>
                <button class="btn-warning" id="btn-edit-canbo">Chỉnh sửa cán bộ</button>
                <button class="btn-primary" id="btn-transfer">Chuyển tù nhân</button>
                <button class="btn-history" id="btn-history">Lịch sử</button>
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
                                <td><a href="prisoners_detail.php?id=<?= $p['ID'] ?>&cell_id=<?= $cell_id ?>"
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

        <!-- Modal chuyển tù nhân -->
        <div id="transferModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Chuyển tù nhân</h3>
                <form id="transferForm">
                    <!-- Chọn tù nhân -->
                    <label for="prisoner_id">Chọn tù nhân:</label>
                    <select name="prisoner_id" id="prisoner_id" required>
                        <?php foreach ($prisoners as $p): ?>
                            <option value="<?= $p['ID'] ?>"><?= htmlspecialchars($p['Ho_ten']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Chọn buồng -->
                    <label for="new_cell_id">Chọn buồng mới:</label>
                    <select name="new_cell_id" id="new_cell_id" required></select>

                    <!-- Nút -->
                    <div class="modal-buttons">
                        <button type="button" id="cancelBtn" class="btn-secondary">Hủy</button>
                        <button type="submit" class="btn-primary">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal chỉnh sửa cán bộ -->
        <div id="editCanBoModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Chỉnh sửa cán bộ phụ trách</h3>
                <form id="editCanBoForm">
                    <!-- Chọn cán bộ -->
                    <label for="staff_id">Chọn cán bộ:</label>
                    <select name="staff_id" id="staff_id" required>
                        <option value="">-- Chọn cán bộ --</option>
                    </select>

                    <!-- Chọn buồng -->
                    <label for="cell_id_for_staff">Chọn buồng để quản lý:</label>
                    <select name="cell_id_for_staff" id="cell_id_for_staff" required>
                        <option value="">-- Chọn cán bộ trước --</option>
                    </select>

                    <div class="modal-buttons">
                        <button type="button" id="cancelCanBoBtn" class="btn-secondary">Hủy</button>
                        <button type="submit" class="btn-primary">Xác nhận</button>
                    </div>
                </form>
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
                        <tr>
                            <td colspan="4" style="text-align:center;">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table>
                <div class="modal-buttons">
                    <button class="btn-secondary">Đóng</button>
                </div>
            </div>
        </div>

        <div id="toast-container"></div>
        <script src="js/cells_detail.js"></script>
    </div>
</body>

</html>