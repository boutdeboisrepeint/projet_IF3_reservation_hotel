<?php
error_reporting(E_ALL); ini_set('display_errors',1);
require_once __DIR__.'/../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type']??'')!=='employee') { header('Location: ../../html/login.html'); exit; }

$action = $_POST['action'] ?? '';
$id = intval($_POST['id_reservation'] ?? 0);

$flash = function($ok,$msg){ $_SESSION[$ok?'success':'errors'] = $ok ? $msg : [$msg]; };

if(!$id){ $flash(false,"Réservation invalide."); header('Location: ../admin_gestion_reservations.php'); exit; }

$st=$pdo->prepare("SELECT r.*, rm.room_id FROM reservation r JOIN room rm ON r.room_id=rm.room_id WHERE r.id_reservation=?");
$st->execute([$id]); $res=$st->fetch();
if(!$res){ $flash(false,"Réservation introuvable."); header('Location: ../admin_gestion_reservations.php'); exit; }

try{
  if($action==='confirm'){
    if($res['status']!=='pending') throw new Exception("Statut non compatible.");
    $pdo->prepare("UPDATE reservation SET status='confirmed' WHERE id_reservation=?")->execute([$id]);
    $flash(true,"Réservation confirmée.");
  }elseif($action==='cancel'){
    if(!in_array($res['status'],['pending','confirmed'])) throw new Exception("Statut non compatible.");
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE reservation SET status='cancelled' WHERE id_reservation=?")->execute([$id]);
    $pdo->prepare("UPDATE room SET status='available' WHERE room_id=?")->execute([$res['room_id']]);
    $pdo->commit();
    $flash(true,"Réservation annulée.");
  }elseif($action==='checkin'){
    if($res['status']!=='confirmed') throw new Exception("Statut non compatible.");
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE reservation SET status='checked_in' WHERE id_reservation=?")->execute([$id]);
    $pdo->prepare("UPDATE room SET status='occupied' WHERE room_id=?")->execute([$res['room_id']]);
    $pdo->commit();
    $flash(true,"Check-in effectué.");
  }elseif($action==='checkout'){
    if($res['status']!=='checked_in') throw new Exception("Statut non compatible.");
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE reservation SET status='completed' WHERE id_reservation=?")->execute([$id]);
    $pdo->prepare("UPDATE room SET status='cleaning' WHERE room_id=?")->execute([$res['room_id']]);
    $pdo->commit();
    $flash(true,"Check-out effectué, chambre en nettoyage.");
  }elseif($action==='update'){
    $newRoomNumber = trim($_POST['new_room_number'] ?? '');
    $newIn = $_POST['new_check_in'] ?? '';
    $newOut = $_POST['new_check_out'] ?? '';
    $pdo->beginTransaction();
    if($newRoomNumber!==''){
      $s=$pdo->prepare("SELECT room_id FROM room WHERE room_number=?"); $s->execute([$newRoomNumber]); $room=$s->fetch();
      if(!$room) throw new Exception("Nouvelle chambre inconnue.");
      // Anti-chevauchement si dates connues
      if($res['check_in_date'] && $res['check_out_date']){
        $c=$pdo->prepare("SELECT COUNT(*) c FROM reservation WHERE room_id=? AND id_reservation<>? AND status IN ('pending','confirmed','checked_in','completed') AND NOT (check_out_date<=? OR check_in_date>=?)");
        $c->execute([$room['room_id'],$id,$res['check_in_date'],$res['check_out_date']]);
        if((int)$c->fetch()['c']>0) throw new Exception("Conflit de réservation sur la nouvelle chambre.");
      }
      $pdo->prepare("UPDATE reservation SET room_id=? WHERE id_reservation=?")->execute([$room['room_id'],$id]);
    }
    if($newIn && $newOut){
      if(strtotime($newOut) <= strtotime($newIn)) throw new Exception("Dates invalides.");
      // Anti-chevauchement sur la chambre actuelle
      $c=$pdo->prepare("SELECT COUNT(*) c FROM reservation WHERE room_id=? AND id_reservation<>? AND status IN ('pending','confirmed','checked_in','completed') AND NOT (check_out_date<=? OR check_in_date>=?)");
      $c->execute([$res['room_id'],$id,$newIn,$newOut]);
      if((int)$c->fetch()['c']>0) throw new Exception("Conflit de réservation sur ces dates.");
      $pdo->prepare("UPDATE reservation SET check_in_date=?, check_out_date=? WHERE id_reservation=?")->execute([$newIn,$newOut,$id]);
    }
    $pdo->commit();
    $flash(true,"Réservation mise à jour.");
  }else{
    throw new Exception("Action inconnue.");
  }
}catch(Exception $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  $flash(false,$e->getMessage());
}
header('Location: ../admin_gestion_reservations.php'); exit;
