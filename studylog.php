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

This script is not part of the normal Capricorn user interface (although
can be made to be so simply by adding a link to login_success.php).

It displays a *complete dump* of the case log for the current user and
displays it in a tab-delimited fashion.

*/
include "capricornLib.php";

$startDate = thisJulyFirst();
$startDate = $startDate->format("Y-m-d");
$results = array();

$listTitle = "Complete Case Log";

function printTable() {
    $sql = "SELECT em.AccessionNumber, ecd.ExamCode, ecd.Description,
    aid.LastName as 'Attending', ecd.Section, ecd.Type, ecd.Notes,
    CompletedDTTM FROM `ExamMeta` as em INNER JOIN `ExamCodeDefinition` as
    ecd ON (em.ExamCode = ecd.ExamCode AND ecd.ORG = em.Organization) INNER
    JOIN `AttendingIDDefinition` as aid ON (em.AttendingID
    = aid.AttendingID) WHERE TraineeID=" . $_SESSION['traineeid'] . " ORDER
    BY em.CompletedDTTM";


    $results = getResultsFromSQL($sql);
    $htmlprint = printHTML($results);
    $accessions = array();
    foreach ($results as $r) {
        if (isset($r['Accession'])) $accessions []= $r['Accession'];
    }
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
<?php include "header.php"; ?>
<div id='listTitle' class='reportHeader'><?php echo $listTitle; ?></div>

<?php printTable(); ?>

<script>
$(function(){
    $('.results').tablesorter(); 
});

</script>

<a href="javascript:back()">Back</a>
<?php include "footer.php"; ob_end_flush(); ?>


