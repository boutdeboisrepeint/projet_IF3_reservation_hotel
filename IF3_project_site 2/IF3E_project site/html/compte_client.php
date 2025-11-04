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

$id = $_SESSION['user_id'];

// 🔹 Infos utilisateur
$sql = "SELECT first_name, last_name, email, phone FROM guest WHERE guest_id = :id";
$stmt = $bdd->prepare($sql);
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    die("Aucun utilisateur trouvé avec cet ID.");
}

$firstname = $user['first_name'];
$name = $user['last_name'];
$email = $user['email'];
$phone = $user['phone'];

// 🔹 Réservations du client avec le type de chambre
$sql_res = "SELECT r.*, rt.type_name
            FROM reservation r
            JOIN room rm ON r.room_id = rm.room_id
            JOIN room_type rt ON rm.room_type_id = rt.room_type_id
            WHERE r.guest_id = :id
            ORDER BY r.check_in_date DESC";
$stmt_res = $bdd->prepare($sql_res);
$stmt_res->execute(['id' => $id]);
$reservations = $stmt_res->fetchAll();

$success = isset($_GET['success']);
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
        <h2>Bonjour, <?php echo htmlspecialchars($firstname); ?> 👋</h2>

        <?php if ($success): ?>
            <p style="color: green; font-weight: bold; text-align: center;">
                ✅ Profil mis à jour avec succès !
            </p>
        <?php endif; ?>

        <!-- 🔹 Mes réservations -->
        <div class="account-section">
            <h3>Mes Réservations</h3>

            <?php if (count($reservations) > 0): ?>
                <?php foreach ($reservations as $res): ?>
                    <div class="booking-item">
                        <p><strong><?php echo htmlspecialchars($res['type_name']); ?></strong>
                            (<?php echo date('d M Y', strtotime($res['check_in_date'])); ?> -
                            <?php echo date('d M Y', strtotime($res['check_out_date'])); ?>)</p>
                        <p>Statut : <?php echo htmlspecialchars($res['status']); ?></p>
                        <p>Prix total : <?php echo htmlspecialchars($res['total_price']); ?> €</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune réservation pour le moment.</p>
            <?php endif; ?>
        </div>

        <!-- 🔹 Mon profil -->
        <div class="account-section">
            <h3>Mon Profil</h3>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($name); ?></p>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($firstname); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($phone); ?></p>

            <a href="update-profile.php" class="reservation-button full-width">
                Modifier mon profil
            </a>
        </div>

        <!-- 🔹 Feedback -->
        <div class="account-section" id="feedback">
            <h3>Laisser un Avis</h3>
            <form class="reservation-form auth-form" method="POST" action="submit-feedback.php">
                <div class="reservation-field full-width">
                    <label for="rating">Note (sur 5)</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" required>
                </div>
                <div class="reservation-field full-width">
                    <label for="comment">Commentaire</label>
                    <textarea id="comment" name="comment" rows="5"></textarea>
                </div>
                <button type="submit" class="reservation-button full-width">Envoyer l'avis</button>
            </form>
        </div>

    </section>
</main>
</body>
</html>
