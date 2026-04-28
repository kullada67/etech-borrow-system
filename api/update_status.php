<?php
// 1. ตั้งค่า Header สำหรับ CORS ให้ Vue คุยกับ PHP ได้
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// ดักจับ Preflight Request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    http_response_code(200);
    exit; 
}

// รับข้อมูล JSON จากหน้าเว็บ Vue
$data = json_decode(file_get_contents("php://input"));

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new PDO("mysql:host=localhost;dbname=etech_borrow;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

if (!empty($data->id) && !empty($data->status)) {
    $due_date = null;
    $overdue_reason = isset($data->overdue_reason) ? $data->overdue_reason : null;
    
    // ดึงชื่ออุปกรณ์มาเตรียมไว้สำหรับส่ง LINE
    $stmt_name = $conn->prepare("SELECT name FROM equipment WHERE id = ?");
    $stmt_name->execute([$data->id]);
    $item_row = $stmt_name->fetch(PDO::FETCH_ASSOC);
    $item_name = $item_row ? $item_row['name'] : 'ไม่ทราบชื่ออุปกรณ์';

    // ถ้าเป็นการ "ยืม" (borrowed) ให้คำนวณวันกำหนดคืนล่วงหน้า 7 วัน
    if ($data->status == 'borrowed') {
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    // 2. อัปเดตสถานะและวันกำหนดคืนในตาราง equipment
    $stmt = $conn->prepare("UPDATE equipment SET status = ?, due_date = ? WHERE id = ?");
    $stmt->execute([$data->status, $due_date, $data->id]);
    
    // 3. รับค่าข้อมูลผู้ยืม (ถ้าเป็นการคืนของ ค่าพวกนี้จะเป็น null)
    $b_name = isset($data->borrower_name) ? $data->borrower_name : null;
    $b_phone = isset($data->borrower_phone) ? $data->borrower_phone : null;
    $qty = isset($data->quantity) ? (int)$data->quantity : 1;
    
    // 4. บันทึกประวัติลงตาราง borrow_history
    $action = ($data->status == 'borrowed') ? 'borrow' : 'return';
    $log = $conn->prepare("INSERT INTO borrow_history (equipment_id, user_fullname, action_type, borrower_name, borrower_phone, quantity, overdue_reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $log->execute([$data->id, $data->fullname, $action, $b_name, $b_phone, $qty, $overdue_reason]);
    
    // ==========================================
    // 5. ส่งการแจ้งเตือนผ่าน LINE Messaging API
    // ==========================================
    if ($action == 'borrow') {
        
        // 🔥 1. นำ Channel Access Token (รหัสยาวๆ) มาใส่ตรงนี้
        $channel_access_token = "Rq7VOnSXOGFluj8jGdEboOoAMcY4eCqibz0yMT9ygYYiTsu5A0pPExT4UNBVO6Pbr5J4N9vjIIPWFF/sDzV+VeZ+bxZxLW8e/bSUC4oLv29zKQ7QCqLeKdk7TROuvM75nnlmSeR2vqu4MeHNbrhwFwdB04t89/1O/w1cDnyilFU="; 
        
        // 🔥 2. นำ Your User ID มาใส่ตรงนี้
        $admin_user_id = "U04eb9c4557f05f1b2529b6413cf004f0"; 

        // จัดรูปแบบข้อความที่จะส่งเข้า LINE
        $message_text = "🔔 มีการยืมอุปกรณ์ใหม่!\n";
        $message_text .= "----------------------\n";
        $message_text .= "📦 อุปกรณ์: " . $item_name . "\n";
        $message_text .= "👤 ผู้ยืม: " . $b_name . "\n";
        $message_text .= "📞 เบอร์โทร: " . $b_phone . "\n";
        $message_text .= "🔢 จำนวน: " . $qty . " รายการ\n";
        $message_text .= "📝 ผู้ทำรายการ: " . $data->fullname . "\n";
        $message_text .= "📅 กำหนดคืน: " . date('d/m/Y', strtotime($due_date));

        // จัดรูปแบบข้อมูลเป็น JSON สำหรับ Messaging API
        $data_line = [
            'to' => $admin_user_id,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message_text
                ]
            ]
        ];

        // ยิงข้อมูลไปที่ LINE API ผ่าน cURL
        $ch = curl_init('https://api.line.me/v2/bot/message/push');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_line));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $channel_access_token
        ));
        
        $result = curl_exec($ch);
        curl_close($ch);
    }

    // ตอบกลับหน้าเว็บว่าทำงานสำเร็จ
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
?>