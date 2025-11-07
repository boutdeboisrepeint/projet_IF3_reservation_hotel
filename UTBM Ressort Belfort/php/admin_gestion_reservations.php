<?php
require_once 'config.php';
require_once 'admin_util.php';

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_status') {
            $stmt = $pdo->prepare("UPDATE reservation SET status=? WHERE id_reservation=?");
            $stmt->execute([$_POST['status'], $_POST['reservation_id']]);
            $_SESSION['success'] = "Status updated.";
            } elseif ($_POST['action'] == 'delete') {
                try {
                    $checkFeedback = $pdo->query("SHOW TABLES LIKE 'feedback'");
                    if ($checkFeedback->rowCount() > 0) {
                        $stmt = $pdo->prepare("DELETE FROM feedback WHERE reservation_id=?");
                        $stmt->execute([$_POST['reservation_id']]);
                    }

                    $checkTable = $pdo->query("SHOW TABLES LIKE 'reservation_service'");
                    if ($checkTable->rowCount() > 0) {
                        $stmt = $pdo->prepare("DELETE FROM reservation_service WHERE reservation_id=?");
                        $stmt->execute([$_POST['reservation_id']]);
                    }

                    $checkPayment = $pdo->query("SHOW TABLES LIKE 'payment'");
                    if ($checkPayment->rowCount() > 0) {
                        $stmt = $pdo->prepare("DELETE FROM payment WHERE reservation_id=?");
                        $stmt->execute([$_POST['reservation_id']]);
                    }

                    $stmt = $pdo->prepare("DELETE FROM reservation WHERE id_reservation=?");
                    $stmt->execute([$_POST['reservation_id']]);
                    
                    $_SESSION['success'] = "Reservation and associated data deleted.";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error during deletion: " . $e->getMessage();
                }
            }
          
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: admin_gestion_reservations.php');
    exit;
}

$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

$sql = "
  SELECT r.*, g.first_name, g.last_name, g.email, g.phone, rm.room_number, rt.type_name
  FROM reservation r
  JOIN guest g ON r.guest_id = g.guest_id
  JOIN room rm ON r.room_id = rm.room_id
  JOIN room_type rt ON rm.room_type_id = rt.room_type_id
  WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR rm.room_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($filter_status) {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
}

if ($filter_date === 'today') {
    $sql .= " AND r.check_in_date = CURDATE()";
} elseif ($filter_date === 'upcoming') {
    $sql .= " AND r.check_in_date > CURDATE()";
} elseif ($filter_date === 'current') {
    $sql .= " AND r.check_in_date <= CURDATE() AND r.check_out_date >= CURDATE()";
}

$sql .= " ORDER BY r.booking_date DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Stats
$total_reservations = count($reservations);
$stmt = $pdo->query("SELECT SUM(total_price) as total FROM reservation WHERE status IN ('confirmed','checked_in','completed')");
$total_revenue = $stmt->fetch()['total'] ?? 0;

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>Manage Reservations - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .mini-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
      .mini-stat { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:20px; border-radius:12px; text-align:center; }
      .mini-stat:nth-child(2) { background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); }
      .mini-stat-value { font-size:2rem; font-weight:800; margin:0; }
      .mini-stat-label { font-size:.85rem; opacity:.9; margin-top:4px; }
      .admin-section { background:#fff; border-radius:16px; padding:28px; margin-bottom:24px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
      .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-border:2px solid #e8e8e8; }
      .section-title { font-size:1.4rem; font-weight:800; color:#1a202c; margin:0; }
      .search-filter-bar { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
      .search-input { flex:1; min-width:250px; padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .filter-select { padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .btn-primary-modern { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.9rem; display:inline-block; }
      .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(102,126,234,.4); }
      .modern-table { width:100%; border-collapse:collapse; margin-top:16px; font-size:.9rem; }
      .modern-table thead { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); }
      .modern-table th { padding:12px 14px; text-align:left; font-weight:700; color:#495057; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #dee2e6; }
      .modern-table td { padding:14px; border-bottom:1px solid #f1f3f5; color:#495057; font-size:.9rem; }
      .modern-table tbody tr { transition:background .2s ease; }
      .modern-table tbody tr:hover { background:#f8f9fa; }
      .status-badge { display:inline-block; padding:5px 10px; border-radius:16px; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
      .status-pending { background:#fff3cd; color:#856404; }
      .status-confirmed { background:#d1ecf1; color:#0c5460; }
      .status-checked_in { background:#d4edda; color:#155724; }
      .status-completed { background:#e2e3e5; color:#383d41; }
      .status-cancelled { background:#f8d7da; color:#721c24; }
      .action-buttons { display:flex; gap:8px; align-items:center; }
      .btn-icon { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border:none; border-radius:8px; cursor:pointer; transition:all .2s ease; font-size:1rem; position:relative; }
      .btn-icon:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.15); }
      .btn-view { background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); color:#fff; }
      .btn-view:hover { box-shadow:0 4px 12px rgba(79,172,254,.4); }
      .btn-delete { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); color:#fff; }
      .btn-delete:hover { box-shadow:0 4px 12px rgba(240,147,251,.4); }
      .btn-icon::after { content:attr(data-tooltip); position:absolute; bottom:-32px; left:50%; transform:translateX(-50%) scale(.8); background:rgba(0,0,0,.8); color:#fff; padding:4px 10px; border-radius:6px; font-size:.7rem; white-space:nowrap; opacity:0; pointer-events:none; transition:opacity .2s ease, transform .2s ease; z-index:10; }
      .btn-icon:hover::after { opacity:1; transform:translateX(-50%) scale(1); }
      .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center; }
      .modal.active { display:flex; }
      .modal-content { background:#fff; border-radius:16px; padding:32px; max-width:700px; width:90%; max-height:90vh; overflow-y:auto; }
      .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:2px solid #e8e8e8; padding-bottom:16px; }
      .modal-title { font-size:1.5rem; font-weight:800; color:#1a202c; margin:0; }
      .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#6c757d; }
      .detail-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:20px; }
      .detail-item { background:#f8f9fa; padding:14px; border-radius:8px; border-left:4px solid #667eea; }
      .detail-label { font-size:.8rem; color:#6c757d; font-weight:600; text-transform:uppercase; margin-bottom:4px; }
      .detail-value { font-size:1rem; color:#1a202c; font-weight:600; }
      .detail-full { grid-column:1/-1; }
      .services-list { background:#f8f9fa; padding:14px; border-radius:8px; margin-top:12px; }
      .service-item { padding:8px; border-bottom:1px solid #dee2e6; display:flex; justify-content:space-between; }
      .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; }
      .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
      .alert-error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
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
          <a href="admin_gestion_reservations.php" class="nav-link active">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Manage Reservations</h2>
          <div class="admin-user">
            <span>Welcome, <strong><?php echo h($employee_name); ?></strong></span>
          </div>
        </header>

        <section class="admin-content">
          
          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo h($_SESSION['success']); unset($_SESSION['success']); ?></div>
          <?php endif; ?>
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo h($_SESSION['error']); unset($_SESSION['error']); ?></div>
          <?php endif; ?>
          
          <!-- Mini Stats -->
          <div class="mini-stats">
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $total_reservations; ?></p>
              <p class="mini-stat-label">Reservations</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo number_format($total_revenue, 0); ?> €</p>
              <p class="mini-stat-label">Total Revenues</p>
            </div>
          </div>
          
          <div class="admin-section">
            <div class="section-header">
              <h3 class="section-title">All reservations</h3>
            </div>

            <form method="GET" class="search-filter-bar">
              <input type="text" name="search" class="search-input" placeholder="Research (customer, email, n° room)" value="<?php echo h($search); ?>">
              <select name="filter_status" class="filter-select" onchange="this.form.submit()">
                <option value="">All status</option>
                <option value="pending" <?php echo $filter_status==='pending'?'selected':''; ?>>Pending</option>
                <option value="confirmed" <?php echo $filter_status==='confirmed'?'selected':''; ?>>Confirmed</option>
                <option value="checked_in" <?php echo $filter_status==='checked_in'?'selected':''; ?>>Checked in</option>
                <option value="completed" <?php echo $filter_status==='completed'?'selected':''; ?>>Completed</option>
                <option value="cancelled" <?php echo $filter_status==='cancelled'?'selected':''; ?>>Cancelled</option>
              </select>
              <select name="filter_date" class="filter-select" onchange="this.form.submit()">
                <option value="">All Dates</option>
                <option value="today" <?php echo $filter_date==='today'?'selected':''; ?>>Today</option>
                <option value="current" <?php echo $filter_date==='current'?'selected':''; ?>>Current</option>
                <option value="upcoming" <?php echo $filter_date==='upcoming'?'selected':''; ?>>Upcoming</option>
              </select>
              <button type="submit" class="btn-primary-modern">Research</button>
              <?php if ($search || $filter_status || $filter_date): ?>
                <a href="admin_gestion_reservations.php" class="btn-primary-modern" style="background:#6c757d;">Reset</a>
              <?php endif; ?>
            </form>

            <?php if (!empty($reservations)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Pers.</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reservations as $res): ?>
                    <tr>
                      <td><strong>#<?php echo h($res['id_reservation']); ?></strong></td>
                      <td><?php echo h($res['first_name'] . ' ' . $res['last_name']); ?></td>
                      <td><?php echo h($res['type_name']); ?> (N°<?php echo h($res['room_number']); ?>)</td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?></td>
                      <td><?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></td>
                      <td><?php echo (int)$res['number_of_guest']; ?></td>
                      <td><strong><?php echo number_format((float)$res['total_price'], 0); ?> €</strong></td>
                      <td>
                        <select onchange="updateStatus(<?php echo (int)$res['id_reservation']; ?>, this.value)" style="padding:5px 8px;border:1px solid #ced4da;border-radius:6px;font-size:.8rem;">
                          <option value="pending" <?php echo $res['status']==='pending'?'selected':''; ?>>Pending</option>
                          <option value="confirmed" <?php echo $res['status']==='confirmed'?'selected':''; ?>>Confirmed</option>
                          <option value="checked_in" <?php echo $res['status']==='checked_in'?'selected':''; ?>>Checked In</option>
                          <option value="completed" <?php echo $res['status']==='completed'?'selected':''; ?>>Completed</option>
                          <option value="cancelled" <?php echo $res['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                        </select>
                      </td>
                      <td>
                        <div class="action-buttons">
                          <button class="btn-icon btn-view" data-tooltip="Informations" onclick="viewDetails(<?php echo (int)$res['id_reservation']; ?>)">I</button>
                          <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this reservation ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)$res['id_reservation']; ?>">
                            <button type="submit" class="btn-icon btn-delete" data-tooltip="Delete">D</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p style="text-align:center;padding:40px;color:#6c757d;">No reservation found.</p>
            <?php endif; ?>
          </div>

        </section>
      </main>
    </div>

    <div id="detailsModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Reservation details <span id="modalResId"></span></h3>
          <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div id="detailsContent">Loading...</div>
      </div>
    </div>

    <script>
      function updateStatus(resId, status) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="reservation_id" value="${resId}">
          <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
      }

      async function viewDetails(resId) {
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('detailsContent');
        document.getElementById('modalResId').textContent = `#${resId}`;
        
        modal.classList.add('active');
        content.innerHTML = 'Loading...';
        
        try {
          const response = await fetch(`get_reservation_details.php?id=${resId}`);
          const data = await response.json();
          
          if (data.error) {
            content.innerHTML = `<p style="color:#dc3545;">${data.error}</p>`;
            return;
          }
          
          const statusLabels = {
            pending: 'Pending',
            confirmed: 'Confirmed',
            checked_in: 'Checked In',
            completed: 'Completed',
            cancelled: 'Cancelled'
          };
          
          let servicesHTML = '<p style="color:#6c757d;">No service</p>';
          if (data.services && data.services.length > 0) {
            servicesHTML = '<div class="services-list">';
            data.services.forEach(s => {
              servicesHTML += `<div class="service-item"><span>${s.service_name}</span><strong>${s.price} €</strong></div>`;
            });
            servicesHTML += '</div>';
          }
          
          content.innerHTML = `
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Customer</div>
                <div class="detail-value">${data.first_name} ${data.last_name}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Email</div>
                <div class="detail-value">${data.email}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Telephone</div>
                <div class="detail-value">${data.phone}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Room</div>
                <div class="detail-value">${data.type_name} (N°${data.room_number})</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Check-in</div>
                <div class="detail-value">${new Date(data.check_in_date).toLocaleDateString('fr-FR')}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Check-out</div>
                <div class="detail-value">${new Date(data.check_out_date).toLocaleDateString('fr-FR')}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Pers.</div>
                <div class="detail-value">${data.number_of_guest}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value"><span class="status-badge status-${data.status}">${statusLabels[data.status]}</span></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Total Price</div>
                <div class="detail-value" style="font-size:1.3rem;color:#28a745;">${parseFloat(data.total_price).toFixed(2)} €</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Reservation Date</div>
                <div class="detail-value">${new Date(data.booking_date).toLocaleString('fr-FR')}</div>
              </div>
              <div class="detail-item detail-full">
                <div class="detail-label">Additional Services</div>
                ${servicesHTML}
              </div>
              ${data.payment ? `
                <div class="detail-item detail-full" style="border-left-color:#28a745;">
                  <div class="detail-label">Payment</div>
                  <div class="detail-value">
                    ${data.payment.amount} € - ${data.payment.payment_method} - 
                    <span class="status-badge" style="background:#d4edda;color:#155724;">${data.payment.status}</span>
                  </div>
                </div>
              ` : ''}
            </div>
          `;
        } catch (error) {
          content.innerHTML = '<p style="color:#dc3545;">Loading Error</p>';
        }
      }

      function closeModal() {
        document.getElementById('detailsModal').classList.remove('active');
      }

      document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });
    </script>
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
