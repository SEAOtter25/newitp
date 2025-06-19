<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
define('DB_SERVER', 'localhost'); // หรือ IP Address ของเซิร์ฟเวอร์ฐานข้อมูล
define('DB_USERNAME', 'root');    // ชื่อผู้ใช้ฐานข้อมูล XAMPP เริ่มต้นคือ 'root'
define('DB_PASSWORD', '');        // รหัสผ่านฐานข้อมูล XAMPP เริ่มต้นคือว่างเปล่า
define('DB_NAME', 'itp_Project'); // ชื่อฐานข้อมูลที่เราได้สร้างไว้

// สร้างการเชื่อมต่อฐานข้อมูล
// ใช้ mysqli_connect สำหรับการเชื่อมต่อแบบ Procedural (เหมาะสำหรับมือใหม่)
// หรือจะใช้ new mysqli() สำหรับ Object-Oriented ก็ได้
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// ตรวจสอบการเชื่อมต่อ
if ($conn === false) {
    // หากเชื่อมต่อไม่ได้ ให้แสดงข้อความผิดพลาดและหยุดการทำงาน
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// กำหนดชุดตัวอักษรเป็น UTF-8 เพื่อรองรับภาษาไทย
mysqli_set_charset($conn, "utf8");

// สามารถใส่ข้อความยืนยันการเชื่อมต่อ (สำหรับ debugging เท่านั้น)
// echo "Database connected successfully!";

// เมื่อใช้งานไฟล์นี้ในส่วนอื่นๆ ของโปรเจกต์ (เช่น login.php, dashboard.php)
// ให้ใช้ include_once 'connect.php'; เพื่อเรียกใช้ตัวแปร $conn
// และหลังจากใช้งานเสร็จ ควรเรียก mysqli_close($conn); เพื่อปิดการเชื่อมต่อ
// หรือถ้าเป็นไฟล์ที่ทำงานเสร็จแล้วจบ ก็ไม่ต้องปิดก็ได้ PHP จะจัดการให้เอง

?>