<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - UTBM Resort</title>
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
        <h2>Connexion</h2>
        <p class="form-subtitle">Accédez à votre compte pour gérer vos réservations.</p>

        <form class="reservation-form auth-form" action="login-process.php" method="POST">

            <div class="reservation-field full-width">
                <label for="identifier">Email ou nom d'utilisateur</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>

            <div class="reservation-field full-width">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="reservation-button full-width">Se Connecter</button>
        </form>

        <div class="form-links">
            <a href="register.php">Pas encore de compte ? S'inscrire</a>
            <a href="#">Mot de passe oublié ?</a>
        </div>
    </section>
</main>

</body>
</html>
