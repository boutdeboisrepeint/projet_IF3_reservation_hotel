<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guest') {
    header('Location: ../html/login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: compte_client.php?section=new-reservation');
    exit();
}

$room_id         = (int)($_POST['room_id'] ?? 0);
$check_in        = trim($_POST['check_in'] ?? '');
$check_out       = trim($_POST['check_out'] ?? '');
$num_guests      = (int)($_POST['num_guests'] ?? 1);
$special_requests = trim($_POST['special_requests'] ?? '');
$services        = $_POST['services'] ?? [];

if (empty($room_id) || empty($check_in) || empty($check_out)) {
    $_SESSION['errors'] = ["Data missing."];
    header("Location: compte_client.php?section=new-reservation");
    exit();
}

if (strtotime($check_in) >= strtotime($check_out)) {
    $_SESSION['errors'] = ["Check-out date must be after check-in date."];
    header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}");
    exit();
}

try {
    $stmt = $pdo->prepare("
      SELECT r.room_id, r.price_per_night, rt.capacity
      FROM room r
      JOIN room_type rt ON r.room_type_id = rt.room_type_id
      WHERE r.room_id = ?
      LIMIT 1
    ");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        $_SESSION['errors'] = ["Selected room does not exist."];
        header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}");
        exit();
    }

    if ($num_guests > (int)$room['capacity']) {
        $_SESSION['errors'] = ["The number of people ({$num_guests}) exceeds the capacity of the room ({$room['capacity']})."];
        header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}");
        exit();
    }

    $stmt = $pdo->prepare("
      SELECT COUNT(*) AS c
      FROM reservation
      WHERE room_id = ?
        AND status IN ('pending','confirmed','checked_in')
        AND NOT (check_out_date <= ? OR check_in_date >= ?)
    ");
    $stmt->execute([$room_id, $check_in, $check_out]);
    $conflicts = (int)$stmt->fetch()['c'];

    if ($conflicts > 0) {
        $_SESSION['errors'] = ["The selected room is not available for the chosen dates."];
        header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}");
        exit();
    }

    $nights = (int)max(1, round((strtotime($check_out) - strtotime($check_in)) / 86400));
    $total = $nights * (float)$room['price_per_night'];

    $validServices = [];
    if (!empty($services) && is_array($services)) {
        $services = array_values(array_filter(array_map('intval', $services), fn($v)=>$v>0));
        if (!empty($services)) {
            $in = implode(',', array_fill(0, count($services), '?'));
            $stmt = $pdo->prepare("SELECT service_id, price FROM services WHERE service_id IN ($in)");
            $stmt->execute($services);
            $validServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($validServices as $svc) {
                $total += (float)$svc['price'];
            }
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
      INSERT INTO reservation
      (guest_id, room_id, check_in_date, check_out_date, number_of_guest, total_price, status, booking_date, special_requests)
      VALUES
      (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
    ");
    $stmt->execute([
        $_SESSION['guest_id'],
        $room_id,
        $check_in,
        $check_out,
        $num_guests,
        $total,
        $special_requests
    ]);

    $reservation_id = (int)$pdo->lastInsertId();

    if (!empty($validServices)) {
        $ins = $pdo->prepare("INSERT INTO reservation_service (reservation_id, service_id, price) VALUES (?, ?, ?)");
        foreach ($validServices as $s) {
            $ins->execute([$reservation_id, (int)$s['service_id'], (float)$s['price']]);
        }
    }

    $pdo->commit();

    $_SESSION['success'] = "Reservation created successfully. (Status: Pending confirmation)";
    header("Location: compte_client.php?section=reservations");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['errors'] = ["Reservation Error : " . $e->getMessage()];
    header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}");
    exit();
}
