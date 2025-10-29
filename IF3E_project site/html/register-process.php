<?php
// php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestion_reservation_hotel";

try {
    $bdd = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connexion échouée: " . $e->getMessage());
}

$name = $_POST['lastname'] ?? '';
$first_name = $_POST['firstname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$adresse = $_POST['adresse'] ?? 'indef'; // valeur par défaut si absente
$login = $_POST['login'] ?? 'indef';
$registration_date = (new DateTime())->format('Y-m-d H:i:s');
$date_of_birth = (new DateTime($_POST['date_of_birth']))->format('Y-m-d');

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO guest (
    last_name, first_name, email, loyality_points, phone,
    adress, login, password, registration_date, date_of_birth
) VALUES (
    :last_name, :first_name, :email, :loyality_points, :phone,
    :adress, :login, :password, :registration_date, :date_of_birth
)";

$stmt = $bdd->prepare($sql);

$params = [
    ':last_name' => $name,
    ':first_name' => $first_name,
    ':email' => $email,
    ':loyality_points' => 0,
    ':phone' => $phone,
    ':adress' => $adresse,
    ':login' => $login,
    ':password' => $hashed_password,
    ':registration_date' => $registration_date,
    ':date_of_birth' => $date_of_birth // NULL accepté
];

try {
    $stmt->execute($params);
    echo "✅ Inscription réussie ! <a href='login.php'>Se connecter</a>";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}

$stmt = null;
$bdd = null;
