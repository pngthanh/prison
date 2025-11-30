<?php
session_start();

include 'connect.php';

// Kiểm tra session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: prisoners.php");
    exit();
}

$prisoner_id = intval($_GET['id']);

// Lấy thông tin tù nhân từ DB
$stmt = $conn->prepare("SELECT * FROM prisoners WHERE ID = ?");
$stmt->bind_param("i", $prisoner_id);
$stmt->execute();
$result = $stmt->get_result();
$prisoner = $result->fetch_assoc();
$stmt->close();
$result->close();

if (!$prisoner) {
    header("Location: prisoners.php");
    exit();
}

// Định dạng ngày tháng
$ngay_sinh = $prisoner['Ngay_sinh'] ? date('d/m/Y', strtotime($prisoner['Ngay_sinh'])) : 'Chưa cập nhật';
$ngay_nhap_trai = $prisoner['Ngay_nhap_trai'] ? date('d/m/Y', strtotime($prisoner['Ngay_nhap_trai'])) : 'Chưa cập nhật';

// Xử lý ngày ra trại dựa trên hình phạt
if ($prisoner['Hinh_phat'] === 'Tu_hinh') {
    $ngay_ra_trai = 'Tử Hình';
} elseif ($prisoner['Hinh_phat'] === 'Chung_than') {
    $ngay_ra_trai = 'Chung Thân';
} else { // Co_han
    if ($prisoner['Ngay_ra_trai'] && $prisoner['Ngay_nhap_trai']) {
        $start_date = new DateTime($prisoner['Ngay_nhap_trai']);
        $end_date = new DateTime($prisoner['Ngay_ra_trai']);
        $interval = $start_date->diff($end_date);

        $years = $interval->y;
        $months = $interval->m;

        $ngay_ra_trai = '';
        if ($years > 0) {
            $ngay_ra_trai .= "$years năm";
        }
        if ($months > 0) {
            $ngay_ra_trai .= ($ngay_ra_trai ? ' ' : '') . "$months tháng";
        }
        if (empty($ngay_ra_trai)) {
            $ngay_ra_trai = 'Dưới 1 tháng';
        }
    } else {
        $ngay_ra_trai = 'Chưa xác định';
    }
}

// Xử lý hình phạt với dấu
if ($prisoner['Hinh_phat'] === 'Tu_hinh') {
    $hinh_phat = 'Tử Hình';
} elseif ($prisoner['Hinh_phat'] === 'Chung_than') {
    $hinh_phat = 'Chung Thân';
} elseif ($prisoner['Hinh_phat'] === 'Co_han') {
    $hinh_phat = 'Có hạn';
} else {
    $hinh_phat = 'Chưa cập nhật';
}

// Định nghĩa các biến khác
$so_CMND = htmlspecialchars($prisoner['So_CMND'] ?? 'Chưa cập nhật');
$trang_thai = $prisoner['Trang_thai'] === 'Dang_giam_giu' ? 'Đang giam giữ' : 'Đã ra trại';

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

    <title>Chi Tiết Tù Nhân</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/amnesty_prisoners_detail.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <main class="content">
        <section class="prisoners-detail">
            <h2>Chi Tiết Tù Nhân: <?php echo htmlspecialchars($prisoner['Ho_ten']); ?></h2>
            <div class="prisoners-info">
                <!-- Ảnh -->
                <div class="prisoners-avatar">
                    <img src="<?php echo htmlspecialchars($prisoner['Anh'] ?? 'img/default.jpg'); ?>"
                        alt="Ảnh đại diện">
                </div>
                <!-- Thông tin -->
                <div class="prisoners-details">
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($prisoner['Ho_ten']); ?></p>
                    <p><strong>Ngày sinh:</strong> <?php echo $ngay_sinh; ?></p>
                    <p><strong>Quê quán:</strong>
                        <?php echo htmlspecialchars($prisoner['Que_quan'] ?? 'Chưa cập nhật'); ?></p>
                    <p><strong>Tội danh:</strong>
                        <?php echo htmlspecialchars($prisoner['Toi_danh'] ?? 'Chưa cập nhật'); ?></p>
                    <p><strong>Ngày nhập trại:</strong> <?php echo $ngay_nhap_trai; ?></p>
                    <p><strong>Ngày ra trại:</strong> <?php echo $ngay_ra_trai; ?></p>
                    <p><strong>Hình phạt:</strong> <?php echo $hinh_phat; ?></p>
                    <p><strong>Buồng giam:</strong>
                        <?php echo htmlspecialchars($prisoner['Buong_giam'] ?? 'Chưa được phân công'); ?></p>
                    <p><strong>Trạng thái:</strong> <?php echo $trang_thai; ?></p>
                    <p><strong>Số CMND:</strong> <?php echo $so_CMND; ?></p>
                </div>
            </div>
            <!-- Nút -->
            <div class="actions">
                <a href="amnesty.php" class="btn-back">Quay lại</a>
            </div>
        </section>
    </main>
    <script src="js/prisoner_detail.js"></script>
</body>

</html>