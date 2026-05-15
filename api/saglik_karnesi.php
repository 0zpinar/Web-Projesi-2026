<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200); exit();
}
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

$host='127.0.0.1'; $db='atolyepaylas_db'; $user='root'; $pass='';
try {
    $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
} catch(\PDOException $e) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB hatası!"]); exit(); }

$ekipman_id = isset($_GET['ekipman_id']) ? intval($_GET['ekipman_id']) : 0;
if($ekipman_id <= 0) { http_response_code(400); echo json_encode(["status"=>"error","message"=>"Geçersiz ekipman ID."]); exit(); }

// Toplam kullanım saati
$saat_stmt=$pdo->prepare("SELECT SUM(TIMESTAMPDIFF(HOUR, baslangic_tarihi, bitis_tarihi)) as toplam_saat FROM kiralamalar WHERE ekipman_id=:id AND durum='tamamlandi'");
$saat_stmt->execute([':id'=>$ekipman_id]);
$toplam_saat = intval($saat_stmt->fetch()['toplam_saat']);

// Başlangıç kondisyonu ve toplam yenileme
$bakim_stmt=$pdo->prepare("SELECT kondisyon_yenileme, bakim_tarihi, notlar FROM bakim_gecmisi WHERE ekipman_id=:id ORDER BY bakim_tarihi ASC");
$bakim_stmt->execute([':id'=>$ekipman_id]);
$bakimlar = $bakim_stmt->fetchAll();

$baslangic_kondisyon = 100;
$toplam_yenileme = 0;
$son_bakim = null;

foreach($bakimlar as $i => $b) {
    if($i === 0 && $b['notlar'] === 'Başlangıç kondisyonu') {
        $baslangic_kondisyon = intval($b['kondisyon_yenileme']);
    } else {
        $toplam_yenileme += intval($b['kondisyon_yenileme']);
        $son_bakim = $b['bakim_tarihi'];
    }
}

// Hesapla
$yipranma = $toplam_saat * 0.5;
$guncel = $baslangic_kondisyon - $yipranma + $toplam_yenileme;
$guncel = max(0, min(100, $guncel));

$yorum = "Mükemmel durumda!";
if($guncel < 40) $yorum = "Acil bakım gerekiyor!";
elseif($guncel < 75) $yorum = "İyi durumda, hafif yıpranma var.";

echo json_encode([
    "status" => "success",
    "ekipman_id" => $ekipman_id,
    "saglik_karnesi" => [
        "kondisyon_yuzdesi" => round($guncel, 1),
        "baslangic_kondisyon" => $baslangic_kondisyon,
        "toplam_kullanim_saati" => $toplam_saat,
        "son_bakim_tarihi" => $son_bakim ?? "Hiç bakım yapılmadı",
        "sistem_yorumu" => $yorum
    ]
]);
?>