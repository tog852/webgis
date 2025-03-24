<?php
// Thông tin kết nối cơ sở dữ liệu
$host = "localhost";
$port = "5432";
$dbname = "tltn";
$user = "postgres";
$password = "5432";

try {
    // Tạo kết nối với PostgreSQL
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    // Thiết lập chế độ lỗi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tạo bảng users nếu chưa tồn tại
    $createTable = $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'user' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Thông báo kết nối thành công
    echo ""; // Thêm dòng này để kiểm tra kết nối
} catch (PDOException $e) {
    // Nếu kết nối không thành công, hiển thị lỗi
    die('Connection failed: ' . $e->getMessage());
}
?>
