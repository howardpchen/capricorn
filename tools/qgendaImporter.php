<?php
require "class.iCalReader.php";
include "../capricornLib.php";

$runTimeStart = date_create('NOW');
echo "Updating resident rotations. <BR>";

/** Config **/
$sourceFileOrURL = "http://www.qgenda.com/mycal.aspx?key=YOUR QGENDA KEY";

/* 
This can use both a file or a subscription URL.
$sourceFileOrURL = "University_of_Pennsylvania_-_Radiology_Department_Staff_Report_7-1-2013_to_12-1-2013.ics";         
 Currently designed so on first use, use an ICS file that backpopulates all the rotations.  Then use a subscription URL to keep this data up to date.
*/



$singleDayCalls = array();
/*
Currently this script differentiates between single day calls and call rotations (e.g. nightfloat), so that 2+ consecutive days of Body Call, if anyone is thus unfortunate, would shows up separately.
*/
$singleDayCalls[] = "RES - Baby Call";
$singleDayCalls[] = "FEL - Body Call (5-10:30)";
$singleDayCalls[] = "FEL - Body Call (backup)";
$singleDayCalls[] = "RES - Body Call (5-10:30)";
$singleDayCalls[] = "FEL - Res/Fel Call";
$singleDayCalls[] = "Gen Call EVE 5p-10p";
$singleDayCalls[] = "Gen Call WKND AM 8a-12p";
$singleDayCalls[] = "Gen Call WKND AM,6-8PM+24";

/** System **/

function parseSummary($inputString) {
    /* returns an array.  ['Trainee'] = trainee name; ['Rotation'] = rotation.
    /* First need to identify what the separator is.  One mode may be:
         ShortName=Rotation
       Another form may be: 
         Rotation [ShortName]
    */
    $returnArray = array();
    if (strpos($inputString, '=') !== False) {
        $temp = explode('=', $inputString);
        $returnArray['Trainee'] = trim($temp[0]);
        $returnArray['Rotation'] = trim($temp[1]);
    } else  {
        $temp = explode('[', $inputString);
        $returnArray['Rotation'] = trim($temp[0]);
        $returnArray['Trainee'] = trim(substr($temp[1], 0, -1));
    }
    return $returnArray;
}

$traineeIDMap = array();

if ($result = $resdbConn->query("SELECT TraineeID,QGendaName FROM ResidentIDDefinition WHERE QGendaName IS NOT NULL;")) {
    $result = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Error loading trainee database.";
    exit();
}

foreach ($result as $r) {
    $traineeIDMap[$r['QGendaName']] = $r['TraineeID'];
}


$calendar = new ICal($sourceFileOrURL);
$events = $calendar->events();
$first = True;

foreach ($events as $e) {
    //if (!isset($e['SUMMARY'])) continue;
    $resSummary = parseSummary($e['SUMMARY']);
    $trnee = $resSummary['Trainee'];
    $rotation = $resSummary['Rotation'];
    $startDate = $e['DTSTART'];
    $endDate = $e['DTEND'];
    $uid = hash('md5', $trnee . $rotation . $startDate);
    $startDateObj = date_create($startDate);
    $today = date_create('NOW');
    if ($first) {
        // print_r($startDate);
        // delete entries after the first date in the subscription.
        $sql = "DELETE from ResidentRotationRaw WHERE RotationStartDate > '$startDate';";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        $first = False;
    }

    if (isset($traineeIDMap[$trnee])) $trnee = $traineeIDMap[$trnee];
    else continue;
    $sql = "REPLACE INTO ResidentRotationRaw (UniqueID,TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ('$uid', $trnee, '$rotation', '$startDate', '$endDate')";
    $resdbConn->query($sql) or die (mysqli_error($resdbConn));
}

echo "Done updating raw data.  Now calculating rotations.<br>";
$sql = "DELETE FROM `ResidentRotation` WHERE 1";
$resdbConn->query($sql) or die (mysqli_error($resdbConn));

foreach ($traineeIDMap as $qg=>$traineeID) {
//if (True) {
    //$traineeID = 65596342;
    $sql = "SELECT * FROM ResidentRotationRaw WHERE TraineeID=$traineeID ORDER BY RotationStartDate;";
    $result = array();
    if ($result = $resdbConn->query($sql)) {
        $result = $result->fetch_all(MYSQL_ASSOC);
    } else {
        echo "Error loading trainee database.";
        exit();
    }
    $currentStartDate = array();
    $currentEndDate = array();
    foreach ($result as $r) {
        if (sizeof($currentStartDate) == 0) { // First entry;
            $currentStartDate[$r['Rotation']] = $r['RotationStartDate'];
            $currentEndDate[$r['Rotation']] = $r['RotationStartDate'];
            continue;
        } 
        if (array_key_exists($r['Rotation'], $currentStartDate)) {
            // If still in the same rotation, just update end date.
            $currentEndDate[$r['Rotation']] = $r['RotationStartDate']; 
        } else {
            $currentStartDate[$r['Rotation']] = $r['RotationStartDate'];
            $currentEndDate[$r['Rotation']] = $r['RotationStartDate'];
        }
        foreach ($currentEndDate as $rot=>$edate) {
            $d1 = date_create($edate);
            $d2 = date_create($r['RotationStartDate']);
            $elapsed = $d1->diff($d2);
            if ($elapsed->d > 2 || in_array($rot, $singleDayCalls)) {
                $sql = "INSERT INTO ResidentRotation (TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ($traineeID, '$rot', '$currentStartDate[$rot]', '$currentEndDate[$rot]')";
                $resdbConn->query($sql) or die (mysqli_error($resdbConn));
                unset($currentStartDate[$rot]);
                unset($currentEndDate[$rot]);
            } 
        }
    }
    foreach ($currentEndDate as $rot=>$edate) {
        $sql = "INSERT INTO ResidentRotation (TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ($traineeID, '$rot', '$currentStartDate[$rot]', '$currentEndDate[$rot]')";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        unset($currentStartDate[$rot]);
        unset($currentEndDate[$rot]);
    }
}
$runTimeEnd = date_create('NOW');
$runTime = $runTimeStart->diff($runTimeEnd);
$runTime = $runTime->format("%h:%i:%s");
echo "All done. Run time $runTime";

?>
