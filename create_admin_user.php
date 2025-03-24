<?php
require_once 'db_login_register.php';
require_once 'check_auth.php';

// Yêu cầu quyền admin để truy cập trang
requireAdmin();

$success_message = '';
$error_messages = [];
$form_data = [
    'username' => '',
    'email' => '',
];

try {
    // Kết nối đã được thiết lập trong db_login_register.php
    $connection_success = true;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lấy dữ liệu từ form
        $username = trim($_POST['username']);
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Lưu giá trị đã nhập để hiển thị lại trong form nếu có lỗi
        $form_data['username'] = $username;
        $form_data['email'] = $email;
        
        // Kiểm tra các trường đầu vào
        if (empty($username)) {
            $error_messages[] = "Tên đăng nhập không được để trống";
        }
        
        if (empty($email)) {
            $error_messages[] = "Email không được để trống";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_messages[] = "Email không hợp lệ";
        }
        
        if (empty($password)) {
            $error_messages[] = "Mật khẩu không được để trống";
        } elseif (strlen($password) < 6) {
            $error_messages[] = "Mật khẩu phải có ít nhất 6 ký tự";
        }
        
        if ($password !== $confirm_password) {
            $error_messages[] = "Mật khẩu xác nhận không khớp";
        }
        
        // Kiểm tra nếu username hoặc email đã tồn tại
        if (empty($error_messages)) {
            $checkUser = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $checkUser->bindParam(':username', $username);
            $checkUser->bindParam(':email', $email);
            $checkUser->execute();
            
            if ($checkUser->rowCount() > 0) {
                $user = $checkUser->fetch(PDO::FETCH_ASSOC);
                if ($user['username'] === $username) {
                    $error_messages[] = "Tên đăng nhập đã được sử dụng";
                }
                if ($user['email'] === $email) {
                    $error_messages[] = "Email đã được đăng ký";
                }
            }
        }
        
        // Nếu không có lỗi, tạo tài khoản admin
        if (empty($error_messages)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin'; // Đặt vai trò là admin
            
            $insertUser = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $insertUser->bindParam(':username', $username);
            $insertUser->bindParam(':email', $email);
            $insertUser->bindParam(':password', $hashedPassword);
            $insertUser->bindParam(':role', $role);
            
            if ($insertUser->execute()) {
                $success_message = "Tạo tài khoản admin thành công!";
                // Xóa dữ liệu form sau khi thành công
                $form_data['username'] = '';
                $form_data['email'] = '';
            } else {
                $error_messages[] = "Không thể tạo tài khoản. Vui lòng thử lại sau.";
            }
        }
    }
    
    // Hiển thị danh sách tài khoản admin
    $adminUsers = $pdo->query("SELECT id, username, email, created_at FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
    $admin_count = count($adminUsers);
    
} catch (PDOException $e) {
    $connection_success = false;
    $error_messages[] = "Lỗi kết nối database: " . $e->getMessage();
}

// Include header
include('admin_header.php');
?>

<div class="section-title">
    <h2>Tạo Tài Khoản Admin</h2>
    <p>Tạo và quản lý tài khoản với quyền quản trị hệ thống</p>
</div>

<?php if (!$connection_success): ?>
    <div class="alert alert-danger">
        <?php foreach ($error_messages as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php if (!empty($success_message)): ?>
        <div class="message success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_messages)): ?>
        <div class="message error">
            <ul>
                <?php foreach ($error_messages as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Form tạo tài khoản admin -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Thông tin tài khoản admin mới</h2>
            </div>
            <div class="card-content">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn">Tạo tài khoản admin</button>
                </form>
            </div>
        </div>

        <!-- Danh sách tài khoản admin -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Danh sách tài khoản admin (<?php echo $admin_count; ?>)</h2>
            </div>
            <div class="card-content">
                <?php if ($admin_count > 0): ?>
                    <div class="scrollable-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên đăng nhập</th>
                                    <th>Email</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminUsers as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-data">Chưa có tài khoản admin nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.admin-card {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    overflow: hidden;
}

.card-header {
    background-color: #205375;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.card-content {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group input:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.btn {
    background-color: #205375;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 20px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 16px;
}

.btn:hover {
    background-color: #153b56;
}

.scrollable-table {
    max-height: 500px;
    overflow-y: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
    position: sticky;
    top: 0;
    z-index: 10;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

.message {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.message.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.message.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.empty-data {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    th, td {
        padding: 10px;
    }
    
    .card-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include('admin_footer.php'); ?> 