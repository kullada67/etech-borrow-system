<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$data->username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(["status" => "success", "user" => ["fullname" => $row['fullname'], "role" => $row['role']]]);
    } else {
        echo json_encode(["status" => "error", "message" => "ชื่อผู้ใช้ผิดพลาด"]);
    }
}
?>