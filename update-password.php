<?php
// update-password.php
// รับ token และรหัสผ่านใหม่จาก reset-password.php แล้วอัปเดตในฐานข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "finance_web");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!$token || strlen($password) < 6) {
        header("Location: reset-password.php?token=$token");
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (strtotime($row['expires_at']) < time()) {
            header("Location: reset-password.php?token=$token");
            exit();
        }
        $user_id = $row['user_id'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt2->bind_param("si", $hash, $user_id);
        $stmt2->execute();
        // ลบ token ทิ้ง
        $stmt3 = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt3->bind_param("s", $token);
        $stmt3->execute();
        header("Location: reset-password.php?token=$token&success=1");
        exit();
    } else {
        header("Location: reset-password.php?token=$token");
        exit();
    }
}
header("Location: login.html");
exit();
