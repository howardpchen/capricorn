<?php 
/*
    Capricorn - Open-source analytics tool for radiology residents.
    Copyright (C) 2014  (Howard) Po-Hao Chen

    This file is part of Capricorn.

    Capricorn is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (isset($_GET['yrend'])) $yrend = floatval($_GET['yrend']);   // The final year to use (until July 1 for the calculation).
else {
    // If not set, then use the most recent elapsed July 1.
    $thisJulyFirst = thisJulyFirst();
    $yrend = intval($thisJulyFirst->format("Y"));
}

if (isset($_GET['yrstr'])) $yrstr = floatval($_GET['yrstr']);
else $yrstr = $yrend - 5;       // By Default use 5 years for averaging.

if (isset($_GET['sigma'])) $sig = floatval($_GET['sigma']);
else $sig = 0.674;

/* echoYouVSClassGraph

This assembles a graph that allows the trainee to compare their progress with
their classmates over a given academic year.

The function uses their year-to-date performance to estimate for the rest of the year  

The data array should look like this:

$data['Trainee1'] = 120;
$data['Trainee2'] = 100;
$data['Trainee3'] = 10;
$data['Trainee4'] = 1029;
$data['Trainee5'] = 320;

*/

function echoYouVSClassGraphJS($data) {
    global $examCountGoal;

    // Sort the data in an ascending fashion
    // This function preserves the key, value relationships
    asort($data);

    // Mask the other trainees and set the logged in user to You.
    $id = 1;
    $graph_data = array();
    foreach ($data as $key => $value) {
        if ($key == $_SESSION['traineeid']) {            
            $graph_data['You'] = $value;
        } else {
            $graph_data['R'. $id] = $value;
            $id++;
        }
    }    

    // Prepare $categories JSON from the $graph_data array
    $categories = json_encode(array_keys($graph_data));

    $today = new DateTime('NOW');
    $nextJuneThirty = nextJuneThirty();

    // Now we take the counts and turn them into projections
    $daysLeft = $nextJuneThirty->diff($today)->format("%a");
    $daysSoFar = $today->diff(thisJulyFirst())->format("%a");

    // Update the $graph_data array with projected data
    // Format the data as expected in the JSON object for
    // highcharts and make the projection calculation
    $series_data = array();
    $sum = 0;
    $max = $examCountGoal * 1.20;  // Set the Y axis to 20% more than the goal

    foreach($graph_data as $key => $value) {

        // We are ignoring vacation days, which admittedly adds some noise
        $projectedValue = $value + ($value/$daysSoFar) * $daysLeft;

        $sum += $projectedValue;

        // Adjust the Y axis if someone is killing the goal
        if ($max < $projectedValue) $max = $projectedValue * 1.1;

        if ($key == 'You') {
            $series_data[] = array('name' => $key, 'y' => $projectedValue, 'color' => 'red');
        } else {
            $series_data[] = array('name' => $key, 'y' => $projectedValue);            
        }
    }

    // Calculate the average to display as a plotLine below
    $average = $sum / count($data);

    $examCountGoal = (isset($examCountGoal) ? $examCountGoal : 40 * 310);  // assuming 40 exams/day 310 workdays

    $series_data = json_encode($series_data);
    echo <<< END
    <script>
            <!--
        $(function () {
            $('#projected').highcharts({
                chart: {
                    type: 'column',
                    backgroundColor:'rgba(255, 255, 255, 0.0)'
                },
                legend: {
                    enabled: false
                },
                title: {
                    text: 'Projected Exam Counts For Your Class This Academic Year'
                },
                xAxis: {
                    categories: $categories
                },
                yAxis: {
                    min: 0,
                    max: $max,
                    plotLines: [{
                        id: 'average',
                        label: {
                            text: 'average'                
                        },
                        dashStyle: 'LongDashDot',
                        color: 'purple',
                        value: $average,
                        width: 1
                    },{
                        id: 'goal',
                        label: {
                            text: 'goal'           
                        },
                        dashStyle: 'Dot',
                        color: 'green',
                        value: $examCountGoal,
                        width: 1
                    }],
                    title: {
                        text: 'Exams'
                    }
                },
                tooltip: {
                    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: [{
                    data: $series_data
                }]
            });
        });
        //-->
    </script>
END;
}

// PGY based on current user,
// '%' = all sections
// '%' = all types
// '' = all notes
// startDate = thisJulyFirst()

$data = getOverallCountArray(getLoginUserPGY() - 1, '%', '%', '', thisJulyFirst()->format('Y-m-d'), nextJuneThirty()->format('Y-m-d'));

echoYouVSClassGraphJS($data);
makeDIV('projected', '750px', '400px');  // part of CapicornLib


?>


