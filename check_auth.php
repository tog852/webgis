<?php
// Bắt đầu session nếu chưa được bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra người dùng đã đăng nhập hay chưa
 * 
 * @return boolean True nếu đã đăng nhập, False nếu chưa đăng nhập
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Yêu cầu đăng nhập để truy cập trang
 * Chuyển hướng đến trang đăng nhập nếu chưa đăng nhập
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['login_error'] = 'Please login to access this page';
        header('Location: index.php');
        exit();
    }
}

/**
 * Kiểm tra người dùng có vai trò admin hay không
 * 
 * @return boolean True nếu là admin, False nếu không phải
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Yêu cầu quyền admin để truy cập trang
 * Chuyển hướng đến trang chính nếu không phải admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        header('Location: map.html');
        exit();
    }
}

/**
 * Lấy ID của người dùng hiện tại
 * 
 * @return integer|null ID của người dùng hoặc null nếu chưa đăng nhập
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Lấy username của người dùng hiện tại
 * 
 * @return string|null Username của người dùng hoặc null nếu chưa đăng nhập
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Lấy email của người dùng hiện tại
 * 
 * @return string|null Email của người dùng hoặc null nếu chưa đăng nhập
 */
function getCurrentEmail() {
    return isLoggedIn() ? $_SESSION['email'] : null;
}

/**
 * Lấy vai trò của người dùng hiện tại
 * 
 * @return string|null Vai trò của người dùng hoặc null nếu chưa đăng nhập
 */
function getCurrentRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}
?> 