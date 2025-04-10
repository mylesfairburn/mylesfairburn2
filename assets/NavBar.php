<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="NavBarCss.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        img.calendar{
            width: 40px;
            height: 40px;
            margin-top: 3px;
            position: absolute;right: 5%;
            cursor: pointer;
        }
        a.logout{
            position: absolute;right: 0;
        }

        .date-picker-icon {
            cursor: pointer;
            width: 40px;
            height: 40px;
        }

        #datePicker {
            visibility: hidden;
            position: absolute;right: 0;
            /* Hide the input box but keep it in navbar so that the calendar dropdown is in the correct place */
        }
    </style>
</head>
<body>

<?php
    $db = new SQLite3('ElancoDB.db');
    session_start();

    if (!isset($_SESSION['AccountType'])) {
        header("Location: login.php"); // Redirect if not logged in
        exit;
    }
    $accountType = $_SESSION['AccountType'];

    if (isset($_SESSION['Date'])) {
        $date = $_SESSION['Date']; // retrieves the selected date from the navbar
    }
    
    // set the date the user clicked
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['datePicker'])) {
        $date = $_POST['datePicker'];

        $hour = substr($date, 11, 2);
        $date = substr($date, 0, -3);
        $month = substr($date, 3, 2);
        $year = substr($date, 6, 8);
        
        if($hour < 10){
            $hour = substr($hour, 1, 1);
        }

        $_SESSION['Hour'] = $hour;
        $_SESSION['Date'] = $date;
        $_SESSION['Month'] = $month;
        $_SESSION['Year'] = $year;
    }

    
    $selectedDog = "";
    if (isset($_SESSION['Dog'])) {
        $selectedDog = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selectDog'])) {
        $selectedDog = $_POST['selectDog'];
        $_SESSION['Dog'] = $selectedDog;
    }

    $findDogQuery = $db->prepare("SELECT DISTINCT DogID FROM Activity");
    $findDogresult = $findDogQuery->execute();
    if ($findDogresult) {
        while ($row = $findDogresult->fetchArray(SQLITE3_ASSOC)) {
            $dogIDs[] = $row['DogID']; // store results in an array
        }
    }

    $db->close();
?>

<div class="NavBar">
    <ul>
        <li><a class="Logo" href="Home.php"><img class="Logo" src="ElancoLogo.png" width="60" height="30"></a></li>
        <li><a href="Weight.php">Weight</a></li>
        <li><a href="HeartRate.php">Heart Rate</a></li>
        <li><a href="BehaviourPattern.php">Behaviour Pattern</a></li>
        <li><a href="intake.php">Intake</a></li>
        <li><a href="BreathingRate.php">Breathing Rate</a></li>
        <li><a href="Steps.php">Steps</a></li>
        <li><a href="CalorieBurn.php">Calorie Burn</a></li>
        <?php if($accountType == "Vet"): ?>
        <li>
            <form method="post" class = "selectDog">
                <select class = "selectDog" id="selectDog" name="selectDog" onchange="this.form.submit()">
                <option value="">Select a Dog</option>
                    <?php foreach ($dogIDs as $dogID): ?>
                        <option value="<?= $dogID ?>" <?= $selectedDog == $dogID ? 'selected' : '' ?>>
                            <?= $dogID ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </li>
        <?php endif; ?>
        <li><a class = "logout" href="Login.php">Logout</a></li>
        <li>
            <!-- Date picker -->
            <form class = "calendar"  method="post">
                <input type="text" name="datePicker" id="datePicker" placeholder="Select a date" readonly onchange="this.form.submit()">
                <img class = "calendar" src="CalendarIcon.png" alt="Date Picker Icon" id="datePickerIcon" class="date-picker-icon">
            </form>
        </li>

      

        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            // Get the session date value, or give a default date
            const phpDate = "<?php echo !empty($date) ? $date : '31-12-2023'; ?>";

            const datePicker = flatpickr("#datePicker", {
                enableTime: true, // Time selection option
                time_24hr: true, //Time to 24 hour
                dateFormat: "d-m-Y-H", // Set date format
                defaultDate: phpDate, // Set the date in calendar dropdown
                minDate: "01-01-2021", // Minimum date
                maxDate: "31-12-2023", // Maximum date
            });

            // Image trigger to allow user to select date from image
            document.getElementById("datePickerIcon").addEventListener("click", () => {
                datePicker.open();
            });
        </script>
    </ul>
</div>
</body>
</html>
