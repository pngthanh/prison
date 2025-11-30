<?php
session_start();
include 'connect.php';

// Lấy dữ liệu bảng thăm nom

$sql = "SELECT 
            v.ID, v.Ho_ten_nguoi_tham, p.Ho_ten AS Ho_ten_tu_nhan, 
            v.Ngay_gio_tham, v.Moi_quan_he, v.Trang_thai, v.Thoi_gian_cap_nhat, 
            v.So_CMND_nguoi_tham, v.Ghi_chu,
            v.SDT_nguoi_tham, v.Dia_chi_nguoi_tham, v.prisoner_id
        FROM visits v
        JOIN prisoners p ON v.prisoner_id = p.ID
        WHERE v.Trang_thai = 'Dang_cho_duyet'
        ORDER BY v.Ngay_gio_tham ASC"; 
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$visits = $result->fetch_all(MYSQLI_ASSOC);
// Lấy dữ liệu lịch sử (trạng thái Đã duyệt hoặc Bị từ chối)
$sql_history = "SELECT 
            v.ID, v.Ho_ten_nguoi_tham, p.Ho_ten AS Ho_ten_tu_nhan, 
            v.Ngay_gio_tham, v.Moi_quan_he, v.Trang_thai, v.Thoi_gian_cap_nhat
        FROM visits v
        JOIN prisoners p ON v.prisoner_id = p.ID
        WHERE v.Trang_thai IN ('Da_duyet', 'Bi_tu_choi')
        ORDER BY v.Ngay_gio_tham DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$history_visits = $result_history->fetch_all(MYSQLI_ASSOC);
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

    <title>Quản Lý Thăm Nom</title>
    <link rel="stylesheet" href="css/visits.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <main class="main-content">
            <div class="main-header">
                <h2>Danh sách thăm nom</h2>
                <div class="main-actions">
                    <?php if ($role == 'Admin' || $role == 'Mod') : ?>
                        <button class="btn-add" onclick="openAddModal()">Thêm thăm nom</button>
                    <?php endif; ?>
                    <button class="btn-history" onclick="openHistoryModal()">Lịch sử</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="visit-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên người thăm</th>
                            <th>Họ tên tù nhân</th>
                            <th>Ngày thăm</th>
                            <th>Giờ thăm</th>
                            <th>Mối quan hệ</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="visitTableBody">
                        <?php $i = 1;
                        foreach ($visits as $row): ?>
                            <tr data-href="visit_detail.php?id=<?= $row['ID'] ?>">
                                <td><?= $i++ ?></td>

                                <td>
                                    <a href="visit_detail.php?id=<?= $row['ID'] ?>" class="visit-detail-link">
                                        <?= htmlspecialchars($row['Ho_ten_nguoi_tham']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['Ho_ten_tu_nhan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['Ngay_gio_tham'])) ?></td>
                                <td><?= date('H:i', strtotime($row['Ngay_gio_tham'])) ?></td>
                                <td><?= htmlspecialchars($row['Moi_quan_he']) ?></td>
                                <td>
                                    <?php
                                    $trang_thaiVN = [
                                        'Dang_cho_duyet' => 'Đang chờ duyệt',
                                        'Da_duyet' => 'Đã duyệt',
                                        'Bi_tu_choi' => 'Bị từ chối'
                                    ];
                                    echo $trang_thaiVN[$row['Trang_thai']] ?? $row['Trang_thai'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($role == 'Admin' || $role == 'Mod'): ?>
                                        <?php if ($row['Trang_thai'] == 'Dang_cho_duyet'): ?>
                                            <?php if ($role == 'Admin'): ?>
                                                <button class="btn-approve" onclick="approveVisit(<?= $row['ID'] ?>)">Duyệt</button>
                                                <button class="btn-reject" onclick="rejectVisit(<?= $row['ID'] ?>)">Từ chối</button>
                                            <?php endif; ?>
                                            <button class="btn-edit"
                                                data-id="<?= $row['ID'] ?>"
                                                data-nguoi_tham="<?= htmlspecialchars($row['Ho_ten_nguoi_tham'] ?? '') ?>"
                                                data-cmnd="<?= htmlspecialchars($row['So_CMND_nguoi_tham'] ?? '') ?>"
                                                data-prisoner_id="<?= $row['prisoner_id'] ?? '' ?>"
                                                data-ngay_gio="<?= htmlspecialchars($row['Ngay_gio_tham'] ?? '') ?>"
                                                data-moi_quan_he="<?= htmlspecialchars($row['Moi_quan_he'] ?? '') ?>"
                                                data-ghi_chu="<?= htmlspecialchars($row['Ghi_chu'] ?? '') ?>"
                                                data-sdt="<?= htmlspecialchars($row['SDT_nguoi_tham'] ?? '') ?>"
                                                data-dia_chi="<?= htmlspecialchars($row['Dia_chi_nguoi_tham'] ?? '') ?>"
                                                onclick="openEditModal(this)">Sửa</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal thêm thăm nom -->
    <div id="addModal" class="modal hidden">
        <div class="modal-content large">
            <h3>Thêm thăm nom</h3>
            <form id="addForm">
                <div class="modal-form-columns">
                    <div class="modal-column">
                        <h4>Thông tin người thăm</h4>

                        <label for="Ho_ten_nguoi_tham">Họ và tên người thăm:</label>
                        <input type="text" id="Ho_ten_nguoi_tham" name="Ho_ten_nguoi_tham" placeholder="Họ tên người thăm" required>

                        <label for="So_CMND_nguoi_tham">Số CMND/CCCD:</label>
                        <input type="text" id="So_CMND_nguoi_tham" name="So_CMND_nguoi_tham" placeholder="Số CMND/CCCD" required>

                        <label for="Moi_quan_he">Mối quan hệ:</label>
                        <input type="text" id="Moi_quan_he" name="Moi_quan_he" placeholder="Mối quan hệ với tù nhân" required>

                        <label for="SDT_nguoi_tham">Số điện thoại:</label>
                        <input type="tel" id="SDT_nguoi_tham" name="SDT_nguoi_tham" placeholder="Số điện thoại liên lạc">

                        <label for="Dia_chi_nguoi_tham">Địa chỉ thường trú:</label>
                        <textarea id="Dia_chi_nguoi_tham" name="Dia_chi_nguoi_tham" placeholder="Địa chỉ thường trú"></textarea>
                    </div>

                    <div class="modal-column">
                        <h4>Thông tin tù nhân</h4>
                        <label for="prisoner_id">Chọn tù nhân:</label>
                        <select name="prisoner_id" id="prisoner_id" required>
                            <option value="">Chọn tù nhân</option>
                            <?php
                            $prisoners_sql = "SELECT ID, Ho_ten FROM prisoners WHERE Trang_thai = 'Dang_giam_giu'";
                            $prisoners_result_add = $conn->query($prisoners_sql);
                            while ($prisoner = $prisoners_result_add->fetch_assoc()) {
                                echo "<option value='{$prisoner['ID']}'>{$prisoner['Ho_ten']}</option>";
                            }
                            ?>
                        </select>

                        <label for="Ghi_chu">Ghi chú (nếu có):</label>
                        <textarea name="Ghi_chu" id="Ghi_chu" placeholder="Ghi chú"></textarea>
                    </div>

                    <div class="modal-column">
                        <h4>Chi tiết thăm</h4>
                        <label for="Ngay_tham">Ngày đăng ký thăm:</label>
                        <input type="date" id="Ngay_tham" name="Ngay_tham" required>

                        <label for="Gio_tham">Giờ đăng ký thăm:</label>
                        <input type="time" id="Gio_tham" name="Gio_tham" required>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit">Xác nhận</button>
                    <button type="button" onclick="closeModal('addModal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal sửa thăm nom -->
    <div id="editModal" class="modal hidden">
        <div class="modal-content large">
            <h3>Cập nhật thăm nom</h3>
            <form id="editForm">
                <input type="hidden" name="ID" id="editId">
                <div class="modal-form-columns">
                    <div class="modal-column">
                        <h4>Thông tin người thăm</h4>

                        <label for="editHoTenNguoiTham">Họ và tên người thăm:</label>
                        <input type="text" id="editHoTenNguoiTham" name="Ho_ten_nguoi_tham" required>

                        <label for="editSoCMND">Số CMND/CCCD:</label>
                        <input type="text" id="editSoCMND" name="So_CMND_nguoi_tham" required>

                        <label for="editMoiQuanHe">Mối quan hệ:</label>
                        <input type="text" id="editMoiQuanHe" name="Moi_quan_he" required>

                        <label for="editSDT">Số điện thoại:</label>
                        <input type="tel" id="editSDT" name="SDT_nguoi_tham">

                        <label for="editDiaChi">Địa chỉ thường trú:</label>
                        <textarea id="editDiaChi" name="Dia_chi_nguoi_tham"></textarea>
                    </div>

                    <div class="modal-column">
                        <h4>Thông tin tù nhân</h4>
                        <label for="editPrisonerId">Chọn tù nhân:</label>
                        <select name="prisoner_id" id="editPrisonerId" required>
                            <option value="">Chọn tù nhân</option>
                            <?php
                            // Tái sử dụng $prisoners_result từ file gốc của bạn
                            $prisoners_result_edit = $conn->query($prisoners_sql); // Dùng lại $prisoners_sql
                            while ($prisoner = $prisoners_result_edit->fetch_assoc()) {
                                echo "<option value='{$prisoner['ID']}'>{$prisoner['Ho_ten']}</option>";
                            }
                            ?>
                        </select>

                        <label for="editGhiChu">Ghi chú (nếu có):</label>
                        <textarea name="Ghi_chu" id="editGhiChu"></textarea>
                    </div>

                    <div class="modal-column">
                        <h4>Chi tiết thăm</h4>
                        <label for="editNgayTham">Ngày đăng ký thăm:</label>
                        <input type="date" id="editNgayTham" name="Ngay_tham" required>

                        <label for="editGioTham">Giờ đăng ký thăm:</label>
                        <input type="time" id="editGioTham" name="Gio_tham" required>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit">Xác nhận</button>
                    <button type="button" onclick="closeModal('editModal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal lịch sử toàn màn hình -->
    <div id="historyModal" class="modal full-screen-modal hidden">
        <div class="modal-content full-screen-content">
            <div class="modal-header">
                <h3>Lịch sử thăm nom</h3>
                <button class="btn-close" onclick="closeModal('historyModal')">Đóng</button>
            </div>
            <div class="table-wrapper">
                <table class="visit-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên người thăm</th>
                            <th>Họ tên tù nhân</th>
                            <th>Ngày thăm</th>
                            <th>Giờ thăm</th>
                            <th>Mối quan hệ</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php $i = 1;
                        foreach ($history_visits as $row): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td>
                                    <a href="visit_detail.php?id=<?= $row['ID'] ?>" class="visit-detail-link" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                        <?= htmlspecialchars($row['Ho_ten_nguoi_tham']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['Ho_ten_tu_nhan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['Ngay_gio_tham'])) ?></td>
                                <td><?= date('H:i', strtotime($row['Ngay_gio_tham'])) ?></td>
                                <td><?= htmlspecialchars($row['Moi_quan_he']) ?></td>
                                <td>
                                    <?php
                                    $trang_thaiVN = [
                                        'Dang_cho_duyet' => 'Đang chờ duyệt',
                                        'Da_duyet' => 'Đã duyệt',
                                        'Bi_tu_choi' => 'Bị từ chối'
                                    ];
                                    $statusClass = '';
                                    if ($row['Trang_thai'] == 'Da_duyet') $statusClass = 'status-active'; 
                                    if ($row['Trang_thai'] == 'Bi_tu_choi') $statusClass = 'status-inactive';

                                    echo isset($trang_thaiVN[$row['Trang_thai']]) ? $trang_thaiVN[$row['Trang_thai']] : $row['Trang_thai'];
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/visits.js"></script>
</body>

</html>