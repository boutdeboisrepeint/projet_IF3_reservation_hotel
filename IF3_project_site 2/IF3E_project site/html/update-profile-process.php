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
$new_email = trim($_POST['email'] ?? 'antoine.mathieu@utbm.fr');
$new_firstname = trim($_POST['firstname'] ?? 'antoine');
$new_name = trim($_POST['lastname'] ?? 'mathieu');
$new_phone = trim($_POST['phone'] ?? '0706040502');

$sql = 'UPDATE guest SET email = :email, first_name = :firstname, last_name = :lastname, phone = :phone WHERE guest_id = :id'
;
$stmt = $bdd->prepare($sql);
$stmt->execute([
    'email' => $new_email,
    'firstname' => $new_firstname,
    'lastname' => $new_name,
    'phone' => $new_phone,
    'id' => $id
]);
$user = $stmt->fetch();

header("Location: ccompte_client.php?email=$new_email");

?>