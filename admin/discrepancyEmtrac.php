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

$sql = "SELECT rid.FirstName, rid.LastName,  YEAR(rid.StartDate)+4 AS Class, 
SUM(EDNotify) AS 'Emtrac', 
SUM(IF(((AdminDiscrepancy='' AND AutoDiscrepancy='MajorChange') OR AdminDiscrepancy='MajorChange') AND EDNotify=1, 1, 0)) AS 'MajorChange',  
SUM(IF(((AdminDiscrepancy='' AND AutoDiscrepancy='MinorChange') OR AdminDiscrepancy='MinorChange') AND EDNotify=1, 1, 0)) AS 'MinorChange',  
SUM(IF(((AdminDiscrepancy='' AND AutoDiscrepancy='Addition') OR AdminDiscrepancy='Addition') AND EDNotify=1, 1, 0)) AS 'Addition',  
SUM(IF(((AdminDiscrepancy='' AND (AutoDiscrepancy='Agree' OR AutoDiscrepancy='Attest')) OR AdminDiscrepancy='Agree') AND EDNotify=1, 1, 0)) AS 'Agree',
SUM(IF(((AdminDiscrepancy='' AND AutoDiscrepancy='None') OR AdminDiscrepancy='None') AND EDNotify=1, 1, 0)) AS 'Other',
COUNT(*) As 'All ER', CONCAT(LEFT(ROUND(SUM(EDNotify)/COUNT(*)*1000)/10, 3), '%') As 'Emtrac Rate'  
FROM ExamMeta as em 
JOIN ExamDiscrepancy AS ed ON em.PrimaryAccessionNumber=ed.AccessionNumber 
JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
WHERE YEAR(rid.StartDate)>=2011 AND YEAR(rid.StartDate)<=2013 AND IsCurrentTrainee='Y' AND 
em.Location='E'
GROUP BY em.TraineeID
ORDER BY Class, rid.LastName, rid.FirstName ";

flush_buffers();

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$output = getResultsHTML($results);

tableStartSection("Emtrac Statistics and Breakdown", 0);
echo $output;
tableEndSection();

?>


<?php include "../footer.php";?>
