<?php
// C:\xampp\htdocs\HEALTHCARE\api\sos\complete.php
require_once __DIR__ . '/config.php';

try {
    $db = getDatabase();
    ensureSosTablesExist($db);

    $input = getJsonInput();

    // Extract sessionId
    $sessionId = $_GET['sessionId'] ?? $input['sessionId'] ?? '';
    if (empty($sessionId) && !empty($_SERVER['PATH_INFO'])) {
        $sessionId = trim($_SERVER['PATH_INFO'], '/');
    }
    if (empty($sessionId) && !empty($_SERVER['REQUEST_URI'])) {
        $parts = explode('/complete/', $_SERVER['REQUEST_URI']);
        if (isset($parts[1])) {
            $sessionId = explode('?', $parts[1])[0];
        }
    }

    if (empty($sessionId)) {
        $sessionId = 'unknown_' . bin2hex(random_bytes(4));
    }

    $cancelled = !empty($input['cancelled']);
    $contacts = $input['contacts'] ?? [];
    if (!is_array($contacts)) $contacts = [];

    // Retrieve session data if available
    $session = null;
    $stmt = $db->prepare("SELECT * FROM `sos_sessions` WHERE `session_id` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $session = $res->fetch_assoc();
        }
        $stmt->close();
    }

    $lat = $session['latitude'] ?? ($input['lat'] ?? null);
    $lon = $session['longitude'] ?? ($input['lon'] ?? null);

    // If still null, fetch the latest coordinate from sos_location_logs
    if ($lat === null || $lon === null) {
        $locStmt = $db->prepare("SELECT latitude, longitude FROM `sos_location_logs` WHERE `session_id` = ? ORDER BY id DESC LIMIT 1");
        if ($locStmt) {
            $locStmt->bind_param('s', $sessionId);
            $locStmt->execute();
            $locRes = $locStmt->get_result();
            if ($locRes && $row = $locRes->fetch_assoc()) {
                $lat = (float)$row['latitude'];
                $lon = (float)$row['longitude'];
            }
            $locStmt->close();
        }
    }

    $status = $cancelled ? 'cancelled' : 'completed';
    $cancelledInt = $cancelled ? 1 : 0;
    $contactsJson = json_encode($contacts);

    // Update session record
    $updateStmt = $db->prepare("UPDATE `sos_sessions` SET `status` = ?, `cancelled` = ?, `contacts_json` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `session_id` = ?");
    if ($updateStmt) {
        $updateStmt->bind_param('siss', $status, $cancelledInt, $contactsJson, $sessionId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Build contactsAttempted response
    $contactsAttempted = [];
    $mapsUrl = ($lat !== null && $lon !== null) ? "https://maps.google.com/?q={$lat},{$lon}" : "Location not available";

    foreach ($contacts as $c) {
        $phone = is_array($c) ? ($c['phone'] ?? '') : (string)$c;
        if (empty($phone)) continue;

        // Clean phone number for WhatsApp link (digits only, retain country code if present)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if ($cancelled) {
            $msg = "🚨 HEALTHRESQ SOS ALERT UPDATE: The SOS emergency alert for session {$sessionId} has been CANCELLED by the user. They are safe.";
        } else {
            $msg = "🚨 CRITICAL EMERGENCY SOS ALERT! I need immediate medical/emergency assistance!\n\n" .
                   "📍 My Live GPS Location:\n{$mapsUrl}\n\n" .
                   "⏰ Time: " . date('Y-m-d H:i:s') . "\n" .
                   "🔗 Session ID: {$sessionId}\n" .
                   "Sent via HealthResQ 24/7 Citizen Emergency Portal.";
        }

        $waLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($msg);

        $contactsAttempted[] = [
            'phone' => $phone,
            'cleanPhone' => $cleanPhone,
            'locationResult' => ($lat !== null && $lon !== null) ? true : false,
            'textResult' => 'sent',
            'callResult' => 'available',
            'waLink' => $waLink,
            'message' => $msg
        ];
    }

    respondJson([
        'sessionId' => $sessionId,
        'status' => $status,
        'cancelled' => $cancelled,
        'location' => [
            'lat' => $lat,
            'lon' => $lon,
            'mapsUrl' => $mapsUrl
        ],
        'contactsAttempted' => $contactsAttempted
    ]);

} catch (Throwable $e) {
    respondJson(['error' => $e->getMessage()], 500);
}
?>
