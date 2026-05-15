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

session_start();

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

if(!empty($gelen_veri->email) && !empty($gelen_veri->sifre)) {
    
    $stmt = $pdo->prepare("SELECT id, ad_soyad, sifre_hash, rol FROM kullanicilar WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $gelen_veri->email]);
    
    if($stmt->rowCount() > 0) {
        $kullanici = $stmt->fetch();
        
        if(password_verify($gelen_veri->sifre, $kullanici['sifre_hash'])) {
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['rol'] = $kullanici['rol'];
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Giriş başarılı.",
                "kullanici" => [
                    "id" => $kullanici['id'],
                    "ad_soyad" => $kullanici['ad_soyad'],
                    "rol" => $kullanici['rol']
                ]
            ]);
        } else {
            http_response_code(401); 
            echo json_encode(["status" => "error", "message" => "Hatalı şifre girdiniz."]);
        }
    } else {
        http_response_code(404); 
        echo json_encode(["status" => "error", "message" => "Bu e-posta adresine ait kullanıcı bulunamadı."]);
    }
} else {
    http_response_code(400); 
    echo json_encode(["status" => "error", "message" => "Email ve şifre zorunludur."]);
}
?>