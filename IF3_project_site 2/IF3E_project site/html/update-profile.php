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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}

// On récupère les données du user connecté
$id = $_SESSION['user_id'];

$sql = 'SELECT guest_id, first_name, last_name, email, phone FROM guest WHERE guest_id = :id LIMIT 1';
$stmt = $bdd->prepare($sql);
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le profil - UTBM Resort</title>
    <link rel="stylesheet" href="../css/reservation.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

<header class="home-page-navbar-container scrolled">
    <div class="navbar-inner">
        <div class="navbar-left">
            <a href="compte_client.php" class="navbar-link">← Retour au compte</a>
        </div>
        <div class="navbar-center">
            <h1 class="navbar-title">
                <span class="line-large">THE UTBM</span><br>
                <span class="line-medium">RESSORT</span><br>
                <span class="line-small">BELFORT</span>
            </h1>
        </div>
        <div class="navbar-right">
            <a href="logout.php" class="navbar-link">DÉCONNEXION</a>
        </div>
    </div>
</header>

<main class="reservation-main">
    <section class="reservation-container form-container">
        <h2>Mettre à jour le profil</h2>
        <p class="form-subtitle">Modifiez vos informations personnelles ci-dessous.</p>

        <form class="reservation-form auth-form" method="POST" action="update-profile-process.php">
            <div class="reservation-field full-width">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname"
                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname"
                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['guest_id']); ?>">

            <button type="submit" class="reservation-button full-width">
                Mettre à jour le profil
            </button>
        </form>
    </section>
</main>
</body>
</html>
