<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="Chart.js"></script>
    <link href='titleStyle.css' rel='stylesheet'>
    <title>HeartRate</title>

    <style> 
 
    div.chart{
        margin-left: 15%;
        height: 300px;
        width: 110% !important;
        max-width: 1000px; /* Adjust this to make it bigger */
    }

    .chart:last-of-type {
        position: absolute;
        top: 400px;     
        left: 850px;    
        width: 200px;
        height: 200px;
    }

    /* Optional hover effect */
    .chart:last-of-type svg {
        transition: transform 0.3s ease, filter 0.3s ease;
    }

    .chart:last-of-type svg:hover {
        transform: scale(1.1);
        filter: drop-shadow(0 0 8px rgba(76, 175, 80, 0.6));
    }

    div.main{
        margin-left: 42.5%;
        margin-top: 1%;
    }
    </style>

</head>
<body>
    <div class="NavBar">
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
    </div>
    
    <h2>Here is <?php echo $dogID; ?>'s info for Heart Rate:</h2> <br>
    
    <div class = "graphText">
    <?php 

    $db = new SQLite3('ElancoDB.db');
    $heartData = [];
    $behaviourData = [];

    // Get the date the user entered in the form 
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['day'])) {
        $newDate = $_POST['day'];
    }

    if ($newDate != null) {
        //get the row number of the date from activity 
        $rowID = $db->prepare('
        WITH cte AS ( --using CTE tables in sql https://www.simplilearn.com/tutorials/sql-tutorial/row-number-funtion-in-sql#:~:text=1)%20How%20do%20I%20get,based%20on%20the%20specified%20ordering.
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
        echo "<p class = 'title'> Selected Date: " . $newDate ."</p>";

       // Fetch heart rates for the given date
       $query = $db->prepare('SELECT Heart_Rate FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID');
       $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
       $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
       $result = $query->execute();

       // Check if the query executed successfully
       if (!$result) {
           echo "Error executing query for heart rates.";
           exit();
       }

       if ($result->numColumns() == 0) {
           echo "No heart rate data found for the selected date: " . $newDate;
           exit();
       }

       // Populate $heartData array with heart rates
       while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           $heartData[] = $row['Heart_Rate'];
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

       // Populate $behaviourData array with heart rates
       while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           $behaviourData[] = $row['Behaviour_Pattern'];
       }
    } else {
        echo "Invalid or missing date input.";
        exit();
    }

    $arrangedDataset = [];
    // Fetch heart rates for the given date
    $query = $db->prepare('SELECT Heart_Rate FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID ORDER BY Heart_Rate DESC');
    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
    $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $result = $query->execute();

    // Populate $heartData array with heart rates
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
       $arrangedDataset[] = $row['Heart_Rate'];
    }

    $query = $db->prepare('SELECT Heart_Rate FROM Activity WHERE Date = :newDate AND Hour = :hour AND DogID = :dogID');
    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
    $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
    $query->bindValue(':hour', $hour, SQLITE3_TEXT);
    $result = $query->execute();

    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC); 
        if ($row) {
            $currentHR = $row['Heart_Rate']; 
        } else {
            $currentHR = null;
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
            <?php echo json_encode($heartData); ?>, // dataset to be displayed as the line
            <?php echo json_encode($behaviourData); ?>, // dataset to be displayed when hoverin over a point on the graph
            'Heart Rate', // line label
            'Beats / Minute', // y axes label
            'Hour', // x axes label
            'Activity: ', // label for the dataset when hovering over a point on the graph
            [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] //x axis labels 
        );
    };
    </script>

    <div class="chart">
        <canvas id="lineGraph" ></canvas>
    </div>

    <div class="main" >
        <form class = "TraffContainer" style="background: linear-gradient(to bottom right, #ffffff, #f0f8ff); border: 3px solid #0E253E; background-color: white; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 0 25px rgba(30, 144, 255, 0.3);">
            <?php 
                echo "Current Heart Rate: ". $currentHR .".<br>";

                if($currentHR > $upperBound){
                    echo "<span class='trafficLight' style='background-color: red'></span>";
                    echo "<label>This is higher than normal.</label><br>";

                }
                else if($currentHR < $lowerBound){
                    echo "<span class='trafficLight' style='background-color: red'></span>";
                    echo "<label>This is lower than normal.</label><br>";
                }
                else{
                    echo "<span class='trafficLight' style='background-color: green'></span>";
                    echo "<label>This is normal.</label><br>";
                }
            ?>
        </form>
        <form class = "heartNotes">
            <label>This graph shows the dog's heart rate (Beats Per Minute) per hour, throughout the selected date.</label>
        </form>
    </div>

    

</body>

</html>
    
</body>
</html>