<?php
// reset-password.php
// หน้านี้ให้ผู้ใช้กรอกรหัสผ่านใหม่ โดยตรวจสอบ token
$conn = new mysqli("localhost", "root", "", "finance_web");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$token = $_GET['token'] ?? '';
if (!$token) { echo "ไม่พบลิงก์รีเซ็ตที่ถูกต้อง"; exit(); }

$stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if (strtotime($row['expires_at']) < time()) {
        echo "ลิงก์หมดอายุ กรุณาขอใหม่"; exit();
    }
    $user_id = $row['user_id'];
} else {
    echo "ลิงก์ไม่ถูกต้อง กรุณาขอใหม่"; exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>รีเซ็ตรหัสผ่านใหม่ - Finance Web</title>
  <link rel="stylesheet" href="login.css">
  <style>
    body { background: #f7fafb; font-family: sans-serif; }
    .reset-container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 16px; box-shadow: 0 6px 24px rgba(0,0,0,.08); padding: 32px; }
    h2 { color: #10b981; margin-bottom: 18px; }
    label { display: block; margin-bottom: 8px; }
    input[type=password] { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 16px; }
    button { background: #10b981; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 1rem; cursor: pointer; }
    .msg { color: red; margin-bottom: 12px; }
    .back-link { display: block; margin-top: 18px; color: #10b981; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="reset-container">
    <h2>ตั้งรหัสผ่านใหม่</h2>
    <?php if (isset($_GET['success'])): ?>
      <div class="msg" style="color:green;">รีเซ็ตรหัสผ่านเรียบร้อยแล้ว! <a href="login.html">เข้าสู่ระบบ</a></div>
    <?php else: ?>
      <form method="POST" action="update-password.php">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label for="password">รหัสผ่านใหม่</label>
        <input type="password" name="password" id="password" required minlength="6">
        <button type="submit">บันทึกรหัสผ่านใหม่</button>
      </form>
    <?php endif; ?>
    <a href="login.html" class="back-link">กลับเข้าสู่ระบบ</a>
  </div>
</body>
</html>
