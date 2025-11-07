<?php
error_reporting(E_ALL); ini_set('display_errors',1);
require_once __DIR__.'/../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type']??'')!=='employee') { header('Location: ../../html/login.html'); exit; }

$action = $_POST['action'] ?? '';
$guest_id = intval($_POST['guest_id'] ?? 0);
$flash = function($ok,$msg){ $_SESSION[$ok?'success':'errors'] = $ok ? $msg : [$msg]; };

if(!$guest_id){ $flash(false,"Client invalide."); header('Location: ../admin_gestion_clients.php'); exit; }

try{
  if($action==='update'){
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??'');
    $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??'');
    $adress=trim($_POST['adress']??''); $points=intval($_POST['points']??0);
    if(!$first || !$last || !$email){ throw new Exception("Champs obligatoires manquants."); }
    $st=$pdo->prepare("SELECT guest_id FROM guest WHERE (email=? OR phone=?) AND guest_id<>?"); $st->execute([$email,$phone,$guest_id]);
    if($st->fetch()){ throw new Exception("Email ou téléphone déjà utilisé."); }
    $pdo->prepare("UPDATE guest SET first_name=?, last_name=?, email=?, phone=?, adress=?, loyality_points=? WHERE guest_id=?")
        ->execute([$first,$last,$email,$phone,$adress,$points,$guest_id]);
    $flash(true,"Client mis à jour.");
  }else{
    throw new Exception("Action inconnue.");
  }
}catch(Exception $e){
  $flash(false,$e->getMessage());
}
header('Location: ../admin_gestion_clients.php'); exit;
