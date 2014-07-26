<?php include "capricornLib.php"; ?>

<!doctype html>
<HTML>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts-more.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>

<?php include "header.php"; 
?>

<TABLE WIDTH=800>
<TR><TD>
<?php

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

	$sql = "SELECT em.AccessionNumber, ecd.Description, aid.LastName as 'Attending', ecd.Section, ecd.Type, CompletedDTTM FROM `ExamMeta` as em INNER JOIN `ExamCodeDefinition` as ecd ON (em.ExamCode = ecd.ExamCode AND ecd.ORG = em.Organization) INNER JOIN `AttendingIDDefinition` as aid ON (em.AttendingID = aid.AttendingID) WHERE TraineeID=" . $_SESSION['traineeid'] . " ORDER BY em.CompletedDTTM";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    tableStartSection("Study Log");
    printRaw($results);
    tableEndSection();
}

displayGraph();
ob_flush();


?>
</TR></TABLE>
</BODY>
<HR>
<FONT SIZE=-2>Designed by Po-Hao Chen, Yin Jie Chen, Tessa Cook.  2014</FONT>
</HTML>
