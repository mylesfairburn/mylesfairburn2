function loadLineGraph(canvasId, dataset, outputDataset, graphLabel, yLabel, xLabel, extraOutputLabel, xlblValues){

    if (!dataset.length || !outputDataset.length) {
        alert("Some data is missing for the selected date.");
        return;
    } // error handling if theres some data missing
    else{
        const xValues = xlblValues;

        const ctx = document.getElementById(canvasId);

        return new Chart(ctx, { 
            type: "line",
            data: {
                labels: xValues,
                
                datasets: [{
                    label: graphLabel, //label for the line
                    fill: false,
                    backgroundColor: "white", //Dot fill color
                    borderColor: "rgb(8, 109, 152)", //Line Colour
                    pointBorderColor: "darkblue", //Dot border colour
                    pointBackgroundColor: "darkblue", //Dot fill colour
                    pointRadius: 2, //Size of the dots
                    pointHoverRadius: 6, //Dot size on hover
                    borderWidth: 2, //Line thicknes
                    tension: 0.3, //Adds smoothness to the line
                    data: dataset //dataset from database
                }] 
            },
            options: {
                scales: {
                    yAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: yLabel,
                            fontSize: 16,
                            fontFamily: "'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif",
                            fontColor: "#0E253E"
                        },
                        ticks: {
                            fontSize: 10,
                            fontFamily: "'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif",
                            fontColor: "#0E253E"
                        }
                    }],
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: xLabel,
                            fontSize: 16,
                            fontFamily: "'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif",
                            fontColor: "#0E253E"
                        },
                        ticks: {
                            fontSize: 10,
                            fontFamily: "'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif",
                            fontColor: "#0E253E"
                        }

                    }]
                },
                tooltips:{
                    displayColors: false, // stops the colour from being shown when hovering over a bar
                    callbacks: {
                        title: function() {
                            return null;  // Stops the title from showing 
                        },
                        label: function(tooltipItem) {
                            const hour = xValues[tooltipItem.index]; // Hour
                            const yData = tooltipItem.yLabel; // Breathing rate value
                            const outputData = outputDataset[hour]; // Fallback if behaviour data is missing

                            return[
                                xLabel + ": " + hour,
                                yLabel + ": " + yData, 
                                extraOutputLabel + outputData
                            ] // What we want to be shown when hovering over points on the graph
                        }
                    }
                },
                
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }
}

function loadBarChart(barType, canvasId, dataset, outputDataset, xValues, graphLabel, yLabel, xLabel, extraOutputLabel, xDataLabels = null){

    if (!dataset.length || !outputDataset.length) {
        alert("Some data is missing for the selected date.");
        return;
    } // error handling if theres some data missing
    else{

        const ctx = document.getElementById(canvasId);

        return new Chart(ctx, { 
        type: barType, //bar chart type i.e. "bar", "horizontalBar"
        data: {
                labels: xValues,
                datasets: [{
                    label: graphLabel, // label for the bars
                    backgroundColor: "rgba(13, 13, 94, 0.82)", // colour of bars
                    data: dataset // dataset from database
                }] 
            },
            options: {
                scales: {
                    yAxes: [{
                        scaleLabel:{
                            display: true,
                            labelString: yLabel
                        },
                        //manually changes the values of the label ticks on the graph
                        ticks: xDataLabels ? { //if the xDataLabel isnt null
                            callback: function(value) { //format the label values for the graph
                                return xDataLabels[value-1] //adjust index for graphs layout
                            },
                            min: 1,
                            max: 4,
                            stepSize: 1 //the size of the gaps between y labels
                        }: {} //if xDataLabels is null use the default tick labels
                    }],
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: xLabel
                        },
                    }]
                },
                tooltips: { // change the text when hovering over a bar
                    displayColors: false, // stops the colour from being shown when hovering over a bar
                    callbacks: {
                        title: function() {
                            return null;  // Stops the title from showing 
                        },
                        label: function(tooltipItem) {
                            const hour = xValues[tooltipItem.index]; // Hour
                            const yData = tooltipItem.yLabel; // Breathing rate value
                            const outputData = outputDataset[hour]; // Fallback if behaviour data is missing

                            return[
                                xLabel + ": " + hour,
                                yLabel + ": " + yData, 
                                extraOutputLabel + outputData
                            ] // What we want to be shown when hovering over points on the graph
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }   
}

function loadBehaviorPieChart(canvasId, behaviorDataset, graphLabel) {
    
    if (!behaviorDataset.length) {
        alert("Behavior data is missing for the selected date.");
        return;
    } else {
        // Count occurrences of each behavior
        const behaviorCounts = {};
        
        // Process behavior data
        for (let i = 0; i < behaviorDataset.length; i++) {
            const behavior = behaviorDataset[i];
            
            if (behavior && behavior !== "") {
                if (!behaviorCounts[behavior]) {
                    behaviorCounts[behavior] = 0;
                }
                behaviorCounts[behavior]++;
            }
        }
        
        // Prepare data for the pie chart
        const pieLabels = Object.keys(behaviorCounts);
        const pieData = pieLabels.map(label => behaviorCounts[label]);
        
        const backgroundColors = [
            'rgba(255, 99, 132, 0.8)', 
            'rgba(54, 162, 235, 0.8)',  
            'rgba(255, 206, 86, 0.8)',  
            'rgba(75, 192, 192, 0.8)',  
            'rgba(153, 102, 255, 0.8)',  
            'rgba(255, 159, 64, 0.8)'    
        ];
        
        const ctx = document.getElementById(canvasId);
        
        return new Chart(ctx, {
            type: "pie",
            data: {
                labels: pieLabels,
                datasets: [{
                    backgroundColor: backgroundColors.slice(0, pieLabels.length),
                    data: pieData
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: graphLabel
                },
                legend: {
                    position: 'right'
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const behavior = data.labels[tooltipItem.index];
                            const count = data.datasets[0].data[tooltipItem.index];
                            const total = pieData.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((count / total) * 100);
                            
                            return [
                                behavior + ": " + count + " hours",
                                percentage + "% of the day"
                            ];
                        }
                    }
                }
            }
        });
    }
}

function loadDoughChart(canvasId, progress, valueLeft, title, total){

    //error handling to ensure data is not invalid 
    if (progress == null){
        alert("Cannot find dogs current progress")
        return;
    }else{

        const ctx = document.getElementById(canvasId);
        var xValues = [title, 'Ammount Left']
        var yValues= [progress, valueLeft];
        var barColors = ['#2997F6']; //color of the doughnut bar

        return new Chart(ctx, {

            type: "doughnut",
            data: {
                labels: xValues, //label for hovering over the bars
                datasets: [{
                    backgroundColor: barColors,
                    data: yValues 
                }]
            },
            options: {
                //plugin for the text to be displayed in the center of the doughnut chart
                plugins:{
                    doughnutlabel: {
                        labels: [
                        {
                            text: title,
                            font: {
                            size: 24,
                            weight: 'bold',
                            },
                        },
                        //spacing between title and data
                        {
                            text: " ", 
                            font: {
                                size: 10, 
                            },
                        },
                        //display the current and total water intake e.g. 250/500 
                        {
                            text: `${progress}/${total}`,
                            font: {
                                size: 20,
                            }
                        },
                        ],
                    },
                },

                //hide the labels at the top of the graph
                legend: { 
                    display: false 
                },
                //gap size for the middle of the doughnut chart 
                cutoutPercentage: 80,
            },
        });
    }
}

function loadRadarChart(canvasId, data ){

    const ctx = document.getElementById(canvasId);
    const values = data

    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels:
            ['Normal', 'Walking', 'Eating', 'Sleeping', 'Playing'],
            datasets: [{
                label: 'Dogs behaviour',
                data: values,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(54, 162, 235, 0.8)',
                borderWidth: 2,
            }]
        },
        options: {
            scale: {
                ticks: {
                    //set the min and max values for the chart
                    min:0,
                    max:14,
                },
                pointLabels:{
                    fontSize: 14,
                }
            },
            responsive: true,
            maintainAspectRatio: false,
        }
    })
}

function FindLowerBound(arrangedDataset) {
    var median = FindMedian(arrangedDataset); // median of whole array

    var lowerHalf = arrangedDataset.filter(num => num < median); // new array for lower half

    return FindMedian(lowerHalf); // find median of lower half (this is the lower boundary)
}

function FindUpperBound(arrangedDataset) {
    var median = FindMedian(arrangedDataset); // median of whole array

    var upperHalf = arrangedDataset.filter(num => num > median); // new array for upper half
    
    return FindMedian(upperHalf); // find median of upper half (this is the upper boundary)
}

function FindMedian(arrangedDataset) {
    if (arrangedDataset.length % 2 == 0) { // finds median if array length is even
        var middle1 = arrangedDataset[arrangedDataset.length / 2 - 1];
        var middle2 = arrangedDataset[arrangedDataset.length / 2];
        return (middle1 + middle2) / 2;
    } else { // finds median if array length is odd
        return arrangedDataset[Math.floor(arrangedDataset.length / 2)];
    }
}