<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employee') {
    header('Location: ../html/login.html');
    exit();
}

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$role = $_SESSION['role'] ?? 'Employee';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation");
    $total_reservations = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservation WHERE DATE(booking_date) = CURDATE()");
    $today_reservations = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(DISTINCT room_id) as total FROM reservation WHERE status IN ('confirmed','checked_in') AND check_in_date <= CURDATE() AND check_out_date >= CURDATE()");
    $occupied_rooms = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(total_price) as total FROM reservation WHERE status IN ('completed','confirmed','checked_in')");
    $total_revenue = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("
        SELECT r.id_reservation, r.booking_date, r.check_in_date, r.check_out_date, r.status, r.total_price,
               g.first_name, g.last_name, rt.type_name, rm.room_number
        FROM reservation r
        JOIN guest g ON r.guest_id = g.guest_id
        JOIN room rm ON r.room_id = rm.room_id
        JOIN room_type rt ON rm.room_type_id = rt.room_type_id
        ORDER BY r.booking_date DESC
        LIMIT 5
    ");
    $recent_reservations = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM guest");
    $total_guests = $stmt->fetch()['total'] ?? 0;

} catch (Exception $e) {
    $total_reservations = 0;
    $today_reservations = 0;
    $occupied_rooms = 0;
    $total_revenue = 0;
    $recent_reservations = [];
    $total_guests = 0;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Dashboard - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .stats-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:30px; }
      .stat-card { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,.12); transition:transform .3s ease, box-shadow .3s ease; }
      .stat-card:nth-child(2) { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); }
      .stat-card:nth-child(3) { background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); }
      .stat-card:nth-child(4) { background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); }
      .stat-card:nth-child(5) { background:linear-gradient(135deg,#fa709a 0%,#fee140 100%); }
      .stat-card:nth-child(6) { background:linear-gradient(135deg,#30cfd0 0%,#330867 100%); }
      .stat-card:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,.18); }
      .stat-label { font-size:.9rem; font-weight:500; opacity:.9; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
      .stat-value { font-size:2.5rem; font-weight:800; margin:0; line-height:1; }
      .stat-icon { font-size:2rem; opacity:.3; float:right; margin-top:-10px; }
      .dashboard-section { background:#fff; border-radius:16px; padding:28px; margin-bottom:24px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
      .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #e8e8e8; }
      .section-title { font-size:1.4rem; font-weight:800; color:#1a202c; margin:0; }
      .view-all-btn { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.9rem; display:inline-block; }
      .view-all-btn:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(102,126,234,.4); }
      .modern-table { width:100%; border-collapse:collapse; margin-top:16px; }
      .modern-table thead { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); }
      .modern-table th { padding:14px 16px; text-align:left; font-weight:700; color:#495057; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #dee2e6; }
      .modern-table td { padding:16px; border-bottom:1px solid #f1f3f5; color:#495057; font-size:.95rem; }
      .modern-table tbody tr { transition:background .2s ease; }
      .modern-table tbody tr:hover { background:#f8f9fa; }
      .status-badge { display:inline-block; padding:6px 12px; border-radius:20px; font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
      .status-pending { background:#fff3cd; color:#856404; } .status-confirmed { background:#d1ecf1; color:#0c5460; }
      .status-checked_in { background:#d4edda; color:#155724; } .status-completed { background:#e2e3e5; color:#383d41; }
      .status-cancelled { background:#f8d7da; color:#721c24; }
      .no-data { text-align:center; padding:40px; color:#6c757d; font-size:1rem; }
      .quick-actions { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-top:24px; }
      .action-card { background:linear-gradient(135deg,#ffffff 0%,#f8f9fa 100%); border:2px solid #e9ecef; padding:20px; border-radius:12px; text-align:center; transition:all .3s ease; cursor:pointer; text-decoration:none; color:inherit; display:block; }
      .action-card:hover { border-color:#667eea; transform:translateY(-4px); box-shadow:0 8px 20px rgba(102,126,234,.2); }
      .action-icon { font-size:2.5rem; margin-bottom:12px; }
      .action-label { font-weight:700; color:#495057; font-size:1rem; }
      @media(max-width:768px){ .stats-container{grid-template-columns:1fr;} .modern-table{font-size:.85rem;} .modern-table th, .modern-table td{padding:10px 8px;} }
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
          <a href="admin_dashboard.php" class="nav-link active">Dashboard</a>
          <a href="admin_gestion_clients.php" class="nav-link">Customers</a>
          <a href="admin_gestion_chambres.php" class="nav-link">Rooms</a>
          <a href="admin_gestion_reservations.php" class="nav-link">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Dashboard</h2>
          <div class="admin-user">
            <span>Welcome, <strong><?php echo h($employee_name); ?></strong></span>
            <span style="font-size:0.85rem;color:#666;margin-left:8px;">(<?php echo h($role); ?>)</span>
          </div>
        </header>

        <section class="admin-content">
          
          <div class="stats-container">
            <div class="stat-card">
              <div class="stat-icon">📊</div>
              <div class="stat-label">Total Reservations</div>
              <p class="stat-value"><?php echo number_format($total_reservations); ?></p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">🔔</div>
              <div class="stat-label">Today</div>
              <p class="stat-value"><?php echo number_format($today_reservations); ?></p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">🏨</div>
              <div class="stat-label">Room occupied</div>
              <p class="stat-value"><?php echo number_format($occupied_rooms); ?></p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">💰</div>
              <div class="stat-label">Total Revenues</div>
              <p class="stat-value"><?php echo number_format($total_revenue, 0); ?> €</p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">👥</div>
              <div class="stat-label">Customers</div>
              <p class="stat-value"><?php echo number_format($total_guests); ?></p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">📅</div>
              <div class="stat-label">Occupancy Rate</div>
              <p class="stat-value"><?php echo $occupied_rooms > 0 ? round(($occupied_rooms / 20) * 100) : 0; ?>%</p>
            </div>
          </div>

          <div class="dashboard-section">
            <div class="section-header">
              <h3 class="section-title">Recent Reservations</h3>
              <a href="admin_gestion_reservations.php" class="view-all-btn">See all</a>
            </div>

            <?php if (!empty($recent_reservations)): ?>
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
                  <?php foreach ($recent_reservations as $res): ?>
                    <tr>
                      <td><strong>#<?php echo h($res['id_reservation']); ?></strong></td>
                      <td><?php echo h($res['first_name'] . ' ' . $res['last_name']); ?></td>
                      <td><?php echo h($res['type_name']); ?> (N°<?php echo h($res['room_number']); ?>)</td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?></td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></td>
                      <td><strong><?php echo number_format((float)$res['total_price'], 2); ?> €</strong></td>
                      <td><span class="status-badge status-<?php echo h($res['status']); ?>"><?php echo h($res['status']); ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="no-data">No recent reservation.</div>
            <?php endif; ?>
          </div>

          <div class="dashboard-section">
            <div class="section-header">
              <h3 class="section-title">Actions Rapides</h3>
            </div>

            <div class="quick-actions">
              <a href="admin_gestion_reservations.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-label">Manage Reservations</div>
              </a>

              <a href="admin_gestion_chambres.php" class="action-card">
                <div class="action-icon">🛏️</div>
                <div class="action-label">Manage Rooms</div>
              </a>

              <a href="admin_gestion_clients.php" class="action-card">
                <div class="action-icon">👤</div>
                <div class="action-label">Manage Customers</div>
              </a>

              <a href="admin_checkinout.php" class="action-card">
                <div class="action-icon">🔑</div>
                <div class="action-label">Check-In/Out</div>
              </a>
            </div>
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
