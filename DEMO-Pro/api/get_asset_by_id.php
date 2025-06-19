<?php
// เปิดการแสดงข้อผิดพลาดสำหรับ Debug (ควรปิดในการใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../connect.php'; // เชื่อมต่อฐานข้อมูล

$response = [
    'success' => false,
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'
];

// ตรวจสอบว่ามีการส่ง asset_id มาหรือไม่
if (isset($_GET['asset_id'])) {
    $asset_id = $_GET['asset_id'];

    // เตรียมคำสั่ง SQL เพื่อดึงข้อมูลสินทรัพย์เดียว
    $sql = "SELECT * FROM assets WHERE asset_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $asset_id); // "i" สำหรับ integer
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $asset = $result->fetch_assoc();
            $response['success'] = true;
            $response['message'] = 'ดึงข้อมูลสินทรัพย์สำเร็จแล้ว';
            $response['data'] = $asset;
        } else {
            $response['message'] = 'ไม่พบสินทรัพย์ด้วย Asset ID ที่ระบุ';
        }
        $stmt->close();
    } else {
        $response['message'] = 'ไม่สามารถเตรียมคำสั่ง SQL ได้: ' . $conn->error;
    }
} else {
    $response['message'] = 'ไม่พบ Asset ID ที่ต้องการดึงข้อมูล';
}

$conn->close();
echo json_encode($response);
?>