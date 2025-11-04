<?php
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

// --------------------
// Récupération des champs
// --------------------
$name = trim($_POST['lastname'] ?? '');
$first_name = trim($_POST['firstname'] ?? '');
$login = trim($_POST['login'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone_raw = $_POST['phone'] ?? '';
$phone = preg_replace('/\D+/', '', $phone_raw);
$adresse = trim($_POST['adresse'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$registration_date = (new DateTime('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');

// --------------------
// Vérifications de base
// --------------------
if ($phone === '' || strlen($phone) !== 10) {
    die("Téléphone invalide. Utilisez 10 chiffres.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email invalide.");
}
if (empty($password) || empty($name) || empty($first_name) || empty($login)) {
    die("Certains champs requis sont manquants.");
}
if ($password !== $confirm_password) {
    die("Les mots de passe ne correspondent pas !");
}

// Hachage du mot de passe
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --------------------
// Vérification unicité
// --------------------
try {
    $check_sql = 'SELECT phone, email, login FROM guest WHERE phone = :phone OR email = :email OR login = :login LIMIT 1';
    $checkStmt = $bdd->prepare($check_sql);
    $checkStmt->execute(['phone' => $phone, 'email' => $email, 'login' => $login]);
    $existing = $checkStmt->fetch();
} catch (PDOException $e) {
    die("Erreur lors de la vérification d'unicité : " . $e->getMessage());
}

if ($existing) {
    if (!empty($existing['phone']) && $existing['phone'] === $phone) {
        die("Ce numéro de téléphone est déjà utilisé.");
    }
    if (!empty($existing['email']) && $existing['email'] === $email) {
        die("Cette adresse e-mail est déjà utilisée.");
    }
    if (!empty($existing['login']) && $existing['login'] === $login) {
        die("Ce nom d'utilisateur est déjà utilisé.");
    }
}

// --------------------
// Insertion en base
// --------------------
$sql = "INSERT INTO guest (
    last_name, first_name, login, email, loyalty_points, phone,
    adress, password_hash, registration_date, date_of_birth
) VALUES (
    :last_name, :first_name, :login, :email, :loyalty_points, :phone,
    :adress, :password_hash, :registration_date, :date_of_birth
)";

$stmt = $bdd->prepare($sql);

$params = [
    'last_name' => $name,
    'first_name' => $first_name,
    'login' => $login,
    'email' => $email,
    'loyalty_points' => 0,
    'phone' => $phone,
    'adress' => $adresse,
    'password_hash' => $hashed_password,
    'registration_date' => $registration_date,
    'date_of_birth' => $date_of_birth
];

try {
    $stmt->execute($params);
    if ($stmt->rowCount() > 0) {
        echo "Inscription réussie ! <a href='login.php'>Se connecter</a>";
    } else {
        die("Erreur : aucune ligne insérée.");
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        die("Erreur : donnée déjà existante (contrainte d'unicité).");
    } else {
        die("Erreur lors de l'insertion : " . $e->getMessage());
    }
}

$stmt = null;
$bdd = null;

// Redirection finale
header("Location: login.php?success=1");
exit;
?>
