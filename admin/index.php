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
session_start();
checkAdmin();
?>

<html>
<head>
<title>Administrative Dashboard - Capricorn</title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script>
ajaxQueue = new Object();
ajaxQueue["EmtracAllCount"] = "EDAll";
ajaxQueue["EmtracCount"] = "ED";
ajaxQueue["MajorAllCount"] = "MajorAll";
ajaxQueue["MajorCount"] = "Major";
ajaxQueue["MinorCount"] = "Minor";
ajaxQueue["AdditionCount"] = "Addition";
ajaxQueue["FlaggedCount"] = "Flagged";
loadingString = '<img src="<?php echo $URL_root;?>/css/images/loader_small.gif">';

function updateAjax()  {
    for (var key in ajaxQueue)  {
        if (ajaxQueue.hasOwnProperty(key))  {
            var val = ajaxQueue[key];
            $("#"+key).html(loadingString);
            $.ajax({
                url: "<?php echo $URL_root; ?>/admin/discrepancyWorklist.php?ajax="+key+"&mode="+val +"&from=" + encodeURIComponent($("#from").val()) + "&to=" + encodeURIComponent($("#to").val()),
                success: function(data) {
                    var d = data.trim().split(',');
                    $("#"+d[0]).text("Studies: " + d[1]);
                }
            });
        }
    }
}

function loadReport(access) {
    var win = window.open("<?php echo $URL_root;?>displayReport.php?acc=" + access, "rep", "scrollbars=yes, toolbar=no, status=no, menubar=no, width=1000, height=768"); win.focus();
}


function loadDialog(myTitle, url) {
    var tos_dlg = $("<div class='dia'></div>")
        .dialog({
            autoOpen: true,
            title: myTitle,
            width: 1000,
            height: 600,
            modal: true,
            closeOnEscape: true
        }); 
    tos_dlg.load(url);
}

$(function() {

    $( "#from" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate);
        updateAjax();
    }
    });
    d = new Date();
    d.setDate(d.getDate()-6);
    $("#from").datepicker('setDate', d);

    $( "#to" ).datepicker({
    changeMonth: true,
    numberOfMonths: 1,
    onClose: function( selectedDate ) {
        $( "#from" ).datepicker( "option", "maxDate", selectedDate );
        updateAjax();
    }
    });

    $("#to").datepicker('setDate', new Date("<?php echo $ed; ?>"));
    updateAjax();
});

function loadList(mode)  {
    $("#mode").val(mode);
    $("#range").submit();
}

</script>
</head>
<?php include "../header.php";
?>

<form id="range" method="GET" action="discrepancyWorklist.php">
<label for="from" >From</label>
<input type="text" size=10 id="from" name="from" />
<label for="to">to</label>
<input type="text" size=10 id="to" name="to"/> 
<input id="mode" type="hidden" name="mode">

</form>

<?php
tableStartSection("Discrepancy Worklist",0);
?><br>
<div class="row" data-intro="Click on a button to view the data.">
<div class="3u">
<center><a class="mainMenuButton" href="javascript:loadList('ED');">Emtrac</a>
<p id="EmtracCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Major');">Major</a>
<p id="MajorCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Flagged');">Flagged</a>

<p id="FlaggedCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
</div>

<?php
tableEndSection();
?>

<?php
tableStartSection("Discrepancies by Date",0);
?><br>

<div class="row" data-intro="Click on a button to view the data.">
<div class="3u">
<center><a class="mainMenuButton" href="javascript:loadList('EDAll');">All Emtrac</a>
<p id="EmtracAllCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('MajorAll');">All Major</a>
<p id="MajorAllCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Minor');">Minor</a>
<p id="MinorCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Addition');">Addition</a>
<p id="AdditionCount"><img src="<?php echo $URL_root;?>/css/images/loader_small.gif"></p>
</center>
</div>
</div>

<?php
tableEndSection();
?>

<?php
tableStartSection("Analytics - Work in Progress",0);
?><br>
<div class="row" data-intro="Click on a button to view the data.">
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewCompliance.php">Compliance</a>
</center>
</div>

<div class="4u">
<center>
<a class="mainMenuButton" href="javascript: $('#residentDisc').submit()">Resident</a>
<form action="discrepancyStats.php" method=GET id="residentDisc">
<select class='resViewSelector' name='traineeid' style='margin-top:5px'>
<?php

$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' AND IsResident=1 AND IsFellow=0 ORDER BY StartDate ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\">" . $r['FirstName']  . " " . $r['LastName'];
}
?>
</select>
</form>
</center>
</div>

<div class="4u">
<center>
<a class="mainMenuButton" href="discrepancyClass.php">Discrepancy By Class</a>
</center>
</div>

</div>
<div class='row'>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewOverviewAll.php">Overall Res+Fel</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewResidentByModality.php">Residents Overview</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewFellowByModality.php">Fellows Overview</a>
</center>
</div>

<!--
<div class="4u">
<center>
<a class="mainMenuButton" href="discrepancyEmtrac.php">ED Analytics</a>
</center>
</div>
-->
</div>


<?php
tableEndSection();
?>

<?php
tableStartSection("Utility Tools",0);
?><br>
<div class="row">
<div class="6u">

See the resident view for (opens new windows): <form target=_blank action="<?php echo $URL_root; ?>login_success.php" method=GET>

<select class='resViewSelector' name='changeid'>
<option>======RESIDENTS======
<?php

$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' AND IsResident=1 AND IsFellow=0 ORDER BY StartDate ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\">" . ucfirst(strtolower($r['FirstName']))  . " " . ucfirst(strtolower($r['LastName']));

}
?>
<option>======FELLOWS======
<?php
$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' AND IsFellow=1 ORDER BY LastName ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\">" . ucfirst(strtolower($r['FirstName']))  . " " . ucfirst(strtolower($r['LastName']));

}

?>
</select>

<input type=submit value="Go"></form>

</div>
<div class="6u"><strong>Report by Accession </strong>
    <form action="javascript:loadReport(document.getElementById('reportByAcc').value)"><input type=text id='reportByAcc' size=15 maxlength=15 name='acc'><input type=button onClick="loadReport(document.getElementById('reportByAcc').value)" value="Go"></form>
</div>


</div>
<?php
tableEndSection();
?>

<?php include "../footer.php"; ob_end_flush(); ?>

