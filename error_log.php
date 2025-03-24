<?php
/**
 * Ghi lỗi vào file log và hiển thị thông báo cho người dùng
 * 
 * @param string $error_message Thông báo lỗi
 * @param string $error_detail Chi tiết lỗi (sẽ được ghi vào log)
 * @param string $redirect_url URL để chuyển hướng sau khi hiển thị lỗi
 * @return void
 */
function log_error($error_message, $error_detail = '', $redirect_url = 'index.php') {
    // Ghi lỗi vào file
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] Error: $error_message - Detail: $error_detail" . PHP_EOL;
    
    // Đảm bảo thư mục logs tồn tại
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    // Ghi vào file
    file_put_contents('logs/error.log', $log_message, FILE_APPEND);
    
    // Bắt đầu session nếu chưa bắt đầu
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Lưu thông báo lỗi để hiển thị
    $_SESSION['login_error'] = $error_message;
    
    // Chuyển hướng
    header("Location: $redirect_url");
    exit();
}
?> 