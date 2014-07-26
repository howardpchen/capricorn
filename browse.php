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

include_once "capricornLib.php";
?>

<!doctype html>
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
/*    if (a < -90)  {
        document.getElementById('mod').value = document.getElementById('mod').value==''?'CR':document.getElementById('mod').value;
    }
*/
    $("#from").datepicker('setDate',a);
    $("#to").datepicker('setDate',new Date());
    $("#range").submit();
}
//-->
</script>


<?php include "header.php"; ?>
<Title>Browse - <?php echo getLoginUserFullName();?> - Capricorn</title>

<p>
<table border=0 width=100%><tr>
<td valign=top width=250 style="padding:15px">

<!-- Display Rotations Here -->
<?php

function displayRotationButton($rot, $startDate, $endDate) {
    global $schemaColor;
    $startDate = toJSDate($startDate);
    $endDate = toJSDate($endDate);
    echo <<< END
<form id="rotationRange">
<input type="hidden" size=10 name="from" value="$startDate"/>
<input type="hidden" size=10 name="to" value="$endDate"/> 
<input type="hidden" name="rota" value="$rot"/>
<input type="submit" id="sub" title="$startDate to $endDate" value="$rot" style="line-height:1em; border:none;margin-bottom: 0;margin-top: 0;background:none"/>
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
    
    if (isCallRotation($r['Rotation'])) $calls[] = $r;
    else if ($today > $endD) $prev[] = $r;
    else if ($today < $startD) $future[] = $r;
    else $current[] = $r;
}
?>
<div  data-intro="Click on a rotation to display its data." data-position="bottom">
<?php
tableStartSection("Current Rotation", 0);
foreach ($current as $r) {
    $r['RotationStartDate'] = str_replace("-", "/", $r['RotationStartDate']);
    $r['RotationEndDate'] = str_replace("-", "/", $r['RotationEndDate']);
    displayRotationButton($r['Rotation'], $r['RotationStartDate'], $r['RotationEndDate']);
}
tableEndSection();
?>
</div>
<?php
tableStartSection("Previous 2013-2014", 0);
foreach ($prev as $r) {
    $r['RotationStartDate'] = str_replace("-", "/", $r['RotationStartDate']);
    $r['RotationEndDate'] = str_replace("-", "/", $r['RotationEndDate']);
    displayRotationButton($r['Rotation'], $r['RotationStartDate'], $r['RotationEndDate']);
}
tableEndSection();

tableStartSection("Future", 0);
foreach ($future as $r) {
    displayRotationButton($r['Rotation'], $r['RotationStartDate'], $r['RotationEndDate']);
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
<div class='control' data-intro="Control Panel to select date range and display style." data-position="right">
<form id="range">
<label for="from" >From</label>
<input style="border:solid 1px;background:none" type="text" size=10 id="from" name="from" />
<label for="to">to</label>
<input style="border:solid 1px;background:none" type="text" size=10 id="to" name="to"/> 
<label><input type="checkbox" title="Total studies interpreted versus daily counts." onClick="$('#range').submit();" id="cumulative" name="cumulative" value="Y" <?php echo $cumulative?"checked":""?>>Cumulative</label>
<input type="submit" id="sub" value="Go" /><br>
<label>Modality:
<select style="background:none" name='mod' id='mod'>
<option value=''> All </option>

<?php

$examType = getExamCodeData('Type', NULL, 'ORDER BY Type');

foreach ($examType as $type) {
    $short = $type[0];
    $long = codeToEnglish($short);
    if (isset($excludeBrowse))  {
        // Remove the exam types in the exclude list (under capricornConfig.php)
        if (in_array($short, $excludeBrowse)) continue;
    }
    $selected = '';
    if (isset($_GET['mod']) && $_GET['mod'] == $short) $selected = 'selected';

    echo "<option value='" . $short . "' " . $selected . " >" . $long . "</option>\n";
}

?>

</select></label>
</form>
Past: [ <a href="#" onclick="clickInterval(-31)">1 month</a> | 
<a href="#" onclick="clickInterval(-183)">6 months</a> | 
<a href="#" onclick="clickInterval(-365)" title="May take up to 1 minute to process. Speed things up by selecting a specific modality.">1 year</a> |
<a href="#" onclick="clickInterval(-1431)" title="May take up to 5 minutes to process.  Speed things up by selecting a specific modality.">4 years</a> ]

</div>
<p>

<?php 
if (isset($_GET['mod']) && $_GET['mod'] != '')  {
    include "disp_single_modality.php";
} else {
    include "disp_by_modality.php"; 
}

?>

</tr></table>


<P><A HREF="logout.php">Log Out</A></P>
<?php
include "footer.php";
ob_end_flush();
?>
