<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UTBM Resort</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-grid-container">
    
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h3>UTBM Resort</h3>
            <span>Admin Panel</span>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="active">Tableau de bord</a>
            <a href="admin_gestion_reservations.php">Gérer les Réservations</a> <a href="admin_gestion_chambres.php">Gérer les Chambres</a> <a href="admin_gestion_clients.php">Gérer les Clients</a> <a href="admin_checkinout.php">Check-in / Check-out</a> <a href="admin_rapports.php">Rapports & Stats</a> <a href="index.php">Retour au site</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Tableau de bord</h2>
            <div class="admin-user">
                <span>Connecté en tant que: <strong>Manager</strong></span>
            </div>
        </header>

        <section class="admin-content">
            <div class="stat-cards-container">
                <div class="stat-card">
                    <h4>Taux d'occupation</h4>
                    <p>75%</p> </div>
                <div class="stat-card">
                    <h4>Arrivées prévues (24h)</h4>
                    <p>12</p>
                </div>
                <div class="stat-card">
                    <h4>Chambres à nettoyer</h4>
                    <p>5</p> </div>
                <div class="stat-card">
                    <h4>Avis en attente</h4>
                    <p>3</p> </div>
            </div>

            <div class="quick-actions">
                <h3>Actions Rapides</h3>
                <a href="admin_checkinout.php" class="action-btn">Effectuer un Check-in</a>
                <a href="admin_gestion_chambres.php" class="action-btn">Modifier statut chambre</a>
                <a href="admin_gestion_reservations.php" class="action-btn">Voir les réservations</a>
            </div>

        </section>
    </main>

</div>

</body>
</html>