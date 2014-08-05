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
<!doctype html>
<html>
<head>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<link href="<?php echo $URL_root; ?>css/chardinjs.css" rel="stylesheet">
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts-more.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
</head>
<?php include "header.php";?>
<Title>Capricorn - <?php echo getLoginUserFullName();?></title>

<table border=0 width=100% cellpadding=0 cellspacing=0><tr><td style="padding:15px" valign=top width=250 data-intro="Modalities are generally organized by sections. <P>e.g. CXR is under Chest->Radiography. <P>Body CT is under Body->Computed Tomography <P> Chest CT is under Chest->Computed Tomography" data-position="right">

<?php
if (!isset($_GET['sec'])) $_GET['sec'] = $statisticsDefault['Section'];
if (!isset($_GET['mod'])) $_GET['mod'] = $statisticsDefault['Type'];


$smn = getExamCodeData('Section, Type', NULL, "ORDER BY SECTION, TYPE");
$tableTitles = array();
foreach ($smn as $codeData) {
    $modality = $codeData[1];
    $section = $codeData[0];
    if ($section == "MISC") continue;
    if (!isset($tableTitles[$section]))  {
        $tableTitles[$section] = array();
    }
    $tableTitles[$section] []= $modality;
}

foreach ($tableTitles as $sec=>$mod) {
    tableStartSection($sec);
    foreach($mod as $m) {
        $modExpanded = codeToEnglish($m);
        if ($_GET['sec'] == $sec && $_GET['mod'] == $m) $modExpanded = '>' . $modExpanded . '<';
        echo <<< END
        <form id="secMod">
        <input type="hidden" size=10 name="sec" value="$sec"/>
        <input type="hidden" size=10 name="mod" value="$m"/> 
        <input type="submit" id="sub" value="$modExpanded" style="border:none;background:none;"/>
        </form>
END;
    }
    if ($_GET['sec'] != $sec) echo "<script> toggle_visibility('tbl$sec','lnk$sec') </script>";
    tableEndSection();
}
?>
<td valign=top>
<div class='control' data-intro="Change the range of years used to calculate historical data here.  <p> Also modify the confidence interval (error bars) here" data-position="right">
<form id='range' method='GET'>
<input type="hidden" name="sec" value="<?php echo $_GET['sec'] ?>">
<input type="hidden" name="mod" value="<?php echo $_GET['mod'] ?>">
Use historical data from 7/1/<select title="Change historical data range here" data-intro="Historical data range" data-position="top" id='yrstr' name='yrstr' style="border:none;background:none"> </select>
to 7/1/<select title="Change historical data range here" id='yrend' name='yrend'style="border:none;background:none"> </select>
<br>

Conf. Intervals: <select title="Change the confidence interval on the graph." name='sigma' style="border:none;background:none">
<option value="0.674" <?php if (isset($_GET['sigma']) && $_GET['sigma']==0.674) echo 'selected'; ?>>25%-75%
<option value="1.282" <?php if (isset($_GET['sigma']) && $_GET['sigma']==1.282) echo 'selected'; ?>>10%-90%
<option value="1.644" <?php if (isset($_GET['sigma']) && $_GET['sigma']==1.644) echo 'selected'; ?>>5%-95%
</select>
<label data-intro="Check to toggle between cumulative count vs yearly counts." data-position="bottom" for="cumulative"><input type="checkbox" title="If checked, the count for each year is cumulative from 1st year." onClick="$('#range').submit();" id="cumulative" name="cumulative" value="Y" <?php echo $cumulative?"checked":""?>>Sum Yearly Counts</label>
<input type=submit value="Go" />
</form>
</div>
<p>
<?php include "stats_uservshistorical.php"; ?>
</p>
<strong>Note: Capricorn presents the counts as-is and does not control for curriculum differences or changes in hospital case flow during the timeframe used for historical averages.</strong>
<script>

function createOption(myid, value, oldVal) {
    el = document.createElement('option');
    el.value = value;
    el.innerHTML = value;
    el.id = value;
    if (value == oldVal) el.selected=true;
    document.getElementById(myid).appendChild(el);
}

/* Load the select menu */
for (var i = 1999; i < parseInt(<?php echo $yrend?>); i++) {
    createOption('yrstr',i, <?php echo $yrstr?>);
}
for (var i = parseInt(<?php echo $yrstr?>+1); i <= <?php echo thisJulyFirst()->format("Y"); ?>; i++) {
    createOption('yrend',i, <?php echo $yrend?>);
}


document.getElementById('yrend').addEventListener('change', function() {
    currentVal = document.getElementById('yrstr').value;
    document.getElementById('yrstr').innerHTML = '';
    selectedDate = document.getElementById('yrend').value;
    for (var i = 1999; i < parseInt(selectedDate); i++) {
        createOption('yrstr',i, currentVal);
    }
});
document.getElementById('yrstr').addEventListener('change', function() {
    currentVal = document.getElementById('yrend').value;
    document.getElementById('yrend').innerHTML = '';
    selectedDate = document.getElementById('yrstr').value;
    for (var i = parseInt(selectedDate)+1; i <= <?php echo thisJulyFirst()->format("Y"); ?>; i++) {
        createOption('yrend',i,currentVal);
    }
});
</script>
</tr></table>

<P><A HREF="logout.php">Log Out</A></P>

<?php 
include "footer.php";
ob_end_flush();
?>

