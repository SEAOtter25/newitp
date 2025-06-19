<?php
$password_to_check = 'userpass456'; // เปลี่ยนเป็นรหัสผ่านที่คุณต้องการทดสอบ
$hashed_password_from_db = 'ค่า hash ที่คุณคัดลอกจาก phpMyAdmin มา'; // คัดลอกจากฐานข้อมูลมาวางตรงนี้

if (password_verify($password_to_check, $hashed_password_from_db)) {
    echo "Password matches!";
} else {
    echo "Password does NOT match.";
}

// หากต้องการสร้าง hash ใหม่สำหรับรหัสผ่าน
echo "<br>New hash for 'userpass456': " . password_hash('userpass456', PASSWORD_DEFAULT);
?>