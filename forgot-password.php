<?php
// forgot-password.php
// หน้านี้ให้ผู้ใช้กรอกอีเมลเพื่อขอรีเซ็ตรหัสผ่าน
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ลืมรหัสผ่าน - Finance Web</title>
  <link rel="stylesheet" href="login.css">
  <style>
    body { background: #f7fafb; font-family: sans-serif; }
    .forgot-container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 16px; box-shadow: 0 6px 24px rgba(0,0,0,.08); padding: 32px; }
    h2 { color: #10b981; margin-bottom: 18px; }
    label { display: block; margin-bottom: 8px; }
    input[type=email] { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 16px; }
    button { background: #10b981; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 1rem; cursor: pointer; }
    .msg { color: red; margin-bottom: 12px; }
    .back-link { display: block; margin-top: 18px; color: #10b981; text-decoration: underline; }
  </style>
</head>
<body>
  <!-- Language Switch -->
  <div class="language-switch" style="position: absolute; top: 20px; left: 30px; z-index: 99;">
    <select id="langSelect" onchange="switchLang()" style="padding: 6px 12px; border-radius: 20px; border: 1px solid #ccc; color: #10b981;">
      <option value="th">ไทย</option>
      <option value="en">English</option>
    </select>
  </div>
  <div class="forgot-container">
    <h2 id="forgot-title">ลืมรหัสผ่าน</h2>
    <?php if (isset($_GET['sent'])): ?>
      <div class="msg" id="msg-success" style="color:green;">ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลแล้ว กรุณาตรวจสอบอีเมลของคุณ</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="msg" id="msg-error">ไม่พบอีเมลนี้ในระบบ</div>
    <?php endif; ?>
    <form method="POST" action="send-reset-link.php">
      <label for="email" id="forgot-label">กรอกอีเมลที่ใช้สมัครสมาชิก</label>
      <input type="email" name="email" id="email" required autofocus>
      <button type="submit" id="forgot-btn">ขอลิงก์รีเซ็ตรหัสผ่าน</button>
    </form>
    <a href="login.html" class="back-link" id="forgot-back">กลับเข้าสู่ระบบ</a>
  </div>
  <script>
    const texts = {
      th: {
        title: "ลืมรหัสผ่าน",
        label: "กรอกอีเมลที่ใช้สมัครสมาชิก",
        btn: "ขอลิงก์รีเซ็ตรหัสผ่าน",
        back: "กลับเข้าสู่ระบบ",
        success: "ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลแล้ว กรุณาตรวจสอบอีเมลของคุณ",
        error: "ไม่พบอีเมลนี้ในระบบ"
      },
      en: {
        title: "Forgot Password",
        label: "Enter your registered email",
        btn: "Request password reset link",
        back: "Back to Login",
        success: "Password reset link sent! Please check your email.",
        error: "Email not found in our system."
      }
    };
    function switchLang() {
      const lang = document.getElementById("langSelect").value;
      localStorage.setItem("lang", lang);
      const t = texts[lang];
      document.getElementById("forgot-title").innerText = t.title;
      document.getElementById("forgot-label").innerText = t.label;
      document.getElementById("forgot-btn").innerText = t.btn;
      document.getElementById("forgot-back").innerText = t.back;
      if (document.getElementById("msg-success")) document.getElementById("msg-success").innerText = t.success;
      if (document.getElementById("msg-error")) document.getElementById("msg-error").innerText = t.error;
    }
    document.addEventListener("DOMContentLoaded", () => {
      const lang = localStorage.getItem("lang") || "th";
      document.getElementById("langSelect").value = lang;
      switchLang();
    });
  </script>
</body>
</html>
