<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guest') {
    header('Location: ../html/login.html');
    exit();
}

$reservation_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.*, rm.room_number, rt.type_name, rm.price_per_night
    FROM reservation r
    JOIN room rm ON r.room_id = rm.room_id
    JOIN room_type rt ON rm.room_type_id = rt.room_type_id
    WHERE r.id_reservation = ? AND r.guest_id = ? AND r.status IN ('pending', 'confirmed')
");
$stmt->execute([$reservation_id, $_SESSION['guest_id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    $_SESSION['errors'] = ["Reservation not found or cannot be modified."];
    header('Location: compte_client.php?section=reservations');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_room_id = (int)$_POST['room_id'];
        $new_checkout = $_POST['check_out_date'];
        $new_guests = (int)$_POST['number_of_guest'];
        
        if (strtotime($new_checkout) <= strtotime($reservation['check_in_date'])) {
            throw new Exception("Check-out date must be after check-in date.");
        }
        
        $stmt = $pdo->prepare("
            SELECT r.room_id, r.price_per_night, rt.capacity
            FROM room r
            JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE r.room_id = ?
        ");
        $stmt->execute([$new_room_id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            throw new Exception("Room not found.");
        }
        
        if ($new_guests > $room['capacity']) {
            throw new Exception("Number of guests ({$new_guests}) exceeds room capacity ({$room['capacity']}).");
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM reservation
            WHERE room_id = ?
              AND id_reservation != ?
              AND status IN ('pending','confirmed','checked_in')
              AND NOT (check_out_date <= ? OR check_in_date >= ?)
        ");
        $stmt->execute([$new_room_id, $reservation_id, $reservation['check_in_date'], $new_checkout]);
        $conflicts = (int)$stmt->fetch()['c'];
        
        if ($conflicts > 0) {
            throw new Exception("This room is not available for the selected dates.");
        }
        
        $nights = max(1, round((strtotime($new_checkout) - strtotime($reservation['check_in_date'])) / 86400));
        $new_total = $nights * $room['price_per_night'];
        
        try {
            $stmt = $pdo->prepare("SELECT SUM(price) as services_total FROM reservation_service WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $services_total = $stmt->fetch()['services_total'] ?? 0;
            $new_total += $services_total;
        } catch (Exception $e) {
        }
        
        $stmt = $pdo->prepare("
            UPDATE reservation 
            SET room_id = ?, check_out_date = ?, number_of_guest = ?, total_price = ? 
            WHERE id_reservation = ?
        ");
        $stmt->execute([$new_room_id, $new_checkout, $new_guests, $new_total, $reservation_id]);
        
        $_SESSION['success'] = "Reservation modified successfully!";
        header('Location: compte_client.php?section=reservations');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['errors'] = [$e->getMessage()];
    }
}

$stmt = $pdo->prepare("
    SELECT r.*, rt.type_name, rt.capacity, rt.description
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.room_id NOT IN (
        SELECT room_id FROM reservation 
        WHERE status IN ('pending', 'confirmed', 'checked_in')
        AND NOT (check_out_date <= ? OR check_in_date >= ?)
    )
    ORDER BY rt.type_name, r.room_number
");
$stmt->execute([$reservation['check_in_date'], $reservation['check_out_date']]);
$rooms = $stmt->fetchAll();


function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Modify Reservation #<?php echo $reservation_id; ?> - UTBM Resort</title>
        <link rel="stylesheet" href="../css/style.css">
        <style>
            body { background: #f5f5f5; font-family: 'Segoe UI', Arial, sans-serif; }
            .modify-container { max-width: 900px; margin: 50px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .page-header { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 3px solid #003366; }
            .page-header h1 { color: #003366; font-size: 2rem; margin-bottom: 10px; }
            .page-header p { color: #666; }
            .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; }
            .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            .current-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #003366; }
            .current-info h3 { color: #003366; margin-bottom: 15px; }
            .current-info p { margin: 8px 0; color: #333; }
            .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 25px; }
            .form-group { display: flex; flex-direction: column; }
            .form-group label { font-weight: 600; margin-bottom: 8px; color: #333; font-size: 0.95rem; }
            .form-group input, .form-group select { padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
            .form-group input:focus, .form-group select:focus { outline: none; border-color: #003366; }
            .form-group select { cursor: pointer; }
            .form-group small { color: #666; margin-top: 5px; font-size: 0.85rem; }
            .button-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
            .btn { padding: 14px 35px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
            .btn-submit { background: linear-gradient(135deg, #003366 0%, #0055aa 100%); color: white; }
            .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,51,102,0.3); }
            .btn-cancel { background: #6c757d; color: white; }
            .btn-cancel:hover { background: #545b62; }
        </style>
    </head>
    <body>
        <div class="modify-container">
            <div class="page-header">
                <h1>Modify Reservation</h1>
                <p>Update your reservation details below</p>
            </div>
            
            <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <p><?php echo h($error); ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['errors']); ?>
                </div>
            <?php endif; ?>
            
            <div class="current-info">
                <h3>Current Reservation Details:</h3>
                <p><strong>Reservation ID:</strong> #<?php echo $reservation_id; ?></p>
                <p><strong>Current Room:</strong> <?php echo h($reservation['type_name']); ?> - Room #<?php echo h($reservation['room_number']); ?></p>
                <p><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($reservation['check_in_date'])); ?></p>
                <p><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($reservation['check_out_date'])); ?></p>
                <p><strong>Guests:</strong> <?php echo $reservation['number_of_guest']; ?></p>
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="room_id">Select New Room:</label>
                        <select name="room_id" id="room_id" required>
                            <option value="<?php echo $reservation['room_id']; ?>"><?php echo h($reservation['type_name']); ?> - Room #<?php echo h($reservation['room_number']); ?> (Current)</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>">
                                    <?php echo h($room['type_name']); ?> - Room #<?php echo h($room['room_number']); ?> 
                                    (Capacity: <?php echo $room['capacity']; ?> | €<?php echo number_format($room['price_per_night'], 2); ?>/night)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choose a different room type if needed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="check_out_date">New Check-out Date:</label>
                        <input type="date" 
                            name="check_out_date" 
                            id="check_out_date" 
                            value="<?php echo $reservation['check_out_date']; ?>" 
                            min="<?php echo date('Y-m-d', strtotime($reservation['check_in_date'] . ' +1 day')); ?>" 
                            required>
                        <small>Extend or shorten your stay</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="number_of_guest">Number of Guests:</label>
                        <input type="number" 
                            name="number_of_guest" 
                            id="number_of_guest" 
                            value="<?php echo $reservation['number_of_guest']; ?>" 
                            min="1" 
                            max="10"
                            required>
                        <small>Must not exceed room capacity</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Check-in Date (Fixed):</label>
                        <input type="date" value="<?php echo $reservation['check_in_date']; ?>" disabled style="background:#f5f5f5;cursor:not-allowed;">
                        <small>Check-in date cannot be changed</small>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Save Changes</button>
                    <a href="compte_client.php?section=reservations" class="btn btn-cancel">✕ Cancel</a>
                </div>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const roomSelect = document.getElementById('room_id');
                const checkoutInput = document.getElementById('check_out_date');
                const checkinDate = new Date('<?php echo $reservation['check_in_date']; ?>');
                
                function calculatePrice() {
                    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                    const roomText = selectedOption.text;
                    const priceMatch = roomText.match(/€([\d,\.]+)/);
                    
                    if (priceMatch) {
                        const pricePerNight = parseFloat(priceMatch[1].replace(',', ''));
                        const checkoutDate = new Date(checkoutInput.value);
                        const nights = Math.max(1, Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24)));
                        const total = nights * pricePerNight;
                        
                        console.log(`Estimated: ${nights} night(s) × €${pricePerNight} = €${total.toFixed(2)}`);
                    }
                }
                
                roomSelect.addEventListener('change', calculatePrice);
                checkoutInput.addEventListener('change', calculatePrice);
            });
        </script>
    </body>
</html>
