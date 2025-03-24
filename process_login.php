<?php
require_once 'db_login_register.php';
require_once 'error_log.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usernameOrEmail = trim($_POST['username']);
        $password = $_POST['password'];

        // Kiểm tra các trường đầu vào
        if(empty($usernameOrEmail) || empty($password)) {
            log_error("Vui lòng điền đầy đủ thông tin đăng nhập", "Empty fields in login form");
        }

        // Ghi log thông tin đăng nhập (bỏ mật khẩu)
        error_log("Login attempt: $usernameOrEmail");

        // Tìm kiếm user bằng username hoặc email
        $query = "SELECT id, username, email, password, role FROM users WHERE username = :credential OR email = :credential";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':credential', $usernameOrEmail);

        if (!$stmt->execute()) {
            $error = implode(", ", $stmt->errorInfo());
            log_error("Lỗi truy vấn cơ sở dữ liệu", "Database query error: $error");
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Người dùng tồn tại, kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                // Mật khẩu đúng, lưu thông tin người dùng vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Ghi log đăng nhập thành công
                error_log("Login successful: " . $user['username'] . " with role: " . $user['role']);
                
                // Chuyển hướng dựa vào vai trò
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: map.html');
                }
                exit();
            } else {
                // Mật khẩu không đúng
                log_error("Tên đăng nhập hoặc mật khẩu không đúng", "Invalid password for user: $usernameOrEmail");
            }
        } else {
            // Không tìm thấy người dùng
            log_error("Tên đăng nhập hoặc mật khẩu không đúng", "User not found: $usernameOrEmail");
        }
    } catch (PDOException $e) {
        log_error("Lỗi cơ sở dữ liệu", "Database error: " . $e->getMessage());
    } catch (Exception $e) {
        log_error("Lỗi không xác định", "Unexpected error: " . $e->getMessage());
    }
}
?>
