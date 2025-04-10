<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="Chart.js"></script>
    <link href='titleStyle.css' rel='stylesheet'>
    <title>Intake</title>
    <style>
        h1{
            color: #0E253E;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 1.8rem;
            padding-bottom: 8px;
            position: relative;
            margin-top: 50px;
            margin-left: 50px;
        }

        .foodChart{
            width: 700px;
            position: absolute;
            left: 25px;
            top: 175px;
        }

        .waterChart{
            width: 700px;
            position: absolute;
            left: 770px;
            top: 175px;
        }

        .foodNote {
            float: left;
            margin-top: 25%;
            margin-left: 10%;
            
            border-color: black;
            padding: 8px;
            text-align: left;
            width: 300px;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }
        
        .waterNote {
            float: right;
            margin-top: 25%;
            margin-right: 10%;
            
            border-color: black;
            padding: 8px;
            text-align: left;
            width: 300px;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }

    </style>
</head>

<body>
    <div class="NavBar">
    <?php include("NavBar.php");

    $db = new SQLite3('ElancoDB.db');
    $foodIntakeData = [];
    $waterIntakeData = [];
    $behaviourData = [];

    if (!isset($_SESSION['Date'])) {
        echo "No date Selected";
        exit;
    }
    else{
        $calDate = $_SESSION['Date']; // retrieves the selected date (from navbar)
    }

    if (!isset($_SESSION['Dog'])) {
        echo "No dog Selected";
        exit;
    }
    else{
        $dogID = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
    }

    // Fetch steps for the given date
    $query = $db->prepare('SELECT Food_Intake, Water_Intake FROM Activity WHERE Date = :calDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID');
    $query->bindValue(':calDate', $calDate, SQLITE3_TEXT);
    $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $result = $query->execute();

    // Check if the query executed successfully
    if (!$result) {
        echo "Error executing query for food intake.";
        exit();
    }

    if ($result->numColumns() == 0) {
        echo "No food intake data found for the selected date: " . $calDate;
        exit();
    }

    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $foodIntakeData[] = $row['Food_Intake'];
        $waterIntakeData[] = $row['Water_Intake'];
    }

    //handle the data where the dog does not eat
    if ($foodIntakeData != null){
        $prevEmpty = 0; // used to find if there is a range of hours where the dog does not eat
        $count = 0; // indexing array
        $dataToDelete = []; // array used to store which indexes of the $foodIntakeData array should be deleted
        $hours = [];

        while ($count < count($foodIntakeData)) { // loop though the $foodIntakeData array
            if ($foodIntakeData[$count] == 0) { // if the dog doesn't eat any food that hour
                $prevEmpty++; 

                if ($count == 23) { // error handling if the dog doesn't eat in the last hour of the day
                    if ($prevEmpty == 1) {
                        $hours[] = $count;
                    } 
                    else {
                        $startNum = ($count - $prevEmpty) + 1;
                        $hours[] = $startNum . " - " . ($count); // creates a range of hours where no food is eaten
                    }
                } 
                else {
                    if ($count + 1 < count($foodIntakeData) && $foodIntakeData[$count + 1] == 0) {
                        $dataToDelete[] = $count;
                    } // only adds to the $dataToDelete array if the next hour is also 0
                }
            } else {
                if ($prevEmpty > 2) {
                    $startNum = $count - $prevEmpty;
                    $hours[] = $startNum . " - " . ($count - 1); // creates a range of hours where no food is eaten
                } 
                else if ($prevEmpty == 1 || $prevEmpty == 2) {
                    for ($i = $count - $prevEmpty; $i < $count; $i++) {
                        $hours[] = $i;
                    } // retains the hour when only 1 or 2 hours have 0 steps
                }

                $hours[] = $count;
                $prevEmpty = 0;
            }

            $count++;
        }
    }

    //handle the data where the dog does not drink
    if ($waterIntakeData != null){
        $wtrPrevEmpty = 0; // used to find if there is a range of hours where the dog does not eat
        $wtrCount = 0; // indexing array
        $wtrDataToDelete = []; // array used to store which indexes of the $waterIntakeData array should be deleted
        $wtrHours = [];

        while ($wtrCount < count($waterIntakeData)) { // loop though the $waterIntakeData array
            if ($waterIntakeData[$wtrCount] == 0) { // if the dog doesn't eat any food that hour
                $wtrPrevEmpty++; 

                if ($wtrCount == 23) { // error handling if the dog doesn't eat in the last hour of the day
                    if ($wtrPrevEmpty == 1) {
                        $wtrHours[] = $wtrCount;
                    } 
                    else {
                        $startNum = ($wtrCount - $wtrPrevEmpty) + 1;
                        $wtrHours[] = $startNum . " - " . ($wtrCount); // creates a range of hours where no food is eaten
                    }
                } 
                else {
                    if ($wtrCount + 1 < count($waterIntakeData) && $waterIntakeData[$wtrCount + 1] == 0) {
                        $wtrDataToDelete[] = $wtrCount;
                    } // only adds to the $dataToDelete array if the next hour is also 0
                }
            } else {
                if ($wtrPrevEmpty >= 2) {
                    $startNum = $wtrCount - $wtrPrevEmpty;
                    $wtrHours[] = $startNum . " - " . ($wtrCount - 1); // creates a range of hours where no food is eaten
                } 
                else if ($wtrPrevEmpty == 1 || $wtrPrevEmpty == 2) {
                    for ($i = $wtrCount - $wtrPrevEmpty; $i < $wtrCount; $i++) {
                        $wtrHours[] = $i;
                    } // retains the hour when only 1 or 2 hours have 0 steps
                }
                $wtrHours[] = $wtrCount;
                $wtrPrevEmpty = 0;
            }
            $wtrCount++;
        }
    }
    // delete data in reverse order to avoid index shifting - have to do this in a diffrent loop otherwise it would break the first while loop
    foreach (array_reverse($dataToDelete) as $index) {
        array_splice($foodIntakeData, $index, 1);
    }

    foreach (array_reverse($wtrDataToDelete) as $wtrIndex) {
        array_splice($waterIntakeData, $wtrIndex, 1);
    }


    $query = $db->prepare('
        SELECT Behaviour.Behaviour_Pattern 
        FROM Activity 
        INNER JOIN Behaviour ON Activity.BehaviourID = Behaviour.BehaviourID
        WHERE Date = :calDate 
        AND Hour >= 0 AND Hour <= 23 
        AND DogID = :dogID
        ');

        $query->bindValue(':calDate', $calDate, SQLITE3_TEXT);
        $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $result = $query->execute();

        //fill an array with the behaviour data over the day
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $behaviourData[] = $row['Behaviour_Pattern'];
        }

    $db->close();
    ?>
    </div>


    <h2>Here is <?php echo $dogID; ?>'s info for Intake:</h2>

    <?php echo "<p class = 'title'> Selected Date: " . $date ."<p><br>"; ?>

    <div class = "Main">

    <script>
        window.onload = function() {
            loadBarChart(
                'bar', //type of bar chart
                'foodChart', //canvas ID
                <?php echo json_encode($foodIntakeData); ?>, //data to be displayed
                <?php echo json_encode($behaviourData); ?>, //data to be shown when hovering over a point
                <?php echo json_encode($hours); ?>, //data for the x axis label
                'Calories', //label at the top of the chart
                'Food Intake', //y label
                'Hour', //x label
                'Activity:' //label for the data shown when hovering over a point
            );

            loadBarChart(
                'bar',
                'waterChart',
                <?php echo json_encode($waterIntakeData)?>,
                <?php echo json_encode($behaviourData); ?>, 
                <?php echo json_encode($wtrHours); ?>,
                'Water (ml)',
                'Water Intake',
                'Hour',
                'Activity:'
            );
        };
    </script>
    
    <div class="foodChart">
        <canvas id="foodChart" style="width:100%;max-width:700px;"></canvas>
    </div>
    <div class="waterChart">
        <canvas id="waterChart" style="width:100%;max-width:700px;"></canvas>
    </div>

    <form class = "foodNote">
        <label>This graph shows the dog's food intake (calories) per hour, throughout the selected date.</label>
    </form>
    <form class = "waterNote">
        <label>This graph shows the dog's water intake (milliliters) per hour, throughout the selected date.</label>
    </form>

</body>
    