<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$data = json_decode(file_get_contents("php://input"));

// เช็คว่าส่งข้อมูลมาครบไหม
if (!empty($data->username) && !empty($data->password) && !empty($data->fullname)) {
    $conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");

    // 1. เช็คก่อนว่ามี Username นี้ในระบบหรือยัง
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$data->username]);
    
    if ($check->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Username นี้มีผู้ใช้งานแล้ว กรุณาใช้ชื่ออื่นครับ"]);
        exit;
    }

    // 2. ถ้ายังไม่มี ให้เพิ่มผู้ใช้ใหม่ลงไป (กำหนด role เป็น ta)
    $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'ta')");
    $stmt->execute([$data->username, $data->password, $data->fullname]);

    echo json_encode(["status" => "success", "message" => "ลงทะเบียนสำเร็จ! เข้าสู่ระบบได้เลย"]);
} else {
    echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบทุกช่องครับ"]);
}
?>