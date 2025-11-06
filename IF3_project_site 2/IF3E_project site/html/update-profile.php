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

$id = trim($_POST['id']?? 2);
$email = trim($_POST['email'] ?? 'antoine.mathieu@utbm.fr');
$firstname = trim($_POST['firstname'] ?? 'antoine');
$name = trim($_POST['lastname'] ?? 'mathieu');
$phone = trim($_POST['phone'] ?? '0706040502');
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
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
            </div >

            <div class="reservation-field full-width">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="reservation-field full-width">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['guest_id']); ?>">

            <button type="submit" class="reservation-button full-width">Mettre à jour le profil</button>
        </form>