<?php
require_once 'config.php';
require_once 'admin_util.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT r.*, g.first_name, g.last_name, g.email, g.phone,
               rm.room_number, rt.type_name
        FROM reservation r
        JOIN guest g ON r.guest_id = g.guest_id
        JOIN room rm ON r.room_id = rm.room_id
        JOIN room_type rt ON rm.room_type_id = rt.room_type_id
        WHERE r.id_reservation = ?
    ");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        echo json_encode(['error' => 'Reservation not found']);
        exit;
    }
    
    $reservation['services'] = [];
    
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'reservation_service'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT s.service_name, s.price
                FROM reservation_service rs
                JOIN services s ON rs.service_id = s.service_id
                WHERE rs.reservation_id = ?
            ");
            $stmt->execute([$id]);
            $reservation['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $reservation['services'] = [];
    }

    
    $reservation['payment'] = null;
    
    try {
        $checkPayment = $pdo->query("SHOW TABLES LIKE 'payment'");
        if ($checkPayment->rowCount() > 0) {
            // La table existe, récupérer le paiement
            $stmt = $pdo->prepare("SELECT * FROM payment WHERE reservation_id = ?");
            $stmt->execute([$id]);
            $reservation['payment'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $reservation['payment'] = null;
    }
    
    echo json_encode($reservation);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
