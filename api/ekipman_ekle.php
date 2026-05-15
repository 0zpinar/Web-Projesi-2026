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
if($_SESSION['rol'] !== 'sahip') { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Sadece sahipler ekleyebilir."]); exit(); }
$host='127.0.0.1'; $db='atolyepaylas_db'; $user='root'; $pass='';
try {
    $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
} catch(\PDOException $e) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB hatası!"]); exit(); }

$gelen=json_decode(file_get_contents("php://input"));
if(!empty($gelen->kategori) && !empty($gelen->baslik) && !empty($gelen->saatlik_ucret)) {
    $fotograf = isset($gelen->fotograf) ? $gelen->fotograf : null;
    $kondisyon = isset($gelen->kondisyon) ? intval($gelen->kondisyon) : 100;

    $stmt=$pdo->prepare("INSERT INTO ekipmanlar (sahip_id,kategori,baslik,aciklama,saatlik_ucret,fotograf) VALUES (:sahip_id,:kategori,:baslik,:aciklama,:saatlik_ucret,:fotograf)");
    $sonuc=$stmt->execute([
        ':sahip_id'=>$_SESSION['kullanici_id'],
        ':kategori'=>htmlspecialchars(strip_tags($gelen->kategori)),
        ':baslik'=>htmlspecialchars(strip_tags($gelen->baslik)),
        ':aciklama'=>isset($gelen->aciklama)?htmlspecialchars(strip_tags($gelen->aciklama)):null,
        ':saatlik_ucret'=>floatval($gelen->saatlik_ucret),
        ':fotograf'=>$fotograf
    ]);

    if($sonuc) {
        $ekipman_id = $pdo->lastInsertId();
        // Başlangıç kondisyonunu bakim_gecmisi tablosuna kaydet
        $bakim=$pdo->prepare("INSERT INTO bakim_gecmisi (ekipman_id, bakim_tarihi, kondisyon_yenileme, notlar) VALUES (:ekipman_id, CURDATE(), :kondisyon, 'Başlangıç kondisyonu')");
        $bakim->execute([':ekipman_id'=>$ekipman_id, ':kondisyon'=>$kondisyon]);

        http_response_code(201);
        echo json_encode(["status"=>"success","message"=>"Ekipman eklendi."]);
    } else {
        http_response_code(503);
        echo json_encode(["status"=>"error","message"=>"Hata oluştu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Zorunlu alanlar eksik."]);
}
?>