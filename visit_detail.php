<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy ID của lượt thăm
if (!isset($_GET['id'])) {
    header("Location: visits.php");
    exit();
}
$visit_id = intval($_GET['id']);


$stmt = $conn->prepare("
    SELECT 
        v.*, 
        p.Ho_ten AS Ho_ten_tu_nhan, 
        p.Toi_danh, 
        p.Ngay_nhap_trai,
        c.Ten_buong
    FROM visits v
    JOIN prisoners p ON v.prisoner_id = p.ID
    LEFT JOIN cells c ON p.Buong_giam = c.ID
    WHERE v.ID = ?
");
$stmt->bind_param("i", $visit_id);
$stmt->execute();
$result = $stmt->get_result();
$visit = $result->fetch_assoc();

if (!$visit) {
    header("Location: visits.php");
    exit();
}


$ngay_gio_tham_formatted = $visit['Ngay_gio_tham'] ? date('H:i \n\g\à\y d/m/Y', strtotime($visit['Ngay_gio_tham'])) : 'Chưa cập nhật';

$ngay_nhap_trai_formatted = $visit['Ngay_nhap_trai'] ? date('d/m/Y', strtotime($visit['Ngay_nhap_trai'])) : 'Chưa cập nhật';

$trang_thai_text = 'Chưa xác định';
$trang_thai_class = '';
switch ($visit['Trang_thai']) {
    case 'Da_duyet':
        $trang_thai_text = 'Đã duyệt';
        $trang_thai_class = 'status-da_duyet';
        break;
    case 'Dang_cho_duyet':
        $trang_thai_text = 'Đang chờ duyệt';
        $trang_thai_class = 'status-dang_cho_duyet';
        break;
    case 'Bi_tu_choi':
        $trang_thai_text = 'Bị từ chối';
        $trang_thai_class = 'status-bi_tu_choi';
        break;
}

function displayField($field)
{
    return htmlspecialchars($field ? $field : 'Chưa cập nhật');
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

    <title>Chi Tiết Thăm Nom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/visit_detail.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="content">
        <section class="visit-detail">
            <h2>Chi Tiết Lượt Thăm Nom (ID: <?php echo $visit['ID']; ?>)</h2>

            <div class="visit-info">
                <div class="info-column">
                    <h3>Thông Tin Người Thăm</h3>
                    <p><strong>Họ tên:</strong> <?php echo displayField($visit['Ho_ten_nguoi_tham']); ?></p>
                    <p><strong>Số CMND/CCCD:</strong> <?php echo displayField($visit['So_CMND_nguoi_tham']); ?></p>
                    <p><strong>Mối quan hệ:</strong> <?php echo displayField($visit['Moi_quan_he']); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo displayField($visit['SDT_nguoi_tham']); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo displayField($visit['Dia_chi_nguoi_tham']); ?></p>
                </div>

                <div class="info-column">
                    <h3>Thông Tin Tù Nhân Được Thăm</h3>
                    <p><strong>Họ tên tù nhân:</strong> <?php echo displayField($visit['Ho_ten_tu_nhan']); ?></p>
                    <p><strong>Buồng giam:</strong> <?php echo displayField($visit['Ten_buong']); ?></p>
                    <p><strong>Tội danh:</strong> <?php echo displayField($visit['Toi_danh']); ?></p>
                    <p><strong>Ngày nhập trại:</strong> <?php echo $ngay_nhap_trai_formatted; ?></p>
                </div>
            </div>

            <div class="visit-status-section">
                <div class="info-column">
                    <h3>Chi Tiết Đăng Ký</h3>
                    <p><strong>Thời gian đăng ký:</strong> <?php echo $ngay_gio_tham_formatted; ?></p>
                    <p><strong>Ghi chú:</strong> <?php echo displayField($visit['Ghi_chu']); ?></p>
                    <p><strong>Trạng thái:</strong>
                        <span class="status-badge <?php echo $trang_thai_class; ?>">
                            <?php echo $trang_thai_text; ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="actions">
                <a href="visits.php" class="btn-back">Quay lại danh sách</a>
            </div>
        </section>
    </main>
</body>

</html>