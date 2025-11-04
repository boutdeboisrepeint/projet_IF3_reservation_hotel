<?php

session_start();

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
$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    die("Identifiant ou mot de passe manquant.");
}

// Recherche par email ou login
$sql = 'SELECT * FROM guest WHERE email = :id OR login = :id LIMIT 1';
$stmt = $bdd->prepare($sql);
$stmt->execute(['id' => $identifier]);
$user = $stmt->fetch();

if ($user) {
    // Récupération du mot de passe haché (vérifie le bon nom de colonne dans ta table)
    $stored = $user['password_hash'] ?? $user['password'] ?? '';

    // Vérification du mot de passe
    if ($stored !== '' && password_verify($password, $stored)) {
        // ✅ Enregistrement des infos en session
        $_SESSION['user_id'] = $user['guest_id'] ?? $user['id'] ?? null;
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_login'] = $user['login'];

        // Redirection vers le compte client
        header("Location: compte_client.php");
        exit;
    }
}

// ❌ Si on arrive ici : échec de connexion
header("Location: login.php?error=invalid");
exit;
?>
