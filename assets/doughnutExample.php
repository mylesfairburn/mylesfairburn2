<?php 

    $db = new SQLite3('ElancoDB.db');

    //get the time from when the user opens the website
    $currentTime = date("G");

    //account for databases time format (starting at 0)
    $currentTime -= 1;
        
    //run a select statement to get the water intake of the dog throughout the day 
    $query = $db->prepare('SELECT AVG(Weight) AS avgWeight, SUM(Water_Intake) AS totalIntake, SUM(Calorie_Burn) AS totalBurnt FROM Activity WHERE Hour <= :currentTime AND DogID = "CANINE001" AND Date = "01-01-2021" ');
    $query->bindValue(":currentTime", $currentTime, SQLITE3_INTEGER);
    $result = $query->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    $totalIntake = round($row['totalIntake']);
    $weight = $row['avgWeight'];
    $totalBurnt = round($row['totalBurnt']);

    //calculate the ammount of water the dog needs 
    $totalMl = round($weight * 60);

    //check if the goal has been hit 
    if ($totalIntake > $totalMl){
        $intakeLeft = 0;
    } else{
        //if the goal hasn't been hit then calculate the ammount left
        $intakeLeft = $totalMl - $totalIntake;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-doughnutlabel"></script>
    <script src="chart.js"></script>
    <link rel="stylesheet" href="summary.css"> 
    <style>
        .charts {
            margin-top: 50px;
            width: 500px;
            height: 500px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }
    </style>

</head>
<body>

<script>
window.onload = function() {
    loadDoughChart('doughChart', <?php echo json_encode($totalIntake)?>, <?php echo json_encode($intakeLeft)?>, 'Water Intake(ml)', <?php echo json_encode($totalMl)?>);
    loadDoughChart('burnt', <?php echo json_encode($totalBurnt)?>, 100, 'Calories Burnt', 600);
    loadDoughChart('calorieIntake', 150, 250, 'Calories needed', 550);
    loadDoughChart('steps', 3000, 2000, 'Step Goal', 5000)
}
</script>
<div class="charts">
    <div class="chart">
        <canvas id="doughChart"></canvas>
    </div>
    <div class="chart">
        <canvas id="burnt"></canvas>
    </div>
    <div class="chart">
        <canvas id="calorieIntake"></canvas>
    </div>  
    <div class="chart">
        <canvas id="steps"></canvas>
    </div>  
</div>

</body>
</html>