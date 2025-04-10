<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-doughnutlabel"></script>
    <script src="chart.js"></script>
    <style>
         form.notes { 
            float: left;
            margin-top: 5%;
            margin-left: 5%;
            margin-right: 5%;

            border-color: black;
            padding: 8px;
            text-align: left;
            width: 250px;
            padding: 20px;
            background: lightblue;
            border-radius: 8px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        } 


        li.dogOption {
            margin-top: 14px;
        }

        .warning{
            position: absolute;
            display: flex;
            top: 50px;
            right: 20px;
            font-size: 8px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-style: italic;
        }

        .chart {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }

        

        .charts {
            width: 100%;
            max-width: 800px;
            height: 600px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 5px;
            margin: 0 auto;
        }

    </style>
</head>

<body>

<?php 
    require_once 'UpperLowerBoundFunctions.php';
    include("NavBar.php");
    
        $db = new SQLite3('ElancoDB.db');

        //get date selected from the navbar calendar
        if (!isset($_SESSION['Date'])) {
            echo "No date Selected";
            exit;
        }
        else{
            $calDate = $_SESSION['Date']; // retrieves the selected date and time (from navbar)
            $calTime = $_SESSION['Hour'];
        }

        if (!isset($_SESSION['Dog'])) {
            echo "No dog Selected";
            exit;
        }
        else{
            $dogID = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
        }
            
        //run a select statement to get the water intake of the dog throughout the day 
        $query = $db->prepare('SELECT 
        AVG(Weight) AS avgWeight, 
        SUM(Water_Intake) AS totalIntake, 
        SUM(Calorie_Burn) AS totalBurnt, 
        SUM(Activity_Level)AS steps, 
        SUM(Food_Intake) AS totalCalories 
        FROM Activity WHERE Hour <= :calTime AND DogID = :dogID AND Date = :calDate');
        $query->bindValue(":calTime", $calTime, SQLITE3_INTEGER);
        $query->bindValue(":calDate", $calDate, SQLITE3_TEXT);
        $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $result = $query->execute();

        $row = $result->fetchArray(SQLITE3_ASSOC);
        //store data from the query into variables
        $totalIntake = round($row['totalIntake']);
        $weight = $row['avgWeight'];
        $totalBurnt = round($row['totalBurnt']);
        $totalSteps = $row['steps'];
        $totalCalories = round($row['totalCalories']);
        //calculate the goals for the dog
        $totalMl = round($weight * 60);
        $burntGoal = round(($weight * 2.2) * 30);
        $calorieGoal = round(pow($weight, 0.75) * 70);
        //check if the water intake goal has been hit 
        if ($totalIntake > $totalMl){
            $intakeLeft = 0;
        } else{
            //if the goal hasn't been hit then calculate the ammount left
            $intakeLeft = $totalMl - $totalIntake;
        }

        if($totalSteps > 8000){
            $stepsLeft = 0;
        } else{
            $stepsLeft = 8000 - $totalSteps;
        }

        if($totalBurnt > $burntGoal){
            $burntLeft = 0;
        } else{
            $burntLeft = $burntGoal - $totalBurnt;
        }

        if($totalCalories > $calorieGoal){
            $caloriesLeft = 0;
        } else{
            $caloriesLeft = $calorieGoal - $totalCalories;
        }

    $heartRate = 0;
    $heartColour = '#4CAF50';
    $statusText = '';
    $recordCount = 0;
    $arrangedDataset = [];

    try {
        // get all heart rates for the day to calculate bounds
    $allDayQuery = "SELECT Heart_Rate
    FROM Activity
    WHERE Date = :selectedDate
    AND DogID = :dogID";

    $allDayStmt = $db->prepare($allDayQuery);
    $allDayStmt->bindValue(':selectedDate', $calDate, SQLITE3_TEXT);
    $allDayStmt->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $allDayResult = $allDayStmt->execute();

    // populate array
    while ($row = $allDayResult->fetchArray(SQLITE3_ASSOC)) {
    $arrangedDataset[] = $row['Heart_Rate'];
    }

    $recordCount = count($arrangedDataset);

    // get the heart rate for the specific hour if provided
    if ($calTime !== null) {
        $hourQuery = "SELECT Heart_Rate
        FROM Activity
        WHERE Date = :selectedDate 
        AND Hour = :selectedHour
        AND DogID = :dogID";

    $hourStmt = $db->prepare($hourQuery);
    $hourStmt->bindValue(':selectedDate', $calDate, SQLITE3_TEXT);
    $hourStmt->bindValue(':selectedHour', $calTime, SQLITE3_INTEGER);
    $hourStmt->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $hourResult = $hourStmt->execute();

    $hourRow = $hourResult->fetchArray(SQLITE3_ASSOC);

    // if we found a heart rate for the specific hour, use it
    if ($hourRow) {
        $heartRate = $hourRow['Heart_Rate'];
        } elseif ($recordCount > 0) {
        // if no heart rate for specific hour but we have data for the day,
        // use the highest heart rate of the day
        $heartRate = max($arrangedDataset);
        $statusText = 'No data for hour ' . $calTime . '. Showing highest heart rate for the day.';
        }
        } elseif ($recordCount > 0) {
        // no hour selected, use the highest heart rate for the day
        $heartRate = max($arrangedDataset);
        }

        // calculate bounds only if we have records
        if ($recordCount > 0) {
        // calculate the bounds using our imported functions
            $lowerBound = FindLowerBound($arrangedDataset);
            $upperBound = FindUpperBound($arrangedDataset);

        // set status text if it wasn't set by the hour check
        if (empty($statusText)) {
        // logic to decide the status based on calculated bounds
            if ($heartRate > $upperBound) {
            $heartColour = '#FF9C09'; 
            $statusText = 'Alert: Heart rate above upper bound';
            } elseif ($heartRate < $lowerBound) {
            $heartColour = '#FF9C09'; 
            $statusText = 'Alert: Heart rate below lower bound';
            } else {
            $heartColour = '#4CAF50';
            $statusText = 'Normal heart rate (within bounds)';
            }
        }
        } else {
            $statusText = 'No data found for selected date';
        }
    } catch (Exception $e) {
        $statusText = 'Error: ' . $e->getMessage(); // catch to see if any errors occur
    }
    
    
    $weightValue = 0;
    $weightColour = '#4CAF50';
    $weightStatusText = '';
    $weightRecordCount = 0;
    $weightDataset = [];

    try {
        // get all weights for the day to calculate bounds
        $allWeightQuery = "SELECT Weight
        FROM Activity
        WHERE Date = :selectedDate
        AND DogID = :dogID";

        $allWeightStmt = $db->prepare($allWeightQuery);
        $allWeightStmt->bindValue(':selectedDate', $calDate, SQLITE3_TEXT);
        $allWeightStmt->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $allWeightResult = $allWeightStmt->execute();

        // populate array
        while ($row = $allWeightResult->fetchArray(SQLITE3_ASSOC)) {
            $weightDataset[] = $row['Weight'];
        }

        $weightRecordCount = count($weightDataset);

        // get the weight for the specific hour if provided
        if ($calTime !== null) {
            $hourWeightQuery = "SELECT Weight
            FROM Activity
            WHERE Date = :selectedDate 
            AND Hour = :selectedHour
            AND DogID = :dogID";

            $hourWeightStmt = $db->prepare($hourWeightQuery);
            $hourWeightStmt->bindValue(':selectedDate', $calDate, SQLITE3_TEXT);
            $hourWeightStmt->bindValue(':selectedHour', $calTime, SQLITE3_INTEGER);
            $hourWeightStmt->bindValue(':dogID', $dogID, SQLITE3_TEXT);
            $hourWeightResult = $hourWeightStmt->execute();

            $hourWeightRow = $hourWeightResult->fetchArray(SQLITE3_ASSOC);

            // if we found a weight for the specific hour, use it
            if ($hourWeightRow) {
                $weightValue = round($hourWeightRow['Weight'], 2);
            } elseif ($weightRecordCount > 0) {
                // if no weight for specific hour but we have data for the day,
                // use the average weight of the day
                $weightValue = array_sum($weightDataset) / $weightRecordCount;
                $weightStatusText = 'No data for hour ' . $calTime . '. Showing average weight for the day.';
            }
        } elseif ($weightRecordCount > 0) {
            // no hour selected, use the average weight for the day
            $weightValue = array_sum($weightDataset) / $weightRecordCount;
        }

    // calculate bounds only if we have records
    if ($weightRecordCount > 0) {
        // calculate the bounds using imported functions
        $weightLowerBound = FindLowerBound($weightDataset);
        $weightUpperBound = FindUpperBound($weightDataset);

        // set status text if it wasn't set by the hour check
        if (empty($weightStatusText)) {
            // logic to decide the status based on calculated bounds
            if ($weightValue > $weightUpperBound) {
                $weightColour = '#FF9C09'; 
                $weightStatusText = 'Alert: Weight above upper bound';
            } elseif ($weightValue < $weightLowerBound) {
                $weightColour = '#FF9C09'; 
                $weightStatusText = 'Alert: Weight below lower bound';
            } else {
                $weightColour = '#4CAF50';
                $weightStatusText = 'Normal weight (within bounds)';
            }
        }
    } else {
        $weightStatusText = 'No weight data found for selected date';
    }
    } catch (Exception $e) {
        $weightStatusText = 'Error: ' . $e->getMessage();
    }

    
    //get data for the summary box on the left of the home screen
    $sumQuery = $db->prepare('SELECT 
    BehaviourID, 
    Temperature, 
    BarkingID, 
    Breathing_Rate, 
    Water_Intake,
    Calorie_Burn,
    Food_Intake,
    Activity_Level
    FROM Activity WHERE DogID = :dogID AND Date = :calDate AND Hour = :calTime');

    $sumQuery->bindValue(":calTime", $calTime, SQLITE3_INTEGER);
    $sumQuery->bindValue(":calDate", $calDate, SQLITE3_TEXT);
    $sumQuery->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $result = $sumQuery->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    $behaviourID = $row['BehaviourID'];
    $temp = $row['Temperature'];
    $barkingID = $row['BarkingID'];
    $breathingRate = $row['Breathing_Rate'];
    $wtrIntake = $row['Water_Intake'];
    $calBurnt = $row['Calorie_Burn'];
    $calIntake = $row['Food_Intake'];
    $steps = $row['Activity_Level'];

    //get the behaviour and barking patterns from their tables
    $behaviourQuery = $db->prepare('SELECT Behaviour_Pattern FROM Behaviour WHERE BehaviourID = :behaviourID');
    $behaviourQuery->bindValue(":behaviourID", $behaviourID, SQLITE3_INTEGER);

    $behaviourRes = $behaviourQuery->execute();
    $row = $behaviourRes->fetchArray(SQLITE3_ASSOC);

    $behaviour = $row['Behaviour_Pattern'];

    $barkingQuery = $db->prepare('SELECT Barking_Frequency FROM Barking WHERE BarkingID = :barkingID');
    $barkingQuery->bindValue(":barkingID", $barkingID, SQLITE3_INTEGER);

    $barkingRes = $barkingQuery->execute();
    $row = $barkingRes->fetchArray(SQLITE3_ASSOC);

    $barking = $row['Barking_Frequency'];

?>
    <h2 class="h2Header">Here is <?php echo $dogID; ?>'s recent activity:</h2>
    <div class="Main">

        <form class = "notes">
            <label>Heart-Rate: <?php echo $heartRate; ?> BPM</label>
            <br><br><br>
            <label>Behaviour Pattern: <?php echo $behaviour; ?></label>
            <br><br><br>
            <label>Barking Frequency: <?php echo $barking; ?> </label>
            <br><br><br>
            <label>Weight: <?php echo $weightValue; ?> KG</label>
            <br><br><br>
            <label>Food Intake: <?php echo $calIntake ;?> (Calories)</label>
            <br><br><br>
            <label>Water Intake: <?php echo $wtrIntake; ?> ML</label>
            <br><br><br>
            <label>Temperature: <?php echo $temp; ?>°C</label>
            <br><br><br>
            <label>Calories Burnt: <?php echo $calBurnt; ?></label>
            <br><br><br>
            <label> Steps: <?php echo $steps; ?></label>
        </form>

    </div>

    <div class="warning">
        <h1>*Please note all goals are a general calculations and may not be specific to your dog</h1>
    </div>

    <script>
    window.onload = function() {
        loadDoughChart('doughChart', <?php echo json_encode($totalIntake)?>, <?php echo json_encode($intakeLeft)?>, 'Water Intake(ml)', <?php echo json_encode($totalMl)?>);
        loadDoughChart('caloriesBurnt', <?php echo json_encode($totalBurnt)?>, <?php echo json_encode($burntLeft)?>, 'Calories Burnt', <?php echo json_encode($burntGoal)?>);
        loadDoughChart('calorieIntake', <?php echo json_encode($totalCalories)?>, <?php echo json_encode($caloriesLeft)?>, 'Calorie Intake', <?php echo json_encode($calorieGoal)?>);
        loadDoughChart('steps', <?php echo json_encode($totalSteps) ?>, <?php echo json_encode($stepsLeft)?>, 'Step Goal', 8000);
    }
    </script>

    <!--add any more graphs into the charts class to have it be apart of the grid layout -->
<div class="box">
    <style>
        .h2Header {
            color: #0E253E;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 1.8rem;
            padding-bottom: 8px;
            position: relative;
        }

         .box {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-right: 100px;
            width: 70%;
            height: 600px;
            background: linear-gradient(to bottom right, #ffffff, #f0f8ff);
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 0 25px rgba(30, 144, 255, 0.3);
            transition: all 1s ease;
            position: relative;
            overflow: hidden;
            border: 3px solid #0E253E;
        }

        .box::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #0E253E,rgb(33, 61, 92),rgb(56, 100, 148));
            border-radius: 15px 15px 0 0;
        }

        .box:hover {
            box-shadow: 0 15px 30px rgba(14, 37, 62, 0.75), 0 0 25px rgba(14, 37, 62, 0.68);
            transform: translateY(-7px);
        } 

        .heart {
            transition: transform 0.3s ease, filter 0.3s ease;
        }

        .weight {
            transition: transform 0.3s ease, filter 0.3s ease;
        }

        .heart:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 0 10px <?php echo $heartColour; ?>);
        }

        .weight:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 0 10px <?php echo $weightColour; ?>);
        }

        /* Add to your existing style section */


    </style>
    <div class="charts">
        <div class="chart">
            <canvas id="doughChart"></canvas>
        </div>
        <div class="chart">
            <canvas id="caloriesBurnt"></canvas>
        </div>
        <div class="chart">
            <canvas id="calorieIntake"></canvas>
        </div>  
        <div class="chart">
            <canvas id="steps"></canvas>
        </div>  

        <div class="chart">
                <a href="HeartRate.php"><svg class="heart" width="140" height="140" viewBox="0 0 100 90">
                    <!-- heart path for heart shape -->
                    <path d="M50,30 C60,10 90,10 90,40 C90,65 50,85 50,85 C50,85 10,65 10,40 C10,10 40,10 50,30 Z" 
                        style="fill: <?php echo $heartColour; ?>;" />
                        <!-- text for the heart rate in bpm inside heart -->
                    <text x="50" y="55" text-anchor="middle" fill="white" font-weight="bold" font-size="14">
                        <?php echo $heartRate; ?>
                    </text>
                    <text x="50" y="70" text-anchor="middle" fill="white" font-size="10">
                        BPM
                    </text>
                </svg></a>
            </div>
        <div class="chart">
                <a href="Weight.php"><svg class="weight" width="140" height="140" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <!-- kettlebell shape -->
                    <path d="M24.107 12.087h-4.086c0.649-0.851 1.045-1.887 1.045-3.040 0-2.799-2.269-5.067-5.067-5.067s-5.067 2.269-5.067 5.067c0 1.153 0.397 2.189 1.045 3.040h-4.085l-6.080 14.187h28.375l-6.080-14.187zM16 12.087c-1.679 0-3.040-1.361-3.040-3.040 0-1.678 1.362-3.040 3.040-3.040s3.040 1.361 3.040 3.040-1.361 3.040-3.040 3.040z"
                        fill="<?php echo $weightColour; ?>"></path>
                    
                    <!-- weight value inside icon -->
                    <text x="16" y="20" text-anchor="middle" fill="white" font-weight="bold" font-size="5">
                        <?php echo number_format($weightValue, 1); ?>
                    </text>
                    
                    <!-- "KG" text below the weight value -->
                    <text x="16" y="24" text-anchor="middle" fill="white" font-size="3">
                        KG
                    </text>
                </svg></a>
            </div>

    </div>
    </div>
</body>

</html>