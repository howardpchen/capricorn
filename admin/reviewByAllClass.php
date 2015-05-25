<?php include "../capricornLib.php"; 
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

$startDate = thisJulyFirst();
$startDate = $startDate->format("Y-m-d");
$results = array();

$listTitle = "Performance by Modality and Section from " . $startDate;

function printTable($year, $modsec='N') {
	global $startDate, $listTitle;
	
	$yearString="AND YEAR(DATE_ADD(CURDATE(), INTERVAL 6
    MONTH))-YEAR(DATE_ADD(rid.StartDate, INTERVAL 6 MONTH))+1=$year";
	$group = "rid.IsCurrentTrainee='Y' AND rid.IsResident=1 AND rid.IsFellow=0";

	if ($modsec=='Y')  {
		$filter = "
			(ecd.Type='CR' AND ecd.Section='CHEST') OR
			(ecd.Type='CT' AND ecd.Section='CHEST') OR
			(ecd.Type='CR' AND ecd.Section='MSK') OR
			(ecd.Type='CT' AND ecd.Section='MSK') OR
			(ecd.Type='CR' AND ecd.Section='BODY') OR
			(ecd.Type='CT' AND ecd.Section='BODY') OR
			(ecd.Type='CTA') OR
			(ecd.Type='US' AND ecd.Section!='BREAST') OR
			(ecd.Type='MR' AND ecd.Section='NEURO') OR
			(ecd.Type='CT' AND ecd.Section='NEURO') OR
			(ecd.Type='MR' AND ecd.Section='SPINE') OR
			(ecd.Type='CT' AND ecd.Section='SPINE') OR
			(ecd.Type='NM' AND ecd.Section='SCINT')
			";
		$order = "ecd.Type, ecd.Section";
	} else  {
		$filter = "
			(ecd.Type='US' AND ecd.Section!='BREAST') OR
			(ecd.Type='CR') OR
			(ecd.Type='CT') OR
			(ecd.Type='CTA') OR
			(ecd.Type='NM') OR
			(ecd.Type='MR')	
			";
		$order = "ecd.Type";
	}

	if (isset($_GET['class']))  {
		$order = ' rid.TraineeID, rid.LastName, rid.FirstName ';
		if ($_GET['class'] == 'fel')  {
			$listTitle = "Fellows " . $listTitle;
			$yearString = '';
			$group = "rid.IsCurrentTrainee='Y' AND rid.IsFellow=1";
		}
	}

	$sql = "
	SELECT
		$order,
		COUNT(*) as 'Total Volume',
		SUM(IF(em.Location='E', 1, 0)) AS `ER Vol`,
		SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0)) AS `Inpt STAT Vol`,          
		SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) AS `Major`,
		FORMAT(SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) / COUNT(*)*100, 2) AS `Major %`,
		SEC_TO_TIME(AVG(IF(em.Location='E', PrelimTAT, NULL))) AS `ED TAT`,
		SEC_TO_TIME(AVG(IF(em.Location='I' AND em.Urgency LIKE 'S%', PrelimTAT, NULL))) AS `Inpt STAT TAT`,              
		CONCAT(SUM(IF(PrelimTAT > 90*60 AND em.Location='E' AND em.Urgency LIKE 'S%', 1, 0)), ' (', 
			ROUND(SUM(IF(PrelimTAT > 90*60 AND em.Location='E' AND em.Urgency LIKE 'S%', 1, 0))/SUM(IF(em.Location='E', 1, 0))*100, 1),
		'%)')
		    AS `ED > 90m`,
		CONCAT(SUM(IF(PrelimTAT > 60*60 AND em.Location='I' AND em.Urgency LIKE 'S%', 1, 0)), ' (',
			ROUND(SUM(IF(PrelimTAT > 60*60 AND em.Location='I' AND em.Urgency LIKE 'S%', 1, 0))/SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0))*100, 1),
		'%)')
			AS `Inpt > 60m`
	FROM 
		ExamMeta AS em                    
		INNER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
		INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
		LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber AND em.TraineeID=ed.TraineeID
	WHERE 
		$group
		$yearString
		AND CompletedDTTM>'$startDate' 
		AND ed.CompositeDiscrepancy!='None' 
		AND (
			$filter
		)                                           
	AND (
		DAYOFWEEK(em.CompletedDTTM)=7 
		OR DAYOFWEEK(em.CompletedDTTM)=1 
		OR HOUR(em.CompletedDTTM) >= 17 
		OR HOUR(em.CompletedDTTM) <= 7 
		OR ed.CompositeDiscrepancy='MajorChange'
		OR ed.CompositeDiscrepancy='GreatCall'
		OR ed.CompositeDiscrepancy='MinorChange'
		OR ed.CompositeDiscrepancy='Addition'
	)
	GROUP BY rid.StartDate, $order
	ORDER BY rid.StartDate ASC, $order
	";

	$results = getResultsFromSQL($sql);
	$htmlprint = printHTML($results);
	echo $htmlprint;
}




?>

<html>
<head>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.min.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<title><?php echo $listTitle; ?> - Capricorn</title>
</head>
<?php include "../header.php"; ?>
<div id='listTitle' class='reportHeader'><?php echo $listTitle; ?></div>
Click on header to sort.
<?php
// CHECK FOR ADMIN STATUS
checkAdmin();

if (isset($_GET['class']))  {
	printTable($_GET['class']);
}
else {
	echo "<h3>R2 Residents</h3> [<a href='reviewByAllClass.php?class=2'>details</a>]\n";
	printTable(2);
	printTable(2, 'Y');
	echo "<h3>R3 Residents</h3> [<a href='reviewByAllClass.php?class=3'>details</a>]\n";
	printTable(3);
	printTable(3, 'Y');
	echo "<h3>R4 Residents</h3> [<a href='reviewByAllClass.php?class=4'>details</a>]\n";
	printTable(4);
	printTable(4, 'Y');
	echo "<h3>R5 Residents</h3> [<a href='reviewByAllClass.php?class=5'>details</a>]\n";
	printTable(5);
	printTable(5, 'Y');
	
}
?>

<script>
$(function(){
    $('.results').tablesorter(); 
});

</script>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

