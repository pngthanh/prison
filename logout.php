<?php
// logout.php - Đăng xuất
session_start();
session_destroy();
header("Location: login.php");
exit();