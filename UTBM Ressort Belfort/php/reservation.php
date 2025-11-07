<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'guest') {
    header('Location: ../html/login.html');
    exit();
}

$stmt = $pdo->query("SELECT * FROM room_type ORDER BY base_price");
$room_types = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - UTBM Resort</title>
    <link rel="stylesheet" href="../css/reservation.css">
    <style>
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .room-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .price { font-size: 1.5em; color: #d4af37; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #d4af37; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <header>
        <h1>Book a Room</h1>
        <nav>
            <a href="compte_client.php">My Account</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    
    <main>
        <form id="searchForm">
            <h2>Research</h2>
            <div class="form-group">
                <label>Check-in</label>
                <input type="date" id="check_in" required>
            </div>
            <div class="form-group">
                <label>Check-out</label>
                <input type="date" id="check_out" required>
            </div>
            <div class="form-group">
                <label>Type:</label>
                <select id="type_id">
                    <option value="">All</option>
                    <?php foreach ($room_types as $type): ?>
                        <option value="<?php echo $type['room_type_id']; ?>">
                            <?php echo htmlspecialchars($type['type_name']); ?> - <?php echo $type['base_price']; ?>€
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Research</button>
        </form>
        
        <div id="results"></div>
    </main>
    
    <script>
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('check_in').setAttribute('min', today);
        document.getElementById('check_out').setAttribute('min', today);
        
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const typeId = document.getElementById('type_id').value;
            
            fetch(`get_available_rooms.php?check_in=${checkIn}&check_out=${checkOut}&type_id=${typeId}`)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('results');
                    
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<p>Aucune chambre disponible.</p>';
                        return;
                    }
                    
                    let html = '<h2>Chambres Disponibles</h2><div class="rooms-grid">';
                    data.forEach(room => {
                        html += `
                            <div class="room-card">
                                <h3>${room.type_name}</h3>
                                <p>Chambre ${room.room_number}</p>
                                <p>${room.description || ''}</p>
                                <p>Capacité: ${room.capacity} pers.</p>
                                <p class="price">${parseFloat(room.price_per_night).toFixed(2)}€/nuit</p>
                                <form action="reservation_process.php" method="POST">
                                    <input type="hidden" name="room_id" value="${room.room_id}">
                                    <input type="hidden" name="check_in" value="${checkIn}">
                                    <input type="hidden" name="check_out" value="${checkOut}">
                                    <div class="form-group">
                                        <label>People:</label>
                                        <input type="number" name="num_guests" min="1" max="${room.capacity}" value="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Services:</label>
                                        <?php foreach ($services as $s): ?>
                                            <label style="display:block;">
                                                <input type="checkbox" name="services[]" value="<?php echo $s['service_id']; ?>">
                                                <?php echo htmlspecialchars($s['service_name']); ?> (<?php echo $s['price']; ?>€)
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit">Réserver</button>
                                </form>
                            </div>
                        `;
                    });
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                });
        });
    </script>
</body>
</html>
