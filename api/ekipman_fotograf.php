<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200); exit();
}
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
session_start();
if(!isset($_SESSION['kullanici_id'])) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Giriş gerekli."]); exit(); }

$hedef_klasor = '../uploads/';
if (!file_exists($hedef_klasor)) { mkdir($hedef_klasor, 0777, true); }

if(isset($_FILES['fotograf']) && $_FILES['fotograf']['error'] === 0) {
    $dosya = $_FILES['fotograf'];
    $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
    $izinli = ['jpg','jpeg','png','webp'];
    
    if(!in_array($uzanti, $izinli)) { http_response_code(400); echo json_encode(["status"=>"error","message"=>"Sadece JPG, PNG, WEBP yükleyebilirsin."]); exit(); }
    if($dosya['size'] > 5 * 1024 * 1024) { http_response_code(400); echo json_encode(["status"=>"error","message"=>"Dosya 5MB'dan büyük olamaz."]); exit(); }
    
    $yeni_ad = uniqid('ekipman_') . '.' . $uzanti;
    $hedef = $hedef_klasor . $yeni_ad;
    
    if(move_uploaded_file($dosya['tmp_name'], $hedef)) {
        echo json_encode(["status"=>"success","dosya_adi"=>$yeni_ad,"url"=>"http://localhost/atolyepaylas/uploads/".$yeni_ad]);
    } else {
        http_response_code(500); echo json_encode(["status"=>"error","message"=>"Dosya yüklenemedi."]);
    }
} else {
    http_response_code(400); echo json_encode(["status"=>"error","message"=>"Dosya seçilmedi."]);
}
?>