<?php


// --- 1️⃣ Connexion à la base de données ---
$servername = "localhost";
$username = "root";
$password = ""; // vide par défaut sous XAMPP
$dbname = "gestion_reservation_hotel"; // remplace par le nom de ta base

$bdd = new PDO("mysql:host;dbname=gestion_reservation_hotel;charset=utf8", "root", "");

if ($bdd->connect_error) {
    die("Échec de la connexion : " . $bdd->connect_error);
}

$name = $_POST['lastname'];
$first_name = $_POST['firstname'];
$email = $_POST['email'];
$password = $_POST['password'];
$phone = $_POST['phone'];
$adresse = $_POST['adresse'];

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO guest (guest_id, last_name, first_name, email, loyality_points, phone, adress, login, password, registration_date, date_of_birth) VALUES ("", name, first_name, email, 0, phone, adress)";

$stmt = $bdd->prepare($sql);
$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    echo "✅ Inscription réussie ! <a href='login.php'>Se connecter</a>";
} else {
    echo "❌ Erreur : " . $stmt->error;
}

// --- 5️⃣ Fermeture ---
$stmt->close();
$bdd->close();


