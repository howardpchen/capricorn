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

/*

This script displays a *complete dump* of the case log for the current user
and displays it in a tab-delimited fashion.

*/

include "capricornLib.php";
session_start();
?>

<!doctype html>
<HTML>
<BODY>
<PRE>
<?php

$dateNow = date_create('NOW');

echo getLoginUserFullName();
echo "\r\n";
echo "Data printed on " . $dateNow->format("m/d/Y");


function printRaw($results)  {
    echo "<PRE>";
    foreach ($results as $row)  {
        foreach($row as $col) {
            if (is_a($col, "DateTime")){
                $col = $col->format('Y-m-d H:i:s');
            }
            echo $col . "\t";
        }
        echo "\n";
    }
    echo "</PRE>";
}

function printResults($results, $results2, $desc, $dateStr, $dateStr2) {
    $graphName = str_replace(' ', '', $desc);
    $dateStr = str_replace('-', '/', $dateStr);
    $dateStr2 = str_replace('-', '/', $dateStr2);
    $dateArray = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
    $dateArray2 = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
    foreach ($results as $row)  {
        $dateArray[intval($row['ResidentYear'])] ++;
    }
    foreach ($results2 as $row)  {
        $dateArray2[intval($row['ResidentYear'])] ++;
    }

    $resultStr = join(',', $dateArray);
    $resultStr2 = join(',', $dateArray2);

echo <<< END
     <script>
    <!--
    $(function () {
        $("#$graphName").highcharts({
            chart: {
                backgroundColor:'rgba(255, 255, 255, 0.0)'
            },
            title: {
                text: "$desc",
                x: -20
            },
            subtitle: {
                text: "Powered by Capricorn",
                x: -20
            },
            xAxis: {
                categories: ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', '6th Year', '7th Year']
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Studies'
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
            series: [{
        name: "$dateStr",
        data: [$resultStr],
        type: 'column', color: '#088DC7'}, {
        name: "$dateStr2",
        data: [$resultStr2],
        type: 'column', color: '#00AA00'}]
        });
    });
    //-->
    </script> 
    <tr><td>
    <div id="$graphName" style="max-width: 600px; height: 400px; margin:0 auto"></div></td>
END;
}

function displayGraph() {
    global $resdbConn;

	$sql = "SELECT em.AccessionNumber, ecd.ExamCode, ecd.Description,
    aid.LastName as 'Attending', ecd.Section, ecd.Type,
    ecd.Notes, CompletedDTTM FROM `exammeta` as em INNER JOIN `examcodedefinition` as ecd ON (em.ExamCode = ecd.ExamCode AND ecd.ORG = em.Organization) INNER JOIN `attendingiddefinition` as aid ON (em.AttendingID = aid.AttendingID) WHERE TraineeID=" . $_SESSION['traineeid'] . " ORDER BY em.CompletedDTTM";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    printRaw($results);
}

displayGraph();
ob_flush();


?>

Designed by Po-Hao Chen, Yin Jie Chen, Tessa Cook.  2014-2015</FONT>
</PRE>
</HTML>


