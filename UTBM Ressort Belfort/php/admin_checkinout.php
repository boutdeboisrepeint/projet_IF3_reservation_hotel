<?php
require_once 'config.php';
require_once 'admin_util.php';

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

$stmt = $pdo->query("
  SELECT r.*, g.first_name, g.last_name, rm.room_number, rt.type_name
  FROM reservation r
  JOIN guest g ON r.guest_id = g.guest_id
  JOIN room rm ON r.room_id = rm.room_id
  JOIN room_type rt ON rm.room_type_id = rt.room_type_id
  WHERE r.status IN ('confirmed', 'checked_in')
  AND r.check_in_date <= CURDATE()
  ORDER BY r.check_in_date
");
$active_reservations = $stmt->fetchAll();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Check-In/Out - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .admin-section { background:#fff; border-radius:16px; padding:28px; margin-bottom:24px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
      .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #e8e8e8; }
      .section-title { font-size:1.4rem; font-weight:800; color:#1a202c; margin:0; }
      .modern-table { width:100%; border-collapse:collapse; margin-top:16px; }
      .modern-table thead { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); }
      .modern-table th { padding:14px 16px; text-align:left; font-weight:700; color:#495057; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #dee2e6; }
      .modern-table td { padding:16px; border-bottom:1px solid #f1f3f5; color:#495057; font-size:.95rem; }
      .modern-table tbody tr { transition:background .2s ease; }
      .modern-table tbody tr:hover { background:#f8f9fa; }
      .btn-success-modern { background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); color:#fff; border:none; padding:8px 16px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.85rem; display:inline-block; }
      .btn-success-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(67,233,123,.4); }
      .btn-danger-modern { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); color:#fff; border:none; padding:8px 16px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.85rem; display:inline-block; margin-left:8px; }
      .btn-danger-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(240,147,251,.4); }
      .no-data { text-align:center; padding:40px; color:#6c757d; font-size:1rem; }
      .alert {padding: 12px 16px;border-radius: 8px;margin-bottom: 16px;transition: opacity 0.5s ease, transform 0.5s ease;opacity: 1;transform: translateY(0);}
      .alert.fade-out {opacity: 0;transform: translateY(-20px);}
      .alert-success {background: #d4edda;color: #155724;border-left: 4px solid #28a745;}
      .alert-error {background: #f8d7da;color: #721c24;border-left: 4px solid #dc3545;}
    </style>
  </head>
  <body>
    <div class="admin-grid-container">
      <aside class="admin-sidebar">
        <div class="sidebar-header">
          <h3>UTBM Resort</h3>
          <span>Admin Dashboard</span>
        </div>
        <nav class="sidebar-nav">
          <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
          <a href="admin_gestion_clients.php" class="nav-link">Customers</a>
          <a href="admin_gestion_chambres.php" class="nav-link">Rooms</a>
          <a href="admin_gestion_reservations.php" class="nav-link">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link active">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Check-In / Check-Out</h2>
          <div class="admin-user">
            <span>Welcome, <strong><?php echo h($employee_name); ?></strong></span>
          </div>
        </header>

        <section class="admin-content">
          
          <div class="admin-section">
            <div class="section-header">
              <h3 class="section-title">Arrivals and departures of the day</h3>
              <span style="color:#666;font-size:.9rem;">Total: <strong><?php echo count($active_reservations); ?></strong></span>
            </div>

            <?php if (!empty($active_reservations)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($active_reservations as $res): ?>
                    <tr>
                      <td><strong>#<?php echo h($res['id_reservation']); ?></strong></td>
                      <td><?php echo h($res['first_name'] . ' ' . $res['last_name']); ?></td>
                      <td><?php echo h($res['type_name']); ?> (N°<?php echo h($res['room_number']); ?>)</td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?></td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></td>
                      <td><strong><?php echo h($res['status']); ?></strong></td>
                      <td>
                        <?php if ($res['status'] === 'confirmed'): ?>
                          <a href="?checkin=<?php echo (int)$res['id_reservation']; ?>" class="btn-success-modern">Check-In</a>
                        <?php elseif ($res['status'] === 'checked_in'): ?>
                          <a href="?checkout=<?php echo (int)$res['id_reservation']; ?>" class="btn-danger-modern">Check-Out</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="no-data">No arrival or departure today.</div>
            <?php endif; ?>
          </div>

        </section>
      </main>
    </div>
    <script>

    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert-success, .alert-error');
      
      alerts.forEach(function(alert) {
        setTimeout(function() {
          alert.classList.add('fade-out');
          
          setTimeout(function() {
            alert.remove();
          }, 500);
        }, 5000);
      });
    });
    </script>
  </body>
</html>
<?php