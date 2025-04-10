<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel = "stylesheet" href = "titleStyle.css">
    <script src="Chart.js"></script>
    <title>Breathing Rate</title>
    <style>
        div.chart{
            margin-left: 15%;
            width: 110% !important;
            max-width: 1000px; /* Adjust this to make it bigger */
        }
    </style>
</head>

<body>
    <?php include("NavBar.php");

    if (!isset($_SESSION['Date'])) {
        echo "No date Selected";
        exit;
    }
    else{
        $newDate = $_SESSION['Date']; // retrieves the selected date (from navbar)
        $hour = $_SESSION['Hour'];
    }

    if (!isset($_SESSION['Dog'])) {
        echo "No dog Selected";
        exit;
    }
    else{
        $dogID = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
    }

    // Check if bounds are received
    $boundsReceived = isset($_GET['upperBound']) && isset($_GET['lowerBound']) && isset($_GET['date']) && isset($_GET['dogID']);
    $upperBound = $boundsReceived ? $_GET['upperBound'] : null;
    $lowerBound = $boundsReceived ? $_GET['lowerBound'] : null;

    if ($boundsReceived && ($_GET['date'] !== $newDate || $_GET['dogID'] !== $dogID)) {
        $boundsReceived = false;
    } // Find new bounds if date or dogID has changed

    ?>
    
    <h2>Here is <?php echo $dogID; ?>'s info for Breathing Rate:</h2> <br>


    <div class = "graphText">
    <?php
     
    $db = new SQLite3('ElancoDB.db');
    $breathingData = [];
    $behaviourData = [];

    // Get the row number of the date the user enters
    if ($newDate != null) {

        $rowID = $db->prepare('
        WITH cte AS (
            SELECT Date, ROW_NUMBER() OVER() AS row_num 
            FROM (SELECT DISTINCT Date FROM Activity)
        )
        SELECT row_num FROM cte WHERE Date=:newDate');

        $rowID->bindValue(":newDate", $newDate, SQLITE3_TEXT);
        $rowResult = $rowID->execute();

        // Check if query execution is successful
        if (!$rowResult) {
            echo "Error fetching row number for the selected date.";
            exit();
        }

        $row = $rowResult->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            echo "No data found for the selected date: " . $newDate;
            exit();
        }
        echo "<p class = 'title'> Selected Date: " . $newDate ."<br></p>";

        // Fetch breathing rates for the given date
        $query = $db->prepare('SELECT Breathing_Rate FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID');
        $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
        $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $result = $query->execute();

        // Check if the query executed successfully
        if (!$result) {
            echo "Error executing query for breathing rates.";
            exit();
        }

        if ($result->numColumns() == 0) {
            echo "No breathing rate data found for the selected date: " . $newDate;
            exit();
        }

        // Populate $breathingData array with breathing rates
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $breathingData[] = $row['Breathing_Rate'];
        }

        // Fetch behaviour patterns for the given date
        $query = $db->prepare('
        SELECT Behaviour.Behaviour_Pattern 
        FROM Activity 
        INNER JOIN Behaviour ON Activity.BehaviourID = Behaviour.BehaviourID
        WHERE Date = :newDate 
        AND Hour >= 0 AND Hour <= 23 
        AND DogID = :dogID
        ');

        $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
        $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $result = $query->execute();

        // Check if the query executed successfully
        if (!$result) {
            echo "Error executing query for behaviour patterns.";
            exit();
        }

        if ($result->numColumns() == 0) {
            echo "No behaviour patterns found for the selected date: " . $newDate;
            exit();
        }

        // Populate $behaviourData array with breathing rates
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $behaviourData[] = $row['Behaviour_Pattern'];
        }
    } else {
        echo "Invalid or missing date input.";
        exit();
    }

    $arrangedDataset = [];
    // Fetch heart rates for the given date
    $query = $db->prepare('SELECT Breathing_Rate FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID ORDER BY Breathing_Rate DESC');
    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
    $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $result = $query->execute();

    // Populate $heartData array with heart rates
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
       $arrangedDataset[] = $row['Breathing_Rate'];
    }

    $query = $db->prepare('SELECT Breathing_Rate FROM Activity WHERE Date = :newDate AND Hour = :hour AND DogID = :dogID');
    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
    $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $query->bindValue(':hour', $hour, SQLITE3_TEXT);
    $result = $query->execute();

    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC); 
        if ($row) {
            $currentBR = $row['Breathing_Rate']; 
        } else {
            $currentBR = null;
            echo "No heart rate data found for the specified date, hour, and dog.<br>";
        }
    } else {
        echo "Error executing the query.<br>";
    }


    $db->close();
    ?>

    <script>
        const boundsReceived = <?php echo json_encode($boundsReceived); ?>;
        const currentDate = <?php echo json_encode($newDate); ?>;
        const currentDogID = <?php echo json_encode($dogID); ?>; // set php variables in js

        if (!boundsReceived) { 
            const dataset = <?php echo json_encode($arrangedDataset); ?>; // converts php array to js
            const calculatedUpperBound = FindUpperBound(dataset);
            const calculatedLowerBound = FindLowerBound(dataset); // uses js functions to find upper and lower bounds

            // Reload the page with upperBound, lowerBound, and other parameters
            window.location.href = `${window.location.pathname}?upperBound=${encodeURIComponent(calculatedUpperBound)}&lowerBound=${encodeURIComponent(calculatedLowerBound)}&date=${encodeURIComponent(currentDate)}&dogID=${encodeURIComponent(currentDogID)}`;
        }
    </script>

    </div>

    <script>
        window.onload = function() {
            loadLineGraph(
                'lineGraph', // chart ID
                <?php echo json_encode($breathingData); ?>, // dataset to be displayed as the line
                <?php echo json_encode($behaviourData); ?>, // dataset to be displayed when hoverin over a point on the graph
                'Breathing Rate', // line label
                'Breaths / Minute', // y axes label
                'Hour', // x axes label
                'Activity: ', // label for the dataset when hovering over a point on the graph
                [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] //x axis labels 
            );
        };
    </script>

    <div class="chart">
        <canvas id="lineGraph" style="height: 300px;"></canvas>
    </div>

    <div class="main">
        <form class = "TraffContainer">
            <?php 
                echo "Current Breathing Rate: ". $currentBR .".<br>";

                if($currentBR > $upperBound){
                    echo "<span class='trafficLight' style='background-color: red'></span>";
                    echo "<label>This is higher than normal.</label><br>";
                }
                else if($currentBR < $lowerBound){
                    echo "<span class='trafficLight' style='background-color: red'></span>";
                    echo "<label>This is lower than normal.</label><br>";

                }
                else{
                    echo "<span class='trafficLight' style='background-color: green'></span>";
                    echo "<label>This is normal.</label><br>";

                }
            ?>
            
        </form>
        <form class = "breathNotes">
                <label>This graph shows the dog's breathing rate (breaths per minute) per hour, throughout the selected date.</label>
        </form> 
    </div>
    
</body>

</html>