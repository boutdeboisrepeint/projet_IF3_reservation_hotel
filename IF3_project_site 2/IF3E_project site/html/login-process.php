<?php
// php
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

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


if ($email === '' || $password === '') {
    echo "Email ou mot de passe manquant.";
    exit;
}

// Requête avec paramètre nommé
$sql = 'SELECT * FROM guest WHERE email = :email LIMIT 1';
$stmt = $bdd->prepare($sql);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if ($user) {
    $stored = $user['password'] ?? '';

    // Vérifier avec password_verify si le mot de passe est hashé,
    // sinon fallback (uniquement temporaire si la BDD contient des mots de passe en clair)
    $valid = false;
    if ($stored !== '' && password_verify($password, $stored)) {
        $valid = true;
    } elseif ($stored === $password) {
        $valid = true;
    }

    if ($valid) {
        session_start();
        $_SESSION['guest_id'] = $user['guest_id'];
        $_SESSION['user_email'] = $email;
        $displayName = $user['first_name'] ?? $user['firstname'] ?? $user['last_name'] ?? '';
        header("location: compte_client.php?email=$email");
        exit;
    }
}


header("location: login.php");
echo 'Email ou mot de passe incorrect.';
?>