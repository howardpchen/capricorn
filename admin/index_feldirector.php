<?php
tableStartSection("Analytics",0);
?><br>
<div class="row" data-intro="Click on a button to view the data.">

<div class="4u">
<center>
<a class="mainMenuButton" href="reviewOverviewAll.php">Overall Stats</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="../reviewByGroup.php?group=fel">Fellows - Overview</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByAllClass.php?class=fel">Fellows - Individuals</a>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewByTAT.php?group=fel">TAT Outliers</a>
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
if ($_SESSION['traineeid'] == 99999999) {
	echo "<option value='-1'>======RESIDENTS======\n";

	$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' AND IsResident=1 AND IsFellow=0 ORDER BY StartDate ASC";

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
$sql = "SELECT TraineeID, LastName, FirstName FROM `residentiddefinition` WHERE IsCurrentTrainee='Y' AND IsFellow=1 ORDER BY LastName ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    if ($r['LastName'] == "Cook" && $r['FirstName'] == "Tessa") continue;
    echo "<option value=\"" . $r['TraineeID'] . "\">" . ucfirst(strtolower($r['LastName']))  . ", " . ucfirst(strtolower($r['FirstName']));
}

?>
</select><br>

</a>
</form>
</center>
</div>
<div class="4u">
<center>
<a class="mainMenuButton" href="reviewComplianceFel.php">Discrepancy Compliance</a>
</center>
</div>
</div>


<?php
tableEndSection();
?>

