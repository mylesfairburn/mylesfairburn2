<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="Chart.js"></script>
    <title>Calorie Burn</title>
    <link rel = "stylesheet" href = "titleStyle.css">
    <style>
    div.chart{
        margin-left: 15%;
        width: 110% !important;
        max-width: 1000px; /* Adjust this to make it bigger */
    }

    form.calNotes {
            float: left;
            
            margin-left: 40%;
            
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
    <?php include("NavBar.php");

    if (!isset($_SESSION['Date'])) {
        echo "No date Selected";
        exit;
    }
    else{
        $newDate = $_SESSION['Date']; // retrieves the selected date (from navbar)
    }

    if (!isset($_SESSION['Dog'])) {
        echo "No dog Selected";
        exit;
    }
    else{
        $dogID = $_SESSION['Dog']; // retrieves the selected dog (from navbar)
    }
    ?>
    
    <h2>Here is <?php echo $dogID; ?>'s info for Calories Burnt:</h2> <br>


    <div class = "graphText">
    <?php
     
    $db = new SQLite3('ElancoDB.db');
    $caloriesBurnt = [];
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
        $query = $db->prepare('SELECT Calorie_Burn FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = :dogID');
        $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
        $query->bindValue(':dogID', $dogID, SQLITE3_TEXT);
        $result = $query->execute();

        // Check if the query executed successfully
        if (!$result) {
            echo "Error executing query for calorie burn.";
            exit();
        }

        if ($result->numColumns() == 0) {
            echo "No calorie burn data found for the selected date: " . $newDate;
            exit();
        }

        // Populate $caloriesBurnt array with breathing rates
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $caloriesBurnt[] = $row['Calorie_Burn'];
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

    $db->close();
    ?>
    </div>

    <script>
        window.onload = function() {
            loadLineGraph(
                'lineGraph', // chart ID
                <?php echo json_encode($caloriesBurnt); ?>, // dataset to be displayed as the line
                <?php echo json_encode($behaviourData); ?>, // dataset to be displayed when hoverin over a point on the graph
                'Calorie Burn', // line label
                'Calories Burnt', // y axes label
                'Hour', // x axes label
                'Activity: ', // label for the dataset when hovering over a point on the graph
                [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] //x axis values 
            );
        };
    </script>

    <div class="chart">
        <canvas id="lineGraph" style="height: 300px;"></canvas>
    </div>

    <form class = "calNotes">
        <label>This graph shows the dog's calories burnt per hour, throughout the selected date.</label>
    </form>
</body>

</html>