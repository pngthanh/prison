<?php
session_start();
include 'connect.php';

// Tự động cập nhật trạng thái tù nhân đã đến ngày ra tù
$current_date = date('Y-m-d');
$sql_update_status = "UPDATE prisoners 
                      SET Trang_thai = 'Da_ra_tu', Buong_giam = NULL 
                      WHERE Trang_thai = 'Dang_giam_giu' 
                      AND Ngay_ra_trai IS NOT NULL 
                      AND Ngay_ra_trai <= ?";
$stmt_update = $conn->prepare($sql_update_status);
if ($stmt_update) {
    $stmt_update->bind_param("s", $current_date);
    $stmt_update->execute();
    $stmt_update->close();
}

// Lấy danh sách buồng giam + đếm số lượng hiện tại
$sql_cells = "SELECT c.ID, c.Ten_buong, c.Trang_thai, c.Suc_chua,
              (SELECT COUNT(*) FROM prisoners p WHERE p.Buong_giam = c.ID AND p.Trang_thai = 'Dang_giam_giu') as hien_tai
              FROM cells c 
              WHERE c.Trang_thai != 'Dang_sua_chua'
              ORDER BY c.Ten_buong ASC";
$resultCells = $conn->query($sql_cells);

$cells = [];
if ($resultCells && $resultCells->num_rows > 0) {
    while ($row = $resultCells->fetch_assoc()) {
        $cells[] = $row;
    }
}

function trangThaiCellText($value)
{
    $mapping = [
        'Con_trong' => 'Còn trống',
        'Day' => 'Đã đầy',
        'Dang_bao_tri' => 'Đang bảo trì',
        'Dang_sua_chua' => 'Đang sửa chữa'
    ];
    return $mapping[$value] ?? $value;
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

// 2. Truy vấn dữ liệu
$sql = "SELECT p.*, c.Ten_buong 
        FROM prisoners p
        LEFT JOIN cells c ON p.Buong_giam = c.ID";
$resultQuery = $conn->query($sql);

// 3. Biến $result là mảng để foreach
$result = [];
if ($resultQuery && $resultQuery->num_rows > 0) {
    while ($row = $resultQuery->fetch_assoc()) {
        $result[] = $row;
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
    <link rel="stylesheet" href="css/prisoners.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>Quản Lý Tù Nhân</title>
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="admin-container">
            <main class="main-content">
                <!-- header -->
                <div class="main-header">
                    <h2>Danh sách tù nhân</h2>
                    <div class="main-actions">
                        <div class="search-bar">
                            <input type="text" placeholder="Tìm kiếm tù nhân..." id="searchStaff">
                            <i class="fa fa-search"></i>
                        </div>
                        <button class="btn-add" id="btnAddPrisoner">Thêm tù nhân</button>
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
                                <th>Hành động</th>
                            </tr>
                        </thead>

                        <!-- Dữ liệu -->
                        <tbody id="prisonersTableBody">
                            <?php foreach ($result as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><a href="prisoners_detail.php?id=<?= $row['ID'] ?>" class="staff-name-link"><?= htmlspecialchars($row['Ho_ten']) ?></a></td>
                                    <td><?= htmlspecialchars($row['Ngay_sinh']) ?></td>
                                    <td><?= htmlspecialchars($row['Que_quan']) ?></td>
                                    <td><?= htmlspecialchars($row['Toi_danh']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_nhap_trai']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_ra_trai']) ?></td>
                                    <td><?= htmlspecialchars(hinhPhatText($row['Hinh_phat'])) ?></td>
                                    <td><?= htmlspecialchars($row['Ten_buong'] ?? 'Không xác định') ?></td>
                                    <td><?= htmlspecialchars($row['Tong_diem_hoat_dong']) ?></td>
                                    <td><?= htmlspecialchars(trangThaiText($row['Trang_thai'])) ?></td>
                                    
                                    <td>
                                        <?php if ($_SESSION['role'] !== 'User'): ?>
                                            <button class="btn-edit" data-id="<?= $row['ID'] ?>"
                                            data-ho_ten="<?= htmlspecialchars($row['Ho_ten']) ?>"
                                            data-ngay_sinh="<?= htmlspecialchars($row['Ngay_sinh']) ?>"
                                            data-que_quan="<?= htmlspecialchars($row['Que_quan']) ?>"
                                            data-toi_danh="<?= htmlspecialchars($row['Toi_danh']) ?>"
                                            data-ngay_nhap_trai="<?= htmlspecialchars($row['Ngay_nhap_trai']) ?>"
                                            data-ngay_ra_trai="<?= htmlspecialchars($row['Ngay_ra_trai']) ?>"
                                            data-hinh_phat="<?= htmlspecialchars($row['Hinh_phat']) ?>"
                                            data-buong_giam="<?= htmlspecialchars($row['Buong_giam']) ?>"
                                            data-trang_thai="<?= htmlspecialchars($row['Trang_thai']) ?>"
                                            data-so_cmnd="<?= htmlspecialchars($row['So_CMND']) ?>"
                                            data-anh="<?= htmlspecialchars($row['Anh']) ?>" title="Sửa thông tin tù nhân">
                                              Sửa
                                        </button>
                                        <?php if ($_SESSION['role'] == 'Admin') : ?>
                                            <button class="btn-delete" data-id="<?= $row['ID'] ?>">Xóa</button>
                                        <?php endif; ?>
                                    <?php endif; ?> </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>

        <!-- Modal sửa -->
        <div id="editModal" class="modal hidden">
            <div class="modal-content">
                <h3>Cập nhật thông tin tù nhân</h3>
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-grid">
                        <div class="modal-left">
                            <div class="image-section">
                                <label>Ảnh đại diện hiện tại</label>
                                <img id="editAnh" src="" alt="Ảnh đại diện"
                                    style="width: 250px; height: 250px; object-fit: cover; margin: 5px auto;">
                                <label>Đổi ảnh</label>
                                <input type="file" name="anh" id="editAnhInput" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-right">
                            <div class="form-column">
                                <div>
                                    <label>Họ tên</label>
                                    <input type="text" name="ho_ten" id="editHoTen" required>
                                </div>
                                <div>
                                    <label>Ngày sinh</label>
                                    <input type="date" name="ngay_sinh" id="editNgaySinh" required>
                                </div>
                                <div>
                                    <label>Quê quán</label>
                                    <input type="text" name="que_quan" id="editQueQuan" required>
                                </div>
                                <div>
                                    <label>Tội danh</label>
                                    <input type="text" name="toi_danh" id="editToiDanh" required>
                                </div>
                                <div>
                                    <label>Ngày nhập trại</label>
                                    <input type="date" name="ngay_nhap_trai" id="editNgayNhapTrai" required>
                                </div>
                                <div>
                                    <label>Ngày ra trại</label>
                                    <input type="date" name="ngay_ra_trai" id="editNgayRaTrai">
                                </div>
                                <div>
                                    <label>Hình phạt</label>
                                    <select name="hinh_phat" id="editHinhPhat" required>
                                        <option value="Tu_hinh">Tử hình</option>
                                        <option value="Chung_than">Chung thân</option>
                                        <option value="Co_han">Có hạn</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Buồng giam</label>
                                        <select name="buong_giam" id="editBuongGiam" required>
                                            <option value="">-- Chọn buồng giam --</option>
                                            <?php foreach ($cells as $cell): ?>
                                                <?php 
                                                    $isFull = $cell['hien_tai'] >= $cell['Suc_chua'];
                                                    $isMaintenance = $cell['Trang_thai'] == 'Dang_bao_tri';
                                                    
                                                    $statusText = 'Còn trống';
                                                    $disableAttr = '';

                                                    if ($isMaintenance) {
                                                        $statusText = 'Đang bảo trì';
                                                        $disableAttr = 'disabled';
                                                    } elseif ($isFull) {
                                                        $statusText = 'Đã đầy';
                                                        $disableAttr = 'disabled';
                                                    }
                                                ?>
                                                <option value="<?= $cell['ID'] ?>" <?= $disableAttr ?>>
                                                    <?= htmlspecialchars($cell['Ten_buong']) ?> 
                                                    (<?= $statusText ?> - <?= $cell['hien_tai'] ?>/<?= $cell['Suc_chua'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                </div>
                                <div>
                                     <label>Trạng thái</label>
                                     <select id="editTrangThaiDisplay" disabled style="background-color: #e9ecef;">
                                         <option value="Dang_giam_giu">Đang giam giữ</option>
                                          <option value="Da_ra_tu">Đã ra tù</option>
                                     </select>
                                    <input type="hidden" name="trang_thai" id="editTrangThai">
                                </div>
                                <div>
                                    <label>Số CMND</label>
                                    <input type="text" name="so_cmnd" id="editSoCMND" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" onclick="closeModal('editModal')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal xóa -->
        <div id="deleteModal" class="modal hidden">
            <div class="modal-content">
                <h3>Xác nhận xóa</h3>
                <p>Bạn có chắc chắn muốn xóa tài khoản này?</p>
                <form id="deleteForm">
                    <input type="hidden" name="id" id="deleteId">
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
                <h3>Thêm tù nhân mới</h3>
                <form id="addForm" enctype="multipart/form-data">
                    <div class="modal-grid">
                        <div class="modal-left">
                            <div class="image-section">
                                <label>Ảnh đại diện</label>
                                <input type="file" name="anh" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-right">
                            <div class="form-column">
                                <div>
                                    <label>Họ tên</label>
                                    <input type="text" name="ho_ten" id="addHoTen" required>
                                </div>
                                <div>
                                    <label>Ngày sinh</label>
                                    <input type="date" name="ngay_sinh" id="addNgaySinh" required>
                                </div>
                                <div>
                                    <label>Quê quán</label>
                                    <input type="text" name="que_quan" id="addQueQuan" required>
                                </div>
                                <div>
                                    <label>Tội danh</label>
                                    <input type="text" name="toi_danh" id="addToiDanh" required>
                                </div>
                                <div>
                                    <label>Ngày nhập trại</label>
                                    <input type="date" name="ngay_nhap_trai" id="addNgayNhapTrai" required>
                                </div>
                                <div>
                                    <label>Ngày ra trại</label>
                                    <input type="date" name="ngay_ra_trai" id="addNgayRaTrai">
                                </div>
                                <div>
                                    <label>Hình phạt</label>
                                    <select name="hinh_phat" id="addHinhPhat" required>
                                        <option value="Tu_hinh">Tử hình</option>
                                        <option value="Chung_than">Chung thân</option>
                                        <option value="Co_han">Có hạn</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Buồng giam</label>
                                    <select name="buong_giam" id="addBuongGiam" required>
                                        <option value="">-- Chọn buồng giam --</option>
                                        <?php foreach ($cells as $cell): ?>
                                            <?php 
                                                $isFull = $cell['hien_tai'] >= $cell['Suc_chua'];
                                                $isMaintenance = $cell['Trang_thai'] == 'Dang_bao_tri';
                                                
                                                $statusText = 'Còn trống';
                                                $disableAttr = '';

                                                if ($isMaintenance) {
                                                    $statusText = 'Đang bảo trì';
                                                    $disableAttr = 'disabled';
                                                } elseif ($isFull) {
                                                    $statusText = 'Đã đầy';
                                                    $disableAttr = 'disabled';
                                                }
                                            ?>
                                            <option value="<?= $cell['ID'] ?>" <?= $disableAttr ?>>
                                                <?= htmlspecialchars($cell['Ten_buong']) ?> 
                                                (<?= $statusText ?> - <?= $cell['hien_tai'] ?>/<?= $cell['Suc_chua'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label>Trạng thái</label>
                                    <select disabled style="background-color: #e9ecef;">
                                        <option value="Dang_giam_giu" selected>Đang giam giữ</option>
                                    </select>
                                    <input type="hidden" name="trang_thai" id="addTrangThai" value="Dang_giam_giu">
                                </div>
                                <div>
                                    <label>Số CMND</label>
                                    <input type="text" name="so_cmnd" id="addSoCMND" required>
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
    <script src="js/prisoners.js"></script>
</body>

</html>