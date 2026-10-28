<?php
// C:\xampp\htdocs\HEALTHCARE\api\sos\trigger.php
require_once __DIR__ . '/config.php';

try {
    $db = getDatabase();
    ensureSosTablesExist($db);

    $input = getJsonInput();

    $sessionId = 'sos_' . bin2hex(random_bytes(8));
    $device = $input['device'] ?? 'web';
    $gestureType = $input['gestureType'] ?? 'manual_button';
    $senderPhone = $input['senderPhone'] ?? '';
    $citizenId = $input['citizenId'] ?? null;

    $lat = null;
    $lon = null;
    if (isset($input['location']) && is_array($input['location'])) {
        $lat = isset($input['location']['lat']) ? (float)$input['location']['lat'] : null;
        $lon = isset($input['location']['lon']) ? (float)$input['location']['lon'] : null;
    }

    $stmt = $db->prepare("INSERT INTO `sos_sessions` (`session_id`, `citizen_id`, `sender_phone`, `gesture_type`, `device`, `latitude`, `longitude`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, 'triggered')");
    if (!$stmt) {
        throw new Exception($db->error);
    }
    $stmt->bind_param('sssssdd', $sessionId, $citizenId, $senderPhone, $gestureType, $device, $lat, $lon);
    $stmt->execute();
    $stmt->close();

    if ($lat !== null && $lon !== null) {
        $locStmt = $db->prepare("INSERT INTO `sos_location_logs` (`session_id`, `latitude`, `longitude`, `update_number`) VALUES (?, ?, ?, 1)");
        if ($locStmt) {
            $locStmt->bind_param('sdd', $sessionId, $lat, $lon);
            $locStmt->execute();
            $locStmt->close();
        }
    }

    respondJson([
        'sessionId' => $sessionId,
        'countdownSeconds' => 10,
        'message' => 'Emergency SOS session initialized'
    ]);

} catch (Throwable $e) {
    respondJson(['error' => $e->getMessage()], 500);
}
?>
