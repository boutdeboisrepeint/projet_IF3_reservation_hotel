<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'guest' || empty($_SESSION['guest_id'])) {
    $_SESSION['errors'] = ["Please log in to submit feedback."];
    header('Location: ../html/login.html');
    exit();
}

$reservation_id = intval($_POST['reservation_id'] ?? 0);
$rating         = intval($_POST['rating'] ?? 0);
$comment        = trim($_POST['comment'] ?? '');

$errors = [];
if ($reservation_id <= 0) { $errors[] = "Invalid Reservation."; }
if ($rating < 1 || $rating > 5) { $errors[] = "The grade must be between 1 and 5."; }
if ($comment === '') { $errors[] = "The comment is required."; }

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: compte_client.php?section=reviews');
    exit();
}

$st = $pdo->prepare("
  SELECT r.id_reservation 
  FROM reservation r
  WHERE r.id_reservation = ? AND r.guest_id = ? AND r.status = 'completed'
");
$st->execute([$reservation_id, $_SESSION['guest_id']]);
if (!$st->fetch()) {
    $_SESSION['errors'] = ["You can only leave feedback for completed reservations."];
    header('Location: compte_client.php?section=reviews');
    exit();
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM feedback WHERE reservation_id = ? AND guest_id = ?");
$st->execute([$reservation_id, $_SESSION['guest_id']]);
if ((int)$st->fetch()['c'] > 0) {
    $_SESSION['errors'] = ["You have already submitted feedback for this reservation."];
    header('Location: compte_client.php?section=reviews');
    exit();
}

try {
    $ins = $pdo->prepare("
      INSERT INTO feedback (comment, rating, date_posted, guest_id, reservation_id)
      VALUES (?, ?, NOW(), ?, ?)
    ");
    $ins->execute([$comment, $rating, $_SESSION['guest_id'], $reservation_id]);

    $_SESSION['success'] = "Thank you for your feedback!";
    header('Location: compte_client.php?section=reviews');
    exit();

} catch (Exception $e) {
    $_SESSION['errors'] = ["Error when sending the feedback: " . $e->getMessage()];
    header('Location: compte_client.php?section=reviews');
    exit();
}
