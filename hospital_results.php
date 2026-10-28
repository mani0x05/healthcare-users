<?php

require_once "db.php";

/*
===========================================================
GET USER REQUIREMENTS
===========================================================
*/

$specialization = isset($_GET['specialization'])
    ? trim($_GET['specialization'])
    : '';

$location = isset($_GET['location'])
    ? trim($_GET['location'])
    : '';

$age_group = isset($_GET['age_group'])
    ? trim($_GET['age_group'])
    : '';

$problem = isset($_GET['problem'])
    ? trim($_GET['problem'])
    : '';


/*
===========================================================
VALIDATION
===========================================================
*/

if ($specialization === '' || $location === '' || $age_group === '') {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            padding:50px;
        '>
            <h2>Invalid Request</h2>
            <p>Please fill all required details.</p>
            <a href='specialized_hospital_form.php'>
                Go Back
            </a>
        </div>
    ");

}


/*
===========================================================
GET ALL HOSPITALS
===========================================================
*/

$hospitals = [];

$sql = "
    SELECT *
    FROM hospitals
    ORDER BY hospital_name ASC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $hospitals[] = $row;

    }

}


/*
===========================================================
GET DOCTORS FOR HOSPITAL
===========================================================
*/

function getDoctors($conn, $hospitalId, $specialization)
{

    $doctors = [];

    $sql = "
        SELECT *
        FROM hospital_doctors
        WHERE hospital_id = ?
        AND LOWER(TRIM(specialization))
        = LOWER(TRIM(?))
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return $doctors;
    }

    $stmt->bind_param(
        "is",
        $hospitalId,
        $specialization
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $doctors[] = $row;

    }

    $stmt->close();

    return $doctors;
}


/*
===========================================================
GET BEDS FOR HOSPITAL
===========================================================
*/

function getBeds($conn, $hospitalId)
{

    $beds = [];

    $sql = "
        SELECT *
        FROM hospital_beds
        WHERE hospital_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return $beds;
    }

    $stmt->bind_param(
        "i",
        $hospitalId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $beds[] = $row;

    }

    $stmt->close();

    return $beds;
}


/*
===========================================================
BED INFORMATION
===========================================================
*/

function calculateBeds($beds)
{

    $generalTotal = 0;
    $generalAvailable = 0;

    $icuTotal = 0;
    $icuAvailable = 0;

    $emergencyTotal = 0;
    $emergencyAvailable = 0;


    foreach ($beds as $bed) {

        $generalTotal += (int)(
            $bed['general_total'] ?? 0
        );

        $generalAvailable += (int)(
            $bed['general_available'] ?? 0
        );


        $icuTotal += (int)(
            $bed['icu_total'] ?? 0
        );

        $icuAvailable += (int)(
            $bed['icu_available'] ?? 0
        );


        $emergencyTotal += (int)(
            $bed['emergency_total'] ?? 0
        );

        $emergencyAvailable += (int)(
            $bed['emergency_available'] ?? 0
        );

    }


    return [

        'general_total' => $generalTotal,

        'general_available' => $generalAvailable,

        'icu_total' => $icuTotal,

        'icu_available' => $icuAvailable,

        'emergency_total' => $emergencyTotal,

        'emergency_available' => $emergencyAvailable

    ];
}


/*
===========================================================
ESCAPE HTML
===========================================================
*/

function safe($value)
{

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );

}


/*
===========================================================
GOOGLE MAPS DIRECTIONS
===========================================================
*/

function getMapsLink($hospital)
{

    $name = $hospital['hospital_name'] ?? 'Hospital';

    $address = $hospital['address'] ?? '';

    $city = $hospital['city'] ?? '';

    $destination = trim(
        $name . ' ' .
        $address . ' ' .
        $city
    );

    return
        "https://www.google.com/maps/dir/?api=1&destination="
        . urlencode($destination);

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Suitable Specialized Hospitals - HealthResQ
</title>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {

    box-sizing: border-box;

    margin: 0;

    padding: 0;

    font-family: Arial, sans-serif;

}


body {

    background: #f4f7fb;

    color: #222;

}


/* =========================================================
   HEADER
========================================================= */

.header {

    background: #d90429;

    color: white;

    padding: 25px 20px;

    text-align: center;

}


.header h1 {

    font-size: 30px;

    margin-bottom: 8px;

}


.header p {

    font-size: 15px;

}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.container {

    width: 94%;

    max-width: 1100px;

    margin: 30px auto;

}


/* =========================================================
   BACK BUTTON
========================================================= */

.back {

    display: inline-block;

    text-decoration: none;

    color: #d90429;

    font-weight: bold;

    margin-bottom: 20px;

}


/* =========================================================
   REQUIREMENT BOX
========================================================= */

.requirement {

    background: white;

    padding: 22px;

    border-radius: 16px;

    margin-bottom: 28px;

    box-shadow:
        0 4px 18px rgba(0,0,0,0.07);

}


.requirement h2 {

    margin-bottom: 15px;

}


.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

}


.tag {

    background: #fff0f2;

    color: #c00023;

    padding: 9px 14px;

    border-radius: 20px;

    font-size: 14px;

    font-weight: bold;

}


/* =========================================================
   RESULTS TITLE
========================================================= */

.results-title {

    margin-bottom: 18px;

}


/* =========================================================
   HOSPITAL CARD
========================================================= */

.hospital-card {

    background: white;

    padding: 25px;

    border-radius: 18px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 22px rgba(0,0,0,0.08);

}


.hospital-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

}


.hospital-name {

    font-size: 23px;

    font-weight: bold;

    color: #d90429;

    margin-bottom: 9px;

}


.address {

    color: #666;

    line-height: 1.5;

}


.hospital-status {

    padding: 8px 14px;

    border-radius: 20px;

    background: #e9f8ef;

    color: #087f3f;

    font-weight: bold;

    white-space: nowrap;

}


/* =========================================================
   DETAILS GRID
========================================================= */

.details {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;

    margin-top: 22px;

}


.detail-box {

    background: #f7f8fa;

    padding: 16px;

    border-radius: 12px;

}


.detail-box strong {

    display: block;

    margin-bottom: 8px;

    color: #333;

}


.detail-box span {

    color: #555;

    line-height: 1.7;

}


/* =========================================================
   DOCTORS
========================================================= */

.doctors {

    margin-top: 25px;

}


.doctors h3 {

    margin-bottom: 13px;

}


.doctor {

    background: #f8f9fb;

    border-left: 4px solid #d90429;

    padding: 13px;

    margin-bottom: 9px;

    border-radius: 7px;

}


.doctor-name {

    font-weight: bold;

    margin-bottom: 5px;

}


.doctor-info {

    color: #666;

    font-size: 14px;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display: flex;

    gap: 12px;

    margin-top: 24px;

}


.btn {

    text-decoration: none;

    padding: 13px 20px;

    border-radius: 9px;

    font-weight: bold;

    text-align: center;

}


.map-btn {

    background: #d90429;

    color: white;

}


.map-btn:hover {

    background: #b80322;

}


.call-btn {

    background: #e8f8ee;

    color: #087f3f;

}


/* =========================================================
   NO RESULTS
========================================================= */

.no-results {

    background: white;

    padding: 45px 25px;

    border-radius: 16px;

    text-align: center;

    box-shadow:
        0 4px 18px rgba(0,0,0,0.07);

}


.no-results h2 {

    margin-bottom: 10px;

}


.no-results p {

    color: #666;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .hospital-top {

        flex-direction: column;

    }


    .details {

        grid-template-columns: 1fr;

    }


    .actions {

        flex-direction: column;

    }


    .btn {

        width: 100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

    <h1>
        🏥 Specialized Hospitals
    </h1>

    <p>
        Find hospitals based on your treatment requirement
    </p>

</div>


<div class="container">


<!-- =====================================================
     BACK
===================================================== -->

<a
    href="specialized_hospital_form.php"
    class="back"
>
    ← Change Requirements
</a>


<!-- =====================================================
     USER REQUIREMENT
===================================================== -->

<div class="requirement">

    <h2>
        Your Requirement
    </h2>


    <div class="tags">

        <div class="tag">

            🩺
            <?php echo safe($specialization); ?>

        </div>


        <div class="tag">

            📍
            <?php echo safe($location); ?>

        </div>


        <div class="tag">

            👤
            <?php echo safe($age_group); ?>

        </div>


        <?php if ($problem !== '') { ?>

            <div class="tag">

                📝 Problem Provided

            </div>

        <?php } ?>

    </div>

</div>


<!-- =====================================================
     RESULTS
===================================================== -->

<h2 class="results-title">

    🏥 Hospitals for
    <?php echo safe($specialization); ?>

</h2>


<?php


if (empty($hospitals)) {

?>

    <div class="no-results">

        <h2>
            😔 No Hospitals Available
        </h2>

        <p>
            No hospital information is currently available.
        </p>

    </div>

<?php

}


else {


    foreach ($hospitals as $hospital) {


        /*
        ---------------------------------------------------
        HOSPITAL BASIC DETAILS
        ---------------------------------------------------
        */

        $hospitalId = (int)(
            $hospital['id'] ?? 0
        );


        $hospitalName =
            $hospital['hospital_name']
            ?? 'Hospital';


        $address =
            $hospital['address']
            ?? 'Address not available';


        $city =
            $hospital['city']
            ?? '';


        $phone =
            $hospital['phone']
            ?? '';


        /*
        ---------------------------------------------------
        TIMINGS
        ---------------------------------------------------
        */

        $openingTime =
            $hospital['opening_time']
            ?? '';


        $closingTime =
            $hospital['closing_time']
            ?? '';


        if (
            $openingTime !== '' &&
            $closingTime !== ''
        ) {

            $timings =
                date(
                    "h:i A",
                    strtotime($openingTime)
                )
                . " - " .
                date(
                    "h:i A",
                    strtotime($closingTime)
                );

        }

        else {

            $timings =
                "Timings not available";

        }


        /*
        ---------------------------------------------------
        EMERGENCY SERVICE
        ---------------------------------------------------
        */

        $emergencyAvailable =
            (int)(
                $hospital['emergency_available']
                ?? 0
            );


        /*
        ---------------------------------------------------
        DOCTORS
        ---------------------------------------------------
        */

        $doctors = getDoctors(
            $conn,
            $hospitalId,
            $specialization
        );


        /*
        ---------------------------------------------------
        BEDS
        ---------------------------------------------------
        */

        $beds = getBeds(
            $conn,
            $hospitalId
        );


        $bedInfo =
            calculateBeds($beds);


?>


<!-- =====================================================
     HOSPITAL CARD
===================================================== -->

<div class="hospital-card">


    <!-- HOSPITAL HEADER -->

    <div class="hospital-top">


        <div>

            <div class="hospital-name">

                🏥
                <?php
                echo safe($hospitalName);
                ?>

            </div>


            <div class="address">

                📍
                <?php
                echo safe($address);
                ?>

                <?php

                if ($city !== '') {

                    echo ", " . safe($city);

                }

                ?>

            </div>

        </div>


        <div class="hospital-status">

            ✓ Hospital Available

        </div>


    </div>



    <!-- =================================================
         HOSPITAL DETAILS
    ================================================= -->

    <div class="details">


        <!-- SPECIALIZATION -->

        <div class="detail-box">

            <strong>

                🩺 Specialization

            </strong>


            <span>

                <?php
                echo safe($specialization);
                ?>


                <br>


                <?php

                if (count($doctors) > 0) {

                    echo "👨‍⚕️ "
                        . count($doctors)
                        . " doctor(s) found";

                }

                else {

                    echo "Doctor information not available";

                }

                ?>

            </span>

        </div>



        <!-- GENERAL BEDS -->

        <div class="detail-box">

            <strong>

                🛏️ General Beds

            </strong>


            <span>

                🟢 Available:
                <?php
                echo $bedInfo['general_available'];
                ?>

                <br>

                Total:
                <?php
                echo $bedInfo['general_total'];
                ?>

            </span>

        </div>



        <!-- ICU -->

        <div class="detail-box">

            <strong>

                🏥 ICU Beds

            </strong>


            <span>

                🟢 Available:
                <?php
                echo $bedInfo['icu_available'];
                ?>

                <br>

                Total:
                <?php
                echo $bedInfo['icu_total'];
                ?>

            </span>

        </div>



        <!-- EMERGENCY BEDS -->

        <div class="detail-box">

            <strong>

                🚨 Emergency Beds

            </strong>


            <span>

                🟢 Available:
                <?php
                echo $bedInfo['emergency_available'];
                ?>

                <br>

                Total:
                <?php
                echo $bedInfo['emergency_total'];
                ?>

            </span>

        </div>



        <!-- TIMINGS -->

        <div class="detail-box">

            <strong>

                🕐 Hospital Timings

            </strong>


            <span>

                <?php
                echo safe($timings);
                ?>

            </span>

        </div>



        <!-- EMERGENCY SERVICE -->

        <div class="detail-box">

            <strong>

                🚨 Emergency Services

            </strong>


            <span>

                <?php

                if ($emergencyAvailable === 1) {

                    echo "🟢 Emergency Services Available";

                }

                else {

                    echo "🔴 Emergency Services Not Available";

                }

                ?>

            </span>

        </div>


    </div>



    <!-- =================================================
         DOCTOR SECTION
    ================================================= -->

    <div class="doctors">


        <h3>

            👨‍⚕️
            <?php
            echo safe($specialization);
            ?>
            Doctors

        </h3>


        <?php


        if (empty($doctors)) {

        ?>

            <div class="doctor">

                <div class="doctor-info">

                    No doctors are currently registered
                    for this specialization.

                </div>

            </div>

        <?php

        }


        else {


            foreach ($doctors as $doctor) {


                $doctorName =
                    $doctor['doctor_name']
                    ?? 'Doctor';


                $doctorSpecialization =
                    $doctor['specialization']
                    ?? $specialization;


                $doctorAvailable =
                    (int)(
                        $doctor['available']
                        ?? 0
                    );


        ?>

            <div class="doctor">


                <div class="doctor-name">

                    👨‍⚕️
                    <?php
                    echo safe($doctorName);
                    ?>

                </div>


                <div class="doctor-info">

                    🩺
                    <?php
                    echo safe(
                        $doctorSpecialization
                    );
                    ?>


                    &nbsp;&nbsp; | &nbsp;&nbsp;


                    <?php

                    if ($doctorAvailable === 1) {

                        echo "🟢 Available";

                    }

                    else {

                        echo "🔴 Not Available";

                    }

                    ?>

                </div>


            </div>


        <?php

            }

        }

        ?>

    </div>



    <!-- =================================================
         ACTION BUTTONS
    ================================================= -->

    <div class="actions">


        <!-- GOOGLE MAPS -->

        <a
            class="btn map-btn"
            href="<?php
                echo safe(
                    getMapsLink($hospital)
                );
            ?>"
            target="_blank"
        >

            📍 Get Directions

        </a>



        <!-- CALL -->

        <?php

        if ($phone !== '') {

        ?>

            <a
                class="btn call-btn"
                href="tel:<?php echo safe($phone); ?>"
            >

                📞 Call Hospital

            </a>

        <?php

        }

        ?>


    </div>


</div>


<?php

    }

}


$conn->close();

?>


</div>


</body>

</html>