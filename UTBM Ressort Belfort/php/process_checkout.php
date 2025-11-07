<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employee') {
    header('Location: login.html');
    exit();
}

$reservation_id = intval($_GET['id'] ?? 0);

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE reservation SET status = 'completed' WHERE id_reservation = ?");
    $stmt->execute([$reservation_id]);
    
    $stmt = $pdo->prepare("
        UPDATE room r
        JOIN reservation res ON r.room_id = res.room_id
        SET r.status = 'cleaning'
        WHERE res.id_reservation = ?
    ");
    $stmt->execute([$reservation_id]);
    
    $stmt = $pdo->prepare("UPDATE payment SET status = 'completed', payment_date = NOW() WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Check-out succcesfully completed. Room waiting for cleaning.";
} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['errors'] = ["Error : " . $e->getMessage()];
}

header('Location: admin_dashboard.php');
exit();
?>
