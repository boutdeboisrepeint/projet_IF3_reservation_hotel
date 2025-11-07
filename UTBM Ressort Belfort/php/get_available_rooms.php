<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    $check_in  = $_GET['check_in']  ?? '';
    $check_out = $_GET['check_out'] ?? '';
    $type_id   = $_GET['type_id']   ?? '';

    if (empty($check_in) || empty($check_out)) {
        echo json_encode(['error' => 'Missing dates']);
        exit;
    }

    if ($check_out <= $check_in) {
        echo json_encode(['error' => 'Invalid date range']);
        exit;
    }

    $sql = "
        SELECT 
            r.room_id,
            r.room_number,
            rt.room_type_id,
            rt.type_name,
            rt.base_price AS price_per_night,
            rt.capacity,
            rt.description,
            rt.amenities
        FROM room r
        JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE r.status = 'available'
          AND r.room_id NOT IN (
              SELECT room_id 
              FROM reservation 
              WHERE status IN ('confirmed', 'checked_in', 'pending')
                AND (
                    (check_in_date <= :check_in AND check_out_date > :check_in)
                    OR (check_in_date < :check_out AND check_out_date >= :check_out)
                    OR (check_in_date >= :check_in AND check_out_date <= :check_out)
                )
          )
    ";

    $params = [
        'check_in'  => $check_in,
        'check_out' => $check_out
    ];

    if (!empty($type_id) && is_numeric($type_id)) {
        $sql .= " AND r.room_type_id = :type_id";
        $params['type_id'] = (int)$type_id;
    }

    $sql .= " ORDER BY rt.base_price ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (Exception $e) {
    error_log("Error get_available_rooms: " . $e->getMessage());
    echo json_encode(['error' => 'Server error.']);
}
