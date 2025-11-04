<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=not_logged_in");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestion_reservation_hotel";

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 🔹 Récupération des données du formulaire
$guest_id = $_SESSION['user_id'];
$room_id = $_POST['room_id'] ?? null;
$check_in = $_POST['check_in_date'] ?? null;
$check_out = $_POST['check_out_date'] ?? null;
$nb_adultes = (int)($_POST['nb_adultes'] ?? 1);
$nb_enfants = (int)($_POST['nb_enfants'] ?? 0);

if (!$room_id || !$check_in || !$check_out) {
    die("Tous les champs de réservation doivent être remplis.");
}

if (strtotime($check_out) <= strtotime($check_in)) {
    die("La date de départ doit être postérieure à la date d'arrivée.");
}

// 🔹 Calcule le nombre de nuits
$datetime1 = new DateTime($check_in);
$datetime2 = new DateTime($check_out);
$interval = $datetime1->diff($datetime2);
$nb_nuits = $interval->days;

// 🔹 Vérifie que la chambre existe
$sql_price = "SELECT price_per_night FROM room WHERE room_id = :room_id";
$stmt_price = $bdd->prepare($sql_price);
$stmt_price->execute(['room_id' => $room_id]);
$room = $stmt_price->fetch();

if (!$room) {
    die("Chambre introuvable.");
}

$total_price = $room['price_per_night'] * $nb_nuits;

// 🔹 Insère la réservation
$sql = "INSERT INTO reservation (guest_id, room_id, number_of_guest, status, check_in_date, check_out_date, total_price, booking_date)
        VALUES (:guest_id, :room_id, :number_of_guest, 'Confirmée', :check_in, :check_out, :total_price, :booking_date)";
$stmt = $bdd->prepare($sql);
$stmt->execute([
    'guest_id' => $guest_id,
    'room_id' => $room_id,
    'number_of_guest' => $nb_adultes + $nb_enfants,
    'check_in' => $check_in,
    'check_out' => $check_out,
    'total_price' => $total_price,
    'booking_date' => date('Y-m-d')
]);

header("Location: compte_client.php?success=1");
exit;
?>
