<?php

include "db.php";

$message = "";
$success = false;


/* =====================================================
   FORM SUBMISSION
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =================================================
       GET FORM VALUES
    ================================================= */

    $name =
        trim($_POST['name'] ?? '');

    $age =
        trim($_POST['age'] ?? '');

    $gender =
        trim($_POST['gender'] ?? '');

    $blood_group =
        trim($_POST['blood_group'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $city =
        trim($_POST['city'] ?? '');


    /* =================================================
       OPTIONAL VALUES
    ================================================= */

    $state =
        trim($_POST['state'] ?? '');

    $pin_code =
        trim($_POST['pin_code'] ?? '');

    $village =
        trim($_POST['village'] ?? '');

    $available =
        trim($_POST['available'] ?? 'Yes');


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $name === "" ||
        $age === "" ||
        $gender === "" ||
        $blood_group === "" ||
        $phone === "" ||
        $city === ""
    ) {

        $message =
            "Please fill all required fields.";

    }


    elseif (
        !is_numeric($age) ||
        $age < 18 ||
        $age > 65
    ) {

        $message =
            "Age must be between 18 and 65.";

    }


    elseif (
        !preg_match(
            '/^[0-9]{10}$/',
            $phone
        )
    ) {

        $message =
            "Please enter a valid 10-digit mobile number.";

    }


    elseif (
        $email !== "" &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

    }


    else {


        /* =============================================
           INSERT DONOR
           
           IMPORTANT:
           city is stored in donors.city
        ============================================= */

        $sql = "
            INSERT INTO donors
            (
                name,
                age,
                gender,
                blood_group,
                phone,
                email,
                city
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            $message =
                "Database Error: " .
                $conn->error;

        }


        else {


            $age =
                (int)$age;


            /* =========================================
               BIND VALUES
            ========================================= */

            $stmt->bind_param(
                "sisssss",
                $name,
                $age,
                $gender,
                $blood_group,
                $phone,
                $email,
                $city
            );


            /* =========================================
               EXECUTE
            ========================================= */

            if ($stmt->execute()) {

                header(
                    "Location: donor_success.php"
                );

                exit();

            }


            else {

                $message =
                    "Registration failed: " .
                    $stmt->error;

            }


            $stmt->close();

        }

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Blood Donor Registration</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    min-height: 100vh;

    display: flex;

    justify-content: center;

    align-items: center;

    background:
        linear-gradient(
            135deg,
            #8b0000,
            #e63946
        );

    padding: 20px;

}


.container {

    width: 100%;

    max-width: 550px;

    background: white;

    padding: 35px;

    border-radius: 15px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.3);

}


h1 {

    text-align: center;

    color: #c00000;

    margin-bottom: 10px;

}


.subtitle {

    text-align: center;

    color: #666;

    margin-bottom: 25px;

}


.info {

    background: #e8f5e9;

    color: #1b5e20;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 20px;

    text-align: center;

}


.error {

    background: #ffe0e0;

    color: #b00000;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 20px;

    text-align: center;

}


.form-group {

    margin-bottom: 18px;

}


label {

    display: block;

    margin-bottom: 7px;

    font-weight: bold;

    color: #333;

}


input,
select {

    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

}


input:focus,
select:focus {

    outline: none;

    border-color: #c00000;

}


button {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 7px;

    background: #c00000;

    color: white;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

}


button:hover {

    background: #8b0000;

}


.required {

    color: red;

}


.back {

    display: block;

    text-align: center;

    margin-top: 20px;

    color: #c00000;

    text-decoration: none;

    font-weight: bold;

}

</style>

</head>


<body>


<div class="container">


<h1>
🩸 Blood Donor Registration
</h1>


<p class="subtitle">

Register as a blood donor and help save lives

</p>


<?php if (!empty($message)): ?>

<div class="error">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<form
    method="POST"
    action="register_donor.php"
>


<!-- =========================================
     NAME
========================================= -->

<div class="form-group">

<label>

Full Name
<span class="required">*</span>

</label>


<input
    type="text"
    name="name"
    placeholder="Enter your full name"
    required
>

</div>


<!-- =========================================
     AGE
========================================= -->

<div class="form-group">

<label>

Age
<span class="required">*</span>

</label>


<input
    type="number"
    name="age"
    min="18"
    max="65"
    placeholder="Enter your age"
    required
>

</div>


<!-- =========================================
     GENDER
========================================= -->

<div class="form-group">

<label>

Gender
<span class="required">*</span>

</label>


<select
    name="gender"
    required
>

<option value="">
Select Gender
</option>

<option value="Male">
Male
</option>

<option value="Female">
Female
</option>

<option value="Other">
Other
</option>

</select>

</div>


<!-- =========================================
     BLOOD GROUP
========================================= -->

<div class="form-group">

<label>

Blood Group
<span class="required">*</span>

</label>


<select
    name="blood_group"
    required
>

<option value="">
Select Blood Group
</option>

<option value="A+">A+</option>

<option value="A-">A-</option>

<option value="B+">B+</option>

<option value="B-">B-</option>

<option value="AB+">AB+</option>

<option value="AB-">AB-</option>

<option value="O+">O+</option>

<option value="O-">O-</option>

</select>

</div>


<!-- =========================================
     PHONE
========================================= -->

<div class="form-group">

<label>

Mobile Number
<span class="required">*</span>

</label>


<input
    type="tel"
    name="phone"
    maxlength="10"
    pattern="[0-9]{10}"
    placeholder="Enter 10-digit mobile number"
    required
>

</div>


<!-- =========================================
     EMAIL
========================================= -->

<div class="form-group">

<label>
Email Address
</label>


<input
    type="email"
    name="email"
    placeholder="Enter email address"
>

</div>


<!-- =========================================
     CITY
========================================= -->

<div class="form-group">

<label>

City
<span class="required">*</span>

</label>


<input
    type="text"
    name="city"
    placeholder="Enter City"
    required
>

</div>


<!-- =========================================
     SUBMIT
========================================= -->

<button type="submit">

🩸 Register as Blood Donor

</button>


</form>


<a
    href="index.html"
    class="back"
>

← Back to Home

</a>


</div>


</body>

</html>