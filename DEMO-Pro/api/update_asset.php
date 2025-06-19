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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input'); // อ่าน raw POST body
    $data = json_decode($input, true); // พยายาม decode เป็น JSON array

    // *** ส่วนที่เพิ่ม/แก้ไข เพื่อจัดการกรณี php://input ว่างเปล่า หรือ decode ไม่ได้ ***
    // ตรวจสอบว่า json_decode ล้มเหลว หรือ $data ว่างเปล่า/ไม่ใช่ array
    if (json_last_error() !== JSON_ERROR_NONE || empty($data) || !is_array($data)) {
        error_log("Attempting to get data from php://input failed or resulted in empty/invalid data.");
        error_log("Raw JSON input (from php://input): " . $input); // แสดง input ที่ได้รับมาจริงๆ
        error_log("JSON Decode Error: " . json_last_error_msg()); // แสดง error ของ json_decode
        error_log("Decoded data (from php://input): " . print_r($data, true)); // แสดงผลลัพธ์การ decode

        // ถ้ายังไม่มีข้อมูล หรือ decode ไม่สำเร็จ ให้แจ้งข้อผิดพลาดและหยุดการทำงาน
        $response['message'] = 'ไม่ได้รับข้อมูล หรือข้อมูลอยู่ในรูปแบบที่ไม่ถูกต้อง (ไม่ใช่ JSON Array).';
        echo json_encode($response);
        $conn->close();
        exit(); // หยุดการทำงานทันที
    }
    // **************************************************************************

    error_log("Final decoded data after all checks: " . print_r($data, true)); // Log ข้อมูลที่ใช้จริง หลังจากตรวจสอบ

    // ตรวจสอบว่าข้อมูลที่จำเป็นมีอยู่ครบหรือไม่ (รายการ Fields Missing ที่ SweetAlert2 แจ้งก่อนหน้านี้)
    $required_fields = [
        'asset_id', 'asset_tag', 'serial_number', 'type', 'brand', 'model', 'status',
        'owner', 'location', 'purchase_date', 'warranty_end_date', 'notes'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field) {
        // ตรวจสอบว่า key มีอยู่จริง
        if (!isset($data[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วนสำหรับการอัปเดตสินทรัพย์. Fields missing: ' . implode(', ', $missing_fields);
        error_log("Missing fields in data: " . implode(', ', $missing_fields)); // Log fields ที่ขาด
        echo json_encode($response);
        $conn->close();
        exit();
    }

    // ดึงข้อมูลจาก $data (เมื่อแน่ใจว่ามีครบถ้วน)
    $asset_id = $data['asset_id'];
    $asset_tag = $data['asset_tag'];
    $serial_number = $data['serial_number'];
    $type = $data['type'];
    $brand = $data['brand'];
    $model = $data['model'];
    $status = $data['status'];
    $owner = empty($data['owner']) ? null : $data['owner']; // แปลง string ว่างเป็น null
    $location = $data['location'];
    $purchase_date = $data['purchase_date'];
    $warranty_end_date = empty($data['warranty_end_date']) ? null : $data['warranty_end_date']; // แปลง string ว่างเป็น null
    $notes = empty($data['notes']) ? null : $data['notes']; // แปลง string ว่างเป็น null

    // *** ตรวจสอบค่าที่ถูกดึงมาอีกครั้งก่อน Query (เพื่อ Debug เพิ่มเติมหาก SQL มีปัญหา) ***
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
    // ********************************************************************************

    // เตรียมคำสั่ง SQL สำหรับอัปเดตข้อมูล
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
        // "ssssssissssi" => s = string, i = integer
        // asset_tag, serial_number, type, brand, model, status, owner, location, purchase_date, warranty_end_date, notes, asset_id
        // ใช้ "s" สำหรับ owner, warranty_end_date, notes เพราะ bind_param จะจัดการ null ได้ถ้าเป็น string type (ถ้าเป็น PHP 7.1+ และ PDO/MySQLi ใช้ prepare/execute)
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
            error_log("SQL Error: " . $stmt->error); // Log SQL error
        }
        $stmt->close();
    } else {
        $response['message'] = 'ไม่สามารถเตรียมคำสั่ง SQL ได้: ' . $conn->error;
        error_log("Prepare statement failed: " . $conn->error); // Log prepare error
    }

} else {
    $response['message'] = 'Method ไม่ถูกต้อง. ต้องเป็น POST request.';
}

$conn->close();
echo json_encode($response);
?>