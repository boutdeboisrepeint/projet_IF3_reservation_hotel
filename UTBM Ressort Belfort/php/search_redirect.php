<?php
session_start();
require_once __DIR__.'/config.php';

if (isset($_GET['section']) && $_GET['section'] === 'new-reservation') {
    // Si connecté → aller directement sur l'onglet réservation
    if (isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'guest') {
        header('Location: compte_client.php?section=new-reservation');
        exit();
    }
    // Sinon → mémoriser et rediriger vers login
    $_SESSION['redirect_after_login'] = 'reservation';
    header('Location: ../html/login.html');
    exit();
}

$check_in  = $_GET['check_in']  ?? '';
$check_out = $_GET['check_out'] ?? '';
$adultes   = $_GET['adultes']   ?? 1;
$enfants   = $_GET['enfants']   ?? 0;
$type_id   = $_GET['type_id']   ?? '';
$type_name = trim($_GET['type_name'] ?? '');

if (!$check_in || !$check_out) {
    $_SESSION['errors'] = ["Veuillez sélectionner les dates d'arrivée et de départ."];
    header('Location: ../html/index.html');
    exit();
}

// Résoudre l'ID du type si seul le nom est reçu
if (!$type_id && $type_name !== '') {
    try {
        $st = $pdo->prepare("SELECT room_type_id FROM room_type WHERE type_name = ? LIMIT 1");
        $st->execute([$type_name]);
        if ($row = $st->fetch()) {
            $type_id = (string)$row['room_type_id'];
        }
    } catch (Exception $e) {
        // silencieux
    }
}

// Si déjà connecté en client
if (isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'guest') {
    $q = http_build_query([
        'section'   => 'new-reservation',
        'check_in'  => $check_in,
        'check_out' => $check_out,
        'guests'    => $adultes,
        'type_id'   => $type_id
    ]);
    header("Location: compte_client.php?{$q}");
    exit();
}

// Sinon mémoriser et rediriger vers login
$_SESSION['search_params'] = [
    'check_in'  => $check_in,
    'check_out' => $check_out,
    'adultes'   => $adultes,
    'enfants'   => $enfants,
    'type_id'   => $type_id
];
$_SESSION['redirect_after_login'] = 'search';
header('Location: ../html/login.html');
exit();
