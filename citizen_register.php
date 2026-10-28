<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

/*
 * This file uses the existing db.php from your HEALTHCARE project.
 * It accepts JSON from index.html and stores the citizen in MySQL.
 */

function getDbConnection() {
    global $conn, $con, $mysqli, $db;

    if (isset($conn) && $conn instanceof mysqli) return $conn;
    if (isset($con) && $con instanceof mysqli) return $con;
    if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
    if (isset($db) && $db instanceof mysqli) return $db;

    throw new Exception('No mysqli connection found in db.php. Your db.php should create $conn = new mysqli(...).');
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
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        respond(false, 'Invalid request data.', null, 400);
    }

    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = preg_replace('/\D+/', '', $input['phone'] ?? '');
    $dob = trim($input['dob'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $password = (string)($input['password'] ?? '');

    $nationalId = trim($input['national_id'] ?? '');
    $blood = trim($input['blood_group'] ?? '');
    $weight = (float)($input['weight'] ?? 0);
    $sosContact = trim($input['sos_contact'] ?? '');
    $conditions = trim($input['conditions'] ?? 'None reported');

    $country = trim($input['country'] ?? 'India');
    $state = trim($input['state'] ?? 'Telangana');
    $district = trim($input['district'] ?? '');
    $mandal = trim($input['mandal'] ?? '');
    $village = trim($input['village'] ?? '');
    $pincode = trim($input['pincode'] ?? '');

    $isDonor = !empty($input['is_donor']) ? 1 : 0;
    $donorAvailability = trim($input['donor_availability'] ?? 'Not Registered (Citizen Only)');
    $donorLastDate = trim($input['donor_last_date'] ?? '');
    $donorConsent = !empty($input['donor_consent']) ? 1 : 0;

    if ($name === '' || $email === '' || $phone === '' || $dob === '' ||
        $gender === '' || $password === '' || $blood === '' ||
        $weight <= 0 || $sosContact === '' || $district === '' ||
        $mandal === '' || $village === '' || $pincode === '') {
        respond(false, 'Please fill all required registration fields.', null, 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Please enter a valid email address.', null, 422);
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        respond(false, 'Mobile number must contain exactly 10 digits.', null, 422);
    }

    if (strlen($password) < 6) {
        respond(false, 'Password must contain at least 6 characters.', null, 422);
    }

    $allowedBlood = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    if (!in_array($blood, $allowedBlood, true)) {
        respond(false, 'Invalid blood group.', null, 422);
    }

    // Check duplicate email or phone.
    $check = $db->prepare('SELECT id FROM citizens WHERE email = ? OR phone = ? LIMIT 1');
    if (!$check) throw new Exception($db->error);
    $check->bind_param('ss', $email, $phone);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        respond(false, 'This email or mobile number is already registered. Please login instead.', null, 409);
    }
    $check->close();

    $citizenId = 'CIT-' . strtoupper(bin2hex(random_bytes(4))) . '-IN';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = 'INSERT INTO citizens
        (citizen_id, full_name, email, phone, password_hash, date_of_birth, gender,
         national_id_reference, blood_group, weight_kg, sos_contact, known_conditions,
         country, state, district, mandal, village, pincode,
         is_donor, donor_availability, donor_last_date, donor_consent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?)';

    $stmt = $db->prepare($sql);
    if (!$stmt) throw new Exception($db->error);

    $stmt->bind_param(
        'sssssssssdssssssssissi',
        $citizenId,
        $name,
        $email,
        $phone,
        $passwordHash,
        $dob,
        $gender,
        $nationalId,
        $blood,
        $weight,
        $sosContact,
        $conditions,
        $country,
        $state,
        $district,
        $mandal,
        $village,
        $pincode,
        $isDonor,
        $donorAvailability,
        $donorLastDate,
        $donorConsent
    );

    if (!$stmt->execute()) {
        if ($stmt->errno == 1062) {
            respond(false, 'Email, mobile number, or citizen ID already exists.', null, 409);
        }
        throw new Exception($stmt->error);
    }

    $stmt->close();

    $user = [
        'id' => $citizenId,
        'name' => $name,
        'email' => $email,
        'phone' => '+91 ' . substr($phone, 0, 5) . ' ' . substr($phone, 5),
        'rawPhone' => $phone,
        'dob' => $dob,
        'gender' => $gender,
        'bloodGroup' => $blood,
        'weight' => (string)$weight,
        'conditions' => $conditions,
        'sosContact' => $sosContact,
        'country' => $country,
        'state' => $state,
        'district' => $district,
        'mandal' => $mandal,
        'village' => $village,
        'pincode' => $pincode,
        'isDonor' => (bool)$isDonor,
        'donorAvailability' => $donorAvailability,
        'donorLastDonation' => $donorLastDate,
        'medicalRecords' => []
    ];

    respond(true, 'Registration saved successfully.', $user, 201);

} catch (Throwable $e) {
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
?>
