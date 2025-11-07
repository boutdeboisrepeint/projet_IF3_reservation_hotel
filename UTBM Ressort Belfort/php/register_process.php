<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/register.html');
    exit();
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

if (empty($first_name)) {
    $errors[] = "First name is required.";
}

if (empty($last_name)) {
    $errors[] = "Last name is required.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email address is required.";
}

if (empty($phone)) {
    $errors[] = "Phone number is required.";
}

if (empty($date_of_birth)) {
    $errors[] = "Date of birth is required.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("SELECT guest_id FROM guest WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "This email address is already registered.";
        }
        
        $stmt = $pdo->prepare("SELECT guest_id FROM guest WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = "This phone number is already registered.";
        }
    } catch(Exception $e) {
        $errors[] = "Verification Error: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    unset($_SESSION['old_input']['password']);
    unset($_SESSION['old_input']['confirm_password']);
    
    header('Location: ../html/register.html');
    exit();
}

try {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO guest (first_name, last_name, email, phone, adress, password, registration_date, date_of_birth, loyality_points)
        VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, 0)
    ");
    
    $result = $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $address,
        $password_hash,
        $date_of_birth
    ]);
    
    if ($result) {
        $guest_id = $pdo->lastInsertId();
        
        $_SESSION['user_id'] = $guest_id;
        $_SESSION['user_type'] = 'guest';
        $_SESSION['guest_id'] = $guest_id;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        $_SESSION['success'] = "Account created successfully! Welcome to UTBM Resort.";
        header('Location: compte_client.php');
        exit();
    } else {
        $_SESSION['errors'] = ["Registration failed. Please try again."];
        $_SESSION['old_input'] = $_POST;
        header('Location: ../html/register.html');
        exit();
    }
    
} catch(PDOException $e) {
    $_SESSION['errors'] = ["SQL Error: " . $e->getMessage()];
    $_SESSION['old_input'] = $_POST;
    header('Location: ../html/register.html');
    exit();
    
} catch(Exception $e) {
    $_SESSION['errors'] = ["Error: " . $e->getMessage()];
    $_SESSION['old_input'] = $_POST;
    header('Location: ../html/register.html');
    exit();
}
