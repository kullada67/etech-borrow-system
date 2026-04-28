<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");
$stmt = $conn->prepare("SELECT * FROM equipment");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>