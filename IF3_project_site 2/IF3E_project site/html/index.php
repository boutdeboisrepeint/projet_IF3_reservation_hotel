<?php
// --- Connexion à la base ---
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

// --- Récupération des types de chambres avec un exemple de chambre disponible ---
$sql = "SELECT rt.room_type_id, rt.type_name, rt.description, rt.capacity, rt.base_price,
               MIN(rm.price_per_night) AS price_per_night,
               MIN(rm.status) AS status
        FROM room_type rt
        LEFT JOIN room rm ON rm.room_type_id = rt.room_type_id
        GROUP BY rt.room_type_id, rt.type_name, rt.description, rt.capacity, rt.base_price";
$stmt = $bdd->query($sql);
$room_types = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The UTBM Resort Belfort</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>

<header class="home-page-navbar-container">
    <div class="navbar-inner">
        <div class="navbar-left">
            <a href="#contact" class="navbar-link">CONTACT</a>
        </div>

        <div class="navbar-center">
            <h1 class="navbar-title">
                <span class="line-large">THE UTBM</span><br>
                <span class="line-medium">RESSORT</span><br>
                <span class="line-small">BELFORT</span>
            </h1>
        </div>

        <div class="navbar-right">
            <a href="login.php" class="navbar-button">MON COMPTE</a>
        </div>
    </div>
    <div class="navbar-bottom-line"></div>
    <nav class="navbar-submenu">
        <a href="#our_rooms" class="submenu-link">OUR ROOMS</a>
        <a href="../html/restaurents.php" class="submenu-link">RESTAURANTS</a>
        <a href="" class="submenu-link">Spa & Bien-être</a>
        <a href="#contact" class="submenu-link">Contact</a>
    </nav>
</header>

<div class="home-page-section-main-bg-container">
    <img class="home-page-section-main-bg" src="../img/2654691_xlarge_1ea75b8c.jpg" alt="">
    <h1 class="home-page-section-main-title">Your experts in budget hotels around the world.</h1>
</div>

<section class="home-page-search-section">
    <div class="search-container">
        <div class="search-field">
            <label for="date-arrivee" class="search-label">CHECK-IN</label>
            <input type="date" id="date-arrivee" class="search-input" name="date-arrivee">
        </div>
        <div class="search-field">
            <label for="date-depart" class="search-label">CHECK-OUT</label>
            <input type="date" id="date-depart" class="search-input" name="date-depart">
        </div>
        <div class="search-field">
            <label for="adultes" class="search-label">Adultes</label>
            <input type="number" id="adultes" class="search-input" name="adultes" min="1" value="1">
        </div>
        <div class="search-field">
            <label for="enfants" class="search-label">Enfants</label>
            <input type="number" id="enfants" class="search-input" name="enfants" min="0" value="0">
        </div>
        <button class="search-button">FIND ROOM</button>
    </div>
</section>

<section class="home-page-intro">
    <div class="intro-container">
        <h2 class="intro-title">THIS IS THE MOST ULTRA-LUXURY EXPERIENTIAL RESORT IN THE WORLD</h2>
        <p class="intro-text">Embrace a new standard of luxury, where the highest level of service sets a new benchmark for excellence.</p>
        <p class="intro-text">This is The UTBM.</p>
        <a href="#our_rooms" class="intro-button">BOOK NOW</a>
    </div>
</section>

<main class="rooms-main" id="our_rooms">
    <section class="rooms-container">
        <?php if ($room_types): ?>
            <?php foreach ($room_types as $index => $room): ?>
                <div class="room-card">
                    <?php
                    $images = [
                            'Suite Junior' => '../img/room3.webp',
                            'Chambre Familiale' => '../img/room5.webp',
                            'Chambre Deluxe' => '../img/room1.webp',
                            'Chambre Premium' => '../img/room2.webp',
                            'Suite Senior' => '../img/room4.webp',
                            'Suite Luxe' => '../img/room6.webp'
                    ];
                    $img = $images[$room['type_name']] ?? '../img/default_room.jpg';
                    ?>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($room['type_name']) ?>">
                    <h3 class="room-title"><?= htmlspecialchars($room['type_name']) ?></h3>
                    <p class="room-price"><?= number_format($room['price_per_night'], 2) ?> € / nuit</p>
                    <p class="room-description"><?= htmlspecialchars($room['description']) ?></p>
                    <p class="room-capacity">Capacité : <?= htmlspecialchars($room['capacity']) ?> personnes</p>
                    <p class="room-status">Statut : <?= htmlspecialchars($room['status'] ?? 'Disponible') ?></p>

                    <?php if (strtolower($room['status']) === 'disponible' || $room['status'] === null): ?>
                        <form class="room-reservation-form" action="reservation.php" method="POST">
                            <input type="hidden" name="room_type_id" value="<?= $room['room_type_id'] ?>">
                            <div class="reservation-field">
                                <label>CHECK-IN</label>
                                <input type="date" name="check_in_date" required>
                            </div>
                            <div class="reservation-field">
                                <label>CHECK-OUT</label>
                                <input type="date" name="check_out_date" required>
                            </div>
                            <div class="reservation-field">
                                <label>Nb personnes</label>
                                <input type="number" name="number_of_guest" min="1" value="1" required>
                            </div>
                            <button type="submit" class="reservation-button">Réserver</button>
                        </form>
                    <?php else: ?>
                        <p style="color:red;">Chambres actuellement indisponibles</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">Aucune catégorie de chambre trouvée dans la base.</p>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>© 2025 UTBM Resort Belfort. All rights reserved.</p>
        <p>
            <a href="#" class="footer-link">Privacy Policy</a> |
            <a href="#" class="footer-link">Terms & Conditions</a>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr("input[type='date']", {
        altInput: true,
        altFormat: "d-m-Y",
        dateFormat: "Y-m-d",
        minDate: "today"
    });
</script>

</body>
</html>
