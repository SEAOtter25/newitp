<?php
ini_set('display_errors', 1); // เปิดการแสดงผลข้อผิดพลาด
ini_set('display_startup_errors', 1); // เปิดการแสดงผลข้อผิดพลาดตอนเริ่มต้น
error_reporting(E_ALL);
// กำหนด HTTP Header เพื่อบอกเบราว์เซอร์ว่าไฟล์นี้จะส่งข้อมูลแบบ JSON
header('Content-Type: application/json');
// อนุญาตให้โดเมนอื่น (หรือ localhost ของคุณ) เข้าถึงไฟล์นี้ได้ (สำคัญสำหรับการพัฒนา)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
// ตรวจสอบเส้นทางให้ถูกต้อง! ถ้า connect.php อยู่ในโฟลเดอร์เดียวกับ api ให้ใช้ 'connect.php'
require_once '../connect.php'; 

// เตรียมตัวแปรสำหรับเก็บผลลัพธ์ที่จะส่งกลับไป
$response = [
    'success' => false,
    'message' => 'เกิดข้อผิดพลาดบางอย่าง',
    'data' => []
];

try {
    // เตรียมคำสั่ง SQL เพื่อเลือกข้อมูลสินทรัพย์ทั้งหมดที่ยังไม่ได้ถูกลบ
    // เราเลือกเฉพาะคอลัมน์ที่จำเป็นตามโครงสร้างตาราง Assets ล่าสุดของคุณ
    $sql = "SELECT asset_id, asset_tag, serial_number, type, brand, model, status, owner, location, purchase_date, warranty_end_date, notes FROM Assets WHERE IS_DELETED = 0 ORDER BY asset_tag ASC";
    
    // เตรียม Statement (ป้องกัน SQL Injection)
    $stmt = $conn->prepare($sql);
    
    // รันคำสั่ง SQL
    $stmt->execute();
    
    // รับผลลัพธ์
    $result = $stmt->get_result();

    $assets = [];
    // วนลูปอ่านข้อมูลแต่ละแถว และเพิ่มลงใน array $assets
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }

    // กำหนดให้การเรียก API สำเร็จ และใส่ข้อมูลลงใน 'data'
    $response['success'] = true;
    $response['message'] = 'ดึงข้อมูลสินทรัพย์สำเร็จแล้ว';
    $response['data'] = $assets;

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูลหรือการ Query
    $response['message'] = 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage();
    // (สำหรับ Debugging) บันทึกข้อผิดพลาดใน Log
    error_log('เกิดข้อผิดพลาดในการดึงข้อมูลสินทรัพย์: ' . $e->getMessage());
} finally {
    // ปิดการเชื่อมต่อฐานข้อมูลเสมอ
    if ($conn) {
        $conn->close();
    }
}

// แปลง array $response ให้เป็น JSON string แล้วส่งออกไป
echo json_encode($response);
?>