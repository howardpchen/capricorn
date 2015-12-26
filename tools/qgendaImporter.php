<?php
require "class.iCalReader.php";
include_once "../capricornLib.php";

$runTimeStart = date_create('NOW');
echo "Updating resident rotations. <BR>";

/** Config **/
$sourceFileOrURL = "https://app.qgenda.com/ical?key=a097aed0-3316-4840-ab40-eab0c314c1a1";
//$sourceFileOrURL = "blah.ics";
// Should be written so this can use both a file or a subscription URL.

$singleDayCalls = array();

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
        $returnArray['Rotation'] = trim($temp[0]);
        $returnArray['Trainee'] = trim($temp[1]);
    } else  {
        $temp = explode('[', $inputString);
        $returnArray['Rotation'] = trim($temp[0]);
        $returnArray['Trainee'] = trim(substr($temp[1], 0, -1));
    }
    //print_r($returnArray);
    return $returnArray;
}

$traineeIDMap = array();

$resdbConn = new mysqli('localhost', 'chenp', '6qvQ6drD572x3hut','capricorn');
if (mysqli_connect_errno($resdbConn)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

if ($result = $resdbConn->query("SELECT TraineeID,QGendaName FROM ResidentIDDefinition WHERE QGendaName IS NOT NULL;")) {
    $result = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Error loading trainee database.";
    exit();
}

foreach ($result as $r) {
    $traineeIDMap[trim($r['QGendaName'])] = trim($r['TraineeID']);
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
        $sql = "DELETE from residentrotationraw WHERE RotationStartDate > '$startDate';";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        $first = False;
    }

    if (isset($traineeIDMap[$trnee])) $trnee = $traineeIDMap[$trnee];
    else continue;
    $sql = "REPLACE INTO residentrotationraw (UniqueID,TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ('$uid', $trnee, '$rotation', '$startDate', '$endDate')";
    $resdbConn->query($sql) or die (mysqli_error($resdbConn));
}

echo "Done updating raw data.  Now calculating rotations.<br>";
$sql = "DELETE FROM `residentrotation` WHERE 1";
$resdbConn->query($sql) or die (mysqli_error($resdbConn));

foreach ($traineeIDMap as $qg=>$traineeID) {
    $sql = "SELECT * FROM residentrotationraw WHERE TraineeID=$traineeID ORDER BY RotationStartDate;";
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
                $sql = "INSERT INTO residentrotation (TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ($traineeID, '$rot', '$currentStartDate[$rot]', '$currentEndDate[$rot]')";
                $resdbConn->query($sql) or die (mysqli_error($resdbConn));
                unset($currentStartDate[$rot]);
                unset($currentEndDate[$rot]);
            } 
        }
    }
    foreach ($currentEndDate as $rot=>$edate) {
        $sql = "INSERT INTO residentrotation (TraineeID, Rotation, RotationStartDate, RotationEndDate) VALUES ($traineeID, '$rot', '$currentStartDate[$rot]', '$currentEndDate[$rot]')";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        unset($currentStartDate[$rot]);
        unset($currentEndDate[$rot]);
    }
}
$runTimeEnd = date_create('NOW');
$runTime = $runTimeStart->diff($runTimeEnd);
$runTime = $runTime->format("%h:%i:%s");
echo "All done. Run time $runTime";
writeLog("QGenda updated. Run time $runTime");

?>
