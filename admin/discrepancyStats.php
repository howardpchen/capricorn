<?php 
session_start();
include "../capricornLib.php"; 
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
?>

<!doctype html>
<html>
<head> 
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<link href="chardinjs.css" rel="stylesheet">

<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
<SCRIPT>

function shadeColor(color, shade) {
    var colorInt = parseInt(color.substring(1),16);

    var R = (colorInt & 0xFF0000) >> 16;
    var G = (colorInt & 0x00FF00) >> 8;
    var B = (colorInt & 0x0000FF) >> 0;

    R = R + Math.floor((shade/255)*R);
    G = G + Math.floor((shade/255)*G);
    B = B + Math.floor((shade/255)*B);

    var newColorInt = (R<<16) + (G<<8) + (B);
    var newColorStr = "#"+newColorInt.toString(16);

    return newColorStr;
}
</SCRIPT>
<?php

if (isset($_GET['traineeid']))  {
    $traineeID=$_GET['traineeid'];
} else if (isset($_SESSION['traineeid'])) {
    $traineeID=$_SESSION['traineeid'];
} else $traineeID=NULL;

function encodeSummaryGraph($barGraph, $dcs) {
    global $graphColor;
    $rec = implode("," , $barGraph[$dcs]['recent']);
    $all = implode("," , $barGraph[$dcs]['all']);
    $output = "[{
            name: 'Recent',
            data: [$rec],
            color:'" . $graphColor[$dcs] . "'
        }, {
            name: 'All',
            data: [$all],
            color:shadeColor('" . $graphColor[$dcs] . "', -100)
        }]";   
    return $output;
}

function encodeDiscrepancyColors($arr)  {
    global $graphColor;
    $inputArrays = array();
    foreach ($arr as $k=>$v) {
        $inputArrays []= "'" . $graphColor[$k] . "'"; 
    }
    $output = "[" . implode(",", $inputArrays) . "]";
    return $output;
}

function encodeDiscrepancyGraph($arr)  {
    $inputArrays = array();
    foreach ($arr as $k=>$v) {
        $inputArrays []= "['$k', $v]";
    }
    $output = "[" . implode(",", $inputArrays) . "]";
    return $output;
}

// Build the count arays and store for the graphs.
$allCountArrays = array();
$allCountSumArray = array();
$recentCountArrays = array();
$recentCountSumArray = array();
$barGraph = array();

foreach ($discrepancyCallString as $dcs)  {
    $barGraph[$dcs] = array();
}

$recentSumAll = 0;
$allSumAll = 0;

foreach ($callStudies as $c) {
    if (!isset($allCountArrays[$c[0]])) $allCountArrays[$c[0]] = array();
    $allCountArrays[$c[0]][$c[1]] = getDiscrepancyCountsByStudy($c[1], $c[0], $traineeID);
    if (!isset($recentCountArrays[$c[0]])) $recentCountArrays[$c[0]] = array();
    if (array_sum($allCountArrays[$c[0]][$c[1]]) <= $recentCountLimit)  {
        $recentCountArrays[$c[0]][$c[1]] = $allCountArrays[$c[0]][$c[1]];
    } else {
        $recentCountArrays[$c[0]][$c[1]] = getMostRecentDiscrepancyCounts($c[1], $c[0], $traineeID, $recentCountLimit);
    }
    // Reassemble the graphs to parse out the individual types of discrepancies
    $recentSum = array_sum($recentCountArrays[$c[0]][$c[1]]);
    $allSum = array_sum($allCountArrays[$c[0]][$c[1]]);

    $recentSumAll += $recentSum;
    $allSumAll += $allSum;

    if ($recentSum == 0) $recentSum=1;  //avoid division by zero
    if ($allSum ==0) $allSum = 1;       //avoid division by zero

    foreach ($discrepancyCallString as $dcs)  {
       if (isset($recentCountSumArray[$dcs])) {
           $allCountSumArray[$dcs] += $allCountArrays[$c[0]][$c[1]][$dcs];
           $recentCountSumArray[$dcs] += $recentCountArrays[$c[0]][$c[1]][$dcs];
       }
       else {
           $allCountSumArray[$dcs] = $allCountArrays[$c[0]][$c[1]][$dcs];
           $recentCountSumArray[$dcs] = $recentCountArrays[$c[0]][$c[1]][$dcs];
       }
       $recentDCS = round(10000*$recentCountArrays[$c[0]][$c[1]][$dcs]/$recentSum)/100;
       $barGraph[$dcs]['recent'] []= $recentDCS;

       $allDCS = round(10000*$allCountArrays[$c[0]][$c[1]][$dcs]/$allSum)/100;
       $barGraph[$dcs]['all'] []= $allDCS;
    }
}

foreach ($discrepancyCallString as $dcs)  {
   $barGraph[$dcs]['recent'] []= round(10000*$recentCountSumArray[$dcs]/$recentSumAll)/100;
   $barGraph[$dcs]['all'] []= round(10000*$allCountSumArray[$dcs]/$allSumAll)/100;
}

$callStudyArray = array();
foreach ($callStudies as $c)  {
    $callStudyArray []= $c[1] . " " . $c[0];
}
$callStudyArray []= "All";
$categories = json_encode($callStudyArray);

function makeSummaryGraph($discType)  {
    global $categories;
    global $barGraph;
    $series = encodeSummaryGraph($barGraph, $discType);
    
    echo <<< END
<script>
$(function () {
    $("#Summary$discType").highcharts({
        chart: {
            type: 'column',
            backgroundColor:'rgba(255, 255, 255, 0.0)',
        },
        title: {
            text: "Summary for $discType"
        },
        xAxis: {
            categories: $categories,
            title: {
                text: null
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: "$discType Rate (%)",
                align: 'high'
            },
            labels: {
                overflow: 'justify'
            }
        },
        tooltip: {
            valueSuffix: ' %'
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -100,
            y: 40,
            floating: true,
            borderWidth: 1,
            backgroundColor: ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'),
            shadow: true
        },
        credits: {
            enabled: false
        },
        series: $series
    });
});
</script>
<div id="Summary$discType" style="max-width: 1000px; height: 400px; margin: 0 auto"></div>
END;
}

function makeDiscrepancyGraph($section, $type)  {
    global $traineeID;
    global $allCountArrays;
    global $recentCountArrays;
	global $recentCountLimit;
    $sectionEnglish=codeToEnglish($section);
    $typeEnglish=codeToEnglish($type);
    //$recentCounts = getDiscrepancyCountsByStudy($section, $type, $traineeID);
    $allCounts = $allCountArrays[$type][$section];
    $colors = encodeDiscrepancyColors($allCounts);
    $allSum=array_sum($allCounts);
    $allString = encodeDiscrepancyGraph($allCounts);
    $recentCounts = $recentCountArrays[$type][$section];
    $recentSum=array_sum($recentCounts);
    $recentString = encodeDiscrepancyGraph($recentCounts);
    tableStartSection("$sectionEnglish $typeEnglish", 0, True);
    echo <<< END
    <script>
    $(function () {
        $("#recent$section$type").highcharts({
            chart: {
            backgroundColor:'rgba(255, 255, 255, 0.0)',
            plotBackgroundColor: null,
            plotBorderWidth: 1,//null,
            plotShadow: false
        },
    //    colors: ["#f45b5b", "#2b908f", "#90ee7e", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
        colors: $colors,
        title: {
            text: "Most Recent (n=$recentSum)"
        },
        subtitle: {
            text: "Statistics uses most recent $recentCountLimit studies, or all studies if less than threshold."
        },
        tooltip: {
    	    pointFormat: '<b>{point.y} studies</b> ({point.percentage:.1f}%)'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        }, series: [{
            type: 'pie',
            name: 'Discrepancy %',
            data: $recentString
        }]
        });
        $("#all$section$type").highcharts({
            chart: {
            backgroundColor:'rgba(255, 255, 255, 0.0)',
            plotBackgroundColor: null,
            plotBorderWidth: 1,//null,
            plotShadow: false
        },
        
        //colors: ["#f45b5b", "#2b908f", "#90ee7e", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
        colors: $colors,
        title: {
            text: "All (n=$allSum)"
        },
        subtitle: {
            text: "Statistics uses all studies with 'agree' or 'change' macros"
        },
        tooltip: {
    	    pointFormat: '<b>{point.y} studies</b> ({point.percentage:.1f}%)'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        }, 
        series: [{
            type: 'pie',
            name: 'Discrepancy %',
            data: $allString
        }]
        });
    });
    </script>    
<div class="row">
<div class="6u" id="recent$section$type"></div>
<div class="6u" id="all$section$type"></div>
</div>
END;
    tableEndSection();
}
include "../header.php"; 
checkAdmin();
?>

<form method=GET>
<select class='resViewSelector' name='traineeid' style="font-size:16pt">
<?php

$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' ORDER BY StartDate ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    $selected = ($r['TraineeID'] == $traineeID)?'selected':'';
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\" $selected>" . $r['FirstName']  . " " . $r['LastName'];
}
?>
</select>
<!--
<select class='resViewSelector' name='sec'>
<option value='CHEST'>Chest
<option value='MSK'>Bone/Muscle
<option value='BODY'>Body (Abd/Pelv)
<option value='NEURO'>Brain, Head, Neck
</select>
<select class='resViewSelector' name='typ'>
<option value='CT'>CT
<option value='CR'>Radiography
<option value='MR'>MRI
<option value='US'>Ultrasound
</select>
-->
<input type=submit value="Go"></form>
<?php 
tableStartSection("Summaries For Discrepancies", 0);
echo "<h4 align=center>'Recent' statistics are calculated using most recent $recentCountLimit studies.</h4>";
makeSummaryGraph("MajorChange");
makeSummaryGraph("MinorChange");
makeSummaryGraph("Addition");
makeSummaryGraph("Agree");
makeSummaryGraph("GreatCall");
tableEndSection();
foreach ($callStudies as $c) {
    makeDiscrepancyGraph($c[1], $c[0]);
}
?>

</BODY>

<?php include "../footer.php";?>
