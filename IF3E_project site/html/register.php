<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - UTBM Resort</title>
    <link rel="stylesheet" href="../css/reservation.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

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
    </div>
</header>

<main class="reservation-main">
    <section class="reservation-container form-container">
        <h2>Créer un Compte</h2>
        <p class="form-subtitle">Rejoignez-nous pour une expérience de réservation simplifiée.</p>
        
        <form class="reservation-form auth-form" action="register-process.php" method="POST" action="register-process.php" method="post">
            
            <div class="reservation-field">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            
            <div class="reservation-field">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>

            <div class="reservation-field full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="reservation-field full-width">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="reservation-field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="reservation-field">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="reservation-button full-width">S'inscrire</button>
        </form>
        <div class="form-links">
            <a href="login.php">Déjà un compte ? Se connecter</a>
        </div>
    </section>
</main>

</body>
</html>