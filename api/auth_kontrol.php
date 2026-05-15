<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['kullanici_id'])) {
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(401); 
    
    echo json_encode(["status" => "error", "message" => "Erişim engellendi. Lütfen giriş yapın."]);
    
    exit(); 
}
?>