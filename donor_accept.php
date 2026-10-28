<?php

include "db.php";


/*
====================================================
GET DONOR ID
====================================================
*/

$donor_id = isset($_GET['donor_id'])
    ? (int)$_GET['donor_id']
    : 0;


if ($donor_id <= 0) {

    die("Invalid donor.");

}


/*
====================================================
CHECK DONOR
====================================================
*/

$sql = "
    SELECT
        id,
        name,
        available,
        next_available_at
    FROM donors
    WHERE id = ?
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $donor_id
);

$stmt->execute();

$result = $stmt->get_result();

$donor = $result->fetch_assoc();

$stmt->close();


if (!$donor) {

    die("Donor not found.");

}


/*
====================================================
START 3-MONTH COOLDOWN
====================================================

The date starts NOW.

accepted_at:
    current date/time

next_available_at:
    current date/time + 3 months

available:
    0

====================================================
*/

$sql = "
    UPDATE donors

    SET
        accepted_at = NOW(),

        next_available_at =
            DATE_ADD(NOW(), INTERVAL 3 MONTH),

        available = 0

    WHERE id = ?
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $donor_id
);


if (!$stmt->execute()) {

    die(
        "Unable to update donor: " .
        htmlspecialchars($stmt->error)
    );

}


$stmt->close();


/*
====================================================
GET NEW NEXT AVAILABLE DATE
====================================================
*/

$sql = "
    SELECT
        next_available_at
    FROM donors
    WHERE id = ?
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $donor_id
);

$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$stmt->close();


$next_available =
    $data['next_available_at'];


?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Donor Accepted</title>


<style>

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f5f7fa;

    display: flex;

    justify-content: center;

    align-items: center;

    min-height: 100vh;

}


.card {

    background: white;

    width: 90%;

    max-width: 500px;

    padding: 35px;

    border-radius: 15px;

    text-align: center;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.15);

}


h1 {

    color: #1b7a35;

}


.date {

    background: #f1f8f3;

    padding: 15px;

    border-radius: 8px;

    margin: 20px 0;

    font-weight: bold;

}


a {

    display: inline-block;

    margin-top: 15px;

    padding: 12px 20px;

    background: #c1121f;

    color: white;

    text-decoration: none;

    border-radius: 7px;

}

</style>

</head>


<body>


<div class="card">


<h1>
✅ Donor Accepted
</h1>


<p>

<strong>
<?php echo htmlspecialchars($donor['name']); ?>
</strong>

has accepted the blood request.

</p>


<p>

The donor has been temporarily removed
from blood availability.

</p>


<div class="date">

Next available:

<br><br>

<?php

echo date(
    "d M Y, h:i A",
    strtotime($next_available)
);

?>

</div>


<p>

The 3-month cooldown started automatically
from the acceptance time.

</p>


<a href="blood.html">

Search Another Donor

</a>


</div>


</body>

</html>