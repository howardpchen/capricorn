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

$startDate = thisJulyFirst();
$startDate = $startDate->format("Y-m-d");
$results = array();
$prog = $_SESSION['program'];

$listTitle = "Resident - Performance by $titleFilter from " . $startDate;

function printTable($year) {
	global $startDate;
	if (isset($_GET['modsec']) && $_GET['modsec']=='Y')  {
		$filter = "
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
			";
		$order = "ecd.Type, ecd.Section";
		$titleFilter = " Modality and Section ";
	} else  {
		$filter = "
			(ecd.Type='US' AND ecd.Section!='BREAST') OR
			(ecd.Type='CR') OR
			(ecd.Type='CT') OR
			(ecd.Type='MR')	
			";
		$order = "ecd.Type";
		$titleFilter = " Modality ";
	}

	if (isset($_GET['class']))  {
		$order = ' rid.LastName, rid.FirstName ';
	}
	$sql = "
SELECT
	$order,
    COUNT(*) as 'Total Volume',
#    SUM(IF(em.Location='E', 1, 0)) AS `ER Vol`,
#    SUM(IF(em.Location='I' AND (em.Urgency LIKE 'S%'), 1, 0)) AS `Inpt STAT Vol`,          
    SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) AS `Major`,
    FORMAT(SUM(IF(CompositeDiscrepancy='MajorChange',1,0)) / COUNT(*)*100, 2) AS `Major %`
#    SEC_TO_TIME(AVG(IF(em.Location='E', PrelimTAT, NULL))) AS `ED TAT`,
#    SEC_TO_TIME(AVG(IF(em.Location='I' AND em.Urgency LIKE 'S%', PrelimTAT, NULL))) AS `Inpt STAT TAT`,              
#    SUM(IF(PrelimTAT > 90*60 AND em.Location='E' AND em.Urgency LIKE 'S%', 1, 0)) AS `ED > 90m`,
#    SUM(IF(PrelimTAT > 60*60 AND em.Location='I' AND em.Urgency LIKE 'S%', 1, 0)) AS `Inpt > 60m`
	FROM 
		ExamMeta AS em                    
		INNER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
		INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
		LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber  AND em.TraineeID=ed.TraineeID
	WHERE 
		rid.Program='$prog' AND rid.IsCurrentTrainee='Y' AND rid.IsResident=1 AND rid.IsFellow=0 
		AND YEAR(CURDATE())-YEAR(rid.StartDate)+1=$year
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
			OR ed.CompositeDiscrepancy='MinorChange'
			OR ed.CompositeDiscrepancy='GreatCall'
			OR ed.CompositeDiscrepancy='Addition'
		)
	GROUP BY rid.StartDate, $order
	ORDER BY rid.StartDate ASC, $order
	";

	$results = getResultsFromSQL($sql);
	$htmlprint = printHTML($results);
	$accessions = array();
	foreach ($results as $r) {
		if (isset($r['Accession'])) $accessions []= $r['Accession'];
	}
	echo $htmlprint;
}


function printHTML($results)  {
    global $URL_root;
    $output = "<table id='resultsTable' class='results'>\n";
    // Header
    
    $first = True;

    while ($row = $results->fetch_array(MYSQL_ASSOC))  {
        if ($first)  {
            $first = False;
            $headers = array_keys($row);
            $output .= "<thead><tr>";
            foreach ($headers as $h)  {
                $output .= "<th align=left><strong>$h</strong></th>";
            }
            $output .= "</tr></thead>\n<tbody>\n";
        }
        $output .= "<tr>";
        foreach($row as $k=>$col) {
            if (is_a($col, "DateTime")){
                $col = $col->format('Y-m-d H:i:s');
            }

            if ($k == "TraineeID")  {
                $output .= "<td id='" . $col . "'>";
                $output .= "<a href='reviewByAllClass.php?group=" . $col . "'>$col</a>";
            }
            else $output .= "<td>" . $col;
            /************************/
            //$output .= $col;

        }
        $output .= "</tr>\n";
    }
    if ($first)  {
        echo "<tbody><tr><td>No study satisfies the search crtieria.</tr></tbody>";
    }
    $output .= "\n</tbody>\n</table>";
    return $output;
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

if (isset($_GET['class']))  {
	printTable($_GET['class']);
}
else {
	printTable(2);
	printTable(3);
	printTable(4);
}
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

</script>

<a href="javascript:back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>

