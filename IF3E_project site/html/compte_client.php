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

<?php
// 🔧 Paramètres de connexion
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

$email = $_GET['email'];
echo $email;

$sql = "SELECT first_name, last_name, guest_id, phone, login FROM guest WHERE email = :email";
$stmt = $bdd->prepare($sql);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    die("Aucun utilisateur trouvé avec cet ID.");
}

// Variables contenant les vraies données
$firstname = $user['first_name'];
$name = $user['last_name'];
$email = $user['email'];
$phone = $user['phone'];
$login = $user['login'];
?>

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
            <a href="login.php" class="navbar-link">DÉCONNEXION</a>
        </div>
    </div>
</header>

<main class="reservation-main">
    <section class="reservation-container form-container account-container">
        <h2>Bonjour, <?php echo htmlspecialchars($firstname); ?> </h2>

        <div class="account-section">
            <h3>Mes Réservations</h3>

            <div class="booking-item">
                <p><strong>Suite Junior</strong> (20 Oct 2025 - 25 Oct 2025)</p>
                <p>Statut : Confirmé</p>
                <a href="#" class="btn-cancel">Annuler</a>
            </div>

            <div class="booking-item">
                <p><strong>Chambre Deluxe</strong> (15 Juin 2025 - 16 Juin 2025)</p>
                <p>Statut : Terminé</p>
                <a href="#feedback" class="btn-review">Laisser un avis</a>
            </div>
        </div>

        <div class="account-section">
            <h3>Mon Profil</h3>

            <form class="reservation-form auth-form" method="POST" action="update-profile.php">
                <div class="reservation-field">
                    <label for="firstname">Prénom</label>
                    <input type="text" id="firstname" name="firstname"
                           value="<?php echo htmlspecialchars($firstname); ?>" readonly>
                </div>

                <div class="reservation-field">
                    <label for="lastname">Nom</label>
                    <input type="text" id="lastname" name="lastname"
                           value="<?php echo htmlspecialchars($name); ?>" readonly>
                </div>

                <div class="reservation-field full-width">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>

                <div class="reservation-field full-width">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($phone); ?>" readonly>
                </div>



                <button type="submit" class="reservation-button full-width">Mettre à jour le profil</button>
            </form>
        </div>

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
