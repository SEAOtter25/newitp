<?php
// เปิดการแสดงข้อผิดพลาดสำหรับ Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// กำหนดให้ PHP บันทึก Error Log ไปที่ไฟล์เฉพาะ
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error_log.txt');

header('Content-Type: application/json');

$input = file_get_contents('php://input'); // อ่าน raw POST body
$data = json_decode($input, true); // พยายาม decode เป็น JSON array

error_log("--- test_post_input.php START ---");
error_log("Raw input from test_post_input.php: " . (is_bool($input) ? ($input ? 'true' : 'false') : $input));
error_log("JSON Decode Error from test_post_input.php: " . json_last_error_msg());
error_log("Decoded data from test_post_input.php: " . print_r($data, true));
error_log("--- test_post_input.php END ---");

$response = [
    'success' => true,
    'message' => 'Test script executed.',
    'raw_input_type' => gettype($input),
    'raw_input_value' => (is_bool($input) ? ($input ? 'true' : 'false') : $input),
    'decoded_data' => $data,
    'json_error' => json_last_error_msg()
];

echo json_encode($response);
?>