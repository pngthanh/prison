<?php
session_start();
include 'connect.php';

// Hàm ánh xạ hình phạt
function hinhPhatText($value)
{
    $mapping = [
        'Tu_hinh' => 'Tử hình',
        'Chung_than' => 'Chung thân',
        'Co_han' => 'Có hạn'
    ];
    return $mapping[$value] ?? $value;
}

// Hàm ánh xạ trạng thái
function trangThaiText($value)
{
    $mapping = [
        'Dang_giam_giu' => 'Đang giam giữ',
        'Da_ra_tu' => 'Đã ra tù'
    ];
    return $mapping[$value] ?? $value;
}

// Truy vấn dữ liệu tù nhân có điểm hoạt động
$sql = "
    SELECT * FROM prisoners 
    WHERE Trang_thai = 'Dang_giam_giu' 
      AND (
            (Hinh_phat = 'Co_han' AND Tong_diem_hoat_dong > 100)
         OR (Hinh_phat = 'Chung_than' AND Tong_diem_hoat_dong > 500)
          )
    ORDER BY Tong_diem_hoat_dong DESC
";

$resultQuery = $conn->query($sql);
$result = [];

// Biến $result là mảng để foreach
$result = [];
if ($resultQuery && $resultQuery->num_rows > 0) {
    while ($row = $resultQuery->fetch_assoc()) {
        $result[] = $row;
    }
}

// Truy vấn dữ liệu lịch sử ân xá
$sql_history = "SELECT * FROM amnesty WHERE status = 'approved' ORDER BY approved_date DESC";
$history_result = $conn->query($sql_history);
$history_amnesty = [];
if ($history_result && $history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $history_amnesty[] = $row;
    }
}
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
    <link rel="stylesheet" href="css/amnesty.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>Quản Lý Ân Xá</title>
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="admin-container">
            <main class="main-content">
                <!-- header -->
                <div class="main-header">
                    <h2>Danh sách tù nhân đủ điều kiện ân xá</h2>
                    <div class="main-actions">
                        <div class="search-bar">
                            <input type="text" placeholder="Tìm kiếm tù nhân..." id="searchPrisoner">
                            <i class="fa fa-search"></i>
                        </div>
                        <button class="btn-history" onclick="openHistoryModal()">Lịch sử</button>
                    </div>
                </div>

                <!-- bảng -->
                <div class="table-wrapper">
                    <table class="prisoners-table">
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
                                <th>Buồng giam</th>
                                <th>Điểm hoạt động</th>
                                <th>Trạng thái</th>
                                <th>Số CMND</th>
                                <th>Ảnh</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="prisonersTableBody">
                            <?php foreach ($result as $i => $row): ?>
                                <?php
                                // Kiểm tra yêu cầu ân xá cho tù nhân này
                                $prisoner_id = $row['ID'];
                                $request_sql = "SELECT * FROM amnesty WHERE prisoner_id = $prisoner_id AND status = 'pending' ORDER BY id DESC LIMIT 1";
                                $request_result = $conn->query($request_sql);
                                $request = $request_result->fetch_assoc();
                                $has_pending = $request && $request['status'] == 'pending';
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><a href="amnesty_prisoners_detail.php?id=<?= $row['ID'] ?>"
                                            class="staff-name-link"><?= htmlspecialchars($row['Ho_ten']) ?></a></td>
                                    <td><?= htmlspecialchars($row['Ngay_sinh']) ?></td>
                                    <td><?= htmlspecialchars($row['Que_quan']) ?></td>
                                    <td><?= htmlspecialchars($row['Toi_danh']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_nhap_trai']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_ra_trai']) ?></td>
                                    <td><?= htmlspecialchars(hinhPhatText($row['Hinh_phat'])) ?></td>
                                    <td><?= htmlspecialchars($row['Buong_giam']) ?></td>
                                    <td><?= htmlspecialchars($row['Tong_diem_hoat_dong']) ?></td>
                                    <td><?= htmlspecialchars(trangThaiText($row['Trang_thai'])) ?></td>
                                    <td><?= htmlspecialchars($row['So_CMND']) ?></td>
                                    <td><img src="<?= htmlspecialchars($row['Anh']) ?>" alt="Ảnh tù nhân"
                                            style="width: 50px; height: 50px; object-fit: cover;"></td>
                                    <td>
                                        <?php
                                        $is_chung_than = $row['Hinh_phat'] == 'Chung_than';
                                        ?>
                                        <?php if ($role != 'User' && !$has_pending && $row['Tong_diem_hoat_dong'] > 100): ?>
                                            <button class="btn-request" data-id="<?= $row['ID'] ?>"
                                                title="Gửi yêu cầu ân xá">Gửi yêu cầu</button>
                                        <?php endif; ?>
                                        <?php if ($role == 'Admin' && $has_pending): ?>
                                            <button class="btn-confirm"
                                                data-id="<?= $row['ID'] ?>"
                                                data-request-id="<?= $request['id'] ?>"
                                                data-chung-than="<?= $is_chung_than ? '1' : '0' ?>"
                                                title="Xác nhận ân xá">Xác nhận</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
        <!-- Modal gửi yêu cầu ân xá (cho Mod) -->
        <div id="requestModal" class="modal hidden">
            <div class="modal-content">
                <h3>Gửi yêu cầu ân xá</h3>
                <p>Bạn có chắc chắn muốn gửi yêu cầu ân xá cho tù nhân này?</p>
                <form id="requestForm">
                    <input type="hidden" name="id" id="requestId">
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" onclick="closeModal('requestModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal xác nhận ân xá (phân biệt Có hạn và Chung thân) -->
        <div id="confirmModal" class="modal hidden">
            <div class="modal-content">
                <h3 id="confirmModalTitle">Xác nhận ân xá</h3>

                <!-- Form cho tù nhân Có hạn -->
                <form id="confirmFormNormal" class="amnesty-form" enctype="multipart/form-data" style="display: none;">
                    <input type="hidden" name="request_id" id="normalRequestId">
                    <input type="hidden" name="prisoner_id" id="normalPrisonerId">
                    <input type="hidden" name="is_chung_than" value="0">

                    <div class="form-group">
                        <label for="reduction_amount">Số lượng giảm án:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="reduction_amount" id="reduction_amount" min="1" required style="width: 100px;">
                            <select name="reduction_unit" id="reduction_unit" required>
                                <option value="day">Ngày</option>
                                <option value="month">Tháng</option>
                                <option value="year">Năm</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="points_deducted">Số điểm bị trừ:</label>
                        <input type="number" name="points_deducted" id="points_deducted" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="word_file_normal">Quyết định ân xá (Word):</label>
                        <input type="file" name="word_file" id="word_file_normal" accept=".doc,.docx" required>
                    </div>

                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" onclick="closeModal('confirmModal')">Hủy</button>
                    </div>
                </form>

                <!-- Form cho tù nhân Chung thân (chỉ xem, không chỉnh sửa) -->
                <form id="confirmFormChungThan" class="amnesty-form" style="display: none;">
                    <input type="hidden" name="request_id" id="chungThanRequestId">
                    <input type="hidden" name="prisoner_id" id="chungThanPrisonerId">
                    <input type="hidden" name="is_chung_than" value="1">
                    <input type="hidden" name="reduction_amount" value="25">
                    <input type="hidden" name="reduction_unit" value="year">
                    <input type="hidden" name="points_deducted" value="500">

                    <div class="form-info">
                        <p><strong>Ân xá đặc biệt (Chung thân):</strong></p>
                        <p><strong>Giảm án:</strong> <span style="color: #d32f2f; font-weight: bold;">25 năm</span></p>
                        <p><strong>Trừ điểm:</strong> <span style="color: #d32f2f; font-weight: bold;">500 điểm</span></p>
                        <p><strong>Sau ân xá:</strong> Án chuyển thành <strong>có thời hạn 25 năm</strong></p>
                    </div>

                    <div class="form-group">
                        <label for="word_file_chungthan">Quyết định ân xá (Word):</label>
                        <input type="file" name="word_file" id="word_file_chungthan" accept=".doc,.docx" required>
                    </div>

                    <div class="modal-buttons">
                        <button type="submit">Xác nhận ân xá</button>
                        <button type="button" onclick="closeModal('confirmModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal lịch sử toàn màn hình -->
        <div id="historyModal" class="modal full-screen-modal hidden">
            <div class="modal-content full-screen-content">
                <div class="modal-header">
                    <h3>Lịch sử ân xá</h3>
                    <button class="btn-close" onclick="closeModal('historyModal')">Đóng</button>
                </div>
                <div class="table-wrapper">
                    <table class="prisoners-table">
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
                                <th>Buồng giam</th>
                                <th>Điểm hoạt động</th>
                                <th>Trạng thái</th>
                                <th>Số CMND</th>
                                <th>Ảnh</th>
                                <th>Giảm án</th>
                                <th>Điểm trừ</th>
                                <th>Thời gian thực xác nhận</th>
                                <th>Quyết định</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <?php $i = 1;
                            foreach ($history_amnesty as $row): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><a href="amnesty_prisoners_detail.php?id=<?= $row['prisoner_id'] ?>"
                                            class="staff-name-link"><?= htmlspecialchars($row['Ho_ten']) ?></a></td>
                                    <td><?= htmlspecialchars($row['Ngay_sinh']) ?></td>
                                    <td><?= htmlspecialchars($row['Que_quan']) ?></td>
                                    <td><?= htmlspecialchars($row['Toi_danh']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_nhap_trai']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_ra_trai']) ?></td>
                                    <td><?= htmlspecialchars(hinhPhatText($row['Hinh_phat'])) ?></td>
                                    <td><?= htmlspecialchars($row['Buong_giam']) ?></td>
                                    <td><?= htmlspecialchars($row['Tong_diem_hoat_dong']) ?></td>
                                    <td><?= htmlspecialchars(trangThaiText($row['Trang_thai'])) ?></td>
                                    <td><?= htmlspecialchars($row['So_CMND']) ?></td>
                                    <td><img src="<?= htmlspecialchars($row['Anh']) ?>" alt="Ảnh tù nhân"
                                            style="width: 50px; height: 50px; object-fit: cover;"></td>
                                    <td><?= htmlspecialchars($row['reduction_amount'] . ' ' . $row['reduction_unit']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['points_deducted']) ?></td>
                                    <td><?= htmlspecialchars($row['approved_date']) ?></td>
                                    <td><a href="<?= htmlspecialchars($row['word_file_path']) ?>" download>Tải</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="js/amnesty.js"></script>
</body>

</html>