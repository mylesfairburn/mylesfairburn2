<?php

// define db path
$dbPath = 'ElancoDB.db';

// define variables for the following:
$heartRate = 0;
$heartColour = '#4CAF50';
$statusText = '';
$recordCount = 0;

// defining the date the user selects as a variable and formatting it
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    $db = new SQLite3($dbPath);

    // converting date as a string to a timestamp and defining the end date
    $endDate = date('d-m-Y', strtotime($selectedDate));
    // same as previous but -30 days to define the start date
    $startDate = date('d-m-Y', strtotime($selectedDate . ' -30 days'));

    $query="SELECT AVG(`Heart_Rate`) as averageHeartRate, COUNT(*) as count -- Query to find heart rate average --
            FROM Activity
            -- calculating the date range where avg HR comes from using each char as an int (e.g.01-01-2021, where the first char is 1, second char 2 etc.)
            WHERE substr(Date, 7, 4) || substr(Date, 4, 2) || substr(Date, 1, 2) 
            BETWEEN substr(:startDate, 7, 4) || substr(:startDate, 4, 2) || substr(:startDate, 1, 2)
            AND substr(:endDate, 7, 4) || substr(:endDate, 4, 2) || substr(:endDate, 1, 2)";

    // preparing and executing query
    $stmt = $db->prepare($query);
    $stmt->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $result = $stmt->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    // logic to decide the colour of the heart with placeholder thresholds
    if ($row && $row['averageHeartRate'] !== null && $row['count'] > 0){
        $heartRate = round($row['averageHeartRate'], 1);
        $recordCount = $row['count'];

        if ($heartRate >= 160) {
            $heartColour = '#F44336';
            $statusText = 'Alert: Average HR too high';
        } elseif ($heartRate >= 120) {
            $heartColour = '#FFC107';
            $statusText = 'Average HR is elevated';
        } else {
            $heartColour = '#4CAF50';
            $statusText = 'Normal average HR';
        }
    } else {
        $statusText = 'No data found';
        $recordCount = 0;
    }

    $db->close();

} catch (Exception $e){
    $statusText = 'Error' . $e->getMessage(); // catch to see if any errors occur
}

// displaying start and end date
$displayStartDate = date('M j, Y', strtotime($startDate));
$displayEndDate = date('M j, Y', strtotime($endDate));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Heart Rate Test</title>
</head>
<body>
    <h1>Heart Rate Data</h1>
    
    <form method="get">
        <label for="date">End date:</label>
        <input type="date" id="date" name="date" value="<?php echo $selectedDate; ?>">
        <button type="submit">Update</button>
    </form>
    
    <!-- Heart visualization -->
    <div style="width: 150px; height: 150px; margin: 20px 0;">
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <path d="M16,28.261c0,0-14-7.926-14-17.046c0-9.356,13.159-10.399,14-0.454
            c1.011-9.938,14-8.903,14,0.454C30,20.335,16,28.261,16,28.261z" 
                  fill="<?php echo $heartColour; ?>" />
        </svg>
    </div>
    
    <p>Heart Rate: <?php echo $heartRate; ?> BPM</p>
    <p>Status: <?php echo $statusText; ?></p>
    <p>Date Range: <?php echo $displayStartDate; ?> to <?php echo $displayEndDate; ?></p>
    <p>Records: <?php echo $recordCount; ?></p>
</body>
</html>
