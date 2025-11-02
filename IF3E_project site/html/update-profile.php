<?php
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

$email = trim($_POST['email'] ?? 'antoine.mathieu@utbm.fr');
$sql = 'select * from guest where email = :email LIMIT 1';
$stmt = $bdd->prepare($sql);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

?>
<main class="reservation-main">
    <section class="reservation-container form-container">
        <h2>Mettre à jour le profil</h2>
        <p class="form-subtitle">Modifiez vos informations personnelles ci-dessous.</p>

        <form class="reservation-form auth-form" method="POST" action="update-profile-process.php">

            <div class="reservation-field full-width">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>" required>
            </div >

            <div class="reservation-field full-width">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
            </div>

            <button type="submit" class="reservation-button full-width">Mettre à jour le profil</button>
        </form>