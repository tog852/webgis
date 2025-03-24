<?php
require_once 'db_login_register.php';
require_once 'check_auth.php';

// Yêu cầu quyền admin để truy cập trang
requireAdmin();

// Xử lý các thao tác quản lý người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        // Đảm bảo user_id hợp lệ
        if ($userId <= 0) {
            $error = "ID người dùng không hợp lệ";
        } else {
            switch ($_POST['action']) {
                case 'delete':
                    // Xóa người dùng
                    try {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                        $stmt->bindParam(':id', $userId);
                        if ($stmt->execute()) {
                            $success = "Đã xóa người dùng thành công";
                        } else {
                            $error = "Không thể xóa người dùng";
                        }
                    } catch (PDOException $e) {
                        $error = "Lỗi: " . $e->getMessage();
                    }
                    break;
                    
                case 'make_admin':
                    // Phân quyền admin cho người dùng
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
                        $stmt->bindParam(':id', $userId);
                        if ($stmt->execute()) {
                            $success = "Đã cấp quyền admin cho người dùng thành công";
                        } else {
                            $error = "Không thể cấp quyền admin cho người dùng";
                        }
                    } catch (PDOException $e) {
                        $error = "Lỗi: " . $e->getMessage();
                    }
                    break;
                    
                case 'remove_admin':
                    // Gỡ quyền admin của người dùng
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = :id");
                        $stmt->bindParam(':id', $userId);
                        if ($stmt->execute()) {
                            $success = "Đã gỡ quyền admin của người dùng thành công";
                        } else {
                            $error = "Không thể gỡ quyền admin của người dùng";
                        }
                    } catch (PDOException $e) {
                        $error = "Lỗi: " . $e->getMessage();
                    }
                    break;
                    
                default:
                    $error = "Hành động không hợp lệ";
                    break;
            }
        }
    }
}

// Lấy danh sách người dùng
try {
    $users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Không thể lấy danh sách người dùng: " . $e->getMessage();
    $users = [];
}

// Include header
include('admin_header.php');
?>

<div class="section-title">
    <h2>Quản Lý Người Dùng</h2>
    <p>Xem và quản lý tài khoản người dùng trên hệ thống</p>
</div>

<?php if (isset($success)): ?>
    <div class="message success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên người dùng</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <?php if ($user['id'] != getCurrentUserId()): // Không cho phép thay đổi chính mình ?>
                                <form method="post" style="display: inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger">Xóa</button>
                                </form>
                                
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="post" style="display: inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="make_admin">
                                        <button type="submit" class="btn btn-primary">Cấp quyền admin</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="remove_admin">
                                        <button type="submit" class="btn btn-secondary">Gỡ quyền admin</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span>(Tài khoản hiện tại)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Không có người dùng nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.btn-danger {
    background-color: #dc3545;
}
.btn-primary {
    background-color: #007bff;
}
.btn-secondary {
    background-color: #6c757d;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background-color: #205375;
    color: white;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
tr:hover {
    background-color: #ddd;
}
.card {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}
</style>

<?php include('admin_footer.php'); ?> 