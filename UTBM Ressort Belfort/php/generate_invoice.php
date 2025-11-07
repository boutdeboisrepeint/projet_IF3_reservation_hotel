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
    SELECT r.*, g.first_name, g.last_name, g.email, g.phone, g.adress,
           rm.room_number, rt.type_name, rm.price_per_night
    FROM reservation r
    JOIN guest g ON r.guest_id = g.guest_id
    JOIN room rm ON r.room_id = rm.room_id
    JOIN room_type rt ON rm.room_type_id = rt.room_type_id
    WHERE r.id_reservation = ? AND r.guest_id = ?
");
$stmt->execute([$reservation_id, $_SESSION['guest_id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die('Reservation not found or access denied');
}

$services = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.service_name, rs.price
        FROM reservation_service rs
        JOIN services s ON rs.service_id = s.service_id
        WHERE rs.reservation_id = ?
    ");
    $stmt->execute([$reservation_id]);
    $services = $stmt->fetchAll();
} catch (Exception $e) {
}

$nights = max(1, round((strtotime($reservation['check_out_date']) - strtotime($reservation['check_in_date'])) / 86400));
$room_total = $nights * (float)$reservation['price_per_night'];
$services_total = array_sum(array_column($services, 'price'));
$subtotal = $room_total + $services_total;
$tax_rate = 0.10;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $reservation_id; ?> - UTBM Resort</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .invoice-container { max-width: 900px; margin: 0 auto; background: white; padding: 50px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .invoice-header { text-align: center; border-bottom: 3px solid #003366; padding-bottom: 30px; margin-bottom: 40px; }
        .invoice-header h1 { color: #003366; font-size: 2.5rem; margin-bottom: 10px; }
        .invoice-header .company-info { color: #666; font-size: 0.95rem; line-height: 1.6; }
        .invoice-number { font-size: 1.8rem; color: #003366; margin: 20px 0; font-weight: bold; }
        .invoice-details { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .detail-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #003366; }
        .detail-box h3 { color: #003366; margin-bottom: 15px; font-size: 1.1rem; }
        .detail-box p { margin: 8px 0; color: #333; font-size: 0.95rem; }
        .detail-box strong { color: #003366; display: inline-block; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        thead { background: linear-gradient(135deg, #003366 0%, #0055aa 100%); color: white; }
        th { padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem; }
        td { padding: 15px; border-bottom: 1px solid #e0e0e0; color: #333; }
        tbody tr:hover { background: #f8f9fa; }
        .text-right { text-align: right; }
        .subtotal-row td { font-weight: 600; padding-top: 20px; }
        .tax-row td { color: #666; }
        .total-row { background: #f8f9fa; }
        .total-row td { font-size: 1.3rem; font-weight: bold; color: #003366; padding: 20px 15px; }
        .footer { margin-top: 50px; padding-top: 30px; border-top: 2px solid #e0e0e0; text-align: center; color: #666; }
        .footer p { margin: 10px 0; font-size: 0.9rem; }
        .print-buttons { text-align: center; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 30px; margin: 0 10px; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s; }
        .btn-print { background: #003366; color: white; }
        .btn-print:hover { background: #002244; transform: translateY(-2px); }
        .btn-close { background: #666; color: white; }
        .btn-close:hover { background: #444; }
        @media print {
            body { background: white; padding: 0; }
            .print-buttons { display: none; }
            .invoice-container { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="print-buttons no-print">
    <button onclick="window.print()" class="btn btn-print">Print Invoice</button>
    <a href="compte_client.php?section=reservations" class="btn btn-close">← Back to Reservations</a>
</div>

<div class="invoice-container">
    <div class="invoice-header">
        <h1>UTBM RESORT</h1>
        <div class="company-info">
            <p>123 Resort Avenue, Belfort 90000, France</p>
            <p>Phone: +33 1 23 45 67 89 | Email: contact@utbm-resort.com</p>
            <p>SIRET: 123 456 789 00010 | VAT: FR12345678900</p>
        </div>
        <div class="invoice-number">INVOICE #<?php echo str_pad($reservation_id, 6, '0', STR_PAD_LEFT); ?></div>
        <p style="color:#666; font-size:0.9rem;">Issue Date: <?php echo date('F d, Y'); ?></p>
    </div>

    <div class="invoice-details">
        <div class="detail-box">
            <h3>Bill To:</h3>
            <p><strong>Name:</strong> <?php echo h($reservation['first_name'] . ' ' . $reservation['last_name']); ?></p>
            <p><strong>Address:</strong> <?php echo h($reservation['adress'] ?? 'N/A'); ?></p>
            <p><strong>Email:</strong> <?php echo h($reservation['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo h($reservation['phone']); ?></p>
        </div>
        
        <div class="detail-box">
            <h3>Reservation Details:</h3>
            <p><strong>ID:</strong> #<?php echo $reservation_id; ?></p>
            <p><strong>Room:</strong> <?php echo h($reservation['type_name']); ?> #<?php echo h($reservation['room_number']); ?></p>
            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></p>
            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></p>
            <p><strong>Nights:</strong> <?php echo $nights; ?></p>
            <p><strong>Guests:</strong> <?php echo $reservation['number_of_guest']; ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong><?php echo h($reservation['type_name']); ?></strong> - Room #<?php echo h($reservation['room_number']); ?>
                    <br><small style="color:#666;">Accommodation from <?php echo date('M d', strtotime($reservation['check_in_date'])); ?> to <?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></small>
                </td>
                <td class="text-right"><?php echo $nights; ?></td>
                <td class="text-right">€<?php echo number_format($reservation['price_per_night'], 2); ?></td>
                <td class="text-right"><strong>€<?php echo number_format($room_total, 2); ?></strong></td>
            </tr>
            
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td>
                        <?php echo h($service['service_name']); ?>
                        <br><small style="color:#666;">Additional Service</small>
                    </td>
                    <td class="text-right">1</td>
                    <td class="text-right">€<?php echo number_format($service['price'], 2); ?></td>
                    <td class="text-right"><strong>€<?php echo number_format($service['price'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <tr class="subtotal-row">
                <td colspan="3" class="text-right">Subtotal:</td>
                <td class="text-right"><strong>€<?php echo number_format($subtotal, 2); ?></strong></td>
            </tr>
            <tr class="tax-row">
                <td colspan="3" class="text-right">VAT (10%):</td>
                <td class="text-right">€<?php echo number_format($tax, 2); ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL AMOUNT:</td>
                <td class="text-right">€<?php echo number_format($total, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Payment Terms:</strong> Payment is due at check-out. We accept cash, credit cards, and bank transfers.</p>
        <p style="margin-top:20px; font-size:1.1rem; color:#003366;"><strong>Thank you for choosing UTBM Resort!</strong></p>
        <p style="margin-top:20px; font-size:0.85rem;">For any questions regarding this invoice, please contact us at billing@utbm-resort.com</p>
    </div>
</div>

</body>
</html>
