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

if (isset($_GET['group']))  {
	if ($_GET['group'] == 'res')  {
		$group = " rid.IsCurrentTrainee='Y' AND rid.IsResident=1 AND rid.IsFellow=0 ";
		$titleGroup = 'Resident Outliers for ';
	} else if ($_GET['group'] == 'fel')  {
		$group = " rid.IsCurrentTrainee='Y' AND rid.IsFellow=1 ";
		$titleGroup = 'Fellow Outliers for ';
	} else  {
		// group can also just be a trainee ID to look at a single individual.
		$group = " rid.TraineeID=" . $_GET['group'];
		$titleGroup = getUserFullName($_GET['group']) . " - ";
	}
} else {
	$group = " 1 ";
	$titleGroup = '';
 }


$listTitle = "$titleGroup Turnaround Time from " . $startDate;

function getSQL($date1, $date2)  {
	global $group;

	$outlier = "
		AND (
				(em.Location = 'E' AND PrelimTAT > 180*60) OR    
				PrelimTAT > 120*60
			)
		AND (
				PrelimTAT < 24*60*60
		    )
		";

	if (isset($_GET['group']) && ( ($_GET['group'] != 'res' && $_GET['group'] != 'fel'))) $outlier = " ";

	return "
SELECT em.PrimaryAccessionNumber as `Accession`, 
em.Location AS `ED/Inpt`, rid.TraineeID,
CONCAT(rid.LastName, ', ', rid.FirstName) AS `Trainee`, 
ecd.Section AS `Section`, 
ecd.Type AS `Modality`, 
ecd.Description, 
CompletedDTTM as `Completed`,
PrelimDTTM as `Prelim'd`,
ROUND(PrelimTAT/60) AS `TAT (mins)`
FROM `exammeta` as em
INNER JOIN `ExamCodeDefinition` as ecd ON (em.ExamCode = ecd.ExamCode AND ecd.ORG = em.Organization) 
INNER JOIN `ResidentIDDefinition` as rid ON (em.TraineeID = rid.TraineeID)
WHERE
	$group
    AND CompletedDTTM>'$date1' AND CompletedDTTM<='$date2'
    AND em.Location != 'O'
    AND em.Urgency LIKE 'S%'
    AND (
	DAYOFWEEK(em.CompletedDTTM)=7 
	OR DAYOFWEEK(em.CompletedDTTM)=1 
	OR HOUR(em.CompletedDTTM) >= 17 
	OR HOUR(em.CompletedDTTM) <= 7 
	)   
	$outlier
    AND (
        (ecd.Type='CR' AND ecd.Section='CHEST') OR
        (ecd.Type='CT' AND ecd.Section='CHEST') OR
        (ecd.Type='CR' AND ecd.Section='MSK') OR
        (ecd.Type='CT' AND ecd.Section='MSK') OR
        (ecd.Type='CR' AND ecd.Section='BODY') OR
        (ecd.Type='CT' AND ecd.Section='BODY') OR
        (ecd.Type='US' AND ecd.Section!='BREAST') OR
        (ecd.Type='MR' AND ecd.Section='NEURO') OR
        (ecd.Type='CT' AND ecd.Section='NEURO') OR
        (ecd.Type='MR' AND ecd.Section='SPINE') OR
        (ecd.Type='CT' AND ecd.Section='SPINE') OR
        (ecd.Type='NM' AND ecd.Section='SCINT')
    )
ORDER BY CompletedDTTM DESC
	";
}

function printTable($modsec='N') {
	$today = date_create('NOW');
	$startDate1 = thisJulyFirst();


	$mySQL = getSQL($startDate1->format('Y-m-d'), $today->format('Y-m-d'));
	$results = getResultsFromSQL($mySQL);
	$htmlprint = getResultsHTML($results);
	echo $htmlprint;
}

?>

<html>
<head>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/theme.blue.css" />
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.min.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.widgets.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<title><?php echo $listTitle; ?> - Capricorn</title>
</head>
<?php include "../header.php"; ?>
<div id='listTitle' class='reportHeader'><?php echo $listTitle; ?></div>
Capricorn is designed to let you dive in and interact with the data directly. <p> Example: Entering "&gt;180" in "TAT" column will include only studies with turnaround time &gt; 180 minutes. <br>Example: Entering "&lt;2014-7-10" in "Completed" will include only studies completed earlier than 2014-7-10.<br>Example: Typing "Pelvis" under "Descriptions" will include only studies with the word "pelvis" (not case sensitive) in the description.<p>It is known there are sporadic errors in recording of timestamps, causing artificially high TAT.  Capricorn makes the assumption that RIS is the official record and will simply display the data for your interpretation.
<?php
// CHECK FOR ADMIN STATUS
checkAdmin();

printTable();

?>

<script>
$(function(){
    $('.results').tablesorter({
		theme: 'blue',
		widgets: ['zebra', 'filter'],
		ignoreCase:true,
		widgetOption: {
			filter_onlyAvail: 'dropdownFilter'
		}
	}); 
});
</script>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

