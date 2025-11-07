<?php
require_once 'config.php';
require_once 'admin_util.php';

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$current_role = $_SESSION['role'] ?? '';

if (!in_array($current_role, ['administrator', 'manager'])) {
    $_SESSION['error'] = "Accès refusé. Seuls les administrateurs peuvent gérer le personnel.";
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $login = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
                    
                    $stmt = $pdo->prepare("INSERT INTO employee (first_name, last_name, email, phone, role, login, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['role'],
                        $login,
                        $hashedPassword
                    ]);
                    $_SESSION['success'] = "Staff successfully add.";
                    break;

                case 'edit':
                    $login = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
                    
                    if (!empty($_POST['password'])) {
                        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE employee SET first_name=?, last_name=?, email=?, phone=?, role=?, login=?, password=? WHERE employee_id=?");
                        $stmt->execute([
                            $_POST['first_name'],
                            $_POST['last_name'],
                            $_POST['email'],
                            $_POST['phone'],
                            $_POST['role'],
                            $login,
                            $hashedPassword,
                            $_POST['employee_id']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE employee SET first_name=?, last_name=?, email=?, phone=?, role=?, login=? WHERE employee_id=?");
                        $stmt->execute([
                            $_POST['first_name'],
                            $_POST['last_name'],
                            $_POST['email'],
                            $_POST['phone'],
                            $_POST['role'],
                            $login,
                            $_POST['employee_id']
                        ]);
                    }
                    $_SESSION['success'] = "Staff updated.";
                    break;
                
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM employee WHERE employee_id=?");
                    $stmt->execute([$_POST['employee_id']]);
                    $_SESSION['success'] = "Staff removed.";
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header('Location: admin_gestion_employees.php');
        exit;
    }
}

$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';

$sql = "SELECT * FROM employee WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($filter_role) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

$sql .= " ORDER BY employee_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$total_employees = count($employees);
$stmt = $pdo->query("SELECT COUNT(*) as count FROM employee WHERE role='administrator'");
$admin_count = $stmt->fetch()['count'] ?? 0;

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$roleLabels = [
    'administrator' => 'Administrateur',
    'manager' => 'Manager',
    'receptionist' => 'Réceptionniste',
    'cleaning_staff' => 'Personnel de Nettoyage'
];

$roleColors = [
    'administrator' => 'background:#f8d7da;color:#721c24;',
    'manager' => 'background:#d1ecf1;color:#0c5460;',
    'receptionist' => 'background:#d4edda;color:#155724;',
    'cleaning_staff' => 'background:#fff3cd;color:#856404;'
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Manage Staff - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .mini-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
      .mini-stat { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:20px; border-radius:12px; text-align:center; }
      .mini-stat:nth-child(2) { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); }
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
      .role-badge { display:inline-block; padding:6px 12px; border-radius:16px; font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
      .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center; }
      .modal.active { display:flex; }
      .modal-content { background:#fff; border-radius:16px; padding:32px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; }
      .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
      .modal-title { font-size:1.5rem; font-weight:800; color:#1a202c; margin:0; }
      .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#6c757d; }
      .form-modern { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-top:20px; }
      .form-group-modern { display:flex; flex-direction:column; }
      .form-group-modern label { font-weight:600; color:#495057; margin-bottom:8px; font-size:.9rem; }
      .form-group-modern input, .form-group-modern select { padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .form-group-modern input:focus, .form-group-modern select:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
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
          <a href="admin_gestion_reservations.php" class="nav-link">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link active">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Manage Staff</h2>
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
          
          <div class="mini-stats">
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $total_employees; ?></p>
              <p class="mini-stat-label">Total Staff</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $admin_count; ?></p>
              <p class="mini-stat-label">Administrator</p>
            </div>
          </div>
          
          <div class="admin-section">
            <div class="section-header">
              <h3 class="section-title">Liste of Staff</h3>
              <button class="btn-primary-modern" onclick="openModal('add')">Add Staff</button>
            </div>

            <form method="GET" class="search-filter-bar">
              <input type="text" name="search" class="search-input" placeholder="Research (name, email)" value="<?php echo h($search); ?>">
              <select name="filter_role" class="filter-select" onchange="this.form.submit()">
                <option value="">All of roles</option>
                <option value="administrator" <?php echo $filter_role==='administrator'?'selected':''; ?>>Administrator</option>
                <option value="manager" <?php echo $filter_role==='manager'?'selected':''; ?>>Manager</option>
                <option value="receptionist" <?php echo $filter_role==='receptionist'?'selected':''; ?>>Receptionist</option>
                <option value="cleaning_staff" <?php echo $filter_role==='cleaning_staff'?'selected':''; ?>>Cleaning staff</option>
              </select>
              <button type="submit" class="btn-primary-modern">Research</button>
              <?php if ($search || $filter_role): ?>
                <a href="admin_gestion_employees.php" class="btn-primary-modern" style="background:#6c757d;">Reset</a>
              <?php endif; ?>
            </form>

            <?php if (!empty($employees)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Role</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($employees as $emp): ?>
                    <tr>
                      <td><strong>#<?php echo h($emp['employee_id']); ?></strong></td>
                      <td><?php echo h($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                      <td><?php echo h($emp['email']); ?></td>
                      <td><?php echo h($emp['phone']); ?></td>
                      <td><span class="role-badge" style="<?php echo $roleColors[$emp['role']] ?? ''; ?>"><?php echo $roleLabels[$emp['role']] ?? h($emp['role']); ?></span></td>
                      <td>
                        <div class="action-buttons">
                          <button class="btn-icon btn-edit" data-tooltip="Edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($emp), ENT_QUOTES); ?>)">E</button>
                          <?php if ($emp['employee_id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this staff ?');">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="employee_id" value="<?php echo (int)$emp['employee_id']; ?>">
                              <button type="submit" class="btn-icon btn-delete" data-tooltip="Delete">D</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p style="text-align:center;padding:40px;color:#6c757d;">No staff found</p>
            <?php endif; ?>
          </div>

        </section>
      </main>
    </div>

    <div id="employeeModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title" id="modalTitle">Add Staff</h3>
          <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" id="employeeForm">
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="employee_id" id="formEmployeeId">
          
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
              <label>Role *</label>
              <select name="role" id="formRole" required>
                <option value="receptionist">Receptionist</option>
                <option value="cleaning_staff">Cleaning staff</option>
                <option value="manager">Manager</option>
                <option value="administrator">Administrator</option>
              </select>
            </div>
            
            <div class="form-group-modern">
              <label>Password <span id="passwordHint">*</span></label>
              <input type="password" name="password" id="formPassword">
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
      function openModal(action, employee = null) {
        const modal = document.getElementById('employeeModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('employeeForm');
        const passwordHint = document.getElementById('passwordHint');
        
        if (action === 'add') {
          title.textContent = 'Add Staff';
          document.getElementById('formAction').value = 'add';
          form.reset();
          passwordHint.textContent = '*';
          document.getElementById('formPassword').required = true;
        } else {
          title.textContent = 'Edit Staff';
          document.getElementById('formAction').value = 'edit';
          document.getElementById('formEmployeeId').value = employee.employee_id;
          document.getElementById('formFirstName').value = employee.first_name;
          document.getElementById('formLastName').value = employee.last_name;
          document.getElementById('formEmail').value = employee.email;
          document.getElementById('formPhone').value = employee.phone;
          document.getElementById('formRole').value = employee.role;
          passwordHint.textContent = '(leave blank to keep current password)';
          document.getElementById('formPassword').required = false;
        }
        
        modal.classList.add('active');
      }

      function closeModal() {
        document.getElementById('employeeModal').classList.remove('active');
      }

      document.getElementById('employeeModal').addEventListener('click', function(e) {
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
