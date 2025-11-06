<?php
session_start();

// Vérifie que l'utilisateur est bien connecté
if (empty($_SESSION['guest_id']) || empty($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

// 🔧 Connexion BDD
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

$guest_id = $_SESSION['guest_id'];
$email = $_SESSION['user_email'];

// 🔍 Récupère les infos du client
$sql = "SELECT first_name, last_name, phone, login FROM guest WHERE guest_id = :id";
$stmt = $bdd->prepare($sql);
$stmt->execute(['id' => $guest_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur introuvable.");
}

$firstname = $user['first_name'];
$lastname = $user['last_name'];
$phone = $user['phone'];
$login = $user['login'];

// Récupère les réservations du client
$sql = "SELECT r.id_reservation, r.check_in_date, r.check_out_date, r.status, r.total_price,
               rt.type_name
        FROM reservation r
        JOIN room rm ON r.room_id = rm.room_id
        JOIN room_type rt ON rm.room_type_id = rt.room_type_id
        WHERE r.guest_id = :guest_id
        ORDER BY r.check_in_date DESC";
$stmt = $bdd->prepare($sql);
$stmt->execute(['guest_id' => $guest_id]);
$reservations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - UTBM Resort</title>
    <link rel="stylesheet" href="../css/reservation.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

<header class="home-page-navbar-container scrolled">
    <div class="navbar-inner">
        <div class="navbar-left">
            <a href="index.php" class="navbar-link">ACCUEIL</a>
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
    <section class="reservation-container form-container account-container">
        <h2>Bonjour, <?= htmlspecialchars($firstname) ?> <?= htmlspecialchars($lastname) ?> 👋</h2>

        <!-- Mes Réservations -->
        <div class="account-section">
            <h3>Mes Réservations</h3>

            <?php if (count($reservations) > 0): ?>
                <?php foreach ($reservations as $r): ?>
                    <div class="booking-item">
                        <p><strong><?= htmlspecialchars($r['type_name']) ?></strong></p>
                        <p>Du <?= htmlspecialchars($r['check_in_date']) ?> au <?= htmlspecialchars($r['check_out_date']) ?></p>
                        <p>Statut : <?= htmlspecialchars($r['status']) ?></p>
                        <p>Total : <?= htmlspecialchars($r['total_price']) ?> €</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune réservation trouvée.</p>
            <?php endif; ?>
        </div>

        <!-- Mon Profil -->
        <div class="account-section">
            <h3>Mon Profil</h3>
            <form class="reservation-form auth-form" method="POST" action="update-profile.php">
                <div class="reservation-field">
                    <label for="firstname">Prénom</label>
                    <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($firstname) ?>" readonly>
                </div>

                <div class="reservation-field">
                    <label for="lastname">Nom</label>
                    <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($lastname) ?>" readonly>
                </div>

                <div class="reservation-field full-width">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>

                <div class="reservation-field full-width">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" readonly>
                </div>

                <button type="submit" class="reservation-button full-width">Mettre à jour le profil</button>
            </form>
        </div>
    </section>
</main>

</body>
</html>
