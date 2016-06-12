<form id="range" method="GET" action="discrepancyWorklist.php">
<label style="font-size:14pt;" for="from" >From</label>
<input style="font-size:14pt;" type="text" size=10 id="from" name="from" />
<label style="font-size:14pt;" for="to">to</label>
<input style="font-size:14pt;" type="text" size=10 id="to" name="to"/> (dates imply 12:00AM)
<input id="mode" type="hidden" name="mode">
</form>

<?php
tableStartSection("Discrepancy Worklist",0);
?><br>
<div class="row" data-intro="Click on a button to view the data.">
<div class="3u">
<center><a class="mainMenuButton" href="javascript:loadList('ED');">EDNotify</a>
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
<div class="3u">
<center>
<a class="mainMenuButton" href="<?php echo $URL_root;?>showstudy.php?sec=&typ=&notes=&tags=%&header=Y">Tagged by Me</a>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="<?php echo $URL_root;?>showstudy.php?sec=&typ=&notes=&tags=%23CallTeachingFiles&header=Y">Resident TF</a>
</center>
</div>

</div>

<?php
tableEndSection();
?>


<?php
tableStartSection("Discrepancies by Date",0);
?>
<br>

<div class="row" data-intro="Click on a button to view the data.">
<div class="3u">
<center><a class="mainMenuButton" href="javascript:loadList('EDAll');">All EDNotify</a>
</center>
</div>

<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('MajorAll');">All Major</a>
<!-- <p id="MajorAllCount"><img src="<?php echo
     $URL_root;?>/css/images/loader_small.gif"></p> -->
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Minor');">Minor</a>
<!--<p id="MinorCount"><img src="<?php echo
    $URL_root;?>/css/images/loader_small.gif"></p>-->
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Addition');">Addition</a>
<!--<p id="AdditionCount"><img src="<?php echo
    $URL_root;?>/css/images/loader_small.gif"></p>-->
</center>
</div>
</div>

<?php
tableEndSection();
?>

<?php
tableStartSection("Analytics",0);
?><br>
All analytic sections are calculated on-demand for best accuracy.  Some may take a minute or two to load.
<div class='row'>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewOverviewAll.php">Overall Res+Fel</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="../reviewByGroup.php?group=res">Residents - Overview</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByAllClass.php?group=res">Residents -
By Class</a><br>Class Shortcuts: 
<a href="reviewByAllClass.php?class=2">R2</a> | 
<a href="reviewByAllClass.php?class=3">R3</A> |
<a href="reviewByAllClass.php?class=4">R4</A> |
<a href="reviewByAllClass.php?class=5">Last Year's R4</A>
</center>
</div>
</div>
<div class="row">
<div class="4u">
<center>
<a class="mainMenuButton" href="../reviewByGroup.php?group=fel">Fellows - Overview</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByAllClass.php?class=fel">Fellows -  Individuals</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByAttending.php">Attending Major Rates</a>
</center>
</div>

<div class="4u">
<center>
<form id="traineeAna" action="../reviewByGroup.php" method="GET">
<a class="mainMenuButton" href="javascript:void(0);" onClick="

if (document.getElementById('group').value != '-1') 
	$('#traineeAna').submit();
else
	alert('Select a trainee name first.');

">Specific Trainee</a>
<select class='resViewSelector' id='group' name='group'>
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

</a>
</form>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewCompliance.php">Compliance</a><br>Long load time; please be patient.
</center>
</div>

<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByTAT.php?group=res">TAT Outliers (Res)</a>
</center>
</div>

<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByTAT.php?group=fel">TAT Outliers (Fel)</a>
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

