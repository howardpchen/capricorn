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
$listTitle = 'Fellows Performance and TAT by Modality and Section from ' . $startDate;
$sql = "
SELECT
    ecd.Type, ecd.Section, 
    COUNT(*) as 'Total Volume',
    SUM(IF(em.Location='E', 1, 0)) AS `ER Vol`,
    SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0)) AS `Inpt STAT Vol`,          
    SUM(IF(ed.AutoDiscrepancy='MajorChange' OR ed.AdminDiscrepancy='MajorChange',1,0)) AS `Major`,
    FORMAT(SUM(IF(ed.AutoDiscrepancy='MajorChange' OR ed.AdminDiscrepancy='MajorChange',1,0)) / COUNT(*)*100, 2) AS `Major %`,
    SEC_TO_TIME(AVG(IF(em.Location='E', TIMESTAMPDIFF(SECOND, em.CompletedDTTM, em.PrelimDTTM), NULL))) AS `ED TAT`,              
    SEC_TO_TIME(AVG(IF(em.Location='I' AND em.Urgency LIKE 'S%', TIMESTAMPDIFF(SECOND, em.CompletedDTTM, em.PrelimDTTM), NULL))) AS `Inpt STAT TAT`              
FROM 
    ExamMeta AS em                    
    INNER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
    INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
    LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber
WHERE 
    rid.IsCurrentTrainee='Y' AND rid.IsFellow=1
    AND CompletedDTTM>'$startDate'
    AND ed.AutoDiscrepancy!='None' 
    AND ed.AutoDiscrepancy!='Attest'
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
        (ecd.Type='FLUO' AND ecd.Section='GI') OR
        (ecd.Type='FLUO' AND ecd.Section='GU') OR
        (ecd.Type='NM' AND ecd.Section='SCINT')
    )                                            
GROUP BY ecd.Type, ecd.Section

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
    $('#resultsTable').tablesorter(); 
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

<a href="javascript:back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

