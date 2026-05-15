<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

$host = '127.0.0.1';
$db   = 'atolyepaylas_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Veritabanı bağlantı hatası!"]);
    exit();
}

$gelen_veri = json_decode(file_get_contents("php://input"));

if(
    !empty($gelen_veri->ad_soyad) &&
    !empty($gelen_veri->email) &&
    !empty($gelen_veri->sifre) &&
    !empty($gelen_veri->rol)
) {
    $kontrol_stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = :email LIMIT 1");
    $kontrol_stmt->execute(['email' => $gelen_veri->email]);
    
    if($kontrol_stmt->rowCount() > 0) {
        http_response_code(400); 
        echo json_encode(["status" => "error", "message" => "Bu e-posta adresi sistemde zaten kayıtlı."]);
    } else {
        $sifre_hash = password_hash($gelen_veri->sifre, PASSWORD_DEFAULT);
        $ekle_stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, email, sifre_hash, rol) VALUES (:ad_soyad, :email, :sifre_hash, :rol)");
        
        $sonuc = $ekle_stmt->execute([
            ':ad_soyad' => htmlspecialchars(strip_tags($gelen_veri->ad_soyad)),
            ':email'    => htmlspecialchars(strip_tags($gelen_veri->email)),
            ':sifre_hash' => $sifre_hash,
            ':rol'      => htmlspecialchars(strip_tags($gelen_veri->rol))
        ]);
        
        if($sonuc) {
            http_response_code(201); 
            echo json_encode(["status" => "success", "message" => "Kullanıcı başarıyla oluşturuldu."]);
        } else {
            http_response_code(503); 
            echo json_encode(["status" => "error", "message" => "Veritabanı hatası oluştu."]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Tüm alanlar zorunludur."]);
}
?>