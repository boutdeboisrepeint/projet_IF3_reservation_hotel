<?php
session_start();

// Vérifie que l'utilisateur est connecté
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

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser la Réservation - UTBM Resort</title>
    <link rel="stylesheet" href="../css/reservation.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

<header class="home-page-navbar-container scrolled">
    <div class="navbar-inner">
        <div class="navbar-left">
            <a href="index.php" class="navbar-link">ANNULER</a>
        </div>
        <div class="navbar-center">
            <h1 class="navbar-title">
                <span class="line-large">THE UTBM</span><br>
                <span class="line-medium">RESSORT</span><br>
                <span class="line-small">BELFORT</span>
            </h1>
        </div>
    </div>
</header>

<main class="reservation-main">
    <section class="reservation-container form-container">
        <h2>Finaliser votre réservation</h2>

        <div class="checkout-summary">
            <h3>Votre Séjour</h3>
            <div class="summary-item">
                <span>Chambre :</span>
                <strong>Suite Junior</strong>
            </div>
            <div class="summary-item">
                <span>Check-in :</span>
                <strong>20 Octobre 2025</strong>
            </div>
            <div class="summary-item">
                <span>Check-out :</span>
                <strong>25 Octobre 2025</strong>
            </div>
            <div class="summary-item">
                <span>Hôtes :</span>
                <strong>2 Adultes, 1 Enfant</strong>
            </div>
            <hr>
            <div class="summary-item total">
                <span>Total (5 nuits) :</span>
                <strong>€1750.00</strong>
            </div>
        </div>

        <!-- 🔒 Le formulaire de paiement -->
        <form class="reservation-form auth-form" action="reservation-process.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

            <h3>Paiement</h3>

            <div class="reservation-field full-width">
                <label for="card-name">Nom sur la carte</label>
                <input type="text" id="card-name" name="card-name" required>
            </div>

            <div class="reservation-field full-width">
                <label for="card-number">Numéro de carte</label>
                <input type="text" id="card-number" name="card-number" placeholder="0000 0000 0000 0000" required>
            </div>

            <div class="reservation-field">
                <label for="card-expiry">Date d'expiration</label>
                <input type="text" id="card-expiry" name="card-expiry" placeholder="MM/AA" required>
            </div>

            <div class="reservation-field">
                <label for="card-cvc">CVC</label>
                <input type="text" id="card-cvc" name="card-cvc" placeholder="123" required>
            </div>

            <button type="submit" class="reservation-button full-width">Payer et Confirmer</button>
        </form>
    </section>
</main>

</body>
</html>
