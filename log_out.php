<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
	<style> 
	progress#progressBar {
    width: 100%; /* Chiều rộng của thanh progress */
    height: 30px; /* Chiều cao của thanh progress */
    border-radius: 5px; /* Làm tròn góc */
    background-color: #f3f3f3; /* Màu nền */
}

progress#progressBar::-webkit-progress-bar {
    background-color: #d56d13; /* Màu nền cho thanh progress */
    border-radius: 5px;
}

progress#progressBar::-webkit-progress-value {
    background-color: #dfff00de; /* Màu phần tiến độ */
    border-radius: 5px;
}

progress#progressBar::-moz-progress-bar {
    background-color: #4caf50; /* Màu phần tiến độ cho Firefox */
    border-radius: 5px;
}</style>
    <script>
        // Function to dynamically update the progress bar
        function updateProgress() {
            let progressBar = document.getElementById('progressBar');
            let value = parseInt(progressBar.value);
            
            let interval = setInterval(() => {
                if (value < 100) {
                    value += 1; // Increment the value
                    progressBar.value = value;
                } else {
                    clearInterval(interval); // Stop the interval when value reaches 100
                }
            }, 25); // Update every 25ms (adjust to control speed)
        }
    </script>
</head>
<body onload="updateProgress()">
    <div style="width:500px; margin:auto; height:500px; margin-top:250px; font-size: xx-large;">
	
        <?php
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Lấy username nếu có
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Khách';
            
            // Xóa tất cả dữ liệu session
            session_destroy();
            
            // Bắt đầu session mới để hiển thị thông báo
            session_start();
            $_SESSION['login_info'] = 'Bạn đã đăng xuất thành công';
            
            echo '<meta http-equiv="refresh" content="1.5;url=index.php">';
            echo '<br><span class="itext">Xin chào ' . htmlspecialchars($username) . ', hẹn gặp lại</span>';
            echo '<progress id="progressBar" max="100" value="60"></progress><br>';
        ?>
	
	</div>
</body>
</html>
