<?php
require_once 'db_login_register.php';
require_once 'error_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Kiểm tra các trường đầu vào
        if(empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            log_error("Vui lòng điền đầy đủ thông tin", "Empty fields in registration form");
        }

        if ($password !== $confirm_password) {
            log_error("Mật khẩu không khớp", "Password mismatch in registration");
        }

        // Kiểm tra username và email đã tồn tại chưa
        $checkQuery = "SELECT * FROM users WHERE username = :username OR email = :email";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($user['username'] === $username) {
                log_error("Tên đăng nhập đã được sử dụng", "Username already exists: $username");
            }
            if ($user['email'] === $email) {
                log_error("Email đã được đăng ký", "Email already exists: $email");
            }
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        // Ghi log thông tin đăng ký (bỏ mật khẩu)
        $log_detail = "Registering user: $username, email: $email";
        error_log($log_detail);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['register_success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
            header('Location: index.php');
            exit();
        } else {
            log_error("Lỗi đăng ký tài khoản", "Insert failed, PDO error: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        $error_code = $e->getCode();
        $error_message = $e->getMessage();
        
        if ($error_code === '23505') {
            log_error("Tên đăng nhập hoặc email đã tồn tại", "Duplicate key error: $error_message");
        } else {
            log_error("Lỗi cơ sở dữ liệu", "Database error: $error_message");
        }
    } catch (Exception $e) {
        log_error("Lỗi không xác định", "Unexpected error: " . $e->getMessage());
    }
}
?>
