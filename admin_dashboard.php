<?php
require_once 'db_login_register.php';
require_once 'check_auth.php';

// Yêu cầu quyền admin để truy cập trang
requireAdmin();

// Include header
include('admin_header.php');

// Lấy thông tin người dùng hiện tại
$username = getCurrentUsername();
$email = getCurrentEmail();
$role = getCurrentRole();

// Lấy thống kê mục đích sử dụng đất từ bảng tanbinh_dongxoai
try {
    $statQuery = $pdo->query("SELECT mdsd, COUNT(*) as count FROM tanbinh_dongxoai GROUP BY mdsd ORDER BY count DESC");
    $statData = $statQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statError = "Không thể lấy dữ liệu thống kê: " . $e->getMessage();
    $statData = [];
}
?>

<div class="section-title">
    <h2>Quản Lý Nội Dung</h2>
    <p>Quản lý thông tin liên hệ và dữ liệu thửa đất</p>
</div>

<div class="dashboard-container">
    <!-- Thông tin Liên Hệ -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Thông Tin Liên Hệ</h2>
            <button class="btn" onclick="toggleContactTable()">
                <i class="fas fa-eye"></i> Hiển thị/Ẩn
            </button>
        </div>
        <div id="contactTableContainer" class="card-content" style="display: none;">
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Vấn đề liên hệ</th>
                            <th>Nội dung</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="contactTableBody">
                        <!-- Dữ liệu sẽ được thêm động từ PHP -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quản trị Thửa Đất -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Quản Trị Thửa Đất</h2>
            <button class="btn" onclick="toggleLandTable()">
                <i class="fas fa-eye"></i> Hiển thị/Ẩn
            </button>
        </div>
        <div id="landTableContainer" class="card-content" style="display: none;">
            <div class="search-bar">
                <input id="searchInput" type="text" class="form-control" placeholder="Tìm kiếm theo Tên chủ sở hữu, Số tờ, Số thửa, hoặc Địa chỉ">
            </div>
            <div class="scrollable-table">
                <table id="landTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên chủ sở hữu</th>
                            <th>Số tờ</th>
                            <th>Số thửa</th>
                            <th>Diện tích</th>
                            <th>Giá đất</th>
                            <th>Địa chỉ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="landTableBody">
                        <!-- Dữ liệu sẽ được thêm động từ PHP -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Biểu đồ thống kê -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Biểu Đồ Thống Kê Mục Đích Sử Dụng Đất</h2>
            <button class="btn" onclick="toggleStatsChart()">
                <i class="fas fa-eye"></i> Hiển thị/Ẩn
            </button>
        </div>
        <div id="statsChartContainer" class="card-content" style="display: none;">
            <?php if (isset($statError)): ?>
                <div class="alert alert-danger"><?php echo $statError; ?></div>
            <?php elseif (empty($statData)): ?>
                <div class="alert alert-info">Không có dữ liệu thống kê.</div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="mdsdChart"></canvas>
                </div>
                <div class="stats-table-container mt-4">
                    <h3>Bảng dữ liệu thống kê</h3>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Mục đích sử dụng đất</th>
                                <th>Số lượng</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = array_sum(array_column($statData, 'count'));
                            foreach ($statData as $item): 
                                $percentage = round(($item['count'] / $total) * 100, 1);
                            ?>
                                <tr>
                                    <td><?php echo $item['mdsd'] ? htmlspecialchars($item['mdsd']) : 'Không xác định'; ?></td>
                                    <td><?php echo $item['count']; ?></td>
                                    <td><?php echo $percentage; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Hàm hiển thị bảng "Thông tin Liên Hệ"
    function toggleContactTable() {
        const container = document.getElementById("contactTableContainer");
        container.style.display = container.style.display === "none" ? "block" : "none";
    }

    // Hàm lấy dữ liệu từ PHP cho bảng Thông tin Liên Hệ
    async function fetchContactData() {
        try {
            const response = await fetch("Trang quản trị/fetch_contact_data.php");
            const data = await response.json();
            const tableBody = document.getElementById("contactTableBody");

            // Xóa dữ liệu cũ
            tableBody.innerHTML = "";
            
            // Thêm dữ liệu mới
            data.forEach(contact => {
                const tr = document.createElement("tr");
                tr.dataset.id = contact.id;
                tr.className = contact.status === 'unread' ? 'unread-row' : '';
                
                tr.innerHTML = `
                    <td>${contact.name}</td>
                    <td>${contact.email}</td>
                    <td>${contact.subject}</td>
                    <td>${contact.message}</td>
                    <td>
                        <button class="btn btn-sm ${contact.status === 'unread' ? 'btn-primary' : 'btn-secondary'} status-btn" 
                                data-status="${contact.status}">
                            ${contact.status === 'unread' ? 'Đánh dấu đã đọc' : 'Đánh dấu chưa đọc'}
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn">Xóa</button>
                    </td>
                `;
                
                tableBody.appendChild(tr);
                
                // Xử lý nút đánh dấu đã đọc/chưa đọc
                const statusBtn = tr.querySelector('.status-btn');
                statusBtn.addEventListener('click', async () => {
                    const newStatus = statusBtn.dataset.status === 'unread' ? 'read' : 'unread';
                    try {
                        const response = await fetch('Trang quản trị/update_contact_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: contact.id,
                                status: newStatus
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Cập nhật giao diện
                            statusBtn.dataset.status = newStatus;
                            statusBtn.textContent = newStatus === 'unread' ? 'Đánh dấu đã đọc' : 'Đánh dấu chưa đọc';
                            statusBtn.className = `btn btn-sm ${newStatus === 'unread' ? 'btn-primary' : 'btn-secondary'} status-btn`;
                            tr.className = newStatus === 'unread' ? 'unread-row' : '';
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Lỗi khi cập nhật trạng thái:', error);
                        alert('Đã xảy ra lỗi khi cập nhật trạng thái');
                    }
                });
                
                // Xử lý nút xóa
                const deleteBtn = tr.querySelector('.delete-btn');
                deleteBtn.addEventListener('click', async () => {
                    if (confirm('Bạn có chắc chắn muốn xóa thông tin liên hệ này?')) {
                        try {
                            const response = await fetch(`Trang quản trị/delete_contact.php?id=${contact.id}`);
                            const result = await response.json();
                            
                            if (result.success) {
                                tr.remove();
                                alert('Đã xóa thông tin liên hệ thành công');
                            } else {
                                alert('Lỗi: ' + result.message);
                            }
                        } catch (error) {
                            console.error('Lỗi khi xóa thông tin liên hệ:', error);
                            alert('Đã xảy ra lỗi khi xóa thông tin liên hệ');
                        }
                    }
                });
            });
        } catch (error) {
            console.error("Lỗi khi lấy dữ liệu liên hệ:", error);
            alert("Không thể lấy dữ liệu liên hệ. Vui lòng thử lại sau.");
        }
    }

    // Gọi hàm fetchContactData khi mở bảng "Thông tin Liên Hệ"
    document.querySelector("button[onclick='toggleContactTable()']").addEventListener("click", fetchContactData);

    // Hàm hiển thị bảng "Quản trị Thửa Đất"
    function toggleLandTable() {
        const container = document.getElementById("landTableContainer");
        container.style.display = container.style.display === "none" ? "block" : "none";
    }

    // Fetch dữ liệu từ cơ sở dữ liệu
    async function fetchLandData() {
        try {
            const response = await fetch("Trang quản trị/fetch_land_data.php");
            const data = await response.json();
            const tableBody = document.getElementById("landTableBody");

            // Xóa dữ liệu cũ
            tableBody.innerHTML = "";
            
            // Sắp xếp dữ liệu theo ID để giữ thứ tự
            data.sort((a, b) => parseInt(a.id) - parseInt(b.id));

            // Thêm dữ liệu mới
            data.forEach(row => {
                const tr = document.createElement("tr");
                tr.dataset.id = row.id;
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td contenteditable="false">${row.tenchusdd || ''}</td>
                    <td contenteditable="false">${row.shbando || ''}</td>
                    <td contenteditable="false">${row.shthua || ''}</td>
                    <td contenteditable="false">${row.dientich || ''}</td>
                    <td contenteditable="false">${row.gia_dat || ''}</td>
                    <td contenteditable="false">${row.diachi || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn">Sửa</button>
                        <button class="btn btn-sm btn-success save-btn" style="display:none">Lưu</button>
                    </td>
                `;
                tableBody.appendChild(tr);
                
                // Thêm sự kiện cho nút sửa
                const editBtn = tr.querySelector('.edit-btn');
                const saveBtn = tr.querySelector('.save-btn');
                
                editBtn.addEventListener('click', () => {
                    const cells = tr.querySelectorAll('td[contenteditable]');
                    cells.forEach(cell => {
                        cell.contentEditable = "true";
                        cell.style.backgroundColor = "#fff3cd";
                    });
                    editBtn.style.display = "none";
                    saveBtn.style.display = "inline-block";
                });
                
                // Thêm sự kiện cho nút lưu
                saveBtn.addEventListener('click', async () => {
                    const updatedData = {
                        id: parseInt(tr.dataset.id),
                        tenchusdd: tr.cells[1].textContent.trim(),
                        shbando: tr.cells[2].textContent.trim(),
                        shthua: tr.cells[3].textContent.trim(),
                        dientich: tr.cells[4].textContent.trim(),
                        gia_dat: tr.cells[5].textContent.trim(),
                        diachi: tr.cells[6].textContent.trim()
                    };
                    
                    try {
                        const response = await fetch('Trang quản trị/update_land_data.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(updatedData)
                        });
                        
                        const result = await response.json();
                        
                        if (response.ok) {
                            const cells = tr.querySelectorAll('td[contenteditable]');
                            cells.forEach(cell => {
                                cell.contentEditable = "false";
                                cell.style.backgroundColor = "";
                            });
                            editBtn.style.display = "inline-block";
                            saveBtn.style.display = "none";
                            alert('Cập nhật thành công!');
                        } else {
                            alert('Lỗi khi cập nhật: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Lỗi khi gửi dữ liệu:', error);
                        alert('Lỗi khi gửi dữ liệu. Vui lòng thử lại sau.');
                    }
                });
            });

            // Thêm chức năng tìm kiếm
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const rows = document.getElementById('landTableBody').getElementsByTagName('tr');
                
                Array.from(rows).forEach(row => {
                    const tenchusdd = row.cells[1].textContent.toLowerCase();
                    const shbando = row.cells[2].textContent.toLowerCase();
                    const shthua = row.cells[3].textContent.toLowerCase();
                    const dientich = row.cells[4].textContent.toLowerCase();
                    const gia_dat = row.cells[5].textContent.toLowerCase();
                    const diachi = row.cells[6].textContent.toLowerCase();
                    
                    if (tenchusdd.includes(searchValue) || 
                        shbando.includes(searchValue) || 
                        shthua.includes(searchValue) || 
                        dientich.includes(searchValue) || 
                        gia_dat.includes(searchValue) || 
                        diachi.includes(searchValue)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
            
        } catch (error) {
            console.error("Lỗi khi lấy dữ liệu thửa đất:", error);
            alert("Không thể lấy dữ liệu thửa đất. Vui lòng thử lại sau.");
        }
    }

    // Gọi hàm fetchLandData khi mở bảng "Quản trị Thửa Đất"
    document.querySelector("button[onclick='toggleLandTable()']").addEventListener("click", fetchLandData);

    // Hàm hiển thị Biểu đồ thống kê
    function toggleStatsChart() {
        const container = document.getElementById("statsChartContainer");
        container.style.display = container.style.display === "none" ? "block" : "none";
        
        if (container.style.display === "block" && !window.mdsdChartInstance) {
            initMdsdChart();
        }
    }

    // Khởi tạo biểu đồ thống kê
    function initMdsdChart() {
        <?php if (!empty($statData)): ?>
        // Lấy dữ liệu từ PHP
        const chartData = <?php echo json_encode($statData); ?>;
        
        // Chuẩn bị dữ liệu cho biểu đồ
        const labels = chartData.map(item => item.mdsd ? item.mdsd : 'Không xác định');
        const values = chartData.map(item => parseInt(item.count));
        
        // Kiểm tra chênh lệch giữa giá trị lớn nhất và nhỏ nhất
        const maxValue = Math.max(...values);
        const minValue = Math.min(...values);
        const valueRatio = maxValue / minValue;
        
        // Quyết định sử dụng thang logarit nếu tỷ lệ chênh lệch quá lớn
        const useLogScale = valueRatio > 10;
        
        // Tạo mảng màu ngẫu nhiên cho biểu đồ
        const backgroundColors = generateColors(labels.length);
        
        // Sắp xếp dữ liệu theo thứ tự giảm dần để dễ xem
        const combinedData = labels.map((label, index) => ({
            label,
            value: values[index],
            color: backgroundColors[index]
        }));
        
        combinedData.sort((a, b) => b.value - a.value);
        
        const sortedLabels = combinedData.map(item => item.label);
        const sortedValues = combinedData.map(item => item.value);
        const sortedColors = combinedData.map(item => item.color);
        
        // Tạo biểu đồ
        const ctx = document.getElementById('mdsdChart').getContext('2d');
        window.mdsdChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedLabels,
                datasets: [{
                    label: 'Số lượng thửa đất',
                    data: sortedValues,
                    backgroundColor: sortedColors,
                    borderColor: sortedColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Phân bố mục đích sử dụng đất',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    x: {
                        type: useLogScale ? 'logarithmic' : 'linear',
                        title: {
                            display: true,
                            text: 'Số lượng thửa đất' + (useLogScale ? ' (thang logarit)' : '')
                        },
                        ticks: {
                            callback: function(value) {
                                if (value === 0) return '0';
                                
                                // Định dạng số lớn với phân cách hàng nghìn
                                return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Mục đích sử dụng đất'
                        }
                    }
                }
            }
        });
        
        // Thêm chú thích về thang logarit nếu được sử dụng
        if (useLogScale) {
            document.querySelector('.chart-container').insertAdjacentHTML('afterbegin', 
                '<div class="alert alert-info" style="margin-bottom: 15px; font-size: 14px;">' +
                'Biểu đồ sử dụng thang logarit để hiển thị dữ liệu có sự chênh lệch lớn giữa các giá trị.' +
                '</div>'
            );
        }
        <?php endif; ?>
    }

    // Hàm tạo mảng màu ngẫu nhiên
    function generateColors(count) {
        const colors = [
            '#4e79a7', '#f28e2c', '#e15759', '#76b7b2', '#59a14f',
            '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab',
            '#d37295', '#a6cee3', '#1f78b4', '#b2df8a', '#33a02c',
            '#fb9a99', '#e31a1c', '#fdbf6f', '#ff7f00', '#cab2d6'
        ];
        
        // Nếu có nhiều nhóm hơn số màu có sẵn, tạo thêm màu ngẫu nhiên
        if (count > colors.length) {
            for (let i = colors.length; i < count; i++) {
                const r = Math.floor(Math.random() * 255);
                const g = Math.floor(Math.random() * 255);
                const b = Math.floor(Math.random() * 255);
                colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
            }
        }
        
        return colors.slice(0, count);
    }
</script>

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

.search-bar {
    margin-bottom: 15px;
}

.form-control {
    display: block;
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ced4da;
    border-radius: 4px;
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

.btn {
    background-color: #205375;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 16px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn:hover {
    background-color: #153b56;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 14px;
}

.btn-primary {
    background-color: #007bff;
}

.btn-success {
    background-color: #28a745;
}

.btn-danger {
    background-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
}

.chart-container {
    height: 400px;
    position: relative;
    margin-bottom: 20px;
}

.stats-table-container {
    margin-top: 20px;
}

.stats-table-container h3 {
    margin-bottom: 15px;
    color: #205375;
    font-size: 16px;
    font-weight: 600;
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

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.mt-4 {
    margin-top: 20px;
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
    
    .chart-container {
        height: 300px;
    }
}

/* Định dạng cho thông báo chưa đọc */
.unread-row {
    background-color: #f8f9fa !important;
    font-weight: 500;
}

.unread-row td {
    border-left: 3px solid #0d6efd;
}

/* Làm cho table có thể cuộn ngang trên thiết bị nhỏ */
.scrollable-table {
    overflow-x: auto;
}

/* Cải thiện khoảng cách giữa các nút */
td .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}

/* Thêm các style khác nếu cần thiết */
</style>

<?php include('admin_footer.php'); ?> 