<?php

/*
This script updates current fellows.  NOTE: it requires that a fellow has interpreted at least 1 study since July 1.  Run this on a daily basis in July to ensure you capture all the fellows.
*/
include_once "../CapricornLib.php";

$july = thisJulyFirst();

$sql = "UPDATE ResidentIDDefinition SET IsCurrentTrainee='N' WHERE IsFellow=1;";
$resdbConn->query($sql);

$sql = "SELECT DISTINCT em.TraineeID FROM ExamMeta AS em 
LEFT OUTER JOIN AttendingIDDefinition as aid ON em.TraineeID=aid.AttendingID
LEFT OUTER JOIN ResidentIDDefinition AS rid ON em.TraineeID=rid.TraineeID
WHERE em.TraineeID != em.AttendingID AND CompletedDTTM > '" . $july->format("Y-m-d") . "'
AND (rid.TraineeID IS NULL OR rid.IsFellow=1)
AND aid.AttendingID IS NULL";
$fellowIDs = getSingleResultArray($sql);

$conn2 = sqlsrv_connect($RISName, $connectionInfo);

foreach ($fellowIDs as $fel)  {
    $sql = "SELECT DISTINCT PersonID, LastName, FirstName FROM vProviderIDNumber WHERE PersonID='$fel';";
    $result = sqlsrv_query($conn2, $sql); /** or die("Can't find answer in RIS"); **/
    $r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

    $sql = "INSERT INTO ResidentIDDefinition (TraineeID, FirstName, LastName, IsCurrentTrainee, IsResident, IsFellow, StartDate) VALUES (
    '" . $r['PersonID'] . "',
    '" . $r['FirstName'] . "',
    '" . $r['LastName'] . "', 'Y', 0, 1, 
    '" . $july->format("Y-m-d") . "') ON DUPLICATE KEY UPDATE IsFellow=1, IsCurrentTrainee='Y'";

    $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    
}

echo "Done updating fellows.  Note that residents who go on to become fellows need to be updated by hand.";

?>
