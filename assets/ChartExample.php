<?php 
$db = new SQLite3('ElancoDB.db');
$newDate = "01-01-2021";
$breathingData = [];
$behaviourData = [];

// Get the date the user entered in the form 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['day'])) {
    $newDate = $_POST['day'];
}

// Check if a date is provided
if ($newDate == null || empty($newDate)) {
    echo "No date provided. Please enter a valid date.";
    exit();
}

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
    echo "Selected Date: " . $newDate ."<br>";

    // Get the previous date (row number - 1)
    if ($row != null) {
        $currentRow = $row['row_num'];
        $currentRow = $currentRow - 1;

        $prevQuery = $db->prepare('
        WITH cte AS (
            SELECT Date, ROW_NUMBER() OVER() AS row_num 
            FROM (SELECT DISTINCT Date FROM Activity)
        )
        SELECT Date FROM cte WHERE row_num=:row_num');

        $prevQuery->bindValue(":row_num", $currentRow, SQLITE3_INTEGER);
        $prevResult = $prevQuery->execute();

        // Check if previous date query execution is successful
        if (!$prevResult) {
            echo "Error fetching previous date.";
            exit();
        }

        $prevRow = $prevResult->fetchArray(SQLITE3_ASSOC);
        $prevDate = $prevRow['Date'] ?? null;
        if ($prevDate == null) {
            echo "No data for the previous date.";
        }
    }

    // Get the next date (row number + 1)
    if ($row != null) {
        $currentRow = $row['row_num'];
        $currentRow = $currentRow + 1;

        $nextQuery = $db->prepare('
        WITH cte AS (
            SELECT Date, ROW_NUMBER() OVER() AS row_num 
            FROM (SELECT DISTINCT Date FROM Activity)
        )
        SELECT Date FROM cte WHERE row_num=:row_num');

        $nextQuery->bindValue(":row_num", $currentRow, SQLITE3_INTEGER);
        $nextResult = $nextQuery->execute();

        // Check if next date query execution is successful
        if (!$nextResult) {
            echo "Error fetching next date.";
            exit();
        }

        $nextRow = $nextResult->fetchArray(SQLITE3_ASSOC);
        $nextDate = $nextRow['Date'] ?? null;
        if ($nextDate == null) {
            echo "No data for the next date.";
        }
    }

    // Fetch breathing rates for the given date
    $query = $db->prepare('SELECT Breathing_Rate FROM Activity WHERE Date = :newDate AND Hour >= 0 AND Hour <= 23 AND DogID = "CANINE001"');
    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
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
    AND DogID = "CANINE001"
    ');

    $query->bindValue(':newDate', $newDate, SQLITE3_TEXT);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="Chart.js"></script>
    <title>Test</title>
</head>
<body>

    <div class="buttons">
        <h1>Day</h1>
        <!-- Set the previous button to hold the value of the previous date and submit that previous date to PHP -->
        <?php if (isset($prevDate)) { ?>
            <form action="ChartExample.php" method="post">
                <input type="hidden" name="day" value="<?php echo($prevDate);?>">
                <button type="submit" id="prevDay">
                    <i class='bx bx-chevron-left'></i>
                </button>
            </form>
        <?php } ?>

        <!-- Set the next button to hold the value of the next date and submit that next date to PHP -->
        <?php if (isset($nextDate)) { ?>
            <form action="ChartExample.php" method="post">
                <input type="hidden" name="day" value="<?php echo ($nextDate); ?>">
                <button type="submit" id="nextDay">
                    <i class='bx bx-chevron-right'></i>
                </button>
            </form>
        <?php } ?>

        <!-- Form to search for a specific date -->
        <form action="ChartExample.php" method="post">
            <input type="text" id="day" name="day" placeholder="(DD-MM-YYYY)" required> <!-- Could do with some error checking to make sure date is in the correct format -->
            <input name="submit" type="submit" value="Find"/>
        </form>
    </div>

    <script> 
            window.onload = function() {
                loadBarChart(
                    'barChart', 
                    <?php echo json_encode($breathingData); ?>, // dataset to be displayed as the line
                    <?php echo json_encode($behaviourData); ?>, // dataset to be displayed when hoverin over a point on the graph
                    'Breathing Rate', // line label
                    'Breaths / Minute', // y axes label
                    'Hour', // x axes label
                    'Activity: ' // label for the dataset when hovering over a point on the graph
                );
            };
    </script>

    <div class="chart">
        <canvas id="barChart" style="width:100%;max-width:700px;"></canvas>
    </div>
</body>
</html>
