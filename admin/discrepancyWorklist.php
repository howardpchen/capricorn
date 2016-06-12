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

//if (isset($_GET['from'])) {
    $startDate = date_create($_GET['from']);
    //$startDate = date_create('2014-07-18');
    if (isset($_GET['to']))  {
        $endDate = date_create($_GET['to']);
    }
    else {
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . $_GET['day'] . 'D'));
//        $endDate->add(new DateInterval('P7D'));
    }
//}
$results = array();
$listTitle = '';
if (!isset($_GET['mode'])) $_GET['mode'] = '';
switch ($_GET['mode'])  {
    case "ED":
        $results = getAllEDUnreviewed($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $listTitle = "Unreviewed Emtrac";
        break;
    case "EDAll":
        $results = getAllED($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $listTitle = "All Emtrac";
        break;
    case "Major":
        $results = getMajorChangeAllUnreviewed($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $listTitle = "Unreviewed Major Changes";
        break;
    case "MajorAll":
        $results = getMajorChangeAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $listTitle = "Major Changes" ;
        break;
    case "Minor":
        $results = getTraineeMinor($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), '');
        break;
    case "Addition":
        $results = getTraineeAddition($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), '');
		$listTitle = "Additions";
        break;
    case "Flagged":
        $results = getFlagged('', $endDate->format('Y-m-d'));
        $listTitle = "Flagged by Resident for Additional Review";
        break;
    default: 
        $results = getTraineeStudiesByDiscrepancy($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), '');
}
// Takes AJAX request up here and return the calculation results.
if (isset($_GET['ajax']))  {
    echo $_GET['ajax'] . "," . $results->num_rows;
    exit();
}

?>

<html>
<head>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/theme.blue.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.min.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.widgets.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script>
<!--
$(function() {

    $( "#from" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate);
    }
    });
    d = new Date('<?php echo $startDate->format('Y-m-d');?>');
	d.setDate(d.getDate()+1);
    $("#from").datepicker('setDate', d);

    $( "#to" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#from" ).datepicker("option", "maxDate", selectedDate );
    }
    });
    d = new Date('<?php echo $endDate->format('Y-m-d');?>');
	d.setDate(d.getDate()+1);
    $("#to").datepicker('setDate', d);

});

//-->
</script>
<title><?php echo $listTitle . " " . $startDate->format('m-d-Y') . ' to ' . $endDate->format('m-d-Y'); ?> - Capricorn</title>
</head>
<?php include "../header.php"; ?>
<div id='listTitle' class='reportHeader'><?php echo $listTitle . " "; ?></div>
<form id="range" method="GET" action="discrepancyWorklist.php">
<label style="font-size:14pt;" for="from" >From</label>
<input style="font-size:14pt;" type="text" size=10 id="from" name="from" />
<label style="font-size:14pt;" for="to">to</label>
<input style="font-size:14pt;" type="text" size=10 id="to" name="to"/> 
<input type=submit value='Go'>
(dates imply 12:00AM)
<input id="mode" type="hidden" name="mode" value="<?php echo $_GET['mode'];?>">
</form>

<?php
// CHECK FOR ADMIN STATUS
checkAdmin();

$htmlprint = getResultsHTML($results);
$accessions = array();
foreach ($results as $r) {
    $accessions []= $r['Accession'];
}
echo $htmlprint;

?>

<script type="text/javascript">
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

