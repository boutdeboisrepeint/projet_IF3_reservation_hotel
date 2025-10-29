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

$name = trim($_POST['lastname'] ?? '');
$first_name = trim($_POST['firstname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone_raw = $_POST['phone'] ?? '';
$adresse = $_POST['adresse'] ?? 'indef';
$login = $_POST['login'] ?? 'indef';
$registration_date = (new DateTime())->format('Y-m-d H:i:s');
$date_of_birth = !empty($_POST['date_of_birth']) ? (new DateTime($_POST['date_of_birth']))->format('Y-m-d') : null;

// Normaliser téléphone
$phone = preg_replace('/\D+/', '', $phone_raw);

// Vérifications basiques
if ($phone === '' || strlen($phone) !== 10) {
    echo "Téléphone invalide. Utilisez 10 chiffres.";
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email invalide.";
    exit;
}
if (empty($password) || empty($name) || empty($first_name)) {
    echo "Champs requis manquants.";
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Vérifier unicité (sans sélectionner `id`)
$check_unicite_sql = "SELECT phone, email, login FROM guest WHERE phone = :phone OR email = :email OR login = :login LIMIT 1";

try {
    $checkStmt = $bdd->prepare($check_unicite_sql);
    $checkStmt->execute([
        'phone' => $phone,
        'email' => $email,
        'login' => $login
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la vérification d'unicité : " . $e->getMessage());
}

if ($existing) {
    if (!empty($existing['phone']) && $existing['phone'] === $phone) {
        echo "Ce numéro de téléphone est déjà utilisé.";
    } elseif (!empty($existing['email']) && $existing['email'] === $email) {
        echo "Cette adresse e-mail est déjà utilisée.";
    } else {
        echo "Ce login est déjà utilisé.";
    }
    exit;
}

// Préparer et exécuter l'insertion
$sql = "INSERT INTO guest (
    last_name, first_name, email, loyality_points, phone,
    adress, login, password, registration_date, date_of_birth
) VALUES (
    :last_name, :first_name, :email, :loyality_points, :phone,
    :adress, :login, :password, :registration_date, :date_of_birth
)";

$stmt = $bdd->prepare($sql);

$params = [
    'last_name' => $name,
    'first_name' => $first_name,
    'email' => $email,
    'loyality_points' => 0,
    'phone' => $phone,
    'adress' => $adresse,
    'login' => $login,
    'password' => $hashed_password,
    'registration_date' => $registration_date,
    'date_of_birth' => $date_of_birth
];

try {
    $stmt->execute($params);
    echo "✅ Inscription réussie ! <a href='login.php'>Se connecter</a>";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        echo "Erreur : donnée déjà existante (contrainte d'unicité).";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
}

$stmt = null;
$bdd = null;
