<?php
session_start();
include 'connect.php';

// Lấy dữ liệu bảng sự kiện
$sql = "SELECT 
            ID, Ten_su_kien, Ngay_gio, Mo_ta, Diem_hoat_dong, Gioi_han_tham_gia, So_luong_con_lai, Thoi_gian_cap_nhat, Trang_thai, Phase
        FROM events
        WHERE Trang_thai != 'Hoan_thanh'
        ORDER BY Ngay_gio DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

// Lấy dữ liệu lịch sử (trạng thái Hoàn thành)
$sql_history = "SELECT 
            ID, Ten_su_kien, Ngay_gio, Mo_ta, Diem_hoat_dong, Gioi_han_tham_gia, So_luong_con_lai, Thoi_gian_cap_nhat, Trang_thai, Phase
        FROM events
        WHERE Trang_thai = 'Hoan_thanh'
        ORDER BY Ngay_gio DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$history_events = $result_history->fetch_all(MYSQLI_ASSOC);

// Kiểm tra và cập nhật trạng thái khi tải trang
foreach ($events as &$row) {
    $status = strtolower($row['Trang_thai']);
    if ($row['Gioi_han_tham_gia'] > 0 && $status != 'dang_cho_duyet' && $status != 'dang_thuc_hien' && $status != 'hoan_thanh') {
        if ($row['So_luong_con_lai'] == 0 && $status != 'du') {
            $sql = "UPDATE events SET Trang_thai = 'Du' WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $row['ID']);
            $stmt->execute();
            $row['Trang_thai'] = 'Du';
        } elseif ($row['So_luong_con_lai'] > 0 && $status != 'chua_du') {
            $sql = "UPDATE events SET Trang_thai = 'Chua_du' WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $row['ID']);
            $stmt->execute();
            $row['Trang_thai'] = 'Chua_du';
        }
    }
}
unset($row);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

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
    <title>Quản Lý Hoạt Động</title>
    <link rel="stylesheet" href="css/events.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <main class="main-content">
            <div class="main-header">
                <h2>Danh sách sự kiện hoạt động</h2>
                <div class="main-actions">
                    <?php if ($role != 'User') : ?>
                    <button class="btn-add" onclick="openAddModal()">Thêm hoạt động</button>
                    <?php endif; ?>
                    <button class="btn-history" onclick="openHistoryModal()">Lịch sử</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="event-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên sự kiện</th>
                            <th>Thời gian</th>
                            <th>Mô tả</th>
                            <th>Điểm thưởng</th>
                            <th>Giới hạn</th>
                            <th>Số lượng còn lại</th>
                            <th>Cập nhật</th>
                            <th>Trạng thái</th>
                            <th><?php if ($role !== 'User') : ?>Thao tác<?php endif; ?></th>
                        </tr>
                    </thead>
                    <tbody id="eventTableBody">
                        <?php $i = 1;
                        foreach ($events as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><a href="event_detail.php?id=<?= $row['ID'] ?>"
                                    class="staff-name-link"><?= htmlspecialchars($row['Ten_su_kien']) ?></a></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['Ngay_gio'])) ?></td>
                            <td><?= htmlspecialchars($row['Mo_ta']) ?></td>
                            <td><?= $row['Diem_hoat_dong'] ?></td>
                            <td><?= $row['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $row['Gioi_han_tham_gia'] ?>
                            </td>
                            <td><?= $row['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $row['So_luong_con_lai'] ?></td>
                            <td><?= $row['Thoi_gian_cap_nhat'] ?></td>
                            <td>
                                <?php
                                    $Trang_thaiVN = [
                                        "Chua_du" => "Chưa đủ số lượng",
                                        "chua_du" => "Chưa đủ số lượng",
                                        "Du" => "Đủ số lượng",
                                        "du" => "Đủ số lượng",
                                        "Dang_cho_duyet" => "Đang chờ duyệt",
                                        "Dang_thuc_hien" => "Đang thực hiện",
                                        "Hoan_thanh" => "Hoàn thành"
                                    ];
                                    echo isset($Trang_thaiVN[$row['Trang_thai']]) ? $Trang_thaiVN[$row['Trang_thai']] : $row['Trang_thai'];
                                    ?>
                            </td>
                            <td>
                            <?php
                            if (isset($_SESSION['role'])) {
                                $role = $_SESSION['role'];
                                $status = strtolower($row['Trang_thai']);
                                
                                // Hiển thị nút "Gửi yêu cầu" cho vai trò Mod khi trạng thái là 'du' hoặc 'dang_thuc_hien'
                                if ($role == 'Mod' && ($status == 'du' || $status == 'dang_thuc_hien')) {
                                    echo '<button class="btn btn-request" onclick="sendRequest(' . $row['ID'] . ', \'' . $status . '\')">Gửi yêu cầu</button>';
                                }
                                // Hiển thị nút "Xác nhận" hoặc "Hoàn thành" cho vai trò Admin khi trạng thái là 'dang_cho_duyet'
                                elseif ($role == 'Admin' && $status == 'dang_cho_duyet') {
                                    if ($row['Phase'] == 0) {
                                        echo '<button class="btn" onclick="confirmAdmin(' . $row['ID'] . ')">Xác nhận</button>';
                                    } else {
                                        echo '<button class="btn" onclick="finishEvent(' . $row['ID'] . ')">Hoàn thành</button>';
                                    }
                                }
                            }
                            ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] != 'User') : ?>
                                <button class="btn-edit" data-id="<?= $row['ID'] ?>" 
                                        data-ten="<?= htmlspecialchars($row['Ten_su_kien']) ?>" 
                                        data-ngay="<?= $row['Ngay_gio'] ?>" 
                                        data-mo="<?= htmlspecialchars($row['Mo_ta']) ?>" 
                                        data-diem="<?= $row['Diem_hoat_dong'] ?>" 
                                        data-gioihan="<?= $row['Gioi_han_tham_gia'] ?>" 
                                        onclick="openEditModal(this)">Sửa</button>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') : ?>
                                <button class="btn-delete" 
                                        data-id="<?= $row['ID'] ?>" 
                                        data-ten="<?= htmlspecialchars($row['Ten_su_kien']) ?>" 
                                        data-status="<?= $row['Trang_thai'] ?>"  onclick="openDeleteModal(this)">Xóa</button>
                            <?php endif; ?>
                        </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal thêm sự kiện -->
    <div id="addModal" class="modal hidden">
        <div class="modal-content">
            <h3>Thêm sự kiện</h3>
            <form id="addForm">
                <input type="text" name="Ten_su_kien" placeholder="Tên sự kiện" required>
                <input type="datetime-local" name="Ngay_gio" required>
                <textarea name="Mo_ta" placeholder="Mô tả chi tiết"></textarea>
                <input type="number" name="Diem_hoat_dong" placeholder="Điểm thưởng" min="0" required>
                <input type="number" name="Gioi_han_tham_gia" placeholder="Giới hạn tham gia (0 là không giới hạn)"
                    min="0" required>
                <div class="modal-buttons">
                    <button type="submit">Xác nhận</button>
                    <button type="button" onclick="closeModal('addModal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal sửa sự kiện -->
    <div id="editModal" class="modal hidden">
        <div class="modal-content">
            <h3>Cập nhật sự kiện</h3>
            <form id="editForm">
                <input type="hidden" name="ID" id="editId">
                <input type="text" name="Ten_su_kien" id="editTen" required>
                <input type="datetime-local" name="Ngay_gio" id="editNgay" required>
                <textarea name="Mo_ta" id="editMo"></textarea>
                <input type="number" name="Diem_hoat_dong" id="editDiem" min="0" required>
                <input type="number" name="Gioi_han_tham_gia" id="editGioiHan" min="0" required>
                <div class="modal-buttons">
                    <button type="submit">Xác nhận</button>
                    <button type="button" onclick="closeModal('editModal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal xác nhận xóa -->
    <div id="deleteModal" class="modal hidden">
        <div class="modal-content">
            <h3>Xác nhận xóa sự kiện</h3>
            <form id="deleteForm">
                <input type="hidden" name="ID" id="deleteId">
                <p id="deleteText" style="margin:16px 0;"></p>
                <div class="modal-buttons">
                    <button type="submit">Xác nhận</button>
                    <button type="button" onclick="closeModal('deleteModal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal lịch sử toàn màn hình -->
    <div id="historyModal" class="modal full-screen-modal hidden">
        <div class="modal-content full-screen-content">
            <div class="modal-header">
                <h3>Lịch sử hoạt động</h3>
                <button class="btn-close" onclick="closeModal('historyModal')">Đóng</button>
            </div>
            <div class="table-wrapper">
                <table class="event-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên sự kiện</th>
                            <th>Thời gian</th>
                            <th>Mô tả</th>
                            <th>Điểm thưởng</th>
                            <th>Giới hạn</th>
                            <th>Số lượng còn lại</th>
                            <th>Cập nhật</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php $i = 1;
                        foreach ($history_events as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><a href="event_detail.php?id=<?= $row['ID'] ?>"
                                    class="staff-name-link"><?= htmlspecialchars($row['Ten_su_kien']) ?></a></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['Ngay_gio'])) ?></td>
                            <td><?= htmlspecialchars($row['Mo_ta']) ?></td>
                            <td><?= $row['Diem_hoat_dong'] ?></td>
                            <td><?= $row['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $row['Gioi_han_tham_gia'] ?>
                            </td>
                            <td><?= $row['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $row['So_luong_con_lai'] ?></td>
                            <td><?= $row['Thoi_gian_cap_nhat'] ?></td>
                            <td>
                                <?php
                                    $Trang_thaiVN = [
                                        "Chua_du" => "Chưa đủ số lượng",
                                        "chua_du" => "Chưa đủ số lượng",
                                        "Du" => "Đủ số lượng",
                                        "du" => "Đủ số lượng",
                                        "Dang_cho_duyet" => "Đang chờ duyệt",
                                        "Dang_thuc_hien" => "Đang thực hiện",
                                        "Hoan_thanh" => "Hoàn thành"
                                    ];
                                    echo isset($Trang_thaiVN[$row['Trang_thai']]) ? $Trang_thaiVN[$row['Trang_thai']] : $row['Trang_thai'];
                                    ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/events.js"></script>
</body>

</html>