<?php
require_once 'config.php';
require_once 'admin_util.php';

$employee_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
              case 'add':
                $stmt = $pdo->prepare("SELECT base_price FROM room_type WHERE room_type_id = ?");
                $stmt->execute([$_POST['room_type_id']]);
                $price = $stmt->fetch()['base_price'] ?? 0;
                
                $stmt = $pdo->prepare("INSERT INTO room (room_number, room_type_id, price_per_night, status) VALUES (?, ?, ?, 'available')");
                $stmt->execute([$_POST['room_number'], $_POST['room_type_id'], $price]);
                $_SESSION['success'] = "Room successfully add.";
                break;

              case 'edit':
                  if (empty($_POST['price_per_night'])) {
                      $stmt = $pdo->prepare("SELECT base_price FROM room_type WHERE room_type_id = ?");
                      $stmt->execute([$_POST['room_type_id']]);
                      $price = $stmt->fetch()['base_price'] ?? 0;
                  } else {
                      $price = $_POST['price_per_night'];
                  }
                  
                  $stmt = $pdo->prepare("UPDATE room SET room_number=?, room_type_id=?, price_per_night=?, status=? WHERE room_id=?");
                  $stmt->execute([$_POST['room_number'], $_POST['room_type_id'], $price, $_POST['status'], $_POST['room_id']]);
                  $_SESSION['success'] = "Room updated.";
                  break;

                case 'delete':
                    $check = $pdo->prepare("SELECT COUNT(*) as count FROM reservation WHERE room_id=?");
                    $check->execute([$_POST['room_id']]);
                    $count = $check->fetch()['count'];
                    
                    if ($count > 0) {
                        $stmt = $pdo->prepare("UPDATE room SET status='out-of-service' WHERE room_id=?");
                        $stmt->execute([$_POST['room_id']]);
                        $_SESSION['success'] = "Room out of service (reservations are linked).";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM room WHERE room_id=?");
                        $stmt->execute([$_POST['room_id']]);
                        $_SESSION['success'] = "Room deleted.";
                    }
                    break;
                
                case 'update_status':
                    $stmt = $pdo->prepare("UPDATE room SET status=? WHERE room_id=?");
                    $stmt->execute([$_POST['status'], $_POST['room_id']]);
                    $_SESSION['success'] = "Status updated.";
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header('Location: admin_gestion_chambres.php');
        exit;
    }
}

$stmt = $pdo->query("
    SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.amenities,
           (SELECT COUNT(*) FROM reservation res 
            WHERE res.room_id = r.room_id 
              AND res.status IN ('confirmed', 'checked_in') 
              AND res.check_in_date <= CURDATE() 
              AND res.check_out_date >= CURDATE()) AS is_occupied
    FROM room r 
    JOIN room_type rt ON r.room_type_id = rt.room_type_id 
    ORDER BY r.room_number
");
$rooms = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM room_type ORDER BY type_name");
$room_types = $stmt->fetchAll();

$total_rooms = count($rooms);
$available_rooms = count(array_filter($rooms, fn($r) => $r['status'] === 'available' && $r['is_occupied'] == 0));
$occupied_rooms = count(array_filter($rooms, fn($r) => $r['is_occupied'] > 0));
$maintenance_rooms = count(array_filter($rooms, fn($r) => $r['status'] === 'maintenance'));

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Manage Rooms - UTBM Resort Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
      .mini-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
      .mini-stat { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:20px; border-radius:12px; text-align:center; }
      .mini-stat:nth-child(2) { background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%); }
      .mini-stat:nth-child(3) { background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); }
      .mini-stat:nth-child(4) { background:linear-gradient(135deg,#fa709a 0%,#fee140 100%); }
      .mini-stat-value { font-size:2rem; font-weight:800; margin:0; }
      .mini-stat-label { font-size:.85rem; opacity:.9; margin-top:4px; }
      .admin-section { background:#fff; border-radius:16px; padding:28px; margin-bottom:24px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
      .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #e8e8e8; }
      .section-title { font-size:1.4rem; font-weight:800; color:#1a202c; margin:0; }
      .btn-primary-modern { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s ease; text-decoration:none; font-size:.9rem; display:inline-block; }
      .btn-primary-modern:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(102,126,234,.4); }
      .action-buttons {display: flex;gap: 8px;align-items: center;}
      .btn-icon {display: inline-flex;align-items: center;justify-content: center;width: 36px;height: 36px;border: none;border-radius: 8px;cursor: pointer;transition: all 0.2s ease;font-size: 1.1rem;position: relative;}
      .btn-icon:hover {transform: translateY(-2px);box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);}
      .btn-edit {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);olor: #fff;}
      .btn-edit:hover {
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
      }
      .btn-delete {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: #fff;
      }
      .btn-delete:hover {
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
      }
      .btn-icon::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: -32px;
        left: 50%;
        transform: translateX(-50%) scale(0.8);
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
      }
      .btn-icon:hover::after {opacity: 1;transform: translateX(-50%) scale(1);}
      .modern-table { width:100%; border-collapse:collapse; margin-top:16px; }
      .modern-table thead { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); }
      .modern-table th { padding:14px 16px; text-align:left; font-weight:700; color:#495057; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #dee2e6; }
      .modern-table td { padding:16px; border-bottom:1px solid #f1f3f5; color:#495057; font-size:.95rem; }
      .modern-table tbody tr { transition:background .2s ease; }
      .modern-table tbody tr:hover { background:#f8f9fa; }
      .status-badge { display:inline-block; padding:6px 12px; border-radius:20px; font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
      .status-available { background:#d4edda; color:#155724; }
      .status-maintenance { background:#fff3cd; color:#856404; }
      .status-occupied { background:#d1ecf1; color:#0c5460; }
      .status-cleaning { background:#e2e3e5; color:#383d41; }
      .status-out-of-service { background:#f8d7da; color:#721c24; }
      .real-time-badge { background:#17a2b8; color:#fff; padding:4px 10px; border-radius:12px; font-size:.75rem; font-weight:600; margin-left:8px; }
      .form-modern { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-top:20px; }
      .form-group-modern { display:flex; flex-direction:column; }
      .form-group-modern label { font-weight:600; color:#495057; margin-bottom:8px; font-size:.9rem; }
      .form-group-modern input, .form-group-modern select { padding:12px; border:1px solid #ced4da; border-radius:8px; font-size:.95rem; }
      .form-group-modern input:focus, .form-group-modern select:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
      .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center; }
      .modal.active { display:flex; }
      .modal-content { background:#fff; border-radius:16px; padding:32px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; }
      .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
      .modal-title { font-size:1.5rem; font-weight:800; color:#1a202c; margin:0; }
      .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#6c757d; }
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
          <a href="admin_gestion_chambres.php" class="nav-link active">Rooms</a>
          <a href="admin_gestion_reservations.php" class="nav-link">Reservations</a>
          <a href="admin_checkinout.php" class="nav-link">Check-In/Out</a>
          <a href="admin_gestion_employees.php" class="nav-link">Staff</a>
          <a href="logout.php">Logout</a>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h2>Manage Rooms</h2>
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
              <p class="mini-stat-value"><?php echo $total_rooms; ?></p>
              <p class="mini-stat-label">Total Rooms</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $available_rooms; ?></p>
              <p class="mini-stat-label">Availability</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $occupied_rooms; ?></p>
              <p class="mini-stat-label">Occupied</p>
            </div>
            <div class="mini-stat">
              <p class="mini-stat-value"><?php echo $maintenance_rooms; ?></p>
              <p class="mini-stat-label">Maintenance</p>
            </div>
          </div>
          
          <div class="admin-section">
            <div class="section-header">
              <button class="btn-primary-modern" onclick="openModal('add')">Add Room</button>
            </div>

            <?php if (!empty($rooms)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>N° Room</th>
                    <th>Type</th>
                    <th>Price/Night</th>
                    <th>Capacity</th>
                    <th>Equipment</th>
                    <th>Status</th>
                    <th>Availability</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rooms as $room): ?>
                    <tr>
                      <td><strong>N° <?php echo h($room['room_number']); ?></strong></td>
                      <td><?php echo h($room['type_name']); ?></td>
                      <td><strong><?php echo number_format((float)$room['base_price'], 0); ?> €</strong></td>
                      <td><?php echo h($room['capacity']); ?> pers.</td>
                      <td><?php echo h($room['amenities'] ?? '—'); ?></td>
                      <td>
                        <select onchange="updateStatus(<?php echo (int)$room['room_id']; ?>, this.value)" style="padding:6px 10px;border:1px solid #ced4da;border-radius:6px;font-size:.85rem;">
                          <option value="available" <?php echo $room['status']==='available'?'selected':''; ?>>Available</option>
                          <option value="maintenance" <?php echo $room['status']==='maintenance'?'selected':''; ?>>Maintenance</option>
                          <option value="cleaning" <?php echo $room['status']==='cleaning'?'selected':''; ?>>Cleaning</option>
                          <option value="out-of-service" <?php echo $room['status']==='out-of-service'?'selected':''; ?>>Out of Service</option>
                        </select>
                      </td>
                      <td>
                        <?php if ($room['is_occupied'] > 0): ?>
                          <span class="status-badge status-occupied">Occupied</span>
                        <?php else: ?>
                          <span class="status-badge status-available">Free</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="action-buttons">
                          <button 
                            class="btn-icon btn-edit" 
                            data-tooltip="Edit"
                            onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($room), ENT_QUOTES); ?>)">
                            E
                          </button>
                          <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this room ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="room_id" value="<?php echo (int)$room['room_id']; ?>">
                            <button 
                              type="submit" 
                              class="btn-icon btn-delete"
                              data-tooltip="Delete">
                              D
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p style="text-align:center;padding:40px;color:#6c757d;">No room registered.</p>
            <?php endif; ?>
          </div>

        </section>
      </main>
    </div>

    <div id="roomModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title" id="modalTitle">Add a room</h3>
          <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" id="roomForm">
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="room_id" id="formRoomId">
          
          <div class="form-modern">
            <div class="form-group-modern">
              <label>N° Room</label>
              <input type="text" name="room_number" id="formRoomNumber" required>
            </div>
            
            <div class="form-group-modern">
              <label>Type of Room</label>
              <select name="room_type_id" id="formRoomTypeId" required>
                <?php foreach ($room_types as $type): ?>
                  <option value="<?php echo (int)$type['room_type_id']; ?>"><?php echo h($type['type_name']); ?> (<?php echo number_format((float)$type['base_price'], 0); ?>€)</option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group-modern" id="statusGroup" style="display:none;">
              <label>Statut</label>
              <select name="status" id="formStatus">
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
                <option value="cleaning">Cleaning</option>
                <option value="out-of-service">Out of Service</option>
              </select>
            </div>
          </div>
          
          <div style="margin-top:24px;display:flex;gap:12px;">
            <button type="submit" class="btn-primary-modern" style="flex:1;">Save</button>
            <button type="button" class="btn-danger-modern" onclick="closeModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openModal(action, room = null) {
        const modal = document.getElementById('roomModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('roomForm');
        const statusGroup = document.getElementById('statusGroup');
        
        if (action === 'add') {
          title.textContent = 'Add Room';
          document.getElementById('formAction').value = 'add';
          form.reset();
          statusGroup.style.display = 'none';
        } else {
          title.textContent = 'Edit Room ';
          document.getElementById('formAction').value = 'edit';
          document.getElementById('formRoomId').value = room.room_id;
          document.getElementById('formRoomNumber').value = room.room_number;
          document.getElementById('formRoomTypeId').value = room.room_type_id;
          document.getElementById('formStatus').value = room.status;
          statusGroup.style.display = 'block';
        }
        
        modal.classList.add('active');
      }

      function closeModal() {
        document.getElementById('roomModal').classList.remove('active');
      }

      function updateStatus(roomId, status) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="room_id" value="${roomId}">
          <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
      }

      document.getElementById('roomModal').addEventListener('click', function(e) {
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
