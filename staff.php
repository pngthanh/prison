<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$sql = "
    SELECT ROW_NUMBER() OVER (ORDER BY s.ID ASC) AS stt, s.*, c.ID AS buong_giam_id, c.Ten_buong
    FROM staff s
    LEFT JOIN cells c ON s.ID = c.Can_bo_phu_trach
    GROUP BY s.ID
";
// Lấy tất cả buồng, kh lọc theo Trang_thai
$sql_cells = "SELECT ID, Ten_buong FROM cells ORDER BY Ten_buong";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result_staff = $stmt->get_result();
$result = $result_staff->fetch_all(MYSQLI_ASSOC);

$stmt_cells = $conn->prepare($sql_cells);
$stmt_cells->execute();
$result_cells = $stmt_cells->get_result();
$cells = $result_cells->fetch_all(MYSQLI_ASSOC);
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
    <link rel="stylesheet" href="css/staff.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>Quản Lý Cán Bộ</title>
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="admin-container">
            <main class="main-content">
                <!-- header -->
                <div class="main-header">
                    <h2>Danh sách tài khoản cán bộ</h2>
                    <div class="main-actions">
                        <div class="search-bar">
                            <input type="text" placeholder="Tìm kiếm tài khoản..." id="searchStaff">
                            <i class="fa fa-search"></i>
                        </div>
                        <button class="btn-add" onclick="openAddModal()">Thêm tài khoản</button>
                    </div>
                </div>

                <!-- bảng -->
                <div class="table-wrapper">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Họ tên</th>
                                <th>Chức vụ</th>
                                <th>Vai trò</th>
                                <th>Ngày công tác</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>

                        <!-- Dữ liệu -->
                        <tbody id="staffTableBody">
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?= $row['stt'] ?></td>
                                    <td><a href="staff_detail.php?id=<?= $row['ID'] ?>"
                                            class="staff-name-link"><?= htmlspecialchars($row['Ho_ten']) ?></a></td>
                                    <td><?= htmlspecialchars($row['Chuc_vu']) ?></td>
                                    <td><?= htmlspecialchars($row['Role']) ?></td>
                                    <td><?= htmlspecialchars($row['Ngay_cong_tac']) ?></td>
                                    <td>
                                        <span
                                            class="status <?= $row['Trang_thai'] == 'Kich_hoat' ? 'active' : 'inactive' ?>">
                                            <?= $row['Trang_thai'] == 'Kich_hoat' ? 'Hoạt Động' : 'Khóa' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-edit" data-id="<?= $row['ID'] ?>"
                                            data-username="<?= htmlspecialchars($row['Username']) ?>"
                                            data-ho_ten="<?= htmlspecialchars($row['Ho_ten']) ?>"
                                            data-chuc_vu="<?= htmlspecialchars($row['Chuc_vu']) ?>"
                                            data-role="<?= $row['Role'] ?>"
                                            data-ngay_cong_tac="<?= htmlspecialchars($row['Ngay_cong_tac']) ?>"
                                            data-email="<?= htmlspecialchars($row['Email']) ?>"
                                            data-sdt="<?= htmlspecialchars($row['SDT']) ?>"
                                            data-anh="<?= htmlspecialchars($row['Anh']) ?>"
                                            data-buong-giam="<?= $row['buong_giam_id'] ?? '' ?>">
                                            Sửa
                                        </button>
                                        <button class="btn-toggle" data-id="<?= $row['ID'] ?>"
                                            data-status="<?= $row['Trang_thai'] ?>">
                                            <?= $row['Trang_thai'] == 'Kich_hoat' ? 'Khóa' : 'Mở khóa' ?>
                                        </button>
                                    </td>
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
                <h3>Cập nhật tài khoản cán bộ</h3>
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
                            <div class="room">
                                <label>Chuyển Buồng</label>
                                <select name="buong_giam" id="buong_giam">
                                    <option value="">-- Chọn buồng --</option>
                                    <?php foreach ($cells as $cell): ?>
                                        <option value="<?= $cell['ID'] ?>"><?= htmlspecialchars($cell['Ten_buong']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-right">
                            <div class="form-column">
                                <div>
                                    <label>Tên đăng nhập</label>
                                    <input type="text" id="editUsername" disabled>
                                </div>
                                <div>
                                    <label>Họ tên</label>
                                    <input type="text" name="ho_ten" id="editHoTen" required>
                                </div>
                                <div>
                                    <label>Chức vụ</label>
                                    <select name="chuc_vu" id="editChucVu" required>
                                        <option value="Đại tướng">Đại tướng</option>
                                        <option value="Thượng tướng">Thượng tướng</option>
                                        <option value="Trung tướng">Trung tướng</option>
                                        <option value="Thiếu tướng">Thiếu tướng</option>
                                        <option value="Đại tá">Đại tá</option>
                                        <option value="Thượng tá">Thượng tá</option>
                                        <option value="Trung tá">Trung tá</option>
                                        <option value="Thiếu tá">Thiếu tá</option>
                                        <option value="Đại úy">Đại úy</option>
                                        <option value="Thượng úy">Thượng úy</option>
                                        <option value="Trung úy">Trung úy</option>
                                        <option value="Thiếu úy">Thiếu úy</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vai trò</label>
                                    <select name="role" id="editRole">
                                        <option value="User">User</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Mod">Mod</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Ngày công tác</label>
                                    <input type="date" name="ngay_cong_tac" id="editNgayCongTac" required>
                                </div>
                                <div>
                                    <label>Email</label>
                                    <input type="email" name="email" id="editEmail" required>
                                </div>
                                <div>
                                    <label>Số điện thoại</label>
                                    <input type="text" name="sdt" id="editSdt" required>
                                </div>
                                <div>
                                    <label>Mật khẩu mới</label>
                                    <input type="password" name="password" id="editPassword">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" class="btn-cancel" onclick="closeModal('edit')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal khóa/mở khóa -->
        <div id="toggleModal" class="modal hidden">
            <div class="modal-content">
                <h3>Xác nhận thay đổi trạng thái</h3>
                <p>Bạn có chắc chắn muốn thay đổi trạng thái tài khoản này?</p>
                <form id="toggleForm">
                    <input type="hidden" name="id" id="toggleId">
                    <input type="hidden" name="status" id="toggleStatus">
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" class="btn-cancel" onclick="closeModal('toggle')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal thêm tài khoản -->
        <div id="addModal" class="modal hidden">
            <div class="modal-content">
                <h3>Thêm tài khoản cán bộ</h3>
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
                                    <label>Tên đăng nhập</label>
                                    <input type="text" name="username" id="addUsername" required>
                                </div>
                                <div>
                                    <label>Họ tên</label>
                                    <input type="text" name="ho_ten" id="addHoTen" required>
                                </div>
                                <div>
                                    <label>Chức vụ</label>
                                    <select name="chuc_vu" id="addChucVu" required>
                                        <option value="Đại tướng">Đại tướng</option>
                                        <option value="Thượng tướng">Thượng tướng</option>
                                        <option value="Trung tướng">Trung tướng</option>
                                        <option value="Thiếu tướng">Thiếu tướng</option>
                                        <option value="Đại tá">Đại tá</option>
                                        <option value="Thượng tá">Thượng tá</option>
                                        <option value="Trung tá">Trung tá</option>
                                        <option value="Thiếu tá">Thiếu tá</option>
                                        <option value="Đại úy">Đại úy</option>
                                        <option value="Thượng úy">Thượng úy</option>
                                        <option value="Trung úy">Trung úy</option>
                                        <option value="Thiếu úy">Thiếu úy</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vai trò</label>
                                    <select name="role" id="addRole">
                                        <option value="User">User</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Mod">Mod</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Ngày công tác</label>
                                    <input type="date" name="ngay_cong_tac" id="addNgayCongTac" required>
                                </div>
                                <div>
                                    <label>Email</label>
                                    <input type="email" name="email" id="addEmail" required>
                                </div>
                                <div>
                                    <label>Số điện thoại</label>
                                    <input type="text" name="sdt" id="addSdt" required>
                                </div>
                                <div>
                                    <label>Mật khẩu</label>
                                    <input type="password" name="password" id="addPassword" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit">Xác nhận</button>
                        <button type="button" class="btn-cancel" onclick="closeModal('add')">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="js/staff.js"></script>
</body>

</html>