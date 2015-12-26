<?php 
session_start();
include "capricornLib.php"; 
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

<html>
<head>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.min.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
</head>
<?php include "header.php";
if (isset($_GET['from'])) $startDate = date_create($_GET['from']);
else  {
	$startDate = thisJulyFirst();
#    if ((int)$startDate->format("n") >= 7 && (int)$startDate->format("n") <= 8) $startDate = $startDate->modify('-1 year');
}

if (isset($_GET['to'])) $endDate = date_create($_GET['to']);
else  {
	$endDate = date_create('NOW');
}

$startDate = $startDate->format("Y-m-d");
$endDate = $endDate->format("Y-m-d");

$results = array();

$prog = $_SESSION['program'];

if (isset($_GET['group']) && isAdmin())  {
	if ($_GET['group'] == 'res')  {
		$group = " rid.Program='$prog' AND rid.IsCurrentTrainee='Y' AND rid.IsResident=1 AND rid.IsFellow=0 ";
		$titleGroup = 'Resident';
	} else if ($_GET['group'] == 'fel')  {
		$group = " rid.Program='$prog' AND rid.IsCurrentTrainee='Y' AND rid.IsFellow=1 ";
		$titleGroup = 'Fellow';
	} else  {
		// group can also just be a trainee ID to look at a single individual.
		$group = " rid.TraineeID=" . $_GET['group'];
		$titleGroup = getUserFullName($_GET['group']);
	}
} else {
		$_GET['group'] = $_SESSION['traineeid'];
		$group = " rid.TraineeID=" . $_GET['group'];
		$titleGroup = getUserFullName($_GET['group']);
 }


$listTitle = "$titleGroup - Performance Analytics";

function printTable($modsec='N', $total='N') {
	global $endDate,$startDate, $group;

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
			(ecd.Type='MR')	OR 
			(ecd.Type='NM')
			";
		$order = "ecd.Type";
	}
	if ($total == 'Y')  {
		$order = '';
	}
	if (isset($_GET['class']))  {
		$order = ' rid.LastName, rid.FirstName ';
	}
	$sql = "
	SELECT
		$order " . ($order==""?"":",") . "
		COUNT(*) as 'Total Volume',
#		SUM(IF(em.Location='E', 1, 0)) AS `ER Vol`,
#		SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0)) AS `Inpt STAT Vol`,          
		SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) AS `Major`,
		FORMAT(SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) / COUNT(*)*100, 2) AS `Major %`
	FROM 
		ExamMeta AS em                    
		INNER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
		INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
		LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber AND em.TraineeID=ed.TraineeID
	WHERE 
		$group
		AND CompletedDTTM>'$startDate' 
		AND CompletedDTTM<='$endDate'
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
			OR ed.CompositeDiscrepancy='MinorChange'
			OR ed.CompositeDiscrepancy='Addition'
		)
		";
	if ($order != '')  {
		$sql .= "
			GROUP BY $order
			ORDER BY $order
			";
	}
	$results = getResultsFromSQL($sql);
	$htmlprint = printHTML($results);
	echo $htmlprint;
}
?>
<title><?php echo $listTitle; ?> - Capricorn</title>

<div id='listTitle' class='reportHeader'><?php echo $listTitle; ?></div>
<form id="range" method="GET" action="reviewByGroup.php">
<label for="from" >From </label>
<input type="text" size=10 id="from" name="from" />
<label for="to">to </label> 
<input type="text" size=10 id="to" name="to"/>
<input type="submit" size=10 value='Go'/> (dates imply 12:00AM)
<input type='hidden' name='group' value="<?php echo $_GET['group'];?>">
</form>

<?php

if ($_GET['group'] == 'res')  {
	echo "[<a href='admin/reviewByAllClass.php'>by class</a>]";
} else if ($_GET['group'] == 'fel')  {
	echo "[<a href='admin/reviewByAllClass.php?class=fel'>individual fellows</a>]";
} 

echo "<H3>Total</H3>";
printTable('N','Y');
echo "<H3>Organized by Modality</H3>";
printTable();
echo "<H3>Organized by Modality and Section</H3>";

printTable('Y');

?>

<script>
$(function(){
    $('.results').tablesorter(); 
});


$(function() {

    $( "#from" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate);
    }
    });
    d = new Date('<?php echo $startDate;?>');
	d.setDate(d.getDate()+1);
    $("#from").datepicker('setDate', d);

    $( "#to" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#from" ).datepicker("option", "maxDate", selectedDate );
    }
    });
    d = new Date('<?php echo $endDate;?>');
	d.setDate(d.getDate()+1);
    $("#to").datepicker('setDate', d);

});

</script>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "footer.php"; ob_end_flush(); ?>

