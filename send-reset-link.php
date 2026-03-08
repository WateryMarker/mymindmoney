<?php
// send-reset-link.php
// รับอีเมลจากฟอร์ม forgot-password.php แล้วส่งลิงก์รีเซ็ตไปยังอีเมล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "finance_web");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        header("Location: forgot-password.php?error=1");
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        // สร้าง token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 ชั่วโมง
        // บันทึก token ลง db
        $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $user['id'], $token, $expires);
        $stmt2->execute();
        // ส่งอีเมล
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset-password.php?token=$token";
        $subject = "รีเซ็ตรหัสผ่าน - Finance Web";
        $message = "<p>คลิกที่ลิงก์นี้เพื่อรีเซ็ตรหัสผ่านของคุณ:<br><a href='$resetLink'>$resetLink</a></p>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: finance-web@" . $_SERVER['HTTP_HOST'] . "\r\n";
        mail($email, $subject, $message, $headers);
        header("Location: forgot-password.php?sent=1");
        exit();
    } else {
        header("Location: forgot-password.php?error=1");
        exit();
    }
}
// ถ้าเข้าโดยตรง
header("Location: forgot-password.php");
exit();
