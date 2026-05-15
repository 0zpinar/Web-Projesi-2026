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

if(!isset($_SESSION['kullanici_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Giriş yapmanız gerekiyor."]);
    exit();
}

$host = '127.0.0.1'; $db = 'atolyepaylas_db'; $user = 'root'; $pass = '';
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
    !empty($gelen_veri->ekipman_id) &&
    !empty($gelen_veri->baslangic_tarihi) &&
    !empty($gelen_veri->bitis_tarihi) &&
    !empty($gelen_veri->teslimat_noktasi)
) {
    $toplam_tutar = isset($gelen_veri->toplam_tutar) ? floatval($gelen_veri->toplam_tutar) : 0.00;
    $stmt = $pdo->prepare("INSERT INTO kiralamalar (ekipman_id, kiralayan_id, baslangic_tarihi, bitis_tarihi, toplam_tutar, teslimat_noktasi) VALUES (:ekipman_id, :kiralayan_id, :baslangic_tarihi, :bitis_tarihi, :toplam_tutar, :teslimat_noktasi)");

    $sonuc = $stmt->execute([
        ':ekipman_id'       => intval($gelen_veri->ekipman_id),
        ':kiralayan_id'     => $_SESSION['kullanici_id'],
        ':baslangic_tarihi' => $gelen_veri->baslangic_tarihi,
        ':bitis_tarihi'     => $gelen_veri->bitis_tarihi,
        ':toplam_tutar'     => $toplam_tutar,
        ':teslimat_noktasi' => htmlspecialchars(strip_tags($gelen_veri->teslimat_noktasi))
    ]);

    if($sonuc) {
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "Kiralama isteği başarıyla oluşturuldu."]);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Hata oluştu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Tüm alanlar zorunludur."]);
}
?>