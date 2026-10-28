<?php
// C:\xampp\htdocs\HEALTHCARE\api\sos\location.php
require_once __DIR__ . '/config.php';

try {
    $db = getDatabase();
    ensureSosTablesExist($db);

    $input = getJsonInput();

    // Extract sessionId from PATH_INFO, query parameter, or JSON body
    $sessionId = $_GET['sessionId'] ?? $input['sessionId'] ?? '';
    if (empty($sessionId) && !empty($_SERVER['PATH_INFO'])) {
        $sessionId = trim($_SERVER['PATH_INFO'], '/');
    }
    if (empty($sessionId) && !empty($_SERVER['REQUEST_URI'])) {
        $parts = explode('/location/', $_SERVER['REQUEST_URI']);
        if (isset($parts[1])) {
            $sessionId = explode('?', $parts[1])[0];
        }
    }

    if (empty($sessionId)) {
        respondJson(['error' => 'Missing sessionId parameter'], 400);
    }

    $lat = isset($input['lat']) ? (float)$input['lat'] : null;
    $lon = isset($input['lon']) ? (float)$input['lon'] : null;
    $updateNumber = isset($input['updateNumber']) ? (int)$input['updateNumber'] : 1;

    if ($lat === null || $lon === null) {
        respondJson(['error' => 'Missing lat or lon coordinates'], 400);
    }

    // Insert breadcrumb log
    $locStmt = $db->prepare("INSERT INTO `sos_location_logs` (`session_id`, `latitude`, `longitude`, `update_number`) VALUES (?, ?, ?, ?)");
    if ($locStmt) {
        $locStmt->bind_param('sddi', $sessionId, $lat, $lon, $updateNumber);
        $locStmt->execute();
        $locStmt->close();
    }

    // Update active session latest coordinates
    $upStmt = $db->prepare("UPDATE `sos_sessions` SET `latitude` = ?, `longitude` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `session_id` = ?");
    if ($upStmt) {
        $upStmt->bind_param('dds', $lat, $lon, $sessionId);
        $upStmt->execute();
        $upStmt->close();
    }

    respondJson([
        'success' => true,
        'sessionId' => $sessionId,
        'updateNumber' => $updateNumber,
        'lat' => $lat,
        'lon' => $lon
    ]);

} catch (Throwable $e) {
    respondJson(['error' => $e->getMessage()], 500);
}
?>
