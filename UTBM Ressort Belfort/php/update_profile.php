<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guest' || empty($_SESSION['guest_id'])) {
    $_SESSION['errors'] = ["Please log in to update your profile."];
    header('Location: ../html/login.html');
    exit();
}

$first_name    = trim($_POST['first_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$email         = trim($_POST['email'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$adress        = trim($_POST['adress'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');

$errors = [];

if ($first_name === '' || $last_name === '' || $email === '' || $phone === '') {
    $errors[] = "Tous les champs obligatoires doivent être remplis.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email is not valid.";
}

if (empty($errors)) {
    try {
        $st = $pdo->prepare("SELECT guest_id FROM guest WHERE (email = ? OR phone = ?) AND guest_id <> ?");
        $st->execute([$email, $phone, $_SESSION['guest_id']]);
        if ($st->fetch()) {
            $errors[] = "Email or phone number already in use.";
        }
    } catch (Exception $e) {
        $errors[] = "Duplication verification error: " . $e->getMessage();
    }
}

$hasDob = false;
try {
    $chk = $pdo->prepare("
      SELECT COUNT(*) c 
      FROM INFORMATION_SCHEMA.COLUMNS 
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'guest'
        AND COLUMN_NAME = 'date_of_birth'
    ");
    $chk->execute();
    $hasDob = ((int)$chk->fetch()['c'] > 0);
} catch (Exception $e) {
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: compte_client.php?section=profile');
    exit();
}

try {
    if ($hasDob) {
        if ($date_of_birth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
            $ts = strtotime($date_of_birth);
            $date_of_birth = $ts ? date('Y-m-d', $ts) : null;
        } elseif ($date_of_birth === '') {
            $date_of_birth = null;
        }

        $sql = "UPDATE guest 
                SET first_name=?, last_name=?, email=?, phone=?, adress=?, date_of_birth=? 
                WHERE guest_id=?";
        $params = [$first_name, $last_name, $email, $phone, $adress, $date_of_birth, $_SESSION['guest_id']];
    } else {
        $sql = "UPDATE guest 
                SET first_name=?, last_name=?, email=?, phone=?, adress=? 
                WHERE guest_id=?";
        $params = [$first_name, $last_name, $email, $phone, $adress, $_SESSION['guest_id']];
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);

    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name']  = $last_name;
    $_SESSION['email']      = $email;

    $_SESSION['success'] = "Profile updated successfully.";
    header('Location: compte_client.php?section=profile');
    exit();

} catch (Exception $e) {
    $_SESSION['errors'] = ["Error while updating " . $e->getMessage()];
    header('Location: compte_client.php?section=profile');
    exit();
}
