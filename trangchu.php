<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

include 'connect.php';

$user_id = $_SESSION['user_id'];

// Lấy thông tin user từ DB
$stmt = $conn->prepare("SELECT Ho_ten, Chuc_vu, Ngay_cong_tac, Email, SDT, Anh FROM staff WHERE ID = ? AND Trang_thai = 'Kich_hoat'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Lấy danh sách buồng giam được phân công
if ($_SESSION['role'] === 'Admin') {
    $assigned_cells_list = 'Tất Cả';
} else {
    $assigned_cells_stmt = $conn->prepare("SELECT Ten_buong FROM cells WHERE Can_bo_phu_trach = ?");
    $assigned_cells_stmt->bind_param("i", $user_id);
    $assigned_cells_stmt->execute();
    $assigned_cells_result = $assigned_cells_stmt->get_result();
    $assigned_cells = $assigned_cells_result->fetch_all(MYSQLI_ASSOC);
    $assigned_cells_list = count($assigned_cells) > 0 ? implode(', ', array_column($assigned_cells, 'Ten_buong')) : 'Chưa được phân công';
}

// Lấy danh sách buồng giam và số lượng tù nhân
if ($_SESSION['role'] === 'Admin') {
    $cells_query = "SELECT c.ID, c.Ten_buong, c.Suc_chua, c.Trang_thai, COUNT(p.ID) as So_tu_nhan 
                    FROM cells c 
                    LEFT JOIN prisoners p ON c.ID = p.Buong_giam AND p.Trang_thai = 'Dang_giam_giu' 
                    GROUP BY c.ID";
    $cells_stmt = $conn->prepare($cells_query);
} else {
    $cells_query = "SELECT c.ID, c.Ten_buong, c.Suc_chua, c.Trang_thai, COUNT(p.ID) as So_tu_nhan 
                    FROM cells c 
                    LEFT JOIN prisoners p ON c.ID = p.Buong_giam AND p.Trang_thai = 'Dang_giam_giu' 
                    WHERE c.Can_bo_phu_trach = ? 
                    GROUP BY c.ID";
    $cells_stmt = $conn->prepare($cells_query);
    $cells_stmt->bind_param("i", $user_id);
}
$cells_stmt->execute();
$cells_result = $cells_stmt->get_result();
$cells = $cells_result->fetch_all(MYSQLI_ASSOC);
$cell_count = count($cells);

// Lấy danh sách sự kiện sắp diễn ra
$current_datetime = date('Y-m-d H:i:s');
$events_stmt = $conn->prepare("SELECT Ten_su_kien, Ngay_gio, Mo_ta FROM events WHERE Ngay_gio >= ? ORDER BY Ngay_gio ASC LIMIT 5");
$events_stmt->bind_param("s", $current_datetime);
$events_stmt->execute();
$events_result = $events_stmt->get_result();
$events = $events_result->fetch_all(MYSQLI_ASSOC);
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

    <title>Trang Chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/trangchu.css">
</head>

<body>
    <?php include 'loader.php'; ?>
    <?php include 'sidebar.php'; ?>
    <main class="content">
        <!-- Cán bộ -->
        <section class="user-info">
            <!-- Ảnh -->
            <div class="user-avatar">
                <img src="<?php echo htmlspecialchars($user['Anh'] ?? 'img/anhthe.jpg'); ?>" alt="Avatar">
            </div>
            <!-- Thông tin -->
            <div class="user-info-content">
                <h2>Đồng chí: <?php echo htmlspecialchars($user['Ho_ten']); ?></h2>
                <div class="user-details-container">
                    <div class="user-details">
                        <p>Chức vụ: <?php echo htmlspecialchars($user['Chuc_vu']); ?></p>
                        <p>Buồng giam: <?php echo htmlspecialchars($assigned_cells_list); ?></p>
                        <p>Ngày công tác:
                            <?php echo $user['Ngay_cong_tac'] ? date('d/m/Y', strtotime($user['Ngay_cong_tac'])) : 'Chưa cập nhật'; ?>
                        </p>
                    </div>
                    <div class="user-details-2">
                        <p>SDT: <?php echo htmlspecialchars($user['SDT'] ?? 'Chưa cập nhật'); ?></p>
                        <p>Email: <?php echo htmlspecialchars($user['Email'] ?? 'Chưa cập nhật'); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Buồng giam -->
        <section class="dashboard-sections">
            <div class="cells-section">
                <h3>
                    <?php
                    // Kiểm tra vai trò Admin
                    if ($_SESSION['role'] === 'Admin') {
                        echo "Tất Cả Buồng Giam";
                    } else {
                        echo "Buồng Giam " . $cell_count;
                    }
                    ?>
                </h3>
                <?php if ($cell_count > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tên buồng</th>
                                    <th>Sức chứa</th>
                                    <th>Tù nhân</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cells as $cell): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cell['Ten_buong']); ?></td>
                                        <td><?php echo htmlspecialchars($cell['Suc_chua']); ?></td>
                                        <td><?php echo htmlspecialchars($cell['So_tu_nhan']); ?></td>
                                        <td>
                                            <?php
                                            $trang_thai = htmlspecialchars($cell['Trang_thai']);
                                            if ($trang_thai === 'Con_trong') {
                                                echo 'Còn trống';
                                            } elseif ($trang_thai === 'Day') {
                                                echo 'Đầy';
                                            } elseif ($trang_thai === 'Dang_bao_tri') {
                                                echo 'Đang bảo trì';
                                            } else {
                                                echo $trang_thai;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Không Quản Lý Buồng Giam Nào.</p>
                <?php endif; ?>
            </div>

            <!-- SKien sắp diễn ra -->
            <div class="events-section">
                <h3>Sự Kiện Sắp Diễn Ra</h3>
                <?php if (count($events) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tên sự kiện</th>
                                    <th>Mô tả</th>
                                    <th>Ngày</th>
                                    <th>Giờ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['Ten_su_kien']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($event['Mo_ta'] ?? '', 0, 50)) . (strlen($event['Mo_ta'] ?? '') > 50 ? '...' : ''); ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($event['Ngay_gio'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($event['Ngay_gio'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Không có sự kiện nào sắp diễn ra.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>

</html>