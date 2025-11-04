<?php
session_start();

// Connexion à la base
try {
    $bdd = new PDO("mysql:host=localhost;dbname=gestion_reservation_hotel;charset=utf8", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des chambres
$sql = "SELECT r.room_id, r.price_per_night, r.status, t.type_name, t.description, t.capacity 
        FROM room r
        INNER JOIN room_type t ON r.room_type_id = t.room_type_id";
$rooms = $bdd->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>UTBM Resort Belfort</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header class="home-page-navbar-container">
    <h1 class="navbar-title">THE UTBM RESSORT BELFORT</h1>
    <a href="compte_client.php" class="navbar-button">Mon compte</a>
</header>

<main class="rooms-main" id="our_rooms">
    <section class="rooms-container">
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <img src="../img/room<?= $room['room_id'] ?>.webp" alt="<?= htmlspecialchars($room['type_name']) ?>">
                <h3 class="room-title"><?= htmlspecialchars($room['type_name']) ?></h3>
                <p class="room-price">€<?= htmlspecialchars($room['price_per_night']) ?> / nuit</p>
                <p class="room-description"><?= htmlspecialchars($room['description']) ?></p>
                <p class="room-capacity">Capacité : <?= htmlspecialchars($room['capacity']) ?> personnes</p>
                <p class="room-status">État : <?= htmlspecialchars($room['status']) ?></p>

                <form class="room-reservation-form" action="reservation-process.php" method="POST">
                    <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                    <div class="reservation-field">
                        <label for="checkin<?= $room['room_id'] ?>">CHECK-IN</label>
                        <input type="date" id="checkin<?= $room['room_id'] ?>" name="check_in_date" required>
                    </div>
                    <div class="reservation-field">
                        <label for="checkout<?= $room['room_id'] ?>">CHECK-OUT</label>
                        <input type="date" id="checkout<?= $room['room_id'] ?>" name="check_out_date" required>
                    </div>
                    <div class="reservation-field">
                        <label for="adultes<?= $room['room_id'] ?>">Adultes</label>
                        <input type="number" id="adultes<?= $room['room_id'] ?>" name="nb_adultes" min="1" value="1">
                    </div>
                    <div class="reservation-field">
                        <label for="enfants<?= $room['room_id'] ?>">Enfants</label>
                        <input type="number" id="enfants<?= $room['room_id'] ?>" name="nb_enfants" min="0" value="0">
                    </div>
                    <button type="submit" class="reservation-button">Réserver</button>
                </form>
            </div>
        <?php endforeach; ?>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>© 2025 UTBM Resort Belfort. Tous droits réservés.</p>
    </div>
</footer>
</body>
</html>
