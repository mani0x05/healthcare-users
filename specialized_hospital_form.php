<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Specialized Hospitals - HealthResQ</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fb;
            min-height: 100vh;
        }

        .header {
            background: #d90429;
            color: white;
            padding: 22px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 7px;
        }

        .header p {
            font-size: 15px;
            opacity: 0.95;
        }

        .container {
            width: 92%;
            max-width: 850px;
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            text-decoration: none;
            color: #d90429;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .card h2 {
            color: #222;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .required {
            color: #d90429;
        }

        select,
        input,
        textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d5d9e0;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        select:focus,
        input:focus,
        textarea:focus {
            border-color: #d90429;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .location-row {
            display: flex;
            gap: 10px;
        }

        .location-row input {
            flex: 1;
        }

        .location-btn {
            border: none;
            background: #555;
            color: white;
            padding: 0 18px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .location-btn:hover {
            background: #333;
        }

        .search-btn {
            width: 100%;
            border: none;
            background: #d90429;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        .search-btn:hover {
            background: #b80322;
        }

        .note {
            background: #fff4f5;
            border-left: 4px solid #d90429;
            padding: 13px;
            margin-bottom: 24px;
            color: #555;
            line-height: 1.5;
            border-radius: 5px;
        }

        .emergency {
            text-align: center;
            margin-top: 20px;
            color: #777;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .card {
                padding: 20px;
            }

            .location-row {
                flex-direction: column;
            }

            .location-btn {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <h1>🏥 Specialized Hospitals</h1>
    <p>Find hospitals based on your treatment requirement</p>
</div>

<div class="container">

    <a href="index.html" class="back">← Back to HealthResQ</a>

    <div class="card">

        <h2>Tell Us Your Requirement</h2>

        <p class="subtitle">
            Enter a few details so HealthResQ can find hospitals
            that match your treatment requirement.
        </p>

        <div class="note">
            ℹ️ This service is intended for <b>non-emergency specialized treatment</b>.
            For emergencies, please use the Ambulance Service.
        </div>

        <form action="hospital_results.php" method="GET">

            <!-- Specialization -->
            <div class="form-group">
                <label>
                    Required Specialization <span class="required">*</span>
                </label>

                <select name="specialization" required>
                    <option value="">-- Select Specialization --</option>

                    <option value="Cardiology">❤️ Cardiology</option>
                    <option value="Neurology">🧠 Neurology</option>
                    <option value="Orthopedics">🦴 Orthopedics</option>
                    <option value="Oncology">🎗️ Oncology</option>
                    <option value="Pulmonology">🫁 Pulmonology</option>
                    <option value="Gastroenterology">🩺 Gastroenterology</option>
                    <option value="Nephrology">🫘 Nephrology</option>
                    <option value="Urology">Urology</option>
                    <option value="Pediatrics">👶 Pediatrics</option>
                    <option value="Gynecology">Gynecology</option>
                    <option value="Ophthalmology">👁️ Ophthalmology</option>
                    <option value="ENT">👂 ENT</option>
                    <option value="Dermatology">Dermatology</option>
                    <option value="General Medicine">General Medicine</option>

                </select>
            </div>

            <!-- City -->
            <div class="form-group">
                <label>
                    City / Area <span class="required">*</span>
                </label>

                <div class="location-row">

                    <input
                        type="text"
                        name="location"
                        id="location"
                        placeholder="Example: Hyderabad, Secunderabad"
                        required
                    >

                    <button
                        type="button"
                        class="location-btn"
                        onclick="useLocation()">
                        📍 Use My Location
                    </button>

                </div>
            </div>

            <!-- Age -->
            <div class="form-group">
                <label>
                    Age Group <span class="required">*</span>
                </label>

                <select name="age_group" required>

                    <option value="">-- Select Age Group --</option>
                    <option value="Child">Child (0–12 years)</option>
                    <option value="Teen">Teen (13–17 years)</option>
                    <option value="Adult">Adult (18–59 years)</option>
                    <option value="Senior">Senior (60+ years)</option>

                </select>
            </div>

            <!-- Problem -->
            <div class="form-group">
                <label>
                    Describe Your Problem
                    <span style="font-weight:normal;color:#888;">(Optional)</span>
                </label>

                <textarea
                    name="problem"
                    placeholder="Example: Chest pain for the last few days, knee pain, breathing problem, etc."
                ></textarea>
            </div>

            <button type="submit" class="search-btn">
                🔍 Find Suitable Hospitals
            </button>

        </form>

        <div class="emergency">
            🚨 If this is an emergency, please use the HealthResQ Ambulance Service.
        </div>

    </div>

</div>

<script>

function useLocation() {

    if (!navigator.geolocation) {
        alert("Location is not supported by your browser.");
        return;
    }

    navigator.geolocation.getCurrentPosition(

        function(position) {

            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            /*
             * We are not directly using Google Maps API here.
             * Reverse geocoding can be connected later.
             *
             * For now we send coordinates as the location value.
             */

            document.getElementById("location").value =
                latitude.toFixed(6) + ", " + longitude.toFixed(6);

        },

        function(error) {

            alert(
                "Unable to get your location. Please enter your city or area manually."
            );

        }

    );
}

</script>

</body>
</html>