<?php
// php
// Fichier : `IF3E_project site/html/register-process.php`

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

// Récupération et normalisation des champs
$name = trim($_POST['lastname'] ?? '');
$first_name = trim($_POST['firstname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone_raw = $_POST['phone'] ?? '';
// Normaliser téléphone (garder seulement les chiffres)
$phone = preg_replace('/\D+/', '', $phone_raw);
$adresse = trim($_POST['adresse'] ?? '');
$login = trim($_POST['login'] ?? '');
$registration_date = (new DateTime('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');


// Validations basiques
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

$checkParts = [];
$checkParams = [];

if ($phone !== '') {
    $checkParts[] = 'phone = :phone';
    $checkParams['phone'] = $phone;
}
if ($email !== '') {
    $checkParts[] = 'email = :email';
    $checkParams['email'] = $email;
}
if ($login !== '') {
    $checkParts[] = 'login = :login';
    $checkParams['login'] = $login;
}

if (!empty($checkParts)) {
    $check_sql = 'SELECT phone, email, login FROM guest WHERE ' . implode(' OR ', $checkParts) . ' LIMIT 1';
    try {
        $checkStmt = $bdd->prepare($check_sql);
        $checkStmt->execute($checkParams);
        $existing = $checkStmt->fetch();
    } catch (PDOException $e) {
        die("Erreur lors de la vérification d'unicité : " . $e->getMessage());
    }

    if ($existing) {
        if (!empty($existing['phone']) && $existing['phone'] === $phone) {
            echo "Ce numéro de téléphone est déjà utilisé.";
            header("Location: register.php?error=phone_exists");
        } elseif (!empty($existing['email']) && $existing['email'] === $email) {
            echo "Cette adresse e-mail est déjà utilisée.";
            header("Location: register.php?error=phone_exists");
        } elseif (!empty($existing['login']) && $existing['login'] === $login) {
            echo "Ce login est déjà utilisé.";
            header("Location: register.php?error=phone_exists");
        }
    }
}

$sql = "INSERT INTO guest (
    last_name, first_name, email, loyality_points, phone,
    adress, login, password, registration_date, date_of_birth
) VALUES (
    :last_name, :first_name, :email, :loyality_points, :phone,
    :adress, :login, :password, :registration_date, :date_of_birth
)";

echo '$sql';

$stmt = $bdd->prepare($sql);

$params = [
    'last_name' => $name,
    'first_name' => $first_name,
    'email' => $email,
    'loyality_points' => 0,
    'phone' => $phone,
    'adress' => $adresse,
    'login' => $login !== '' ? $login : null,
    'password' => $hashed_password,
    'registration_date' => $registration_date,
    'date_of_birth' => $date_of_birth
];

error_log('Params insert: ' . print_r($params, true));

try {
    $stmt->execute($params);
    if ($stmt->rowCount() > 0) {
        echo "Inscription réussie ! <a href='login.php'>Se connecter</a>";
    } else {
        echo "Aucune ligne insérée.";
        error_log('Aucune ligne insérée. Params: ' . print_r($params, true));
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        echo "Erreur : donnée déjà existante (contrainte d'unicité).";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
}

$stmt = null;
$bdd = null;

header ("Location: login.php");
exit;
