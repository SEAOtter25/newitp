<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// กำหนด Content-Type เป็น application/json เพื่อให้เบราว์เซอร์รู้ว่านี่คือข้อมูล JSON
header('Content-Type: application/json');

// อนุญาตให้ JavaScript จาก Domain อื่นเข้าถึงได้ (CORS)
// ใน Production ควรระบุ Domain ที่แน่นอนแทน * เพื่อความปลอดภัย
header('Access-Control-Allow-Origin: *'); // สำหรับการทดสอบใน localhost
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ตรวจสอบว่าเป็น HTTP POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ดึงข้อมูล JSON ที่ส่งมาจาก Frontend (JavaScript fetch API)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    // ตรวจสอบว่ามีข้อมูล Username และ Password หรือไม่
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password cannot be empty.']);
        exit();
    }

    // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
    include_once 'connect.php'; // ตรวจสอบเส้นทางให้ถูกต้อง

    // ป้องกัน SQL Injection โดยใช้ Prepared Statements
    $sql = "SELECT user_id, emp_id, username, password_hash, role FROM Users WHERE username = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_username);
        $param_username = $username;

        // ดำเนินการ query
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            // ตรวจสอบว่าพบผู้ใช้งานหรือไม่
            if (mysqli_stmt_num_rows($stmt) == 1) {
                // ผูกผลลัพธ์เข้ากับตัวแปร
                mysqli_stmt_bind_result($stmt, $user_id, $emp_id, $db_username, $password_hash, $role);

                // ดึงข้อมูล
                if (mysqli_stmt_fetch($stmt)) {
                    // ตรวจสอบรหัสผ่านที่ป้อนเข้ามากับรหัสผ่านที่ Hashed ในฐานข้อมูล
                    if (password_verify($password, $password_hash)) {
                        // Login สำเร็จ
                        // อัปเดต last_login ในฐานข้อมูล
                        $update_sql = "UPDATE Users SET last_login = NOW() WHERE user_id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "i", $user_id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }

                        // ส่งข้อมูลผู้ใช้กลับไปยัง Frontend (ไม่ควรส่ง password_hash กลับไป)
                        echo json_encode([
                            'success' => true,
                            'message' => 'Login successful.',
                            'user' => [
                                'user_id' => $user_id,
                                'emp_id' => $emp_id,
                                'username' => $db_username,
                                'role' => $role
                            ]
                        ]);
                    } else {
                        // รหัสผ่านไม่ถูกต้อง
                        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
                    }
                }
            } else {
                // ไม่พบชื่อผู้ใช้งาน
                echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            }
        } else {
            // เกิดข้อผิดพลาดในการรัน query
            echo json_encode(['success' => false, 'message' => 'Oops! Something went wrong. Please try again later.']);
        }

        // ปิด statement
        mysqli_stmt_close($stmt);
    } else {
        // เกิดข้อผิดพลาดในการเตรียม statement
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
    }

    // ปิดการเชื่อมต่อฐานข้อมูล
    mysqli_close($conn);
} else {
    // ไม่ใช่ POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
