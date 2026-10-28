<?php
// C:\xampp\htdocs\HEALTHCARE\api\sos\config.php
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../db.php';

function getDatabase() {
    global $conn, $con, $mysqli, $db;
    if (isset($conn) && $conn instanceof mysqli) return $conn;
    if (isset($con) && $con instanceof mysqli) return $con;
    if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
    if (isset($db) && $db instanceof mysqli) return $db;
    throw new Exception('No valid mysqli connection found in db.php');
}

function respondJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }
    return $data;
}

function ensureSosTablesExist($db) {
    static $checked = false;
    if ($checked) return;

    $createSessionsTable = "CREATE TABLE IF NOT EXISTS `sos_sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` VARCHAR(64) UNIQUE NOT NULL,
        `citizen_id` VARCHAR(64) DEFAULT NULL,
        `sender_phone` VARCHAR(32) DEFAULT NULL,
        `gesture_type` VARCHAR(64) DEFAULT NULL,
        `device` VARCHAR(32) DEFAULT NULL,
        `latitude` DECIMAL(10, 8) DEFAULT NULL,
        `longitude` DECIMAL(11, 8) DEFAULT NULL,
        `status` ENUM('triggered', 'completed', 'cancelled') DEFAULT 'triggered',
        `cancelled` TINYINT(1) DEFAULT 0,
        `contacts_json` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $createLocationsTable = "CREATE TABLE IF NOT EXISTS `sos_location_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` VARCHAR(64) NOT NULL,
        `latitude` DECIMAL(10, 8) NOT NULL,
        `longitude` DECIMAL(11, 8) NOT NULL,
        `update_number` INT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->query($createSessionsTable);
    $db->query($createLocationsTable);
    $checked = true;
}
?>
