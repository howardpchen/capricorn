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

include "capricornLib.php"; 
?>

<html>
<head>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<link href="<?php echo $URL_root; ?>css/chardinjs.css" rel="stylesheet">
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
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
$("#from").datepicker('setDate', new Date("<?php echo $sd?>"));

$( "#to" ).datepicker({
changeMonth: true,
numberOfMonths: 1,
onClose: function( selectedDate ) {
$( "#from" ).datepicker( "option", "maxDate", selectedDate );
}
});

$("#to").datepicker('setDate', new Date("<?php echo $ed?>"));
});

function clickInterval(a) {
    $("#from").datepicker('setDate',a);
    $("#to").datepicker('setDate',new Date());
    $('#range').submit();
}
//-->
</script>


</head>
<?php include "header.php"; ?>
<Title>Calls - <?php echo getLoginUserFullName();?> - Capricorn</title>

<p>
<table border=0 width=100% cellpadding=0 cellspacing=0><tr><td valign=top width=250 data-intro="Click on the call to display its data." data-position="right">

<!-- Display Rotations Here -->
<?php

if (isset($_GET['callDates'])) $dateArray = $_GET['callDates'];
if (isset($_GET['rota'])) $rotationName = $_GET['rota'];

function displayCallButton($rot, $dateArray) {
    global $schemaColor;
    $dateStr = array();
    foreach ($dateArray as $d)  {
        $strD = date_create($d['StartDate']);
        $endD = date_create($d['EndDate']);
        $dayInt = new DateInterval("P1D");
        while ($strD <= $endD) {
            $dateStr []= $strD->format("Y/n/j");
            $strD->add($dayInt);
        }
        $dateString = join("|", $dateStr);
    }
    echo <<< END
<form id="rotationRange">
<input type="hidden" name="callDates" value="$dateString"/> 
<input type="hidden" name="rota" value="$rot"/>
<input type="submit" id="sub" title="" value="$rot" style="border:none;background:none"/>
</form>
END;

}

$rotations = getRotationsByTrainee($_SESSION['traineeid']);

$current = array();
$prev = array();
$future = array();
$calls = array();


foreach ($rotations as $r) {
    if (!startsWith($r['Rotation'], 'RES') 
        && !startsWith($r['Rotation'], 'FEL')
        && !startsWith($r['Rotation'], 'DEPT - Special Winter Vac')
        && !startsWith($r['Rotation'], 'VAC')) continue;

    $today = date_create('NOW');
    $startD = date_create($r['RotationStartDate']);
    $endD = date_create($r['RotationEndDate']);
    $endD->add(new DateInterval("P1D"));
    
    if (isCallRotation($r['Rotation'])) $calls[$r['Rotation']] []= [
        'StartDate' => $r['RotationStartDate'], 
        'EndDate' => $r['RotationEndDate']
        ];
}

// Build call list.


tableStartSection("Calls");
foreach ($calls as $k=>$r) {
    displayCallButton($k , $r);
}
tableEndSection();

?>
<td valign=top> 
<?php
if (isset($_GET['rota'])) {
    $r = $_GET['rota'];
    echo "<table border=0 width=100%><tr><td bgcolor=$schemaColor[0]><center><font size=+1 color=white>$r</font></center></tr></table><br>";
}
?>
<!--
<table width="450" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr><td><table width=100%" cellspacing="1" border=0 bgcolor="#EEEEEE"><tr><td align="center">
<form id="range">
<label for="from">From</label>
<input type="text" size=10 id="from" name="from" />
<label for="to">to</label>
<input type="text" size=10 id="to" name="to" /> 
<label><input type="checkbox" title="Total studies interpreted versus daily counts." onClick="$('#range').submit();" id="cumulative" name="cumulative" value="Y" <?php echo $cumulative?"checked":""?>>Cumulative</label>
<input type="submit" id="sub" value="Go" />
</form>
Past: [ <a href="#" onclick="clickInterval(-31)">1 month</a> | 
<a href="#" onclick="clickInterval(-183)">6 months</a> | 
<a href="#" onclick="clickInterval(-365)">1 year</a> |
<a href="#" onclick="clickInterval(-1431)">4 years</a> ]
</tr></table></tr>
</table>
-->
<p>

<?php 

if (isset($_GET['callDates']) && isset($_GET['rota'])) include "disp_by_modality_call.php"; 

else  {
    echo <<< END
<h3 align=center> Select a call from the left panel.</h3>

END;
}

?>

</tr></table>


<P><A HREF="logout.php">Log Out</A></P>
<?php
include "footer.php";
ob_end_flush();
?>
