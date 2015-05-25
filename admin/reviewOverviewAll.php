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
if (isset($_GET['from'])) $startDate = date_create($_GET['from']);
else  {
	$startDate = thisJulyFirst();
}

if (isset($_GET['to'])) $endDate = date_create($_GET['to']);
else  {
	$endDate = date_create('NOW');
}

$startDate = $startDate->format("Y-m-d");
$endDate = $endDate->format("Y-m-d");

$results = array();
$listTitle = 'Overall Resident and Fellows Statistics';
$sql = "
SELECT
    IF(rid.IsFellow, 'Fellow', 'Resident') AS `Group`,
    COUNT(*) as 'Total Volume',
    SUM(IF(em.Location='E', 1, 0)) AS `ER Vol`,
    SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0)) AS `Inpt STAT Vol`,          
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange',1,0))*10000 / COUNT(*)/100,2) AS 'Major %',
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND em.Location='E', 1, 0)) / SUM(IF(Location='E', 1,0))*100,2) AS `ED Major %`,
    FORMAT(SUM(IF(ed.EDNotify='1', 1, 0)) / SUM(IF(em.Location='E', 1,0))*100,2) AS `ED Emtrac %`,
    FORMAT(SUM(IF(ed.EDNotify='1' AND (IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange') AND em.Location='E', 1,0))/ SUM(IF(Location='E', 1,0))*100,2) AS `Emtrac Major %`,
    SEC_TO_TIME(AVG(IF(em.Location='E', TIMESTAMPDIFF(SECOND, em.CompletedDTTM, em.PrelimDTTM), NULL))) AS `ED TAT`,
    SEC_TO_TIME(AVG(IF(em.Location='I' AND em.Urgency LIKE 'S%', TIMESTAMPDIFF(SECOND, em.CompletedDTTM, em.PrelimDTTM), NULL))) AS `Inpt STAT TAT`,              
    SUM(IF(TIMESTAMPDIFF(MINUTE, em.CompletedDTTM, em.PrelimDTTM) > 90 AND em.Location='E' AND em.Urgency LIKE 'S%', 1, 0)) AS `ED > 90m`,
    SUM(IF(TIMESTAMPDIFF(MINUTE, em.CompletedDTTM, em.PrelimDTTM) > 60 AND em.Location='I' AND em.Urgency LIKE 'S%', 1, 0)) AS `Inpt > 60m`
FROM 
    ExamMeta AS em                    
    INNER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
    LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber
WHERE 
    rid.IsCurrentTrainee='Y' 
	AND CompletedDTTM>'$startDate' 
	AND CompletedDTTM<='$endDate'
    AND ed.CompositeDiscrepancy!='None' 
	AND em.Location != 'O'
	AND (
		DAYOFWEEK(em.CompletedDTTM)=7 
		OR DAYOFWEEK(em.CompletedDTTM)=1 
		OR HOUR(em.CompletedDTTM) >= 17 
		OR HOUR(em.CompletedDTTM) <= 7 
		OR ed.CompositeDiscrepancy='MajorChange'
		OR ed.CompositeDiscrepancy='MinorChange'
		OR ed.CompositeDiscrepancy='GreatCall'
		OR ed.CompositeDiscrepancy='Addition'
	)
GROUP BY rid.IsFellow
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
<form id="range" method="GET" action="reviewOverviewAll.php">
<label for="from" >From</label>
<input type="text" size=10 id="from" name="from" />
<label for="to">to</label>
<input type="text" size=10 id="to" name="to"/> 
<input type="submit" size=10 value='Go'/> 
</form>

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

<p>
<i>Emtrac Major %</i> - Percentage of ED studies that were both major changes and recorded on Emtrac.
</p>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

