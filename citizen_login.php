<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function getDbConnection() {
    global $conn, $con, $mysqli, $db;

    if (isset($conn) && $conn instanceof mysqli) {
        return $conn;
    }

    if (isset($con) && $con instanceof mysqli) {
        return $con;
    }

    if (isset($mysqli) && $mysqli instanceof mysqli) {
        return $mysqli;
    }

    if (isset($db) && $db instanceof mysqli) {
        return $db;
    }

    throw new Exception('No mysqli connection found in db.php.');
}

function respond($success, $message, $user = null, $status = 200) {
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'user' => $user
    ]);

    exit;
}

try {

    $db = getDbConnection();

    $rawInput = file_get_contents('php://input');

    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        $input = $_POST;
    }

    $identifier = trim($input['identifier'] ?? '');
    $password = (string)($input['password'] ?? '');

    if ($identifier === '' || $password === '') {
        respond(
            false,
            'Please enter your mobile/email and password.',
            null,
            422
        );
    }

    $identifierLower = strtolower($identifier);

    // Keep only numbers from mobile number
    $phone = preg_replace('/\D+/', '', $identifier);

    $sql = "
        SELECT
            id,
            citizen_id,
            full_name,
            email,
            phone,
            password_hash,
            date_of_birth,
            gender,
            national_id_reference,
            blood_group,
            weight_kg,
            sos_contact,
            known_conditions,
            country,
            state,
            district,
            mandal,
            village,
            pincode,
            is_donor,
            donor_availability,
            donor_last_date,
            donor_consent
        FROM citizens
        WHERE LOWER(email) = ?
           OR phone = ?
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception($db->error);
    }

    $stmt->bind_param(
        'ss',
        $identifierLower,
        $phone
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        respond(
            false,
            'No registered citizen found with that mobile/email.',
            null,
            401
        );
    }

    $row = $result->fetch_assoc();

    $stmt->close();

    if (!password_verify($password, $row['password_hash'])) {

        respond(
            false,
            'Incorrect password.',
            null,
            401
        );
    }

    $formattedPhone = $row['phone'];

    if (strlen($formattedPhone) >= 10) {
        $formattedPhone =
            '+91 ' .
            substr($formattedPhone, 0, 5) .
            ' ' .
            substr($formattedPhone, 5);
    }

    $user = [
        'id' => $row['citizen_id'],
        'name' => $row['full_name'],
        'email' => $row['email'],
        'phone' => $formattedPhone,
        'rawPhone' => $row['phone'],

        'dob' => $row['date_of_birth'],
        'gender' => $row['gender'],

        'bloodGroup' => $row['blood_group'],
        'weight' => (string)$row['weight_kg'],

        'conditions' => $row['known_conditions'],
        'sosContact' => $row['sos_contact'],

        'country' => $row['country'],
        'state' => $row['state'],
        'district' => $row['district'],
        'mandal' => $row['mandal'],
        'village' => $row['village'],
        'pincode' => $row['pincode'],

        'isDonor' => (bool)$row['is_donor'],
        'donorAvailability' => $row['donor_availability'],
        'donorLastDonation' => $row['donor_last_date'] ?? '',

        'medicalRecords' => []
    ];

    respond(
        true,
        'Login successful.',
        $user
    );

} catch (Throwable $e) {

    respond(
        false,
        'Server error: ' . $e->getMessage(),
        null,
        500
    );
}

?>