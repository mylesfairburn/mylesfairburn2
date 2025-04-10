<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="Chart.js"></script>
    <link href='titleStyle.css' rel='stylesheet'>
    <title>Pet Behavior Chart</title>
    <style>

        h1{
            color: #0E253E;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 1.2rem;
            padding-bottom: 8px;
            position: absolute;
            top: 80px;
            left: 10%;
        }

        h2{
            color: #0E253E;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 1.2rem;
            padding-bottom: 8px;
            position: absolute;
            top: 38px;
            left: 725px;
        }

        .radar{
            width: 500px;
            height: 500px;
            position: absolute;
            left: 100px;
            top: 125px;
        }
        .barChart{
            width: 550px;
            height: 375px;
            position: absolute;
            left: 825px;
            top: 190px;
        }

    </style>
</head>

<body>
    <div class="NavBar">
        <?php include("NavBar.php");

            $db = new SQLite3('ElancoDB.db');

            //get the date from the callender
            if (!isset($_SESSION['Date'])) {
                echo "No date Selected";
                exit;
            }
            else{
                $calDate = $_SESSION['Date']; 
            }
        
            //get the dog id 
            if (!isset($_SESSION['Dog'])) {
                echo "No dog Selected";
                exit;
            }
            else{
                $dogID = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
            }

            //select the total ammount of hours for each behaviour during the day 
            //using CASE WHEN statements https://www.geeksforgeeks.org/sql-server-case-expression/ 
            $query = $db->prepare('
            SELECT
            CASE --select each different behaviour type and store it 
                WHEN BehaviourID = 1 THEN "normal" 
                WHEN BehaviourID = 2 THEN "walking"  
                WHEN BehaviourID = 3 THEN "eating" 
                WHEN BehaviourID = 4 THEN "sleeping" 
                WHEN BehaviourID = 5 THEN "playing"
            END AS "behaviourType",
            COUNT(Hour) AS totalHours --get the total hours of each behaviour
            FROM Activity WHERE DogID = :dogID AND Date = :calDate
            GROUP BY behaviourType
            ORDER BY --reorder from alphabetical to correct order 
            CASE 
                WHEN behaviourType = "normal" THEN 1
                WHEN behaviourType = "walking" THEN 2
                WHEN behaviourType = "eating" THEN 3
                WHEN behaviourType = "sleeping" THEN 4
                WHEN behaviourType = "playing" THEN 5
            END;
            ');
        
            $query->bindValue(":calDate", $calDate, SQLITE3_TEXT);
            $query->bindValue(":dogID", $dogID, SQLITE3_TEXT);
            $result = $query->execute();
        
            //loop through the result for the ammount of behaviours
            for ($i = 0; $i < 5; $i++){
                $row = $result->fetchArray(SQLITE3_ASSOC);
                //if there isnt any data end the loop
                if (!$row) break;
        
                $hours[] = $row['totalHours'];
            }
        
            //seperate each number with a comma to make it a list
            $hours = implode(",", $hours);


            //get the barking id's for the bar chart on the right of the page
            $idQuery = $db->prepare('SELECT BarkingID FROM Activity WHERE DogID = :dogID AND Date = :calDate');

            $idQuery->bindValue(":dogID", $dogID, SQLITE3_TEXT);
            $idQuery->bindValue(":calDate", $calDate, SQLITE3_TEXT);
            $idResult = $idQuery->execute();

            while ($idRow = $idResult->fetchArray(SQLITE3_ASSOC)) {
                $barkingData[] = $idRow['BarkingID'];
            }

            //take out the data where the dog isnt barking 
            if ($barkingData != null){
                $prevEmpty = 0; // used to find if there is a range of hours where the dog wasnt barking
                $count = 0; // indexing array
                $dataToDelete = []; // array used to store which indexes of the $barkingData array should be deleted
                $barkingHours = [];
        
                while ($count < count($barkingData)) { // loop though the $barkingData array
                    if ($barkingData[$count] == 1) { // if the dog doesn't bark that hour
                        $prevEmpty++; 
        
                        if ($count == 23) { // error handling if the dog doesn't bark in the last hour of the day
                            if ($prevEmpty == 1) {
                                $barkingHours[] = $count;
                            } 
                            else {
                                $startNum = ($count - $prevEmpty) + 1;
                                $barkingHours[] = $startNum . " - " . ($count); // creates a range of hours where the dog isnt barking
                            }
                        } 
                        else {
                            if ($count + 1 < count($barkingData) && $barkingData[$count + 1] == 1) {
                                $dataToDelete[] = $count;
                            } // only adds to the $dataToDelete array if the next hour is also 0
                        }
                    } else {
                        if ($prevEmpty >= 2) {
                            $startNum = $count - $prevEmpty;
                            $barkingHours[] = $startNum . " - " . ($count - 1); // creates a range of hours where the dog isnt barking
                        } 
                        else if ($prevEmpty == 1 || $prevEmpty == 2) {
                            for ($i = $count - $prevEmpty; $i < $count; $i++) {
                                $barkingHours[] = $i;
                            } // retains the hour when only 1 or 2 hours have no barking
                        }
        
                        $barkingHours[] = $count;
                        $prevEmpty = 0;
                    }
        
                    $count++;
                }
            }

            // delete data in reverse order to avoid index shifting - have to do this in a diffrent loop otherwise it would break the first while loop
            foreach (array_reverse($dataToDelete) as $index) {
                array_splice($barkingData, $index, 1);
            }
        ?>
    </div>

    <h2>Here is <?php echo $dogID; ?>'s for Barking Frequency:</h2>

    <script>
        window.onload = function() {
            loadRadarChart('behaviour', [<?php echo $hours?>]);

            loadBarChart(
                'bar', //type of bar chart
                'barking', //canvas ID
                <?php echo json_encode($barkingData);?>, //data to be displayed
                'N/A', //data to be shown when hovering
                <?php echo json_encode($barkingHours);?>, //data for the x label 
                'Barking Level', //label at the top of the chart
                'Frequency', //y label 
                'Hours', //x label
                'N/A',  //label for the data shown hovering over a point
                ["None", "Low", "Medium", "High"] //tick labels for the x axis
            );
        } 
    </script>

    <div class="radar">
        <canvas id="behaviour"></canvas>
    </div>

    <h1>Here is <?php echo $dogID; ?>'s for Behaviour:</h1>

    <div class="barChart">
        <canvas id="barking"></canvas>
    </div>
    <div class = "noteWrapper">
        <form class = "notes">
            <label>This graph shows the total time the dog spent doing a certain activity, throughout the selected date.</label>
        </form>

        <form class = "notes">
            <label>This graph shows the dogs barking frequency per hour, throughout the selected date.</label>
        </form>
    </div>
    
</body>
</html>