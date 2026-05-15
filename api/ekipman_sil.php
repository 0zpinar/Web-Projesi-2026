<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200); exit();
}
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
session_start();
if(!isset($_SESSION['kullanici_id'])) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Giriş gerekli."]); exit(); }

$host='127.0.0.1'; $db='atolyepaylas_db'; $user='root'; $pass='';
try {
    $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
} catch(\PDOException $e) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB hatası!"]); exit(); }

$gelen = json_decode(file_get_contents("php://input"));
if(empty($gelen->ekipman_id)) { http_response_code(400); echo json_encode(["status"=>"error","message"=>"Ekipman ID gerekli."]); exit(); }

// Sadece kendi ekipmanını silebilir
$stmt = $pdo->prepare("DELETE FROM ekipmanlar WHERE id=:id AND sahip_id=:sahip_id");
$sonuc = $stmt->execute([':id'=>intval($gelen->ekipman_id), ':sahip_id'=>$_SESSION['kullanici_id']]);

if($sonuc && $stmt->rowCount() > 0) {
    echo json_encode(["status"=>"success","message"=>"Ekipman silindi."]);
} else {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Silinemedi."]);
}
?>