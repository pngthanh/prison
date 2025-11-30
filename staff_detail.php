<?php
session_start();

include 'connect.php';

// ktra ss
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: staff.php");
    exit();
}

$staff_id = intval($_GET['id']);

// Lấy thông tin cán bộ từ DB
$stmt = $conn->prepare("
    SELECT s.*, GROUP_CONCAT(c.Ten_buong SEPARATOR ', ') AS Buong_giam_quan_ly
    FROM staff s
    LEFT JOIN cells c ON s.ID = c.Can_bo_phu_trach
    WHERE s.ID = ?
    GROUP BY s.ID
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    header("Location: staff.php");
    exit();
}

// Định dạng ngày công tác
$ngay_cong_tac = $staff['Ngay_cong_tac'] ? date('d/m/Y', strtotime($staff['Ngay_cong_tac'])) : 'Chưa cập nhật';

// Định dạng trạng thái
$trang_thai = $staff['Trang_thai'] === 'Kich_hoat' ? 'Hoạt động' : 'Khóa';


// Buồng giam quản lý
$buong_giam_quan_ly = $staff['Buong_giam_quan_ly'] ? htmlspecialchars($staff['Buong_giam_quan_ly']) : 'Chưa được phân công';

// Lấy danh sách buồng giam
$sql_cells = "SELECT ID, Ten_buong FROM cells ORDER BY Ten_buong";
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

    <title>Chi Tiết Cán Bộ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/staff_detail.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <main class="content">
        <section class="staff-detail">
            <h2>Chi Tiết Cán Bộ: <?php echo htmlspecialchars($staff['Ho_ten']); ?></h2>
            <div class="staff-info">

                <!-- Ảnh -->
                <div class="staff-avatar">
                    <img src="<?php echo htmlspecialchars($staff['Anh'] ?? 'img/default.jpg'); ?>" alt="Ảnh đại diện">
                </div>

                <!-- Thông tin -->
                <div class="staff-details">
                    <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($staff['Username']); ?></p>
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($staff['Ho_ten']); ?></p>
                    <p><strong>Chức vụ:</strong> <?php echo htmlspecialchars($staff['Chuc_vu']); ?></p>
                    <p><strong>Buồng giam quản lý:</strong> <?php echo $buong_giam_quan_ly; ?></p>
                    <p><strong>Ngày công tác:</strong> <?php echo $ngay_cong_tac; ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($staff['Email'] ?? 'Chưa cập nhật'); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($staff['SDT'] ?? 'Chưa cập nhật'); ?>
                    </p>
                    <p><strong>Trạng thái:</strong> <?php echo $trang_thai; ?></p>
                </div>
            </div>

            <!-- Nút -->
            <div class="actions">
                <a href="staff.php" class="btn-back">Quay lại danh sách</a>
            </div>
        </section>
    </main>
</body>

</html>