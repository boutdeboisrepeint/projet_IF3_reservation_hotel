<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestion_reservation_hotel";

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}

// Vérification des données POST
if (
        empty($_POST['room_type_id']) ||
        empty($_POST['check_in_date']) ||
        empty($_POST['check_out_date']) ||
        empty($_POST['number_of_guest'])
) {
    die("Données manquantes.");
}

$room_type_id = $_POST['room_type_id'];
$check_in = $_POST['check_in_date'];
$check_out = $_POST['check_out_date'];
$nb_guest = $_POST['number_of_guest'];
$guest_id = $_SESSION['guest_id'];

// Vérifier qu’il existe une chambre disponible pour ce type
$sql = "SELECT room_id, price_per_night 
        FROM room 
        WHERE room_type_id = :type AND status = 'Disponible'
        LIMIT 1";
$stmt = $bdd->prepare($sql);
$stmt->execute(['type' => $room_type_id]);
$room = $stmt->fetch();

if (!$room) {
    die("Aucune chambre disponible pour ce type.");
}

// Calcul du prix total (simplement nb nuits × prix/nuit)
$date1 = new DateTime($check_in);
$date2 = new DateTime($check_out);
$nb_nuits = $date1->diff($date2)->days;
if ($nb_nuits <= 0) $nb_nuits = 1; // sécurité
$total = $nb_nuits * $room['price_per_night'];

// Insertion de la réservation
$sql = "INSERT INTO reservation (number_of_guest, status, check_in_date, check_out_date, room_id, total_price, booking_date, guest_id)
        VALUES (:nb_guest, 'confirmée', :check_in, :check_out, :room_id, :total_price, NOW(), :guest_id)";
$stmt = $bdd->prepare($sql);
$stmt->execute([
        'nb_guest' => $nb_guest,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'room_id' => $room['room_id'],
        'total_price' => $total,
        'guest_id' => $guest_id
]);

// Marquer la chambre comme occupée
$update = $bdd->prepare("UPDATE room SET status = 'Occupée' WHERE room_id = :id");
$update->execute(['id' => $room['room_id']]);

// Rediriger vers le compte client
header("Location: compte_client.php?success=1");
exit();
?>
