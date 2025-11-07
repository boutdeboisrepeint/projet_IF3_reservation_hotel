<?php
require_once __DIR__.'/config.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: ../html/login.html'); exit; }

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$errors = [];

if (!$email || !$password) { $errors[]="Email and password required"; }

if (empty($errors)) {
  $st=$pdo->prepare("SELECT * FROM guest WHERE email=?"); $st->execute([$email]); $g=$st->fetch();
  if ($g && password_verify($password, $g['password'])) {
    $_SESSION['user_id']=$g['guest_id']; $_SESSION['user_type']='guest';
    $_SESSION['guest_id']=$g['guest_id']; $_SESSION['email']=$g['email'];
    $_SESSION['first_name']=$g['first_name']; $_SESSION['last_name']=$g['last_name'];

    if (($_SESSION['redirect_after_login'] ?? '') === 'reservation') {
      unset($_SESSION['redirect_after_login']);
      header('Location: compte_client.php?section=new-reservation'); 
      exit;
    }

    if (($_SESSION['redirect_after_login'] ?? '') === 'search') {
      $p=$_SESSION['search_params'] ?? [];
      unset($_SESSION['redirect_after_login'], $_SESSION['search_params']);
      $check_in  = urlencode($p['check_in'] ?? '');
      $check_out = urlencode($p['check_out'] ?? '');
      $guests    = urlencode($p['adultes'] ?? 1);
      $type_id   = urlencode($p['type_id'] ?? '');
      header("Location: compte_client.php?section=new-reservation&check_in={$check_in}&check_out={$check_out}&guests={$guests}&type_id={$type_id}"); 
      exit;
    }

    header('Location: compte_client.php'); exit;
  }

  $st=$pdo->prepare("SELECT * FROM employee WHERE email=?"); 
  $st->execute([$email]); 
  $e=$st->fetch();

  if ($e && password_verify($password, $e['password'])) {
      $_SESSION['user_id'] = $e['employee_id']; 
      $_SESSION['user_type'] = 'employee';
      $_SESSION['first_name'] = $e['first_name']; 
      $_SESSION['last_name'] = $e['last_name']; 
      $_SESSION['role'] = $e['role'];
      header('Location: admin_dashboard.php'); 
      exit;
  }

}

$_SESSION['errors']=$errors;
header('Location: ../html/login.html'); exit;
