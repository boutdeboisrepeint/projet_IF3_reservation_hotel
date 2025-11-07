<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'guest') {
    header('Location: ../html/login.html');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM guest WHERE guest_id = ?");
$stmt->execute([$_SESSION['guest_id']]);
$guest = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT r.*, rm.room_number, rt.type_name, p.status AS payment_status, p.amount
    FROM reservation r
    JOIN room rm ON r.room_id = rm.room_id
    JOIN room_type rt ON rm.room_type_id = rt.room_type_id
    LEFT JOIN payment p ON r.id_reservation = p.reservation_id
    WHERE r.guest_id = ?
    ORDER BY r.booking_date DESC
");
$stmt->execute([$_SESSION['guest_id']]);
$reservations = $stmt->fetchAll();

$feedbacks_given = [];
try {
    $stmt = $pdo->prepare("SELECT reservation_id FROM feedback WHERE guest_id = ?");
    $stmt->execute([$_SESSION['guest_id']]);
    $feedbacks_given = array_column($stmt->fetchAll(), 'reservation_id');
} catch (Exception $e) {}

$stmt = $pdo->query("SELECT * FROM room_type ORDER BY base_price");
$room_types = $stmt->fetchAll();

$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
    $services = $stmt->fetchAll();
} catch (Exception $e) {}

$reservations_to_review = array_filter($reservations, function($res) use ($feedbacks_given) {
    return $res['status'] === 'completed' && !in_array($res['id_reservation'], $feedbacks_given);
});

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>My Account - UTBM Resort</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
      .account-section { background:#fff; border-radius:8px; padding:25px; margin-bottom:25px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
      .account-section h3 { margin:0 0 16px 0; border-bottom:2px solid #d4af37; padding-bottom:8px; }
      .stats-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:20px; }
      .stat-box { background:linear-gradient(135deg,#d4af37,#b8941f); color:#fff; padding:16px; border-radius:8px; text-align:center; }
      .stat-box h4 { margin:0 0 6px 0; font-size:.95em; opacity:.9; }
      .stat-box p { margin:0; font-size:1.8em; font-weight:700; }
      .booking-item { background:#f9f9f9; border-left:4px solid #d4af37; padding:14px; border-radius:6px; margin-bottom:12px; }
      .status-badge { display:inline-block; padding:4px 10px; border-radius:12px; font-size:.85em; font-weight:600; }
      .status-confirmed{ background:#e3f2fd; color:#1976d2; } .status-checked_in{ background:#e8f5e9; color:#388e3c; }
      .status-completed{ background:#f5f5f5; color:#616161; } .status-pending{ background:#fff3e0; color:#f57c00; }
      .status-cancelled{ background:#ffebee; color:#d32f2f; }
      .btn-action{ background:#2196f3; color:#fff; border:none; border-radius:4px; padding:8px 14px; cursor:pointer; }
      .btn-cancel{ background:#ff5252; color:#fff; border:none; border-radius:4px; padding:8px 14px; cursor:pointer; }
      .btn-review{ background:#d4af37; color:#fff; border:none; border-radius:4px; padding:8px 14px; cursor:pointer; }
      .no-content{ text-align:center; color:#777; padding:24px; }
      .search-form { background:#f9f9f9; padding:16px; border-radius:8px; margin-bottom:16px; }
      .search-form-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; }
      .form-group{ margin-bottom:12px; } .form-group label{ display:block; margin-bottom:6px; font-weight:600; }
      .content-section { display:none; }
      .content-section.active { display:block; }
      .results-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:24px; margin-top:24px; }
      .modern-room-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.08); transition:transform .3s ease, box-shadow .3s ease; }
      .modern-room-card:hover { transform:translateY(-6px); box-shadow:0 12px 32px rgba(0,0,0,.15); }
      .room-image-container { position:relative; width:100%; height:240px; overflow:hidden; background:#e9ecef; }
      .room-image-container img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .3s ease; }
      .modern-room-card:hover .room-image-container img { transform:scale(1.05); }
      .room-overlay { position:absolute; inset:0; background:linear-gradient(180deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.15) 50%,rgba(0,0,0,.45) 100%); pointer-events:none; }
      .room-number-badge { position:absolute; top:14px; left:14px; background:rgba(0,51,102,.93); color:#fff; padding:7px 14px; border-radius:24px; font-size:.85rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; box-shadow:0 4px 12px rgba(0,0,0,.2); z-index:2; }
      .room-price-badge { position:absolute; bottom:14px; right:14px; background:rgba(255,255,255,.96); color:#003366; padding:10px 16px; border-radius:10px; font-weight:800; font-size:1.15rem; box-shadow:0 6px 16px rgba(0,0,0,.18); z-index:2; backdrop-filter:blur(4px); }
      .room-details { padding:24px; }
      .room-type-name { font-size:1.35rem; font-weight:800; color:#1a202c; margin:0 0 6px 0; line-height:1.3; }
      .room-capacity-info { font-size:.92rem; color:#64748b; display:flex; align-items:center; gap:6px; margin:0 0 10px 0; }
      .room-description-text { font-size:.94rem; color:#475569; line-height:1.6; margin:10px 0 14px 0; }
      .room-amenities { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
      .amenity-chip { background:#f1f5f9; color:#334155; padding:6px 12px; border-radius:16px; font-size:.82rem; font-weight:500; border:1px solid #e2e8f0; }
      .room-booking-form { border-top:1px solid #e2e8f0; padding-top:18px; margin-top:auto; }
      .form-row { margin-bottom:16px; }
      .form-row label { display:block; font-weight:600; font-size:.92rem; color:#334155; margin-bottom:7px; }
      .form-row input[type="number"] { width:100%; padding:11px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:.95rem; }
      .services-checkboxes { max-height:200px; overflow-y:auto; padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
      .service-option { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; padding:8px; }
      .service-option input[type="checkbox"] { width:18px; height:18px; margin:2px 0 0 0; cursor:pointer; }
      .service-option label { cursor:pointer; flex:1; margin:0!important; font-weight:400!important; font-size:.9rem; color:#475569; line-height:1.5; }
      .reserve-btn { width:100%; background:linear-gradient(135deg,#003366,#004d99); color:#fff; border:none; padding:14px 20px; border-radius:10px; font-weight:700; font-size:1.02rem; cursor:pointer; margin-top:16px; text-transform:uppercase; letter-spacing:.5px; box-shadow:0 4px 12px rgba(0,51,102,.2); transition:all .3s ease; }
      .reserve-btn:hover { background:linear-gradient(135deg,#002244,#003d7a); transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,51,102,.3); }
      @media(max-width:768px){ .results-grid{grid-template-columns:1fr;} .room-image-container{height:200px;} }
      .alert {padding: 14px 18px;border-radius: 10px;margin: 20px 0;transition: opacity 0.5s ease, transform 0.5s ease;opacity: 1;transform: translateY(0);position: relative;font-weight: 500;box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);}
      .alert.fade-out {opacity: 0;transform: translateY(-20px);}
      .alert-success {background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);color: #155724;border-left: 5px solid #28a745;}
      .alert-error {background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);color: #721c24;border-left: 5px solid #dc3545;}
      .alert-info {background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);color: #0c5460;border-left: 5px solid #17a2b8;}
      #profile .form-group {margin-bottom: 18px;}
      #profile .form-group label {display: block;font-weight: 600;color: #333;margin-bottom: 8px;font-size: 0.95rem;}
      #profile .form-group input,
      #profile .form-group textarea,
      #profile .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color 0.2s ease;
      }

      #profile .form-group input:focus,
      #profile .form-group textarea:focus,
      #profile .form-group select:focus {
        outline: none;
        border-color: #003366;
        box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
      }

      #profile .form-group textarea {
        resize: none;
        min-height: 80px;
      }

      #profile .btn-action {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #003366 0%, #0055aa 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
      }

      #profile .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 51, 102, 0.3);
      }

    </style>
  </head>
  <body>
    <div class="admin-grid-container">
      <aside class="admin-sidebar">
        <div class="sidebar-header">
          <h3>UTBM Resort</h3><span>Customer Area</span>
        </div>
        <nav class="sidebar-nav">
          <a href="#" class="nav-link active" data-section="dashboard">Dashboard</a>
          <a href="#" class="nav-link" data-section="new-reservation">New Reservation</a>
          <a href="#" class="nav-link" data-section="reservations">My Reservations</a>
          <a href="#" class="nav-link" data-section="profile">My Profile</a>
          <a href="#" class="nav-link" data-section="reviews">Leave a Feedback</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>My Account</h2>
          <div class="admin-user"><span>Welcome, <strong><?php echo h($guest['first_name'].' '.$guest['last_name']); ?></strong></span></div>
        </header>

        <section class="admin-content">
          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
              <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
          <?php endif; ?>

          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
              <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
          <?php endif; ?>

          <div class="content-section active" id="dashboard">
            <div class="stats-container">
              <div class="stat-box"><h4>Loyalty Points</h4><p><?php echo (int)($guest['loyality_points'] ?? 0); ?></p></div>
              <div class="stat-box"><h4>Total Reservations</h4><p><?php echo count($reservations); ?></p></div>
              <div class="stat-box"><h4>Notice Pending</h4><p><?php echo count($reservations_to_review); ?></p></div>
              <div class="stat-box"><h4>Member Since</h4><p><?php echo $guest['registration_date'] ? date('Y', strtotime($guest['registration_date'])) : '-'; ?></p></div>
            </div>
            <div class="account-section">
              <h3>Welcome to your customer area</h3>
              <p>Manage yout reservations, update your profile and leave your opinions on your stays.</p>
              <p><strong>Email:</strong> <?php echo h($guest['email']); ?> &nbsp; | &nbsp; <strong>Phone:</strong> <?php echo h($guest['phone']); ?></p>
              <div style="margin-top:12px;"><button class="btn-action" onclick="showSection('new-reservation')">Make a reservation</button></div>
            </div>
          </div>

          <div class="content-section" id="new-reservation">
            <div class="account-section">
              <h3>Book a room</h3>
              <div class="search-form">
                <h4>Search for Available Rooms</h4>
                <form id="searchForm">
                  <div class="search-form-grid">
                    <div class="form-group"><label for="check_in">Check-in</label><input type="text" id="check_in" required></div>
                    <div class="form-group"><label for="check_out">Check-out</label><input type="text" id="check_out" required></div>
                    <div class="form-group">
                      <label for="type_id">All rooms</label>
                      <select id="type_id">
                        <option value="">All types</option>
                        <?php foreach ($room_types as $type): ?>
                          <option value="<?php echo (int)$type['room_type_id']; ?>"><?php echo h($type['type_name']); ?> - <?php echo number_format((float)$type['base_price'],2); ?> €/night</option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <button type="submit" class="btn-action">Research</button>
                </form>
              </div>
              <div id="results"></div>
            </div>
          </div>

          <div class="content-section" id="reservations">
            <div class="account-section">
              <h3>My Reservations</h3>
              <?php if (!empty($reservations)): ?>
                <?php $labels = ['pending'=>'En attente','confirmed'=>'Confirmé','checked_in'=>'En cours','completed'=>'Terminé','cancelled'=>'Annulé']; ?>
                <?php foreach ($reservations as $res): ?>
                  <div class="booking-item">
                    <p><strong><?php echo h($res['type_name']); ?></strong> (Room n°<?php echo h($res['room_number']); ?>)</p>
                    <p><strong>Period:</strong> <?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?> - <?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></p>
                    <p><strong>Total Price:</strong> <?php echo number_format((float)$res['total_price'], 2); ?> €</p>
                    <p><span class="status-badge status-<?php echo h($res['status']); ?>"><?php echo h($labels[$res['status']] ?? ucfirst($res['status'])); ?></span></p>
                  </div>
                  <div class="reservation-actions" style="margin-top:15px;display:flex;gap:10px;flex-wrap:wrap;">
                      
                      <?php if (in_array($res['status'], ['pending', 'confirmed'])): ?>
                          <a href="modify_reservation.php?id=<?php echo $res['id_reservation']; ?>" 
                            class="action-btn btn-modify" 
                            style="background:#667eea;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:600;transition:all 0.3s;"
                            onmouseover="this.style.background='#5568d3'" 
                            onmouseout="this.style.background='#667eea'">
                            Modify Reservation
                          </a>
                      <?php endif; ?>
                      
                      <?php if (in_array($res['status'], ['completed', 'checked_in', 'confirmed'])): ?>
                          <a href="generate_invoice.php?id=<?php echo $res['id_reservation']; ?>" 
                            target="_blank"
                            class="action-btn btn-invoice" 
                            style="background:#28a745;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:600;transition:all 0.3s;"
                            onmouseover="this.style.background='#218838'" 
                            onmouseout="this.style.background='#28a745'">
                            View Invoice
                          </a>
                      <?php endif; ?>
                      
                      <?php if ($res['status'] === 'pending'): ?>
                          <form method="POST" action="cancel_reservation.php" style="display:inline;" 
                                onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                              <input type="hidden" name="reservation_id" value="<?php echo $res['id_reservation']; ?>">
                              <button type="submit" 
                                      class="action-btn btn-cancel" 
                                      style="background:#dc3545;color:#fff;padding:10px 20px;border-radius:6px;border:none;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.3s;"
                                      onmouseover="this.style.background='#c82333'" 
                                      onmouseout="this.style.background='#dc3545'">
                                  ✕ Cancel Reservation
                              </button>
                          </form>
                      <?php endif; ?>
                      
                  </div>

                <?php endforeach; ?>
              <?php else: ?>
                <div class="no-content"><p>No reservation.</p></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="content-section" id="profile">
            <div class="account-section">
              <h3>Moy Profile</h3>
              
              <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                  <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p style="margin:5px 0;"><?php echo htmlspecialchars($error); ?></p>
                  <?php endforeach; ?>
                  <?php unset($_SESSION['errors']); ?>
                </div>
              <?php endif; ?>
              
              <form method="POST" action="update_profile.php">
                <div class="form-group">
                  <label>First Name *</label>
                  <input type="text" name="first_name" value="<?php echo h($guest['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label>Last Name *</label>
                  <input type="text" name="last_name" value="<?php echo h($guest['last_name']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label>Email *</label>
                  <input type="email" name="email" value="<?php echo h($guest['email']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label>Phone *</label>
                  <input type="tel" name="phone" value="<?php echo h($guest['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label>Address</label>
                  <textarea name="adress" rows="3"><?php echo h($guest['adress'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                  <label>Birthdate</label>
                  <input type="date" name="date_of_birth" value="<?php echo h($guest['date_of_birth'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn-action">Update my profile</button>
              </form>
            </div>
          </div>

          <div class="content-section" id="reviews">
            <div class="account-section">
              <h3>Leave a Feedback</h3>
              <?php if (!empty($reservations_to_review)): ?>
                <?php foreach($reservations_to_review as $res): ?>
                  <div class="booking-item">
                    <p><strong><?php echo h($res['type_name']); ?></strong> (Room n°<?php echo h($res['room_number']); ?>)</p>
                    <form method="POST" action="submit_feedback.php">
                      <input type="hidden" name="reservation_id" value="<?php echo (int)$res['id_reservation']; ?>">
                      <div class="form-group"><label>Note (1–5)</label><input type="number" name="rating" min="1" max="5" required></div>
                      <div class="form-group"><label>Comment</label><textarea name="comment" rows="3" required></textarea></div>
                      <button type="submit" class="btn-action">Send</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="no-content"><p>No stay waiting for feedback.</p></div>
              <?php endif; ?>
            </div>
          </div>

        </section>
      </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
      const urlParams = new URLSearchParams(window.location.search);
      const section = urlParams.get('section');
      const checkInParam = urlParams.get('check_in');
      const checkOutParam = urlParams.get('check_out');
      const guestsParam = urlParams.get('guests') || '1';
      const typeIdParam = urlParams.get('type_id') || '';

      document.addEventListener('DOMContentLoaded', function() {
        if (section) showSection(section);
        if (typeIdParam) { const sel = document.getElementById('type_id'); if (sel) sel.value = typeIdParam; }
        if (section === 'new-reservation' && checkInParam && checkOutParam) {
          setTimeout(() => document.getElementById('searchForm')?.dispatchEvent(new Event('submit')), 400);
        }
      });

      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const sectionId = this.getAttribute('data-section');
          if (sectionId) {
            showSection(sectionId);
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
          }
        });
      });

      function showSection(sectionId) {
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(sectionId);
        if (target) target.classList.add('active');
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        const activeLink = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
        if (activeLink) activeLink.classList.add('active');
      }

      flatpickr('#check_in', { locale:'fr', altInput:true, altFormat:'d/m/Y', dateFormat:'Y-m-d', minDate:'today', defaultDate: checkInParam });
      flatpickr('#check_out', { locale:'fr', altInput:true, altFormat:'d/m/Y', dateFormat:'Y-m-d', minDate:'today', defaultDate: checkOutParam });

      document.getElementById('searchForm')?.addEventListener('submit', function(e){
        e.preventDefault();
        const ci = document.getElementById('check_in').value;
        const co = document.getElementById('check_out').value;
        const ty = document.getElementById('type_id').value;
        const resultsDiv = document.getElementById('results');

        resultsDiv.innerHTML = '<p style="padding:16px;">Research in progress...</p>';
        if (!ci || !co || co <= ci) { resultsDiv.innerHTML = '<p style="color:#f44336;padding:16px;">Invalid dates</p>'; return; }

        fetch(`get_available_rooms.php?check_in=${encodeURIComponent(ci)}&check_out=${encodeURIComponent(co)}&type_id=${encodeURIComponent(ty)}`)
          .then(r => r.json())
          .then(data => {
            if (data.error) { resultsDiv.innerHTML = `<p style="color:#f44336;padding:16px;">${data.error}</p>`; return; }
            if (!Array.isArray(data) || data.length === 0) { resultsDiv.innerHTML = '<div class="no-content"><p>No rooms available</p></div>'; return; }

            const roomImages = {
              'Chambre Deluxe': '../img/room1.webp',
              'Chambre Premium': '../img/room2.webp',
              'Suite Junior': '../img/room3.webp',
              'Suite Senior': '../img/room4.webp',
              'Chambre Familiale': '../img/room5.webp',
              'Suite Luxe': '../img/room6.webp'
            };
            const defaultImage = '../img/2654691_xlarge_1ea75b8c.jpg';

            let html = '<div class="results-grid">';
            data.forEach(room => {
              const imgSrc = roomImages[room.type_name] || defaultImage;
              const price = (room.price_per_night !== undefined && room.price_per_night !== null) ? parseFloat(room.price_per_night).toFixed(0) : '—';
              const cap = room.capacity ?? '—';
              const amenities = (room.amenities || '').split(',').map(s => s.trim()).filter(Boolean);

              html += `
                <div class="modern-room-card">
                  <div class="room-image-container">
                    <img src="${imgSrc}" alt="${room.type_name ?? 'Chambre'}">
                    <div class="room-overlay"></div>
                    <span class="room-number-badge">N° ${room.room_number}</span>
                    <span class="room-price-badge">€${price} / nuit</span>
                  </div>
                  <div class="room-details">
                    <h4 class="room-type-name">${room.type_name ?? 'Chambre'}</h4>
                    <p class="room-capacity-info">👥 Capacité: ${cap} personne${cap > 1 ? 's' : ''}</p>
                    ${room.description ? `<p class="room-description-text">${room.description}</p>` : ''}
                    ${amenities.length > 0 ? `<div class="room-amenities">${amenities.map(a => `<span class="amenity-chip">${a}</span>`).join('')}</div>` : ''}
                    <form action="reservation_process.php" method="POST" class="room-booking-form">
                      <input type="hidden" name="room_id" value="${room.room_id}">
                      <input type="hidden" name="check_in" value="${ci}">
                      <input type="hidden" name="check_out" value="${co}">
                      <div class="form-row">
                        <label>Number of people</label>
                        <input type="number" name="num_guests" min="1" max="${cap || 1}" value="${guestsParam}" required>
                      </div>
                      ${<?php echo json_encode(!empty($services)); ?> ? `
                        <div class="form-row">
                          <label>Additional services</label>
                          <div class="services-checkboxes">
                            <?php foreach ($services as $service): ?>
                              <div class="service-option">
                                <input type="checkbox" name="services[]" value="<?php echo (int)$service['service_id']; ?>" id="srv-${room.room_id}-<?php echo (int)$service['service_id']; ?>">
                                <label for="srv-${room.room_id}-<?php echo (int)$service['service_id']; ?>">
                                  <?php echo h($service['service_name']); ?> <strong>(<?php echo number_format((float)$service['price'], 0); ?>€)</strong>
                                  <?php if (!empty($service['description'])): ?><br><small><?php echo h($service['description']); ?></small><?php endif; ?>
                                </label>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      ` : ''}
                      <button type="submit" class="reserve-btn">Reserve</button>
                    </form>
                  </div>
                </div>
              `;
            });
            html += '</div>';
            resultsDiv.innerHTML = html;
          })
          .catch(err => {
            console.error(err);
            resultsDiv.innerHTML = '<p style="color:#f44336;padding:16px;">Erreur</p>';
          });
      });
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success, .alert-error, .alert-info');
        
        alerts.forEach(function(alert) {
          const closeBtn = document.createElement('button');
          closeBtn.innerHTML = '×';
          closeBtn.style.cssText = 'position:absolute;top:10px;right:15px;background:none;border:none;font-size:1.8rem;cursor:pointer;color:inherit;opacity:0.6;line-height:1;transition:opacity 0.2s;';
          closeBtn.onmouseover = function() { this.style.opacity = '1'; };
          closeBtn.onmouseout = function() { this.style.opacity = '0.6'; };
          closeBtn.onclick = function() {
            dismissAlert(alert);
          };
          alert.appendChild(closeBtn);
          
          alert.style.paddingRight = '45px';
          
          setTimeout(function() {
            dismissAlert(alert);
          }, 5000);
        });
      });

      function dismissAlert(alert) {
        alert.classList.add('fade-out');
        setTimeout(function() {
          alert.remove();
        }, 500);
      }
    </script>
  </body>
</html>
