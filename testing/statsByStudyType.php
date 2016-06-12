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

$results = array();
$listTitle = 'Major Change Rates by Attending';

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
<?php include "../header.php"; 

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


$sql = "

SELECT
    ecd.Section,
    COUNT(*) as 'Total Reviewed',
        SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange',1,0)) AS `All Major`,
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange',1,0))*10000 / COUNT(*)/100,2) AS 'Major %',
    SUM(IF(em.Location='E', 1,0)) AS `ED`,
    SUM(IF(ed.EDNotify='1', 1, 0)) AS `Emtrac`,
    FORMAT(SUM(IF(ed.EDNotify='1', 1, 0)) / SUM(IF(em.Location='E', 1,0))*100,2) AS `ED Emtrac %`,
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND em.Location='E', 1, 0)) / SUM(IF(Location='E', 1,0))*100,2) AS `ED Major %`,
    FORMAT(SUM(IF(ed.EDNotify='1' AND (IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange') AND em.Location='E', 1,0))/ SUM(IF(Location='E', 1,0))*100,2) AS `ED Major + Emtrac %`,
    SUM(IF(em.Location='I', 1, 0)) AS `Inpatient`,
    SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND (em.Location='I'), 1, 0)) AS `Inpt Major`, 
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND (em.Location='I'), 1, 0))/SUM(IF(Location='I', 1 ,0)) *100,2) AS `Inpt Major%`                                                 
FROM 
    ExamMeta AS em                    
    INNER JOIN AttendingIDDefinition AS aid ON em.AttendingID=aid.AttendingID
    INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
    LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber
WHERE 
    CompletedDTTM > '$startDate'
    AND     CompletedDTTM <= '$endDate'
    AND ed.AutoDiscrepancy!='None' 
    AND em.Location != 'O'
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
    AND (
	DAYOFWEEK(em.CompletedDTTM)=7 
	OR DAYOFWEEK(em.CompletedDTTM)=1 
	OR HOUR(em.CompletedDTTM) >= 17 
	OR HOUR(em.CompletedDTTM) <= 7 
	OR ed.CompositeDiscrepancy='MajorChange'
	OR ed.CompositeDiscrepancy='MinorChange'
	OR ed.CompositeDiscrepancy='Addition'
    )
    AND aid.Section > ''
GROUP BY ecd.Section
";

?>
<div id='listTitle' class='reportHeader'><?php echo $listTitle; ?></div>

<form id="range" method="GET">
<label for="from" >From </label>
<input type="text" size=10 id="from" name="from" />
<label for="to">to </label> 
<input type="text" size=10 id="to" name="to"/>
<input type="submit" size=10 value='Go'/> (dates imply 12:00AM)
<input type='hidden' name='group' value="<?php echo $_GET['group'];?>">
</form>

<?php
// CHECK FOR ADMIN STATUS
checkAdmin();

$results = getResultsFromSQL($sql);
$htmlprint = getResultsHTML($results);
echo $htmlprint;

$sql = "

SELECT
    aid.Section,
    COUNT(*) as 'Total Reviewed',
        SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange',1,0)) AS `All Major`,
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange',1,0))*10000 / COUNT(*)/100,2) AS 'Major %',
    SUM(IF(em.Location='E', 1,0)) AS `ED`,
    SUM(IF(ed.EDNotify='1', 1, 0)) AS `Emtrac`,
    FORMAT(SUM(IF(ed.EDNotify='1', 1, 0)) / SUM(IF(em.Location='E', 1,0))*100,2) AS `ED Emtrac %`,
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND em.Location='E', 1, 0)) / SUM(IF(Location='E', 1,0))*100,2) AS `ED Major %`,
    FORMAT(SUM(IF(ed.EDNotify='1' AND (IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange') AND em.Location='E', 1,0))/ SUM(IF(Location='E', 1,0))*100,2) AS `ED Major + Emtrac %`,
    SUM(IF(em.Location='I', 1, 0)) AS `Inpatient`,
    SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND (em.Location='I'), 1, 0)) AS `Inpt Major`, 
    FORMAT(SUM(IF(IF(ed.AdminDiscrepancy > 0, ed.AdminDiscrepancy, ed.AutoDiscrepancy)='MajorChange' AND (em.Location='I'), 1, 0))/SUM(IF(Location='I', 1 ,0)) *100,2) AS `Inpt Major%`                                                 
FROM 
    ExamMeta AS em                    
    INNER JOIN AttendingIDDefinition AS aid ON em.AttendingID=aid.AttendingID
    INNER JOIN ExamCodeDefinition AS ecd ON em.ExamCode=ecd.ExamCode AND em.Organization=ecd.Org
    LEFT JOIN ExamDiscrepancy AS ed ON em.AccessionNumber=ed.AccessionNumber
WHERE 
    CompletedDTTM > '2014-7-1'
    AND     CompletedDTTM <= '2014-10-31'
    AND ed.AutoDiscrepancy!='None' 
	AND em.Location != 'O'
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
    AND aid.Section='CVI'

";

$results = getResultsFromSQL($sql);
$htmlprint = getResultsHTML($results);
echo $htmlprint;

$results = getResultsFromSQL($sql);
$htmlprint = getResultsHTML($results);
echo $htmlprint;


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
<?php include "../footer.php"; ob_end_flush(); ?>

