<?php
error_reporting(E_ALL); ini_set('display_errors',1);
require_once __DIR__.'/../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type']??'')!=='employee') { header('Location: ../../html/login.html'); exit; }

$action = $_POST['action'] ?? '';
$room_id = intval($_POST['room_id'] ?? 0);

$flash = function($ok,$msg){ $_SESSION[$ok?'success':'errors'] = $ok ? $msg : [$msg]; };

try{
  if($action==='set_available'){
    $pdo->prepare("UPDATE room SET status='available' WHERE room_id=?")->execute([$room_id]);
    $flash(true,"Chambre disponible.");
  }elseif($action==='set_cleaning'){
    $pdo->prepare("UPDATE room SET status='cleaning' WHERE room_id=?")->execute([$room_id]);
    $flash(true,"Chambre en nettoyage.");
  }elseif($action==='set_maintenance'){
    $pdo->prepare("UPDATE room SET status='maintenance' WHERE room_id=?")->execute([$room_id]);
    $flash(true,"Chambre en maintenance.");
  }elseif($action==='update_price'){
    $price = floatval($_POST['price'] ?? 0);
    if($price<=0) throw new Exception("Prix invalide.");
    $pdo->prepare("UPDATE room SET price_per_night=? WHERE room_id=?")->execute([$price,$room_id]);
    $flash(true,"Prix mis à jour.");
  }elseif($action==='create_room'){
    $room_number=intval($_POST['room_number'] ?? 0);
    $room_type_id=intval($_POST['room_type_id'] ?? 0);
    $price=floatval($_POST['price'] ?? 0);
    $status=$_POST['status'] ?? 'available';
    if($room_number<=0 || $room_type_id<=0 || $price<=0) throw new Exception("Champs invalides.");
    $pdo->prepare("INSERT INTO room (room_number, room_type_id, status, price_per_night) VALUES(?,?,?,?)")->execute([$room_number,$room_type_id,$status,$price]);
    $flash(true,"Chambre créée (#$room_number).");
  }else{
    throw new Exception("Action inconnue.");
  }
}catch(Exception $e){
  $flash(false,$e->getMessage());
}
header('Location: ../admin_gestion_chambres.php'); exit;
