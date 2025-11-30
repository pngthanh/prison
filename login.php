<?php
session_start();
require_once 'connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu!';
    } else {
        try {
            $stmt = $conn->prepare("SELECT ID, Username, Mat_khau, Role, Trang_thai FROM staff WHERE Username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = 'Tài khoản không tồn tại!';
            } else {
                $user = $result->fetch_assoc();

                // Kiểm tra trạng thái tài khoản trước để tránh kiểm tra mật khẩu không cần thiết
                if ($user['Trang_thai'] === 'Khoa') {
                    $error = 'Tài khoản đã bị khóa!';
                } elseif (!password_verify($password, $user['Mat_khau'])) {
                    $error = 'Mật khẩu không chính xác!';
                } else {
                    // Đăng nhập thành công, lưu thông tin session
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role'] = $user['Role'];

                    header("Location: trangchu.php");
                    exit();
                }
            }

            $stmt->close();
        } catch (Exception $e) {
            $error = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau!';
        }
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/login.css">
    <title>Đăng Nhập</title>
</head>


<body>
    <div class="container">
        <div class="img">
            <img src="img/bg.jpg" alt="Hình Đăng Nhập">
        </div>
        <div class="login-content">
            <form method="POST" action="login.php">
                <h2 class="title">Đăng Nhập</h2>
                <div class="input-div one">
                    <div class="i">
                        <i class="fa fa-user-circle-o" aria-hidden="true"></i>
                    </div>
                    <div class="div">
                        <h5>Tài Khoản</h5>
                        <input type="text" class="input" name="username">
                    </div>
                </div>
                <div class="input-div pass">
                    <div class="i">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                    </div>
                    <div class="div">
                        <h5>Mật Khẩu</h5>
                        <input type="password" class="input" name="password">
                    </div>
                </div>
                <input type="submit" class="btn" value="Đăng Nhập">
                <p>Quên Mật Khẩu: Vui lòng liên hệ Admin</p>
            </form>
        </div>
    </div>
    <script src="js/login.js"></script>
    <div id="toast"></div>
    <?php if (!empty($error)): ?>
        <script>
            showToast("<?php echo addslashes($error); ?>");
        </script>
    <?php endif; ?>

</body>

</html>