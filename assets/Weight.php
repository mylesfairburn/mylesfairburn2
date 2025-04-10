<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'> 
    <link href='titleStyle.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/regression@2.0.1/dist/regression.min.js"></script>   
    <script src="Chart.js"></script>    
    <title>Weight</title>

    <style>

        h1{
            text-decoration: underline; 
            margin-left: 43%;
            margin-bottom: 15px;
            margin-top: 15px;
            font-weight: bold;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 16px;
        }

        div.chart{
            height: 300px;
            width: 1000px;
            margin-left: 5%;
        }

        div.predictionChart{
            height: 300px;
            width: 1000px;
            margin-left: 5%;
            margin-top: 5%;
        }

        form.weightNotes {
            position: absolute;
            top: 290px;
            right: 100px;
            border-color: black;
            padding: 8px;
            text-align: left;
            width: 300px;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }

        form.predictionNotes {
            position: absolute;
            bottom: 10px;
            right: 100px;
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
        
        //get the date from the navbar callender
        if (!isset($_SESSION['Year'])) {
            echo "No Year Selected";
            exit;
        } else{
            $calYear = $_SESSION['Year']; 
        }

        if (!isset($_SESSION['Dog'])) {
            echo "No dog Selected";
            exit;
        } else{
            $dogID = $_SESSION['Dog'];
        }

        //get the average weight of the dog from each month 
        $query = $db-> prepare('
        SELECT 
        CASE --select the months from the year and store them as the appropriate month i.e "Jan" "Feb" etc
            WHEN substr(Date, 4, 2) = "01"  THEN "Jan"
            WHEN substr(Date, 4, 2) = "02"  THEN "Feb"
            WHEN substr(Date, 4, 2) = "03"  THEN "Mar"
            WHEN substr(Date, 4, 2) = "04"  THEN "Apr"
            WHEN substr(Date, 4, 2) = "05"  THEN "May"
            WHEN substr(Date, 4, 2) = "06"  THEN "Jun"
            WHEN substr(Date, 4, 2) = "07"  THEN "Jul"
            WHEN substr(Date, 4, 2) = "08"  THEN "Aug"
            WHEN substr(Date, 4, 2) = "09"  THEN "Sep"
            WHEN substr(Date, 4, 2) = "10"  THEN "Oct"
            WHEN substr(Date, 4, 2) = "11"  THEN "Nov"
            WHEN substr(Date, 4, 2) = "12"  THEN "Dec"
        END AS "months",
        round(AVG(weight), 1) AS avgWeight --select the average weight (to one deiclam point) for each of these months
        FROM Activity WHERE substr(Date, 7, 4) = :calYear AND DogID = :dogID
        GROUP BY months
        ORDER BY --order the data to be in order of months and not aplhabetical
        CASE 
            WHEN months = "Jan" THEN 1
            WHEN months = "Feb" THEN 2
            WHEN months = "Mar" THEN 3
            WHEN months = "Apr" THEN 4
            WHEN months = "May" THEN 5
            WHEN months = "Jun" THEN 6
            WHEN months = "Jul" THEN 7
            WHEN months = "Aug" THEN 8
            WHEN months = "Sep" THEN 9
            WHEN months = "Oct" THEN 10
            WHEN months = "Nov" THEN 11
            WHEN months = "Dec" THEN 12 
        END;
        ');

        $query->bindValue(":calYear", $calYear, SQLITE3_TEXT);
        $query->bindValue(":dogID", $dogID);
        $result = $query->execute();
        $weightData = [];

        for ($i = 0; $i < 12; $i++){
            $row = $result->fetchArray(SQLITE3_ASSOC);
            //if there isnt any data end the loop
            if (!$row) break;
    
            $weightData[] = $row['avgWeight'];
        }    

        //dictionary to store the data for the prediction
        $formattedWeight = [];
        $counter = 1; //this is for the month in the foreach loop below to index each weight

        //add each average weight into the dictionary 
        foreach($weightData as $avg){
            $formattedWeight[] = [$counter, $avg];
            $counter += 1;
        }
    ?>
    </div>
    <h2>Here is <?php echo $dogID; ?>'s info for Weight:</h2>

    <?php echo "<p class = 'title'> Selected year: " . $calYear ."<p><br>"; ?>


        <script>
            window.onload=function(){

                //take the weights from the php 
                const weights = <?php echo json_encode($formattedWeight); ?>;

                //perform the prediction based of the data, the prediction uses linear regression
                //the prediction works off linear regression the formula for which is y = mx + b
                //y is the predicted value
                //m is the change in value between each x 
                //x is the individual value (the weight)
                //b is the value of y when x invercepts the y axis (x = 0) for almost all data in our database this is redundant as we have no negative values or 0 values
                const result = regression.linear(weights);

                const predictedWeights = [];

                //get each predicted value for the next six months
                for (let i = 13; i <= 18; i++) {
                    const prediction = result.predict(i);
                    //put the prediction in the array 
                    predictedWeights.push(prediction[1]);
                }

                loadLineGraph(
                'weightGraph', 
                <?php echo json_encode($weightData) ?>, 
                'N/A', 
                'Dogs Weight', 
                'Weight(kg)', 
                "Months", 
                'N/A', 
                ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]); //y labels 
        
                loadLineGraph(
                'predictionGraph',
                predictedWeights,
                'N/A',
                'Dogs Predicted Weight',
                'Weight(kg)',
                'Months',
                'N/A',
                ["Jan", "Feb", "Mar", "Apr", "May", "Jun"]
                );
            }
        </script>

        <div class="chart">
            <canvas id="weightGraph"></canvas>
        </div>

        <form class = "weightNotes">
            <label>This graph shows the average weight of the dog per month over the course of a year.</label>
        </form>

        <div class="predictionChart">
            <canvas id="predictionGraph"></canvas>
        </div>

        <form class = "predictionNotes">
            <label>This graph shows the predicted weight of your dog over the next 6 months</label>
        </form>

</body>
</html>