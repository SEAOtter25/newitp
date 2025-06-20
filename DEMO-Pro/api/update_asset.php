<?php
// เปิดการแสดงข้อผิดพลาดสำหรับ Debug (ควรปิดในการใช้งานจริงบน Production Server)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// กำหนดให้ PHP บันทึก Error Log ไปที่ไฟล์เฉพาะ (สำหรับ Debug ชั่วคราว)
// บันทึกในโฟลเดอร์ DEMO-Pro/php_error_log.txt
ini_set('log_errors', 1); // เปิดการบันทึก log
ini_set('error_log', __DIR__ . '/../php_error_log.txt'); 

header('Content-Type: application/json'); // กำหนด Content-Type ของ Response เป็น JSON

require_once '../connect.php'; // เชื่อมต่อฐานข้อมูล

$response = [
    'success' => false,
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = ''; // กำหนดค่าเริ่มต้นของ $input
    $data = [];  // กำหนดค่าเริ่มต้นของ $data

    // *** วิธีการรับข้อมูลใหม่: ลองใช้ $_POST ก่อน ***
    if (!empty($_POST)) {
        // ถ้า $_POST มีข้อมูล (อาจจะเกิดจาก Content-Type ผิด หรือ Server ประมวลผลให้)
        $data = $_POST;
        error_log("Attempt Method 1: Using \$_POST data.");
        // เราจะไม่ json_decode เพราะมันควรจะเป็น array/key-value pair อยู่แล้ว
    } else {
        // ถ้า $_POST ไม่มีข้อมูล (กรณีมาตรฐานสำหรับ application/json)
        // ลองใช้ file_get_contents('php://input') (ซึ่งเราเจอว่ามันมีปัญหาที่นี่)
        $input = file_get_contents('php://input');
        error_log("Attempt Method 2: Using file_get_contents('php://input').");
        error_log("Raw input from Method 2: " . (is_bool($input) ? ($input ? 'true' : 'false') : $input)); // Log raw input

        // พยายาม decode input เป็น JSON array
        $data = json_decode($input, true); 

        // ตรวจสอบ JSON decode error หากใช้ Method 2
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error for Method 2: " . json_last_error_msg());
            $data = []; // เคลียร์ข้อมูลถ้า decode ล้มเหลว
        }
    }

    // *** ตรวจสอบผลลัพธ์การ Decode ***
    // ตรวจสอบว่า $data ว่างเปล่า หรือไม่ใช่ array
    if (empty($data) || !is_array($data)) {
        error_log("Final check: Data is empty or not an array after all attempts. This often means JSON was not received correctly.");
        error_log("Decoded data (print_r at final check): " . print_r($data, true));

        // ถ้ายังไม่มีข้อมูล หรือ decode ไม่สำเร็จ ให้แจ้งข้อผิดพลาดและหยุดการทำงาน
        $response['message'] = 'ไม่ได้รับข้อมูล หรือข้อมูลอยู่ในรูปแบบที่ไม่ถูกต้อง (ไม่ใช่ JSON Array).';
        echo json_encode($response);
        $conn->close();
        exit(); // หยุดการทำงานทันที
    }
    // **************************************************************************

    error_log("Final decoded data (after all checks): " . print_r($data, true)); // Log ข้อมูลที่ใช้จริง หลังจากตรวจสอบ

    // ... (ส่วนที่เหลือของโค้ดตรวจสอบ required_fields, ดึงข้อมูลจาก $data, เตรียม SQL, และ execute เหมือนเดิม) ...

    $required_fields = [
        'asset_id', 'asset_tag', 'serial_number', 'type', 'brand', 'model', 'status',
        'owner', 'location', 'purchase_date', 'warranty_end_date', 'notes'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วนสำหรับการอัปเดตสินทรัพย์. Fields missing: ' . implode(', ', $missing_fields);
        error_log("Missing fields in data: " . implode(', ', $missing_fields));
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $asset_id = $data['asset_id'];
    $asset_tag = $data['asset_tag'];
    $serial_number = $data['serial_number'];
    $type = $data['type'];
    $brand = $data['brand'];
    $model = $data['model'];
    $status = $data['status'];
    $owner = empty($data['owner']) ? null : $data['owner'];
    $location = $data['location'];
    $purchase_date = $data['purchase_date'];
    $warranty_end_date = empty($data['warranty_end_date']) ? null : $data['warranty_end_date'];
    $notes = empty($data['notes']) ? null : $data['notes'];

    error_log("Values before SQL query:");
    error_log("asset_id: " . $asset_id);
    error_log("asset_tag: " . $asset_tag);
    error_log("serial_number: " . $serial_number);
    error_log("type: " . $type);
    error_log("brand: " . $brand);
    error_log("model: " . $model);
    error_log("status: " . $status);
    error_log("owner: " . ($owner ?? 'NULL')); 
    error_log("location: " . $location);
    error_log("purchase_date: " . $purchase_date);
    error_log("warranty_end_date: " . ($warranty_end_date ?? 'NULL'));
    error_log("notes: " . ($notes ?? 'NULL'));

    $sql = "UPDATE assets SET 
                asset_tag = ?, 
                serial_number = ?, 
                type = ?, 
                brand = ?, 
                model = ?, 
                status = ?, 
                owner = ?, 
                location = ?, 
                purchase_date = ?, 
                warranty_end_date = ?, 
                notes = ? 
            WHERE asset_id = ?";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param(
            "ssssssissssi", 
            $asset_tag, $serial_number, $type, $brand, $model, $status,
            $owner, $location, $purchase_date, $warranty_end_date, $notes,
            $asset_id
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'อัปเดตข้อมูลสินทรัพย์สำเร็จแล้ว';
        } else {
            $response['message'] = 'ไม่สามารถอัปเดตข้อมูลสินทรัพย์ได้: ' . $stmt->error;
            error_log("SQL Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'ไม่สามารถเตรียมคำสั่ง SQL ได้: ' . $conn->error;
        error_log("Prepare statement failed: " . $conn->error);
    }

} else {
    $response['message'] = 'Method ไม่ถูกต้อง. ต้องเป็น POST request.';
}

$conn->close();
echo json_encode($response);
?>