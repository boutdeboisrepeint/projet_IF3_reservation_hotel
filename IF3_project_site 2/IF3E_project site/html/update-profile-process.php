<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé. Vous devez être connecté pour modifier votre profil.");
}

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
    die("Connexion échouée: " . $e->getMessage());
}

// Récupération des champs envoyés depuis le formulaire
$id = $_SESSION['user_id']; // ✅ L’ID provient de la session, pas du formulaire
$new_email = trim($_POST['email'] ?? '');
$new_firstname = trim($_POST['firstname'] ?? '');
$new_name = trim($_POST['lastname'] ?? '');
$new_phone = trim($_POST['phone'] ?? '');

if ($new_email === '' || $new_firstname === '' || $new_name === '' || $new_phone === '') {
    die("Erreur : tous les champs doivent être remplis.");
}

// Mise à jour des données
$sql = 'UPDATE guest 
        SET email = :email, first_name = :firstname, last_name = :lastname, phone = :phone 
        WHERE guest_id = :id';

$stmt = $bdd->prepare($sql);
$stmt->execute([
    'email' => $new_email,
    'firstname' => $new_firstname,
    'lastname' => $new_name,
    'phone' => $new_phone,
    'id' => $id
]);

// Redirection après succès
header("Location: compte_client.php?success=1");
exit;
?>
