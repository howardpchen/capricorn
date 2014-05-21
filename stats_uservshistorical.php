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

$mod = $_GET['mod'];    // modality
$sec = $_GET['sec'];    // section

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

$statSuffix = "userHist";

/*
    $makegrapharray contains the required statistical information to be assembled, in the following format:
    ['User'] = [sum, yr1, yr2, yr3, yr4];
    ['Hist'] = [int, int, int, int];  // R1 through R4.
    ['CI'] = [[int, int], [int, int], [int, int], [int, int]]; // Confidence intervals of R1 through R4.
*/
function assembleYouVSHistoricalGraph($graphName, $section, $uniqueID, $type, $makegrapharray) {
    global $yrstr;
    global $yrend;
    global $cumulative;

    $categories = "'1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', '6th Year', '7th Year'";
    if ($cumulative) $categories = "'You', " . $categories;

    $graphSeries = makeYouVSHistoricalGraph($makegrapharray, $section, $type);
    $title = codeToEnglish($section) . " " . codeToEnglish($graphName);
    echo <<< END
    <script>
    <!--
    $(function () {
        $("#$graphName$uniqueID").highcharts({
            chart: {
                backgroundColor:'rgba(255, 255, 255, 0.0)'
            },
            title: {
                text: "$title",
                x: -20
            },
            subtitle: {
                text: "$yrstr-07-01 to $yrend-07-01",
                x: -20
            },
            xAxis: {
                categories: [$categories]
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Interpreted'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                shared: true,
                valueSuffix: ' Studies'
            },
            series: [$graphSeries]
        });
    });
    //-->
    </script>
END;
}

/* makeStatisticsGraph essentially only creates a string of series that can be passed into assembleStatisticsGraph().  $datearray is the data obtained from */

function makeYouVSHistoricalGraph($dataArray, $section, $type="column") {
    global $graphColor;
    global $cumulative;
    $userString = join(",", $dataArray['User']);
    $dataString = join(",", $dataArray['Hist']);
    $CIString = array();
    
    $nullString = $cumulative ? "null," : "";

    foreach ($dataArray['CI'] as $d) {
        $CIString []= "[" . join(",", $d) . "]";
    }
    
    $CIString = join(",", $CIString);

    if ($cumulative) $CIString = "[null, null]," . $CIString;

    $returnString = "{
        name: 'Historical Avg',
        data: [$nullString$dataString],
        type: '$type'";
        if (isset($graphColor[$section])) {
            $returnString .= ", color: '" . $graphColor[$section] . "'";
        }
        $returnString .= "}, {
        name: 'Conf. Int.',
        type: 'errorbar',
        color: '#555555',
        data: [$CIString]
    }, {
        name: '". getLoginUserLastName() ."',
        data: [" . $userString . "],
        type: 'column',
        color: 'black'
    }";

    return $returnString;
}


$dispArray = array();
$userCount = getLoginUserCount($sec, $mod);

$res = array();

$res[0] = getMeanStDevStErr(1, $sec, $mod, "", $yrstr."-07-01", $yrend."-07-01");
$res[1] = getMeanStDevStErr(2, $sec, $mod, "", $yrstr."-07-01", $yrend."-07-01");
$res[2] = getMeanStDevStErr(3, $sec, $mod, "", $yrstr."-07-01", $yrend."-07-01");
$res[3] = getMeanStDevStErr(4, $sec, $mod, "", $yrstr."-07-01", $yrend."-07-01");

if ($cumulative) {
    for ($j = 1; $j < sizeof($res); $j++) {
        $res[$j]['Mean'] += $res[$j-1]['Mean'];
        $res[$j]['StDev'] = pow(pow($res[$j]['StDev'], 2) + pow($res[$j-1]['StDev'], 2), 0.5);
    }

    $userCount = array($userCount[0]);    // Display year1-4 vs just total.
}
else  { 
    array_shift($userCount);
}

$r1 = $res[0];
$r2 = $res[1];
$r3 = $res[2];
$r4 = $res[3];


$dispArray['User'] = $userCount;
$dispArray['Hist'] = [
    round($r1['Mean']),
    round($r2['Mean']),
    round($r3['Mean']),
    round($r4['Mean'])
    ];
$dispArray['CI'] = [
    [ max(0,round($r1['Mean'] - $r1['StDev']*$sig)), round($r1['Mean'] + $r1['StDev']*$sig) ],
    [ max(0,round($r2['Mean'] - $r2['StDev']*$sig)), round($r2['Mean'] + $r2['StDev']*$sig) ],
    [ max(0,round($r3['Mean'] - $r3['StDev']*$sig)), round($r3['Mean'] + $r3['StDev']*$sig) ],
    [ max(0,round($r4['Mean'] - $r4['StDev']*$sig)), round($r4['Mean'] + $r4['StDev']*$sig) ]
    ];

assembleYouVSHistoricalGraph($mod, $sec, 'usrHist', 'column', $dispArray);  // Part of this file
tableStartSection("Historical Comparison",1);
makeDIV($mod . 'usrHist', '750px', '400px');  // part of CapicornLib
tableEndSection();


?>


