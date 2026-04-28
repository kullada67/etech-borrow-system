<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$data = json_decode(file_get_contents("php://input"));
if (empty($data->role) || $data->role !== 'admin') { echo json_encode(["status" => "error", "message" => "ปฏิเสธสิทธิ์"]); exit; }

$conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");
if (!empty($data->id)) {
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$data->id]);
    echo json_encode(["status" => "success"]);
}
?>