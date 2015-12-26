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

$prog = $_SESSION['program'];

$startDate = thisJulyFirst();

if (isset($_GET['group']))  {
	if ($_GET['group'] == 'fellow')  {
		$group = "AND rid.IsFellow=1";
	}
	else  {
		$g = $_GET['group'];
	}
} else  {
    // Defaults to current 2nd years. i.e. started last year
	$g = $_GET['group'] = intval($startDate->format("Y"))+3;
}

if (isset($g)) $group = "AND YEAR(rid.StartDate)+4='$g'";

	
$startDate = $startDate->format("Y-m-d");
$results = array();
$listTitle = 'Major Discrepancy Review Compliance';
$sql = "
SELECT 
    IF(IsFellow=0, YEAR(rid.StartDate)+4, 'Fellow')  AS 'Class',
    CONCAT(rid.LastName, ', ' , rid.FirstName) AS 'Name',
    SUM(IF(CompositeDiscrepancy='MajorChange', 1, 0)) AS 'All Major',
    SUM(IF(CompositeDiscrepancy='MajorChange' AND ed.TraineeMarkAsReviewed>=1, 1, 0)) AS 'Reviewed Major',
    IF(SUM(IF(CompositeDiscrepancy='MajorChange', 1, 0)) > 0, ROUND(SUM(IF(CompositeDiscrepancy='MajorChange' AND ed.TraineeMarkAsReviewed>=1, 1, 0)) / SUM(IF(CompositeDiscrepancy='MajorChange', 1, 0)) * 100), '100') AS 'Compliance (%)',
    SUM(IF(CompositeDiscrepancy='MajorChange' AND ed.TraineeMarkAsReviewed=0, 1, 0)) AS 'Unreviewed Major',
    SUM(IF(CompositeDiscrepancy='MajorChange' AND ed.TraineeMarkAsReviewed=0 AND DATEDIFF(NOW(),CompletedDTTM)>4, 1, 0)) AS 'Unreviewed > 3 days'
FROM ExamDiscrepancy AS ed
INNER JOIN ExamMeta AS em ON em.AccessionNumber=ed.AccessionNumber
INNER JOIN ResidentIDDefinition AS rid ON ed.TraineeID=rid.TraineeID
WHERE em.CompletedDTTM>='$startDate'
$group
AND rid.Program='$prog'
AND rid.IsCurrentTrainee='Y'
AND rid.LastName!='Cook'
GROUP BY rid.TraineeID
ORDER BY rid.IsFellow ASC, rid.StartDate ASC
";

$results = getResultsFromSQL($sql);

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


<?php
$yearStrings = array();
$thisYear = thisJulyFirst();
$thisYear = intval($thisYear->format("Y"))+1;

for ($i=0; $i<4; $i++)  {
	$yearStrings []= "<a href='reviewCompliance.php?group=" . ($thisYear+$i) . "'>" . ($thisYear+$i) . "</a>";
}

?>

<h3>

<?php 
echo $_GET['group']=="fellow"?"Fellows":"Class of ".$_GET['group'];
?>
</h3>
<h4>
[<?php echo join(" | ", $yearStrings); ?> | <a href='reviewCompliance.php?group=fellow'>fellow</a>]
</h4>
<?php
// CHECK FOR ADMIN STATUS
checkAdmin();

$htmlprint = getResultsHTML($results);
$accessions = array();
foreach ($results as $r) {
	if (isset($r['Accession'])) $accessions []= $r['Accession'];
}
echo $htmlprint;

?>

<script>
$(function(){
    $('.results').tablesorter(); 
});


// construct the cookie which allows "Next" and "Prev" function in the report display.
document.cookie = "acc=<?php echo implode(",",$accessions);?>; path=/";

currentStudy = null;
studyList = [<?php echo implode(", ",$accessions);?>];
function updateCurrentStudy(acc, newData)  {
    if (newData != null) {
        dataArray = newData.split("||");
    } else  {
        dataArray = [null, null];
    }

    newStudy = acc;

    for (var i = 0; i < studyList.length; i++)  {
        if (studyList[i] == newStudy)  {
            document.getElementById(studyList[i].toString()).className = 'currentStudy';    
        }
        else document.getElementById(studyList[i].toString()).className = 'initial';
    }
    // Update the table content for the "currentStudy" which presumably has been updated.
    if (dataArray[0] != null) {
        $("#"+currentStudy).closest("td").next().next().text(dataArray[0]);
    } 
    if (dataArray[1] != null) {
        $("#"+currentStudy).closest("td").next().next().next().text(dataArray[1]);
    }

    if (newStudy != null && newStudy != currentStudy)  {
        currentStudy = newStudy;
    }
}
</script>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

