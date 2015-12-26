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
<?php
include "../header.php"; 
checkAdmin();
$discrep = "MajorChange"; // The discrepancy to use for this page.
$discrep_exclude = array("None", "Attest");


// Major Change percentages
function makeTable($class, $information) {
    global $resdbConn, $callStudies, $discrep, $discrep_exclude;
    $sql = "SELECT DISTINCT CONCAT(rid.LastName, ', ', LEFT(rid.FirstName,1)) AS Name,  YEAR(rid.StartDate)+4 AS Class,";
    $studies = array();
    foreach ($callStudies as $c)  {
        $section = $c[1];
        $type = $c[0];
        $exclude = array();
        foreach ($discrep_exclude as $de) $exclude []= "FinalDiscrepancy!='$de'";
        $exclude = implode(" AND ", $exclude);
        switch ($information) {
            case "MajorPercent" :
                $studies []= "CONCAT(LEFT(ROUND(SUM(CASE WHEN Section='$section' AND Type='$type' AND FinalDiscrepancy='$discrep' THEN Count ELSE 0 END)/IF(SUM(CASE WHEN Section='$section' AND Type='$type' AND $exclude THEN Count ELSE 0 END)>0,SUM(CASE WHEN Section='$section' AND Type='$type' AND $exclude THEN Count ELSE 0 END),1)*1000)/10, 3), '%') AS '$section $type'";
                break;
            case "Counts": 
                $studies []= "CONCAT(SUM(CASE WHEN Section='$section' AND Type='$type' AND FinalDiscrepancy='$discrep' THEN Count ELSE 0 END), '/', SUM(CASE WHEN Section='$section' AND Type='$type' AND $exclude THEN Count ELSE 0 END)) AS '$section $type'";
                break;
        }
    }

    $studies = implode(", \n", $studies);

    $sql .= $studies . "
        FROM ExamDiscrepancyCounts as edc1 
        INNER JOIN ResidentIDDefinition rid ON edc1.TraineeID=rid.TraineeID
        WHERE YEAR(rid.StartDate)=$class AND IsCurrentTrainee='Y'
        GROUP BY edc1.TraineeID
        ORDER BY rid.LastName, rid.FirstName, rid.StartDate ";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $output = getResultsHTML($results);
    return $output;
}
$year = date("Y");
$year++;
tableStartSection("Class of " . $year, 0, True);
echo "<h3>Percentages</h3>";
echo makeTable($year-4, "MajorPercent");
echo "<h3>Counts</h3>";
echo makeTable($year-4, "Counts");
tableEndSection();
$year++;
tableStartSection("Class of " . $year, 0, True);
echo "<h3>Percentages</h3>";
echo makeTable($year-4, "MajorPercent");
echo "<h3>Counts</h3>";
echo makeTable($year-4, "Counts");
tableEndSection();
$year++;
tableStartSection("Class of " . $year, 0, True);
echo "<h3>Percentages</h3>";
echo makeTable($year-4, "MajorPercent");
echo "<h3>Counts</h3>";
echo makeTable($year-4, "Counts");
tableEndSection();
$year++;
tableStartSection("Class of " . $year, 0, True);
echo "<h3>Percentages</h3>";
echo makeTable($year-4, "MajorPercent");
echo "<h3>Counts</h3>";
echo makeTable($year-4, "Counts");
tableEndSection();





?>


<?php include "../footer.php";?>
