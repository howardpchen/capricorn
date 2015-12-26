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
$prog = $_SESSION['program'];
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
ajaxQueue["EmtracCount"] = "ED";
ajaxQueue["MajorCount"] = "Major";
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
    d.setDate(d.getDate()-27);
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

if ($_SESSION['adminid'] >= 99000000) include_once "index_resdirector.php";
else if ($_SESSION['adminid'] >= 90000000) include_once "index_feldirector.php";

?>


<?php
tableStartSection("Detailed Views",0);
?><br>
<div class="row">
<div class="4u">
<center>
<form id='specificTrainee' action="<?php echo $URL_root; ?>login_success.php" method=GET>

<a href="javascript:void(0);" onclick="
if (document.getElementById('changeid').value != '-1') 
	$('#specificTrainee').submit();
else
	alert('Select a trainee name first.');
" class="mainMenuButton">Load Trainee</a><br>
<select class='resViewSelector' id='changeid' name='changeid'>
<?php
if ($_SESSION['traineeid'] >= 99000000) {
	echo "<option value='-1'>======RESIDENTS======\n";

	$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE Program='$prog' AND IsCurrentTrainee='Y' AND IsResident=1 AND IsFellow=0 ORDER BY StartDate ASC";

	$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
	$results = $results->fetch_all(MYSQL_ASSOC);
	foreach ($results as $r)  {
		if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
		echo "<option value=\"" . $r['TraineeID'] . "\">" . ucfirst(strtolower($r['LastName']))  . ", " . ucfirst(strtolower($r['FirstName']));

	}
}
?>
<option value='-1'>======FELLOWS======
<?php
$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE Program='$prog' AND IsCurrentTrainee='Y' AND IsFellow=1 ORDER BY LastName ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\">" . ucfirst(strtolower($r['LastName']))  . ", " . ucfirst(strtolower($r['FirstName']));
}

?>
</select>
</form>
</center>
</div>

<div class="4u"><strong>Report by Accession </strong>
    <form action="javascript:loadReport(document.getElementById('reportByAcc').value)"><input type=text id='reportByAcc' size=15 maxlength=15 name='acc'><input type=button onClick="loadReport(document.getElementById('reportByAcc').value)" value="Go"></form>
</div>


</div>
<?php
tableEndSection();
?>

<?php include "../footer.php"; ob_end_flush(); ?>

