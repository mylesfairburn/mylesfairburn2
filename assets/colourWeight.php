<?php

// define db path
$dbPath = 'ElancoDB.db';

// define variables for the following:
$weight = 0;
$weightColour = '#4CAF50';
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

    $query="SELECT AVG(Weight) as averageWeight, COUNT(*) as count -- Query to find weight average --
            FROM Activity
            -- calculating the date range where avg weight comes from using each char as an int (e.g.01-01-2021, where the first char is 1, second char 2 etc.)
            WHERE substr(Date, 7, 4) || substr(Date, 4, 2) || substr(Date, 1, 2) 
            BETWEEN substr(:startDate, 7, 4) || substr(:startDate, 4, 2) || substr(:startDate, 1, 2)
            AND substr(:endDate, 7, 4) || substr(:endDate, 4, 2) || substr(:endDate, 1, 2)";

    // preparing and executing query
    $stmt = $db->prepare($query);
    $stmt->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $result = $stmt->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    // logic to decide the colour of the weight with placeholder thresholds
    if ($row && $row['averageWeight'] !== null && $row['count'] > 0){
        $weight = round($row['averageWeight'], 1);
        $recordCount = $row['count'];

        if ($weight >= 20) {
            $weightColour = '#F44336';
            $statusText = 'Average weight is very high';
        } elseif ($weight >= 12) {
            $weightColour = '#FFC107';
            $statusText = 'Average weight is quite high';
        } else {
            $weightColour = '#4CAF50';
            $statusText = 'Average weight is normal';
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
    <title>Weight Test</title>
</head>
<body>
    <h1>Weight Data</h1>
    
    <form method="get">
        <label for="date">End date:</label>
        <input type="date" id="date" name="date" value="<?php echo $selectedDate; ?>">
        <button type="submit">Update</button>
    </form>

    <div style="width: 150px; height: 150px; margin: 20px 0;">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
        <circle cx="16" cy="16" r="10" fill="<?php echo $weightColour; ?>" />
    </svg>
    </div>

    <p>Weight: <?php echo $weight; ?> Kilograms</p>
    <p>Status: <?php echo $statusText; ?></p>
    <p>Date Range: <?php echo $displayStartDate; ?> to <?php echo $displayEndDate; ?></p>
    <p>Records: <?php echo $recordCount; ?></p>
</body>
</html>