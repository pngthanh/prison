<?php
session_start();
include 'connect.php';

// Lấy id từ URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($event_id === null) {
    die("Lỗi: Không tìm thấy ID sự kiện.");
}
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


// Truy vấn thông tin sự kiện
$event_sql = "SELECT * FROM events WHERE ID = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();
$event_stmt->close();

if (!$event) {
    die("Lỗi: Không tìm thấy thông tin sự kiện.");
}

// Kiểm tra và cập nhật trạng thái khi tải trang
if ($event['Gioi_han_tham_gia'] > 0 && strtolower($event['Trang_thai']) !== 'hoan_thanh') {
    if ($event['So_luong_con_lai'] == 0 && strtolower($event['Trang_thai']) != 'du') {
        $sql = "UPDATE events SET Trang_thai = 'Du' WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event['Trang_thai'] = 'Du';
    } elseif ($event['So_luong_con_lai'] > 0 && strtolower($event['Trang_thai']) != 'chua_du') {
        $sql = "UPDATE events SET Trang_thai = 'Chua_du' WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event['Trang_thai'] = 'Chua_du';
    }
}

// Truy vấn danh sách tù nhân tham gia sự kiện
$prisoners_sql = "SELECT p.* FROM prisoners p 
                 INNER JOIN event_prisoner ep ON p.ID = ep.prisoner_id 
                 WHERE ep.event_id = ?";
$prisoners_stmt = $conn->prepare($prisoners_sql);
$prisoners_stmt->bind_param("i", $event_id);
$prisoners_stmt->execute();
$resultQuery = $prisoners_stmt->get_result();

$result = [];
if ($resultQuery && $resultQuery->num_rows > 0) {
    while ($row = $resultQuery->fetch_assoc()) {
        $result[] = $row;
    }
}
$prisoners_stmt->close();
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
    <link rel="stylesheet" href="css/event_detail.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>Chi Tiết Sự Kiện</title>
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="admin-container">
            <main class="main-content">
                <!-- Thông tin sự kiện -->
                <div class="main-header">
                    <h2>Chi Tiết Sự Kiện: <?= htmlspecialchars($event['Ten_su_kien']) ?></h2>
                    <div class="event-details">
                        <p><strong>Thời gian:</strong> <?= date('d/m/Y H:i', strtotime($event['Ngay_gio'])) ?></p>
                        <p><strong>Mô tả:</strong> <?= htmlspecialchars($event['Mo_ta']) ?></p>
                        <p><strong>Điểm thưởng:</strong> <?= $event['Diem_hoat_dong'] ?></p>
                        <p><strong>Giới hạn tham gia:</strong>
                            <?= $event['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $event['Gioi_han_tham_gia'] ?></p>
                        <p><strong>Số lượng còn lại:</strong>
                            <?= $event['Gioi_han_tham_gia'] == 0 ? 'Không giới hạn' : $event['So_luong_con_lai'] ?></p>
                        <p><strong>Trạng thái:</strong>
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
                            echo isset($Trang_thaiVN[$event['Trang_thai']]) ? $Trang_thaiVN[$event['Trang_thai']] : $event['Trang_thai'];
                            ?>
                        </p>
                        <p><strong>Thời gian cập nhật:</strong> <?= $event['Thoi_gian_cap_nhat'] ?></p>
                    </div>
                </div>

                <!-- Danh sách tù nhân -->
                <div class="main-header">
                    <h2>Danh sách tù nhân</h2>
                    <div class="main-actions">
                        <div class="search-bar">
                            <input type="text" placeholder="Tìm kiếm tù nhân..." id="searchStaff">
                            <i class="fa fa-search"></i>
                        </div>
                        <?php if ($role !== 'User') : ?>
                        <button class="btn-add" onclick="openAddModal()">Thêm tù nhân</button>
                        <?php endif; ?>
                        <a href="events.php" class="btn-back" style="margin-right: auto;">Quay lại</a>
                    </div>
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
                                <th>Trạng thái</th>
                                <th>Số CMND</th>
                                <th>Ảnh</th>
                                <th><?php if ($role !== 'User') : ?>Hành động<?php endif; ?></th>
                            </tr>
                        </thead>
                        <tbody id="prisonersTableBody">
                            <?php foreach ($result as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['Ho_ten']) ?></td>
                                <td><?= htmlspecialchars($row['Ngay_sinh']) ?></td>
                                <td><?= htmlspecialchars($row['Que_quan']) ?></td>
                                <td><?= htmlspecialchars($row['Toi_danh']) ?></td>
                                <td><?= htmlspecialchars($row['Ngay_nhap_trai']) ?></td>
                                <td><?= htmlspecialchars($row['Ngay_ra_trai']) ?></td>
                                <td><?= htmlspecialchars(hinhPhatText($row['Hinh_phat'])) ?></td>
                                <td><?= htmlspecialchars($row['Buong_giam']) ?></td>
                                <td><?= htmlspecialchars(trangThaiText($row['Trang_thai'])) ?></td>
                                <td><?= htmlspecialchars($row['So_CMND']) ?></td>
                                <td><img src="<?= htmlspecialchars($row['Anh']) ?: 'img/default.jpg' ?>"
                                        alt="Ảnh tù nhân" width="50"></td>
                                <td><?php if ($role !== 'User') : ?>
                                    <button class="btn-delete" data-id="<?= $row['ID'] ?>"
                                        data-event_id="<?= $event_id ?>" title="Xóa tù nhân khỏi sự kiện">Xóa</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>

        <!-- Modal xóa -->
        <div id="deleteModal" class="modal hidden">
            <div class="modal-content">
                <h3>Xác nhận xóa</h3>
                <p>Bạn có chắc chắn muốn xóa tù nhân này khỏi sự kiện?</p>
                <form id="deleteForm">
                    <input type="hidden" name="id" id="deleteId">
                    <input type="hidden" name="event_id" id="deleteEventId" value="<?= $event_id ?>">
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" onclick="closeModal('deleteModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal thêm tù nhân -->
        <div id="addModal" class="modal hidden">
            <div class="modal-content">
                <h3>Thêm tù nhân vào sự kiện</h3>
                <form id="addForm">
                    <input type="hidden" name="id" id="addId">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <div class="modal-grid">
                        <div class="modal-left">
                            <div class="image-section">
                                <label>Ảnh đại diện</label>
                                <img id="addAnh" src="img/default.jpg" alt="Ảnh tù nhân" width="100">
                            </div>
                        </div>
                        <div class="modal-right">
                            <div class="form-column">
                                <div>
                                    <label>Họ tên</label>
                                    <div style="position: relative;">
                                        <input type="text" id="addHoTen" name="Ho_ten" autocomplete="off">
                                        <span id="ghostText"></span>
                                    </div>
                                </div>
                                <div>
                                    <label>Ngày sinh</label>
                                    <input type="date" name="ngay_sinh" id="addNgaySinh" readonly>
                                </div>
                                <div>
                                    <label>Quê quán</label>
                                    <input type="text" name="que_quan" id="addQueQuan" readonly>
                                </div>
                                <div>
                                    <label>Tội danh</label>
                                    <input type="text" name="toi_danh" id="addToiDanh" readonly>
                                </div>
                                <div>
                                    <label>Ngày nhập trại</label>
                                    <input type="date" name="ngay_nhap_trai" id="addNgayNhapTrai" readonly>
                                </div>
                                <div>
                                    <label>Ngày ra trại</label>
                                    <input type="date" name="ngay_ra_trai" id="addNgayRaTrai" readonly>
                                </div>
                                <div>
                                    <label>Hình phạt</label>
                                    <input type="text" name="hinh_phat" id="addHinhPhat" readonly>
                                </div>
                                <div>
                                    <label>Buồng giam</label>
                                    <input type="number" name="buong_giam" id="addBuongGiam" readonly>
                                </div>
                                <div>
                                    <label>Trạng thái</label>
                                    <input type="text" name="trang_thai" id="addTrangThai" readonly>
                                </div>
                                <div>
                                    <label>Số CMND</label>
                                    <input type="text" name="so_cmnd" id="addSoCMND" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" onclick="closeModal('addModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="js/event_detail.js"></script>
</body>

</html>