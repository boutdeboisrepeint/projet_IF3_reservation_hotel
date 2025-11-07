<?php
require_once 'config.php';
require_once 'admin_util.php';

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                  $stmt = $pdo->prepare("INSERT INTO guest (first_name, last_name, email, phone, adress, date_of_birth, password, registration_date, loyality_points) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0)");
                  $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                  $stmt->execute([
                      $_POST['first_name'],
                      $_POST['last_name'],
                      $_POST['email'],
                      $_POST['phone'],
                      $_POST['adress'] ?? '',
                      $_POST['date_of_birth'] ?? '2000-01-01',
                      $hashedPassword
                  ]);
                  $_SESSION['success'] = "Customer successfully added.";
                  break;

                case 'edit':
                  if (!empty($_POST['password'])) {
                      $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                      $stmt = $pdo->prepare("UPDATE guest SET first_name=?, last_name=?, email=?, phone=?, adress=?, date_of_birth=?, password=?, loyality_points=? WHERE guest_id=?");
                      $stmt->execute([
                          $_POST['first_name'],
                          $_POST['last_name'],
                          $_POST['email'],
                          $_POST['phone'],
                          $_POST['adress'] ?? '',
                          $_POST['date_of_birth'] ?? '2000-01-01',
                          $hashedPassword,
                          $_POST['loyality_points'] ?? 0,
                          $_POST['guest_id']
                      ]);
                  } else {
                      $stmt = $pdo->prepare("UPDATE guest SET first_name=?, last_name=?, email=?, phone=?, adress=?, date_of_birth=?, loyality_points=? WHERE guest_id=?");
                      $stmt->execute([
                          $_POST['first_name'],
                          $_POST['last_name'],
                          $_POST['email'],
                          $_POST['phone'],
                          $_POST['adress'] ?? '',
                          $_POST['date_of_birth'] ?? '2000-01-01',
                          $_POST['loyality_points'] ?? 0,
                          $_POST['guest_id']
                      ]);
                  }
                  $_SESSION['success'] = "Customer updated.";
                  break;

                case 'delete':
                    $check = $pdo->prepare("SELECT COUNT(*) as count FROM reservation WHERE guest_id=?");
                    $check->execute([$_POST['guest_id']]);
                    $count = $check->fetch()['count'];
                    
                    if ($count > 0) {
                        $_SESSION['error'] = "Cannot delete customer with existing reservations.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM guest WHERE guest_id=?");
                        $stmt->execute([$_POST['guest_id']]);
                        $_SESSION['success'] = "Customer deleted.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header('Location: admin_gestion_clients.php');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$filter_points = $_GET['filter_points'] ?? '';

$sql = "SELECT * FROM guest WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($filter_points === 'high') {
    $sql .= " AND loyality_points >= 100";
} elseif ($filter_points === 'medium') {
    $sql .= " AND loyality_points BETWEEN 50 AND 99";
} elseif ($filter_points === 'low') {
    $sql .= " AND loyality_points < 50";
}

$sql .= " ORDER BY guest_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

$total_clients = count($clients);
$stmt = $pdo->query("SELECT SUM(loyality_points) as total FROM guest");
$total_points = $stmt->fetch()['total'] ?? 0;

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Manage Customers - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .mini-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
      .mini-stat { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:20px; border-radius:12px; text-align:center; }
      .mini-stat:nth-child(2) { background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); }
      .mini-stat-value { font-size:2rem; font-weight:800; margin:0; }
      .mini-stat-label { font-size:.85rem; opacity:.9; margin-top:4px; }
      .admin-section { background:#fff; border-radius:16px; padding:28px; margin-bottom:24px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
      .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #e8e8e8; }
      .section-title { font-size:1.4rem; font-weight:800; color:#1a202c; margin:0; }
      .btn-primary-modern { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.9rem; display:inline-block; }
      .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(102,126,234,.4); }
      .search-filter-bar { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
      .search-input { flex:1; min-width:250px; padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .filter-select { padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .modern-table { width:100%; border-collapse:collapse; margin-top:16px; }
      .modern-table thead { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); }
      .modern-table th { padding:14px 16px; text-align:left; font-weight:700; color:#495057; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #dee2e6; }
      .modern-table td { padding:16px; border-bottom:1px solid #f1f3f5; color:#495057; font-size:.95rem; }
      .modern-table tbody tr { transition:background .2s ease; }
      .modern-table tbody tr:hover { background:#f8f9fa; }
      .action-buttons { display:flex; gap:8px; align-items:center; }
      .btn-icon { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:none; border-radius:8px; cursor:pointer; transition:all .2s ease; font-size:1.1rem; position:relative; }
      .btn-icon:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.15); }
      .btn-edit { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; }
      .btn-edit:hover { box-shadow:0 4px 12px rgba(102,126,234,.4); }
      .btn-delete { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); color:#fff; }
      .btn-delete:hover { box-shadow:0 4px 12px rgba(240,147,251,.4); }
      .btn-icon::after { content:attr(data-tooltip); position:absolute; bottom:-32px; left:50%; transform:translateX(-50%) scale(.8); background:rgba(0,0,0,.8); color:#fff; padding:4px 10px; border-radius:6px; font-size:.75rem; white-space:nowrap; opacity:0; pointer-events:none; transition:opacity .2s ease, transform .2s ease; }
      .btn-icon:hover::after { opacity:1; transform:translateX(-50%) scale(1); }
      .points-badge { background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:12px; font-size:.8rem; font-weight:600; }
      .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center; }
      .modal.active { display:flex; }
      .modal-content { background:#fff; border-radius:16px; padding:32px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; }
      .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
      .modal-title { font-size:1.5rem; font-weight:800; color:#1a202c; margin:0; }
      .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#6c757d; }
      .form-modern { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-top:20px; }
      .form-group-modern { display:flex; flex-direction:column; }
      .form-group-modern label { font-weight:600; color:#495057; margin-bottom:8px; font-size:.9rem; }
      .form-group-modern input, .form-group-modern textarea { padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .form-group-modern input:focus, .form-group-modern textarea:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
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
          <a href="admin_gestion_clients.php" class="nav-link active">Customers</a>
          <a href="admin_gestion_chambres.php" class="nav-link">Rooms</a>
          <a href="admin_gestion_reservations.php" class="nav-link">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Manage Customers</h2>
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
              <p class="mini-stat-value"><?php echo $total_clients; ?></p>
              <p class="mini-stat-label">Total Customers</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo number_format($total_points); ?></p>
              <p class="mini-stat-label">Total Loyalty Points</p>
            </div>
          </div>
          
          <!-- Liste des clients -->
          <div class="admin-section">
            <div class="section-header">
              <h3 class="section-title">List of customers</h3>
              <button class="btn-primary-modern" onclick="openModal('add')">Add Customer</button>
            </div>

            <!-- Recherche et filtres -->
            <form method="GET" class="search-filter-bar">
              <input type="text" name="search" class="search-input" placeholder="Research (Name, email, telephone)" value="<?php echo h($search); ?>">
              <select name="filter_points" class="filter-select" onchange="this.form.submit()">
                <option value="">All the points</option>
                <option value="high" <?php echo $filter_points==='high'?'selected':''; ?>>≥ 100 points</option>
                <option value="medium" <?php echo $filter_points==='medium'?'selected':''; ?>>50-99 points</option>
                <option value="low" <?php echo $filter_points==='low'?'selected':''; ?>>< 50 points</option>
              </select>
              <button type="submit" class="btn-primary-modern">Research</button>
              <?php if ($search || $filter_points): ?>
                <a href="admin_gestion_clients.php" class="btn-primary-modern" style="background:#6c757d;">Reset</a>
              <?php endif; ?>
            </form>

            <?php if (!empty($clients)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Address</th>
                    <th>Points</th>
                    <th>Registration</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($clients as $client): ?>
                    <tr>
                      <td><strong>#<?php echo h($client['guest_id']); ?></strong></td>
                      <td><?php echo h($client['first_name'] . ' ' . $client['last_name']); ?></td>
                      <td><?php echo h($client['email']); ?></td>
                      <td><?php echo h($client['phone']); ?></td>
                      <td><?php echo h($client['adress'] ?? '—'); ?></td>
                      <td><span class="points-badge"><?php echo (int)($client['loyality_points'] ?? 0); ?> pts</span></td>
                      <td><?php echo date('d/m/Y', strtotime($client['registration_date'])); ?></td>
                      <td>
                        <div class="action-buttons">
                          <button class="btn-icon btn-edit" data-tooltip="Edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($client), ENT_QUOTES); ?>)">E</button>
                          <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this customer ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="guest_id" value="<?php echo (int)$client['guest_id']; ?>">
                            <button type="submit" class="btn-icon btn-delete" data-tooltip="Delete">D</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p style="text-align:center;padding:40px;color:#6c757d;">No customers found.</p>
            <?php endif; ?>
          </div>

        </section>
      </main>
    </div>

    <div id="clientModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title" id="modalTitle">Add Customer</h3>
          <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" id="clientForm">
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="guest_id" id="formGuestId">
          
          <div class="form-modern">
            <div class="form-group-modern">
              <label>First Name *</label>
              <input type="text" name="first_name" id="formFirstName" required>
            </div>
            
            <div class="form-group-modern">
              <label>Last Name *</label>
              <input type="text" name="last_name" id="formLastName" required>
            </div>
            
            <div class="form-group-modern">
              <label>Email *</label>
              <input type="email" name="email" id="formEmail" required>
            </div>
            
            <div class="form-group-modern">
              <label>Telephone *</label>
              <input type="tel" name="phone" id="formPhone" required>
            </div>

            <div class="form-group-modern">
              <label>Birthdate</label>
              <input type="date" name="date_of_birth" id="formDateOfBirth">
            </div>
            
            <div class="form-group-modern" style="grid-column:1/-1;">
              <label>Address</label>
              <textarea name="adress" id="formAdress" rows="2"></textarea>
            </div>
            
            <div class="form-group-modern">
              <label>Password <span id="passwordHint">(leave blank to keep unchanged)</span></label>
              <input type="password" name="password" id="formPassword">
            </div>
            
            <div class="form-group-modern" id="pointsGroup" style="display:none;">
              <label>Loyalty Points</label>
              <input type="number" name="loyality_points" id="formPoints" min="0" value="0">
            </div>
          </div>
          
          <div style="margin-top:24px;display:flex;gap:12px;">
            <button type="submit" class="btn-primary-modern" style="flex:1;">Save</button>
            <button type="button" class="btn-primary-modern" style="background:#6c757d;" onclick="closeModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openModal(action, client = null) {
        const modal = document.getElementById('clientModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('clientForm');
        const pointsGroup = document.getElementById('pointsGroup');
        const passwordHint = document.getElementById('passwordHint');
        
        if (action === 'add') {
          title.textContent = 'Add Customer';
          document.getElementById('formAction').value = 'add';
          form.reset();
          pointsGroup.style.display = 'none';
          passwordHint.textContent = '*';
          document.getElementById('formPassword').required = true;
        } else {
          title.textContent = 'Edit Customer';
          document.getElementById('formAction').value = 'edit';
          document.getElementById('formGuestId').value = client.guest_id;
          document.getElementById('formFirstName').value = client.first_name;
          document.getElementById('formLastName').value = client.last_name;
          document.getElementById('formEmail').value = client.email;
          document.getElementById('formPhone').value = client.phone;
          document.getElementById('formDateOfBirth').value = client.date_of_birth || '';
          document.getElementById('formAdress').value = client.adress || '';
          document.getElementById('formPoints').value = client.loyality_points || 0;
          pointsGroup.style.display = 'block';
          passwordHint.textContent = '(leave blank to keep unchanged)';
          document.getElementById('formPassword').required = false;
        }
        
        modal.classList.add('active');
      }

      function closeModal() {
        document.getElementById('clientModal').classList.remove('active');
      }

      document.getElementById('clientModal').addEventListener('click', function(e) {
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
