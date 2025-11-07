<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guest') {
    $_SESSION['errors'] = ["Unauthorized access."];
    header('Location: ../html/login.html');
    exit();
}

$reservation_id = (int)($_POST['reservation_id'] ?? 0);

if (!$reservation_id) {
    $_SESSION['errors'] = ["Invalid reservation ID."];
    header('Location: compte_client.php');
    exit();
}

// Vérifier que la réservation appartient bien au client
$stmt = $pdo->prepare("SELECT * FROM reservation WHERE id_reservation = ? AND guest_id = ?");
$stmt->execute([$reservation_id, $_SESSION['guest_id']]);
$reservation = $stmt->fetch();

if ($reservation && in_array($reservation['status'], ['confirmed', 'pending'])) {
    try {
        $pdo->beginTransaction();
        
        // Annuler la réservation
        $stmt = $pdo->prepare("UPDATE reservation SET status = 'cancelled' WHERE id_reservation = ?");
        $stmt->execute([$reservation_id]);
        
        // Vérifier si la table payment existe avant de mettre à jour
        $checkPayment = $pdo->query("SHOW TABLES LIKE 'payment'");
        if ($checkPayment->rowCount() > 0) {
            // La table existe, on met à jour le statut du paiement
            $stmt = $pdo->prepare("UPDATE payment SET status = 'refunded' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Reservation cancelled successfully.";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['errors'] = ["Error: " . $e->getMessage()];
    }
} else {
    $_SESSION['errors'] = ["Reservation not found or cannot be cancelled."];
}

header('Location: compte_client.php?section=reservations');
exit();