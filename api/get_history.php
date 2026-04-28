<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");
$stmt = $conn->prepare("SELECT * FROM borrow_history ORDER BY action_date DESC LIMIT 100");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>