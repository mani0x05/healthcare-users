<?php

include "db.php";


// =====================================================
// GET REQUIREMENT DETAILS
// =====================================================

$blood_group = trim($_POST['blood_group'] ?? '');
$city        = trim($_POST['city'] ?? '');
$patient_name = trim($_POST['patient_name'] ?? '');
$hospital_name = trim($_POST['hospital_name'] ?? '');
$units       = (int)($_POST['units'] ?? 0);
$contact     = trim($_POST['contact'] ?? '');


// =====================================================
// PATIENT LOCATION
// =====================================================

$patient_latitude =
    trim($_POST['latitude'] ?? '');

$patient_longitude =
    trim($_POST['longitude'] ?? '');


// =====================================================
// VALIDATION
// =====================================================

if ($blood_group === '') {

    die("Blood group was not provided.");

}

if ($city === '') {

    die("City was not provided.");

}


// =====================================================
// 3 MONTH COOLDOWN
// =====================================================

// A donor is eligible if:
// 1. They have never donated before
// OR
// 2. Their last donation was at least 3 months ago

$today = date('Y-m-d');


// =====================================================
// SEARCH DONORS
// =====================================================

$sql = "
    SELECT
        id,
        name,
        age,
        gender,
        blood_group,
        phone,
        email,
        city,
        state,
        available,
        latitude,
        longitude,
        last_donation
    FROM donors
    WHERE
        LOWER(TRIM(blood_group)) = LOWER(TRIM(?))
        AND LOWER(TRIM(city)) = LOWER(TRIM(?))
        AND available = 1
        AND (
            last_donation IS NULL
            OR last_donation = ''
            OR last_donation <= DATE_SUB(?, INTERVAL 3 MONTH)
        )
    ORDER BY name ASC
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "sss",
    $blood_group,
    $city,
    $today
);


if (!$stmt->execute()) {

    die(
        "Search failed: " .
        htmlspecialchars($stmt->error)
    );

}


$result = $stmt->get_result();

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Blood Donors Found</title>


<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f5f7fa;

}


header {

    background: #c1121f;

    color: white;

    padding: 20px;

    text-align: center;

}


.container {

    width: 92%;

    max-width: 1000px;

    margin: 30px auto;

}


.requirement {

    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.12);

}


.requirement h2 {

    color: #c1121f;

    margin-top: 0;

}


.donor-card {

    background: white;

    padding: 22px;

    margin-bottom: 18px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.12);

}


.donor-card h3 {

    color: #c1121f;

    margin-top: 0;

}


.info {

    margin: 7px 0;

}


.label {

    font-weight: bold;

}


.call-button {

    display: inline-block;

    margin-top: 12px;

    padding: 12px 20px;

    background: #c1121f;

    color: white;

    text-decoration: none;

    border-radius: 7px;

}


.call-button:hover {

    background: #8d0d16;

}


.no-donor {

    background: white;

    padding: 30px;

    text-align: center;

    border-radius: 12px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.12);

}


.back-button {

    display: inline-block;

    margin-top: 20px;

    padding: 12px 20px;

    background: #555;

    color: white;

    text-decoration: none;

    border-radius: 7px;

}

</style>

</head>


<body>


<header>

<h1>
🩸 Blood Donors
</h1>

</header>


<div class="container">


<!-- =================================================
     REQUIREMENT DETAILS
================================================= -->

<div class="requirement">

<h2>
Blood Requirement
</h2>


<div class="info">

<span class="label">
Patient:
</span>

<?php

echo htmlspecialchars(
    $patient_name
);

?>

</div>


<div class="info">

<span class="label">
Hospital:
</span>

<?php

echo htmlspecialchars(
    $hospital_name
);

?>

</div>


<div class="info">

<span class="label">
Blood Group:
</span>

<?php

echo htmlspecialchars(
    $blood_group
);

?>

</div>


<div class="info">

<span class="label">
City:
</span>

<?php

echo htmlspecialchars(
    $city
);

?>

</div>


<div class="info">

<span class="label">
Units Required:
</span>

<?php

echo htmlspecialchars(
    $units
);

?>

</div>


</div>


<!-- =================================================
     DONOR RESULTS
================================================= -->


<?php

if ($result->num_rows === 0) {

?>

<div class="no-donor">

<h2>
❌ No Available Donors Found
</h2>

<p>

No eligible donor was found for

<strong>
<?php echo htmlspecialchars($blood_group); ?>
</strong>

in

<strong>
<?php echo htmlspecialchars($city); ?>
</strong>.

</p>

<p>

Donors who donated within the last 3 months
are automatically excluded.

</p>


<a
    href="blood.html"
    class="back-button"
>

← Search Again

</a>

</div>

<?php

}

else {

?>


<h2>

✅
<?php

echo $result->num_rows;

?>

Eligible Donor(s) Found

</h2>


<?php

while ($row = $result->fetch_assoc()) {

?>


<div class="donor-card">


<h3>

🩸
<?php

echo htmlspecialchars(
    $row['name']
);

?>

</h3>


<div class="info">

<span class="label">
Blood Group:
</span>

<?php

echo htmlspecialchars(
    $row['blood_group']
);

?>

</div>


<div class="info">

<span class="label">
Age:
</span>

<?php

echo htmlspecialchars(
    $row['age']
);

?>

</div>


<div class="info">

<span class="label">
Gender:
</span>

<?php

echo htmlspecialchars(
    $row['gender']
);

?>

</div>


<div class="info">

<span class="label">
City:
</span>

<?php

echo htmlspecialchars(
    $row['city']
);

?>

</div>


<div class="info">

<span class="label">
Phone:
</span>

<?php

echo htmlspecialchars(
    $row['phone']
);

?>

</div>


<?php

if (
    !empty($row['last_donation'])
) {

?>

<div class="info">

<span class="label">
Last Donation:
</span>

<?php

echo htmlspecialchars(
    $row['last_donation']
);

?>

</div>

<?php

}

else {

?>

<div class="info">

<span class="label">
Last Donation:
</span>

Never recorded

</div>

<?php

}

?>


<a
    class="call-button"
    href="tel:<?php echo htmlspecialchars($row['phone']); ?>"
>

📞 Call Donor

</a>


</div>


<?php

}

}


$stmt->close();

?>


</div>


</body>

</html>