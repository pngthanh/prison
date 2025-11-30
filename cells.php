<?php
session_start();
include 'connect.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Mod'])) {
    header("Location: login.php");
    exit();
}

// Query lấy tất cả buồng, số tù nhân, và cán bộ phụ trách
$sql = "
    SELECT c.ID, c.Ten_buong, c.Suc_chua, c.Trang_thai, c.Can_bo_phu_trach,
           s.Ho_ten AS can_bo_ten, s.Chuc_vu AS can_bo_chuc_vu, s.Anh AS can_bo_anh,
           (SELECT COUNT(*) FROM prisoners p WHERE p.Buong_giam = c.ID AND p.Trang_thai = 'Dang_giam_giu') AS so_tu_nhan
    FROM cells c
    LEFT JOIN staff s ON c.Can_bo_phu_trach = s.ID
    ORDER BY c.Ten_buong
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$buongs = $result->fetch_all(MYSQLI_ASSOC);

// Query lấy danh sách cán bộ để hiển thị trong dropdown
$sql_staff = "SELECT ID, Ho_ten FROM staff WHERE Trang_thai = 'Kich_hoat' AND Role = 'User' ORDER BY Ho_ten";
$stmt_staff = $conn->prepare($sql_staff);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staff_list = $result_staff->fetch_all(MYSQLI_ASSOC);
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/cells.css">
    <title>Quản Lý Buồng Giam</title>
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="admin-container">
            <main class="main-content">
                <!-- Header -->
                <div class="main-header">
                    <h2>Danh sách buồng giam</h2>
                    <div class="main-actions">
                        <button class="btn-add" onclick="openAddModal()">Thêm buồng giam</button>
                        <button class="btn-history" onclick="openHistoryModal()">Lịch sử</button>
                    </div>
                </div>

                <!-- Grid hiển thị các thẻ buồng -->
                <div class="buong-grid">
                    <?php foreach ($buongs as $buong): ?>
                        <?php
                        $trang_thai_text = '';
                        $so_luong_text = '';
                        $trang_thai_class = '';
                        $co_the_giam = true;

                        if ($buong['Trang_thai'] == 'Dang_bao_tri') {
                            $trang_thai_text = 'Đang bảo trì';
                            $trang_thai_class = 'dang-sua';
                            $co_the_giam = false;
                        } elseif ($buong['Trang_thai'] == 'Day' || $buong['so_tu_nhan'] >= $buong['Suc_chua']) {
                            $trang_thai_text = 'Đã đầy';
                            $trang_thai_class = 'day';
                            $co_the_giam = false;
                        } else { // Con_trong
                            $so_luong_text = $buong['so_tu_nhan'] . '/' . $buong['Suc_chua'] . ' Tù Nhân';
                            $trang_thai_class = 'con-trong';
                        }
                        ?>
                        <div class="buong-card">
                            <!-- Tên buồng -->
                            <div class="buong-header">
                                <h3><?php echo htmlspecialchars($buong['Ten_buong']); ?></h3>
                            </div>
                            <!-- Số lượng và trạng thái -->
                            <div class="buong-info <?php echo $trang_thai_class; ?>">
                                <?php if ($trang_thai_class == 'con-trong' && $so_luong_text): ?>
                                    <p><?php echo $so_luong_text; ?></p>
                                <?php endif; ?>
                                <p class="status"><?php echo $trang_thai_text; ?></p>
                            </div>
                            <!-- Thông tin cán bộ -->
                            <div class="can-bo-info">
                                <?php if ($buong['Can_bo_phu_trach']): ?>
                                    <img src="<?php echo htmlspecialchars($buong['can_bo_anh'] ?? 'img/default.jpg'); ?>"
                                        alt="Cán bộ">
                                    <div class="can-bo-details">
                                        <h4><?php echo htmlspecialchars($buong['can_bo_ten']); ?></h4>
                                        <p><?php echo htmlspecialchars($buong['can_bo_chuc_vu']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p>Chưa có cán bộ phụ trách</p>
                                <?php endif; ?>
                            </div>
                            <!-- nút hành động -->
                            <div class="buong-actions">
                                <button class="btn-detail"
                                    onclick="window.location.href='cells_detail.php?id=<?php echo $buong['ID']; ?>'">Chi
                                    tiết</button>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                    <button class="btn-toggle">Trạng Thái</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <!-- Modal chuyển trạng thái -->
        <?php if ($_SESSION['role'] === 'Admin'): ?>
            <div id="toggleModal" class="modal hidden">
                <div class="modal-content">
                    <h3>Chuyển trạng thái buồng</h3>
                    <form id="toggleForm">
                        <input type="hidden" name="id" id="toggleId">
                        <label>Trạng thái mới</label>
                        <select name="trang_thai" id="toggleTrangThai">
                            <option value="Day">Đầy</option>
                            <option value="Con_trong">Còn trống</option>
                            <option value="Dang_bao_tri">Đang bảo trì</option>
                        </select>
                        <div class="modal-buttons">
                            <button type="submit">Xác nhận</button>
                            <button type="button" onclick="closeModal('toggleModal')">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div id="historyModal" class="modal hidden">
            <div class="modal-content" style="max-width: 800px;">
                <h3>Lịch sử hoạt động buồng giam</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên hoạt động</th>
                            <th>Ngày hành động</th>
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
                    <button type="button" onclick="closeModal('historyModal')">Đóng</button>
                </div>
            </div>
        </div>

        <!-- Modal thêm buồng giam -->
        <div id="addModal" class="modal hidden">
            <div class="modal-content">
                <h3>Thêm buồng giam</h3>
                <form id="addForm">
                    <label>Tên buồng</label>
                    <input type="text" name="ten_buong" id="addTenBuong" required>
                    <label>Sức chứa</label>
                    <input type="number" name="suc_chua" id="addSucChua" min="1" required>
                    <label>Trạng thái</label>
                    <select name="trang_thai" id="addTrangThai" required>
                        <option value="Con_trong">Còn trống</option>
                        <option value="Day">Đầy</option>
                        <option value="Dang_bao_tri">Đang bảo trì</option>
                    </select>
                    <label>Cán bộ phụ trách</label>
                    <select name="can_bo_phu_trach" id="addCanBoPhuTrach">
                        <option value="">Không có cán bộ phụ trách</option>
                        <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['ID']; ?>"><?php echo htmlspecialchars($staff['Ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="modal-buttons">
                        <button type="submit">Thêm</button>
                        <button type="button" onclick="closeModal('addModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="toastContainer"></div>
    <script src="js/cells.js"></script>
</body>

</html>