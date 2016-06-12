<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

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

/**************************************
capricornLib.php

This is the heart of the Capricorn engine.  For (almost) all the frontend 
functions the tasks is simply to use the right function from this library 
to generate the data to plug into the correct charting API such as HighCharts.
 **************************************/

include_once "config/capricornConfig.php";
include_once "userLib.php";


/**************************************
 System-Wide Shared Functions 
 **************************************/

$connectionInfo = array("Database"=>$RISDatabase, "UID"=>$RISLogin, "PWD"=>$RISPwd);

$resdbConn = new mysqli($mysql_host, $mysql_username, $mysql_passwd, $mysql_database);
if (mysqli_connect_errno($resdbConn)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
$login_table="LoginMember";

$ed = date_create('NOW');
$ed->add(new DateInterval('P1D'));
$sd = clone $ed; 
$sd->sub(new DateInterval('P31D')); // end date - decided here.  The javascript just reflects the decisions done here.
$cumulative = False;

writeLog("Page Load: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

$sd = $sd->format("m/d/Y");
$ed = $ed->format("m/d/Y");


if (isset($_GET['from']) && isset($_GET['to'])) {
    $sd = $_GET['from'];     // Start Date for highchart stuff.  Must use this variable.
    $ed = $_GET['to'];     // End Date for highchart stuff.  Must use this variable.
}
if (isset($_GET['cumulative'])) $cumulative = $_GET['cumulative']=='Y' ? True : False;

$dayIntCalc = date_create($ed);
$dayIntCalc = $dayIntCalc->diff(date_create($sd));
$dayInt = $dayIntCalc->format("%a") > 90 ? 7 : 1;
//$dayInt = 7;            // Plot a point every x days.
$intvl = "P" . $dayInt . "D";

function startsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function isCallRotation($r) {
    global $callRotation;
    return array_key_exists($r, $callRotation);
}

function getShiftStart($r) {
    global $callRotation;
    return $callRotation[$r][0];
}

function getShiftDuration($r) {
    global $callRotation;
    return $callRotation[$r][1];
}

function upToDateAsOf() {
    global $resdbConn;
    $sql = "SELECT MAX(CompletedDTTM) FROM ExamMeta;";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array();
    return $results[0];
}


/**************************************
 Pass a string $t.  Automatically puts a timestamp in front of it 
 and will save to a designated log directory.

 Currently it sets to save at $file_root/../capricorn.log, be careful about 
 directory permissions if you are logging sensitive information.
 **************************************/

function writeLog($t)   {
    global $log_flag;
    global $file_root;
    global $log_path;
    global $timezone_string;
    if ($log_flag)  {
        $nowDate = date_create('now', new DateTimeZone($timezone_string));
        $fh = fopen($file_root . $log_path . $nowDate->format("m-Y") . ".log", 'a') or die("can't open log");
        fwrite($fh, "\n" . $nowDate->format("m/d/Y H:i:s") . "\t$t");
        fclose($fh);
    }

}

/**************************************
    Counting functions

    These functions get the count from the Capricorn database.
    Note that your database should be indexed properly to optimize speed, 
    by ExamMeta.CompletedDate, ExamMeta.TraineeID, 
    ExamCodeDefinition.ExamCode, etc. 
 **************************************/

function getCount ($section, $type, $note="") {
    global $resdbConn;
    $sql = "SELECT DISTINCT COUNT(*) as Count FROM ExamMeta as em INNER JOIN ExamCodeDefinition as ecd on em.ExamCode=ecd.ExamCode WHERE TraineeID=" . $_SESSION['traineeid'] . " AND ecd.Type='$type' AND ecd.Section='$section'";
    if ($note != "") {
        $sql = $sql . " AND ecd.Note LIKE '$note'";
    }
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array();
    return $results[0];
}

/**************************************
 getCumulativeCountArray

 Adds up all the counts to create a cumulative array.

 For example, if the daily counts are:
 [2, 4, 7, 8, 3, 5, 6]

 This function should return:
 [2, 6, 13, 21, 24, 29, 35]

 **************************************/

function getCumulativeCountArray($section, $type, $note, $startDate, $endDate, $interval='P1D') {

    global $resdbConn;
    $carray = getCountArray($section, $type, $note, $startDate, $endDate, $interval);
    $startDate = date_create($startDate);
    for ($i = 1; $i < sizeof($carray); $i++) {
        $carray[$i] = $carray[$i] + $carray[$i-1];
    }
    return $carray;
}

/**************************************
 getCountArray

 This function gets counts from the db and returns an array that can be 
 directly plugged into HighCharts "series" attribute for display.

 Currently set to automatically sort studies into 1-day buckets and displays 
 one-day bars on graph.  Change $interval to sort studies into larger or 
 smaller buckets.  (For example currently Capricorn sets $interval='P7D' 
 for large date intervals.
 **************************************/

function getCountArray ($section, $type, $note, $startDate, $endDate, $interval='P1D') {
    global $resdbConn;
    
    if ($section == 'MISC') return;

    $startDate = date_create($startDate);
    $endDate = date_create($endDate);
    $endOfDay = new DateInterval("P1D");
    $endDate->add($endOfDay);
    $interval = new DateInterval($interval);

    $returnArray = array();
    $sql = "SELECT em.InternalID,em.CompletedDTTM FROM ExamMeta as em INNER JOIN ExamCodeDefinition as ecd on em.ExamCode=ecd.ExamCode AND em.Organization=ecd.ORG WHERE TraineeID=" . $_SESSION['traineeid'] . " AND ecd.Type='$type' AND ecd.Section='$section'";
    if ($note != "") {
        $sql = $sql . " AND ecd.Notes LIKE '$note'";
    }
    $sql = $sql . " AND em.CompletedDTTM >= '" . $startDate->format('Y-m-d H:i:s') . "' AND em.CompletedDTTM < '" . $endDate->format('Y-m-d H:i:s') . "'";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    $startDate->add($interval);
    $returnArray[0] = 0;

    for ($i=0; $i < $results->num_rows; $i++) {
        $r = $results->fetch_array(MYSQL_ASSOC);
        $entryDate = date_create($r['CompletedDTTM']);
        while ($entryDate >= $startDate) {
            $returnArray[sizeof($returnArray)] = 0;
            $startDate->add($interval);
        }
        $returnArray[sizeof($returnArray)-1]++;
    }
    
    while ($endDate > $startDate) {
        $returnArray[sizeof($returnArray)] = 0;
        $startDate->add($interval);
    }
    return $returnArray;
}

function getIrregularCumulativeCountArray($section, $type, $note, $individualDates, $start, $duration) {

    global $resdbConn;
    $carray = getIrregularDateCountArray($section, $type, $note, $individualDates, $start, $duration);
    $startDate = date_create($startDate);
    for ($i = 1; $i < sizeof($carray); $i++) {
        $carray[$i] = $carray[$i] + $carray[$i-1];
    }
    return $carray;
}

/* To accomodate for irregular date counts, this function gives an array of arrays 

[0] => [Date0, Count0]
[1] => [Date1, Count1] 
etc
*/

function getIrregularDateCountArray ($section, $type, $note, $individualDates,$start, $duration) {
    global $resdbConn;
    $returnArray = array();
    $today = date_create('NOW');

    foreach ($individualDates as $d) {
        $sql = "SELECT COUNT(*) as count FROM ExamMeta as em INNER JOIN ExamCodeDefinition as ecd on em.ExamCode=ecd.ExamCode AND em.Organization=ecd.ORG WHERE TraineeID=" . $_SESSION['traineeid'] . " AND ecd.Type='$type' AND ecd.Section='$section' ";
        if ($note != "") {
            $sql = $sql . " AND ecd.Notes LIKE '$note'";
        }
        $sameDay = date_create($d);
        $startOfShift = new DateInterval($start);
        $sameDay->add($startOfShift);
        $d1 = $sameDay->format("Y-m-d H:i:s");
        $endOfShift = new DateInterval($duration);
        $sameDay->add($endOfShift);
        if ($sameDay > $today) break;
        $d2 = $sameDay->format("Y-m-d H:i:s");
        $sql .= " AND em.CompletedDTTM > '$d1' AND em.CompletedDTTM < '$d2'";
        //if ($section=="CHEST" and $type=="CT") print_r($sql);
        $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        $results = $results->fetch_array(MYSQL_ASSOC);
        $returnArray[$d] = $results['count'];
    }
    return $returnArray;
}

/**************************************
 Functions to obtain general statistics
 **************************************/

function advanceYearString($dateStr)  {
    $oneyear = new DateInterval("P1Y");
    $dateStr = date_create($dateStr);
    $dateStr->add($oneyear);
    $dateStr = $dateStr->format("Y-m-d");
    return $dateStr;
}

function getLoginUserCount($section, $type, $note="") {
    global $resdbConn;
    $returnArray = array();
    $tempSum = 0;
    $sum = 0;
    $tid = $_SESSION['traineeid'];

    $sql = "SELECT StartDate FROM ResidentIDDefinition WHERE TraineeID=$tid;";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_all(MYSQL_ASSOC);
    $currentYear = $results[0]['StartDate'];
    $tempSum=0;

    $sql = "SELECT Count, CountDT FROM ResidentCounts WHERE TraineeID=$tid AND Type='$type' AND Section='$section'";
    if ($note != "") {
        $sql = $sql . " AND Notes LIKE '$note'";
    }

    $sql .= " ORDER BY CountDT;";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_all(MYSQL_ASSOC);
    foreach ($results as $r) {
        while ($currentYear != $r['CountDT']) {
            $currentYear = advanceYearString($currentYear);
            $returnArray []= $tempSum;
            $tempSum = 0;
        }
        $tempSum += $r['Count'];
        $sum += $r['Count'];
    }
    $returnArray []= $tempSum;
    $july1 = thisJulyFirst();
    if ($currentYear != $july1->format("Y-m-d")) $currentYear = advanceYearString($currentYear);
    while ($currentYear != $july1->format("Y-m-d"))  {
        $currentYear = advanceYearString($currentYear);
        $returnArray []= 0;
    }
    $sql = "SELECT rid.StartDate, COUNT(em.InternalID) as Count FROM
    ExamMeta as em INNER JOIN ExamCodeDefinition as ecd on em.ExamCode=ecd.ExamCode AND em.Organization=ecd.ORG INNER JOIN ResidentIDDefinition as rid ON em.TraineeID=rid.TraineeID WHERE em.TraineeID=". $tid . " AND ecd.Type='$type' AND ecd.Section='$section' AND CompletedDTTM >= '" . $july1->format("Y-m-d") . "'";
    if ($note != "") {
        $sql = $sql . " AND ecd.Notes LIKE '$note'";
    }

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_all(MYSQL_ASSOC);
    if ($results[0]['StartDate'] != $july1->format("Y-m-d"))
        $returnArray []= $results[0]['Count'];
    else $returnArray[0] = $results[0]['Count'];
    $sum += $results[0]['Count'];
    array_unshift($returnArray, $sum);

    return $returnArray;
}

function thisJulyFirst()  {
    $today = date_create('Now');
    $year = intval($today->format('Y'));
    $thisJulyFirst = date_create($year . "-07-01");
    if ($thisJulyFirst > $today) $thisJulyFirst->sub(new DateInterval("P1Y"));
    return $thisJulyFirst;    
}

function nextJuneThirty() {
    $today = date_create('Now');
    $year = intval($today->format('Y'));
    $nextJuneThirty = date_create($year . "-06-30");
    if ($nextJuneThirty < $today) $nextJuneThirty->add(new DateInterval("P1Y"));
    return $nextJuneThirty;
}

/* Obtain an array where each item is a unique resident.  If 
   25 studies are read by 3 residents, then return array may 
   look like this:

   [1285925] = 6
   [7245673] = 12
   [3929282] = 7

   Currently will use data from past 10 years.
*/

function getOverallCountArray($pgy, $section, $type, $note="", $startDate="2008-07-01", $endDate = NULL) {
    global $resdbConn;

    $returnArray = array();
    // Build end-date to the most recent July 1.
    if (is_null($endDate)) {
        $thisJulyFirst = thisJulyFirst();
        $endDate = $thisJulyFirst->format("Y-m-d");
    }

    // Pull historical data from ResidenCounts
    $sql = "SELECT rc.TraineeID, Program, Count FROM ResidentCounts as rc
    INNER JOIN ResidentIDDefinition as rid ON rc.TraineeID=rid.TraineeID
    AND rid.Program='" . $_SESSION['program'] . "' WHERE ResidentYear=". $pgy . " AND Type like '$type' AND Section like '$section'";
    if ($note != "") {
        $sql = $sql . " AND Notes LIKE '$note'";
    }
    $sql = $sql . " AND CountDT >= '" . $startDate . "' AND CountDT < '" . $endDate . "'";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    for ($i = 0; $i < $results->num_rows; $i++)  {
        $r = $results->fetch_array(MYSQL_ASSOC);
        if (isset($returnArray[$r['TraineeID']])) $returnArray[$r['TraineeID']] += $r['Count'];
        else  $returnArray[$r['TraineeID']] = $r['Count'];
    }
    return $returnArray;
}

function getMeanStDevStErr($pgy, $section, $type, $note="", $startDate="2008-07-01", $endDate = NULL) {

    $rawArray = getOverallCountArray($pgy, $section, $type, $note, $startDate, $endDate);
    $returnArray = array();

    $sum = array_sum($rawArray);
    $n = sizeof($rawArray);
    if ($n == 0) {
        return array('Mean'=>-1, 'StDev'=>-1, 'StErr'=>-1);
    }
    $returnArray['Mean'] = $sum/$n;

    $variance = 0.0;
    foreach ($rawArray as $i)
    {
        $variance += pow($i - $returnArray['Mean'], 2);
    }
    $variance /= $n;
    $returnArray['n'] = $n;
    $returnArray['StDev'] = sqrt($variance);
    $returnArray['StErr'] = $returnArray['StDev'] / sqrt($n);
    return $returnArray;
}



/**************************************
 Functions Pertaining to Login User Info 
 **************************************/

function getLoginUserFullName() {
    global $resdbConn;
    $_SESSION['FullName'] = getUserFullName($_SESSION['traineeid']);
    return $_SESSION['FullName'];
}

function getUserFullName($id) {
    global $resdbConn;
    $sql = "SELECT FirstName, MiddleName, LastName FROM ResidentIDDefinition WHERE TraineeID='$id'";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array(MYSQL_NUM);
    $name = implode(" ", $results);
    return $name;
}

function getLoginUserLastName() {
    global $resdbConn;
    $sql = "SELECT LastName FROM ResidentIDDefinition WHERE TraineeID='" . $_SESSION['traineeid'] . "'";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array(MYSQL_NUM);
    $_SESSION['LastName'] = $results[0];
    return $_SESSION['LastName'];
}

function getLoginUserStartDate() {
    global $resdbConn;
    $sql = "SELECT StartDate FROM ResidentIDDefinition WHERE TraineeID='" . $_SESSION['traineeid'] . "'";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array(MYSQL_NUM);
    return join(" ", $results);
}

function getLoginUserPGY() {
    $date = getLoginUserStartDate();
    $startDate = date_create($date);
    $today = date_create('Now');
    $diffDays = $today->diff($startDate)->days;
    return ceil($diffDays / 365 + 1);
}


/**************************************
 Real-Time Display Function (buffer flush) 
 **************************************/

function flush_buffers(){
    ob_flush();
    flush();
    sleep(0.5);
} 

/**************************************
 Rotation-Related Functions
 **************************************/

function codeToEnglish($text) {
    global $replaceDict;
    if (isset($replaceDict[$text])) return $replaceDict[$text];
    else return $text;
}

// Get all rotations from ExamCode Definition
function getRotations() {
    global $resdbConn;
    $sql = "SELECT DISTINCT Rotation FROM ExamCodeDefinition;";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));

    $result_array = array();
    for ($i = 0; $i < $results->num_rows; $i++) {
        $result_array[] = $results->fetch_array(MYSQL_ASSOC);
    }
    return $result_array;
}

/* 
Get all rotations associated with a particular resident ID.
Also include start date and end dates.
*/
function getRotationsByTrainee($residentID) {
    global $resdbConn;
    global $excludedRotations;

    $sql = "SELECT DISTINCT * FROM ResidentRotation WHERE TraineeID=$residentID ORDER BY RotationStartDate;";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $result_array = array();

    for ($i = 0; $i < $results->num_rows; $i++) {
        $temp = $results->fetch_array(MYSQL_ASSOC);


        // This allows us to choose rotations to exclude
        // The exclusion array is set in capricornConfig.php
        $include = true;
        foreach($excludedRotations as $pattern) {
            if (preg_match($pattern, $temp['Rotation'])) $include = false; 
        }

        if ($include) $result_array[] = $temp;
    }
    return $result_array;
}


/* Searchs for section, type, note satisfying these criteria.  
   Takes $array as an argument, where 'key'=>'value' are the search crtieria.
*/
function getExamCodeData($info = 'Section, Type', $array=NULL, $suffix='') {
    global $resdbConn;
    $sql = "SELECT DISTINCT $info FROM ExamCodeDefinition WHERE ";
    if ($array != NULL) { 
        $first = True;
        foreach ($array as $k=>$v) {
            if ($first == False) {
                $sql = $sql . "AND ";
            }
            $first = False;
            $sql = $sql . "`$k`='$v' ";
        }
    }
    else $sql .= "1";
    $sql .= " $suffix"; 
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $result_array = array();
    for ($i = 0; $i < $results->num_rows; $i++) {
        $result_array[] = $results->fetch_array(MYSQL_NUM);
    }
    return $result_array;
}
/**************************************
 Graph Functions
 **************************************/

/**** Collapsable Table Function ****/
function tableStartSection($id, $border=0, $collapsed=False) {
    global $schemaColor;
    /* $array = [date start, date end, volume] */
    $title = codeToEnglish($id);
    $id = str_replace(' ', '_', $id);
    echo <<< END
		<div class="graphheader"><input id="lnk$id" type="button" value="[-]" class="togglebutton" onclick=toggle_visibility("tbl$id","lnk$id")>
        <input type="button" class="togglebutton title" value="$title" onclick=toggle_visibility("tbl$id","lnk$id")></div>
		<div id="tbl$id">
END;
    if ($collapsed)  {
        echo "<script> toggle_visibility('tbl$id','lnk$id') </script>";
    }
}
  
function tableEndSection() {
    echo <<< END
		</div>
END;
}

/**** Highcharts functions ****/
/* makeGraph essentially only creates a string of series that can be passed into assemble_graph().  $datearray is the data obtained from getCumulativeCountArray */
function makeGraph($dataArray, $type="area") {
    global $graphColor;
    global $cumulative;

    $returnString = "";
    $first = True;
    $totalArray = array();

    if (!isset($_GET['callDates'])) {     // This is a hack that tries to show that $k is a rotation name (i.e. it gets changed)... if $k doesn't get changed then this is most likely a set of call cases instead of rotation cases.
        foreach ($dataArray as $k=>$v) {
            if (!$first)  $returnString .= ",";
            else $first = False;
            $totalArray[$k] = array_sum($v);
            $returnString .= "{
type: '$type',
          pointStart: new Date(startDate).valueOf(),
          pointInterval: pointInt,
          name: '" . $k . "',";
            if (isset($graphColor[$k])) {
                $returnString .= "color: '" . $graphColor[$k] . "',";
            }
            $returnString .= "pointWidth: 10,
                data: [" .  join(",", $v) . "]}";
        }
        if (!$cumulative) {
            $returnString .= ",{
type: 'pie',
          name: 'Total',
          data: [";
            $first = True;
            foreach ($totalArray as $section=>$sum) {
                if (!$first) $returnString .= ",";
                else $first = False;
                $returnString .= "{ name: '$section', y: $sum";
                if (isset($graphColor[$section])) {
                    $returnString .= ", color: '" . $graphColor[$section] . "'";
                }
                $returnString .= "}";
            }
            $returnString .= "],
                center: [50, 0], size: 80, showInLegend: false, dataLabels: { enabled: false }}";
        }
    }

    else {
        foreach ($dataArray as $k=>$v) {
            if (!$first)  $returnString .= ",";
            else $first = False;
            $returnString .= "{
type: '$type',
          name: '" . $k . "',
          pointWidth:10,
          data: [";
            $first2 = True;
            foreach ($v as $d=>$val) {
                if (!$first2) $returnString .= ",";
                else $first2 = False;
                $tweakedDT = split("/", $d);
                $tweakedDT[1] = intval($tweakedDT[1]) - 1;
                $returnString .= "[Date.UTC(" . join(", ", $tweakedDT) . "), $val]\n";
            }
            
            $returnString .= "]";
            if (isset($graphColor[$k])) {
                $returnString .= ", color: '" . $graphColor[$k] . "'";
            }
            $returnString .= "}";
        }
    }
        return $returnString;
}


/**
makeDIV
    $graphName = CR, MR or the modality of question.  Corresponds with Type in examcodemeta
    $w is a string corresponding with the width of the widget to be created.
    $h is a string corresponding with the height of the widget to be created.
    if $isEmpty, then instead of creating a visual graph, display something along the lines of "No studies between this timeframe."
*/
function makeDIV($graphName, $w='650px', $h='400px', $isEmpty=False, $intro=NULL) {
    // If array is empty, make an overlay that states so.
    if ($isEmpty) {
        echo <<< END
    <div id="container$graphName" style="position:relative">
        <div id="overlay$graphName" style="position:absolute; width:100%; height:100%; z-index:99999; top:0; left:0; color:black; background-color:white; text-align:center; line-height:300px; font-size:2em; font-color:gray; opacity:0.6">No studies during this timeframe.</div>
    <script> toggle_visibility("tbl$graphName","lnk$graphName") </script>
END;
    }
	
    if ($intro == NULL) {
        echo <<< END
        <div id="$graphName" style="max-width: $w; height: $h; margin: 0 auto"></div>
END;
    } else {
        echo <<< END
        <div id="$graphName" style="max-width: $w; height: $h; margin: 0 auto" data-intro="$intro" data-position="right"></div>
END;
    }
    if ($isEmpty) echo "</div>";

}
function assembleGraph($graphName, $type, $makegrapharray) {
    $graphSeries = makeGraph($makegrapharray, $type);
    $title = codeToEnglish($graphName);
    if ($type == "column")  {
         $subtitle = '"<br>Each bar represents " + pointInt/86400000 + " day(s)"';
    }
    else $subtitle = '"<br>Cumulative Counts"';
    echo <<< END
    <script>
    <!--
    $(function () {
        $("#$graphName").highcharts({
            chart: {
//                type: "$type",
                backgroundColor:'rgba(255, 255, 255, 0.0)'
            },
            title: {
                text: "$title",
                x: -20 //center
            },
            subtitle: {
                text: startDate + " to " + endDate + $subtitle,
                x: -20
            },
            xAxis: {    
                type: 'datetime',
                labels: {
                    format: '{value:%b %e}',
    	            rotation: 0
    	        } 
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Interpreted'
                },
                plotLines: [{

                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: ' studies',
                footerFormat: 'Click on bar graph for details.'
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            plotOptions: {
                column: {
                    pointPadding: 0,
                    borderWidth: 1
                },
                area: {
                    marker: {
                        enabled: false
                    }
                }, 
                series:  {
                    animation: false,    // Animation disabled for IE 10 compatibility
                    point: {
                        events: {
                            click: function() {
                                if (this.series.type != 'column') return;

                                var mydate = new Date(this.category);
                                var dateStr = (mydate.getMonth()+1) + "%2F" + mydate.getUTCDate() + "%2F" + mydate.getFullYear();  // %2F is the escaped '/' char.
                                var section = this.series.name;
                                var type = "$graphName";
                                var notes = '';
                                var tos_dlg = $('<div id="dialog"></div>')
                                    .dialog({
                                        autoOpen: true,
                                        title: 'Studies',
                                        width: 1000,
                                        height: 600,
                                        modal: true,
                                        closeOnEscape: true,
                                        open: function() {
                                            jQuery('.ui-widget-overlay').bind('click', function() {
                                                $("#dialog").remove();
                                            });
                                            jQuery('.ui-dialog-titlebar-close').blur();
                                        }

                                    }); 

                                if (type=='NM')  {
                                    type = section;
                                    section = 'NM';
                                }

                                tos_dlg.load('showstudy.php?from='+ dateStr + "&day=" + (pointInt/86400000) + "&sec=" + encodeURIComponent(section) + "&typ=" + encodeURIComponent(type) + "&notes=" + notes);
                                //alert ('URL: showstudy.php?from='+ dateStr + "&day=" + (pointInt/86400000) + "&sec=" + section + "&typ=" + type + "&notes=" + notes);
                            }
                        }
                    }
                },
            }
            ,
            series: [$graphSeries]
        });
    });
    //-->
    </script>
END;
}

function getTraineeStudiesByDate($startDate, $endDate, $section, $type, $notes, $tags=NULL, $maxLimit=True)  {
    global $resdbConn;
    global $adminTraineeID;
    $vote = '';
    $tagPhrase = '';
    $tagSearch = '';
	$caseInfo = 'em.LastName AS `Last Name`, em.FirstName AS `First Name`, ';
    // The dates are in plain text format.
    if (isset($tags) && sizeof($tags) > 0)  {
        $vote = " (SELECT COUNT(*) FROM ExamUserTags WHERE AccessionNumber=em.AccessionNumber AND Tag='Vote') as Votes, ";
        $tagPhrase = "
		INNER JOIN ExamUserTags AS eut ON em.AccessionNumber=eut.AccessionNumber 
		INNER JOIN `ResidentIDDefinition` AS rid ON (em.TraineeID=rid.TraineeID)
        INNER JOIN ExamDiscrepancy AS ed on em.AccessionNumber=ed.AccessionNumber
			";
		$caseInfo = "eut.Tag, ";
        $tagSearch = ' AND (0 ';
        foreach ($tags as $t)  {
            if ($t[0] != '#') {
                $tagSearch .= "OR ((eut.TraineeID='" . $_SESSION['traineeid'] . "' OR eut.TraineeID=$adminTraineeID) AND eut.Tag LIKE '$t') ";
                //$caseInfo .= " CONCAT(rid.LastName, ', ', rid.FirstName) AS `Trainee`, ed.AdminComment, ";
            }
            else {
                $tagSearch .= "OR eut.Tag='$t' ";
            }

        }
        $tagSearch .= ') ';

    } 

    $sqlquery = "SELECT DISTINCT em.AccessionNumber as `Accession`,
    $caseInfo em.ExamCode as `Exam Code`, ecd.Description,  ecd.Section,ecd.Type, $vote aid.LastName
    AS Attending, CompletedDTTM as `Completed` FROM `ExamMeta` as em
    INNER JOIN `Examcodedefinition` as ecd ON (em.ExamCode = ecd.ExamCode AND ecd.ORG = em.Organization) 
	INNER JOIN `AttendingIDDefinition` as aid ON (em.AttendingID = aid.AttendingID) $tagPhrase 
	WHERE 1 $tagSearch";

    if (!isset($tags) || sizeof($tags) == 0)  {
        $sqlquery .= "AND `CompletedDTTM` >= '$startDate' AND `CompletedDTTM` < '$endDate' ";
        $sqlquery .= " AND em.TraineeID=" . $_SESSION['traineeid'];
    }

    if ($section != "") {
        $sqlquery = $sqlquery . " AND ecd.Section LIKE '$section'";
    }
    if ($type != "") {
        $sqlquery = $sqlquery . " AND ecd.Type LIKE '$type'";
    }
    if ($notes != "") {
        $sqlquery = $sqlquery . " AND ecd.Notes LIKE '$notes'";
    }
//    $sqlquery .= " ORDER BY CompletedDTTM DESC ";
    if ($vote > '')  {
        $sqlquery .= " ORDER BY Votes DESC, em.CompletedDTTM DESC ";
    } else  {
        $sqlquery .= " ORDER BY em.CompletedDTTM DESC ";
    }
    if ($maxLimit) $sqlquery .= " limit 0, 200";
    $results = $resdbConn->query($sqlquery) or die (mysqli_error($resdbConn));
    return $results;
}

function getResultsCommaDelimited($results)  {
    $first = True;
    $output = "";
    while ($row = $results->fetch_array(MYSQL_ASSOC))  {
        if ($first)  {
            $first = False;
            $headers = array_keys($row);
            foreach ($headers as $h)  {
                $output .= "$h,";
            }
            $output .= "\n";
        }

        foreach($row as $col) {
            if (is_a($col, "DateTime")){
                $col = $col->format('Y-m-d H:i:s');
            }
            $output .= $col;
            $output .= ",";

        }
        $output .= "\n";
    }
    return $output;
}
$resultsTable = 0;
function getResultsHTML($results)  {
    global $URL_root, $resultsTable;
    $output = "<table id='resultsTable$resultsTable' class='results'>\n";
	$resultsTable += 1;
    // Header
    
    $first = True;

    while ($row = $results->fetch_array(MYSQL_ASSOC))  {
        if ($first)  {
            $first = False;
            $headers = array_keys($row);
            $output .= "<thead><tr>";
            foreach ($headers as $h)  {
				$fltrstr = '';
				if ($h == 'Res/Fel' || $h == 'Section' || $h == 'Type' || $h == 'Modality' || $h == 'ED/Inpt' || $h == 'Discrepancy' || $h == 'Trainee' || $h == 'Tag')  {
					$fltrstr = "class='filter-select dropdownFilter'";
				}
                $output .= "<th data-placeholder='Filter' align=left $fltrstr ><strong>$h</strong></th>";
            }
            $output .= "</tr></thead>\n<tbody>\n";
        }
        $output .= "<tr>";
        foreach($row as $k=>$col) {
            /************************
            UPHS specific - remove if needed.
            *************************/
            if (is_a($col, "DateTime")){
                $col = $col->format('Y-m-d H:i:s');
            }

            if ($k == "Accession")  {
                $output .= "<td id='" . $col . "'>";
                $output .= "<a href='javascript:void(0)' onClick='var win = window.open(\"$URL_root/displayReport.php?acc=" . $col . "\", \"rep\", \"scrollbars=yes, toolbar=no, status=no, menubar=no, width=1000, height=768\"); win.focus();'>$col</a>";
            }
            else $output .= "<td>" . $col;
            /************************/
            //$output .= $col;

        }
        $output .= "</tr>\n";
    }
    if ($first)  {
        echo "<tbody><tr><td>No study satisfies the search crtieria. Please check your query (for example, the date range). </tr></tbody>";
    }
    $output .= "\n</tbody>\n</table>";
    return $output;
}


function toJSDate($date)  {
	$date = date_create($date);
	return $date->format("m/d/Y");
}
function checkAdmin()  {
    global $URL_root;
    if (!isAdmin()) header("location:$URL_root");
}
function isResident()  {
    $sql = "SELECT IsResident FROM ResidentIDDefinition WHERE TraineeID=" . $_SESSION['traineeid'];
    $result = getSingleResultArray($sql);

    if ($result[0] == 1) return True;
    else return False;
}

function isAdmin()  {
    if ($_SESSION['traineeid'] < 90000000)  {
        if (!isset($_SESSION['adminid']))  {
            return False;
        } else  {
            //$_SESSION['traineeid'] = $_SESSION['adminid'];
            return True;
        }
    }
    else return True;
}

function getAllAssociatedStudies($accession)  {
    $sql = "SELECT DISTINCT em2.AccessionNumber FROM ExamMeta AS em1 INNER JOIN ExamMeta AS em2 ON em2.PrimaryAccessionNumber=em1.PrimaryAccessionNumber WHERE em1.PrimaryAccessionNumber>'' AND em1.AccessionNumber='$accession'";
    $result = getSingleResultArray($sql);
    if (sizeof($result) >= 1) return $result;
    else  {
        $result []= $accession;
    }
    return $result;
}

function getSingleResultArray($sql, $oneRow=False)  {
    global $resdbConn;
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $returnArray = array();
    if ($oneRow) return $results->fetch_array(MYSQL_ASSOC);
    $count = 0;
    while ($r = $results->fetch_array(MYSQL_NUM))  {
        $returnArray []= $r[0];
        $count++;
        if ($count >= 1000) break;
    }
    return $returnArray;
}

function getResultsFromSQL($sql)  {
    global $resdbConn;
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    return $results;
}

/***************************************
 Discrepancy-related functions
 ***************************************/

//$traineeID can be left blank to get all trainees.
function getTraineeStudiesByDiscrepancy ($startDate, $endDate, $discType, $maxLimit=True, $traineeID="", $other="", $showTrainee=False, $edNotify=False, $program=NULL)  {
    global $resdbConn, $_SESSION, $default_res_program;
    
    if ($program == NULL) {
        if (isset($_SESSION['program']))  {
            $program = $_SESSION['program'];
        }
        else $program = $default_res_program;
    }

    if ($program > NULL)  {
        $prog = " AND aid.Program='$program' ";
    } else $prog = "";
    // The dates are in plain text format.
    if ($showTrainee) $st_str = " CONCAT(aid.LastName, ', ', SUBSTR(aid.FirstName, 1,1)) AS 'Trainee', IF(aid.IsFellow=1,'Fel','Res') as 'Res/Fel', ";
    else $st_str = "CONCAT(em.LastName, ', ', em.FirstName) AS Patient , ";
	$sqlquery = "SELECT ed.AccessionNumber AS 'Accession', $st_str CompositeDiscrepancy as `Discrepancy`, IF(TraineeComment>'',TraineeComment,'N/A') as `Trainee Comment`,IF(AdminComment>'', AdminComment,'N/A') as `Admin Comment`, IF(Location='I', 'Inpt', IF(Location='E', 'ED', IF(Location='O', 'Outpt', 'N/A'))) as `Location`, Section, Type, Description, CompletedDTTM as `Completed` FROM ExamDiscrepancy AS ed INNER JOIN ExamMeta as em ON ed.AccessionNumber=em.PrimaryAccessionNumber INNER JOIN ExamCodeDefinition AS ecd ON ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization INNER JOIN ResidentIDDefinition as aid ON ed.TraineeID=aid.TraineeID $prog WHERE em.CompletedDTTM >= '$startDate' AND em.CompletedDTTM < '$endDate' ";

    if ($discType != "")  {
		$sqlquery .= "AND CompositeDiscrepancy='$discType' ";
    } 

    if ($traineeID != "")  {
        $sqlquery .= " AND em.TraineeID=ed.TraineeID AND ed.TraineeID=" . $traineeID;
    } 

    if ($other != "") $sqlquery .= " " . $other;
	$sqlquery .= "  GROUP BY ed.AccessionNumber ORDER BY CompletedDTTM DESC ";
    if ($maxLimit) $sqlquery .= " limit 0, 1000";    
	
    $results = $resdbConn->query($sqlquery) or die (mysqli_error($resdbConn));
    return $results;
}
function interpretedByTrainee($accession, $traineeID)  {
    $sql = "SELECT AccessionNumber FROM ExamMeta WHERE AccessionNumber='$accession' AND TraineeID=$traineeID;";
    $results = getSingleResultArray($sql);
    if (sizeof($results) > 0) return True;
    else return False;
}
function getDiscrepancyByAccession($acc, $type='CompositeDiscrepancy')  {
    global $resdbConn;
    $sql = "SELECT $type FROM ExamDiscrepancy AS ed INNER JOIN ExamMeta AS em ON em.PrimaryAccessionNumber=ed.AccessionNumber AND em.TraineeID=ed.TraineeID WHERE em.AccessionNumber='$acc'";
	if (isset($_SESSION['traineeid']) && $_SESSION['traineeid'] < 90000000) $sql .= " AND em.TraineeID='" . $_SESSION['traineeid'] . "'";

    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array();
    return $results[0]; // When different exams are associated, this returns multiple identical results; only 1 is necessary. 
}
function saveAdminComment($accession, $discrepancy, $comment, $traineeMark)  {
    global $resdbConn;
    $sql = "UPDATE ExamDiscrepancy AS ed INNER JOIN ExamMeta AS em ON em.PrimaryAccessionNumber=ed.AccessionNumber SET AdminDiscrepancy='$discrepancy', CompositeDiscrepancy=IF(AdminDiscrepancy='',AutoDiscrepancy,AdminDiscrepancy), ";

	$sql .= "AdminComment='$comment',  TraineeMarkAsReviewed=$traineeMark WHERE em.AccessionNumber='$accession';";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    if ($results)  {
        return True;
    } else  {
        return False;
    }
}
function saveTraineeComment($accession, $comment, $traineeMark=NULL)  {
    global $resdbConn;
    $mark = '';
    if (isset($traineeMark)) $mark = ", TraineeMarkAsReviewed=$traineeMark";
    $sql = "UPDATE ExamDiscrepancy AS ed INNER JOIN ExamMeta AS em ON em.PrimaryAccessionNumber=ed.AccessionNumber SET TraineeComment='$comment' $mark WHERE em.AccessionNumber='$accession';";
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    if ($results)  {
        return True;
    } else  {
        return False;
    }
}

function getDiscrepancyCount($discType, $section, $type, $startDate="", $traineeID="")  {
    global $resdbConn;
    $sql = "SELECT * FROM ExamDiscrepancyCounts as edc WHERE 
    FinalDiscrepancy='$discType'
    AND Section='$section'
    AND Type='$type'";
    if ($traineeID != "")  {
        $sql .= " AND TraineeID=" . $traineeID;
    } 
    if ($startDate != "")  {
        $sql .= " AND StartDate>=" . $startDate;
    } 
    $results = $resdbConn->query($sqlquery) or die (mysqli_error($resdbConn));
    return $results;
}

/***************************************
 User tag-related functions
 ***************************************/
function getGoodCaseCount($tagName, $accession)  {
    $sql = "SELECT COUNT(*) FROM ExamUserTags as eut WHERE AccessionNumber='" . $accession . "' AND Tag='$tagName'";
    $count = getSingleResultArray($sql);
    if (isset($count)) return $count[0];
    else return 0;

}
function getUserTags($traineeid, $accession=NULL)  {
    $sql = "SELECT DISTINCT Tag FROM ExamUserTags as eut WHERE eut.TraineeID=$traineeid ";
    $assoc = getAllAssociatedStudies($accession);
    if (isset($accession)) {
        $sql .= " AND (0 ";
        foreach ($assoc as $a)  {
            $sql .= " OR eut.AccessionNumber='$a'";
        }
        $sql .=") ";
    }
    else {
        $sql .= " AND Tag NOT LIKE '#%' ";
    }
    $result = getSingleResultArray($sql);
    return $result;
}
function saveUserTag($traineeid, $accession, $tag)  {
    global $resdbConn;
    $sql = "SELECT PrimaryAccessionNumber FROM ExamMeta WHERE AccessionNumber='$accession';";
    $prAccession = getSingleResultArray($sql);

    if (!$prAccession)  {
        $prAccession []= $accession;
    }

    $sql = "INSERT INTO ExamUserTags (AccessionNumber, TraineeID, Tag) VALUES ('$prAccession[0]', $traineeid, '$tag');";
    $success = $resdbConn->query($sql);
    return $success;
    
}
function removeUserTag($traineeid, $accession, $tag)  {
    global $resdbConn;
    $assoc = getAllAssociatedStudies($accession);
    foreach ($assoc as $a)  {
        $buffer []= "AccessionNumber='$a' ";
    }
    $sql = "DELETE FROM ExamUserTags WHERE TraineeID=$traineeid AND Tag='$tag' AND (". implode(" OR ", $buffer) . ") ";
    $success = $resdbConn->query($sql);
    return $success;
}

function getSharedTags($traineeid=NULL, $accession=NULL)  {
    global $resdbConn;
        $sql = "SELECT DISTINCT Tag FROM ExamUserTags AS eut WHERE ";
    $assoc = getAllAssociatedStudies($accession);

    $sql .= " (Tag LIKE '#%' ";

    if (isset($accession)) {
        $sql .= " AND (0 ";
        foreach ($assoc as $a)  {
            $sql .= " OR eut.AccessionNumber='$a'";
        }
        $sql .=") ";
    }
    
    $sql .= ") ";
    if (isset($traineeid))  {
        $sql .= "AND TraineeID != '$traineeid'";
    }
    $result = getSingleResultArray($sql);
    return $result;
}

function getSystemTags($traineeid, $accession)  {
    global $resdbConn;
    // Add system tags one by one by various methods of detection.
    $tags = array();
    $sql = "select ed.* from `ExamDiscrepancy` as ed INNER JOIN ExamMeta as em ON em.PrimaryAccessionNumber=ed.AccessionNumber AND em.TraineeID=ed.TraineeID WHERE em.AccessionNumber='$accession' AND ed.TraineeID='$traineeid' limit 0,1000";
    
        //    $resdbConn->query($sql);
    $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $results = $results->fetch_array(MYSQL_ASSOC);
    if (!$results) return $tags;
    // EMTRAC
    if ($results['EDNotify'] == 1)  {
        $tags []= "EDNotify";
    }
    // Attending Macro
    if ($results['AutoDiscrepancy'] != 'None' && $results['AutoDiscrepancy'] != 'Attest')  {
        $tags []= $results['AutoDiscrepancy'];
    }
    // Macro Revised
    $rev = $results['AdminDiscrepancy'];
    if ($results['AutoDiscrepancy'] != $rev && $rev != '' && $rev != NULL)  {
        $tags []= "Revised: $rev";
    }
    // Reviewed
    if ($results['TraineeMarkAsReviewed'] == 1)  {
        $tags []= "Reviewed";
    }
    if ($results['TraineeMarkAsReviewed'] >= 2)  {
        $tags []= "Flagged";
    }
    if ($results['TraineeMarkAsReviewed'] >= 3)  {
        $tags []= "Resolved";
    }
    
    return $tags;
}

function getAdminTagsForUser($traineeid)  {
    global $adminTraineeID;
    $sql = "SELECT DISTINCT Tag FROM ExamUserTags as eut INNER JOIN ExamMeta as em ON em.PrimaryAccessionNumber=eut.AccessionNumber WHERE eut.TraineeID=$adminTraineeID AND em.TraineeID=$traineeid";
    return getSingleResultArray($sql);
}


function serializeQueryResult($sql, $filename)  {
	$results = getResultsFromSQL($sql);
	$serial = serialize($results);
	file_put_contents($filename, $serial);
}

function unserializeQueryResult($filename)  {
	$s = file_get_contents($filename);
    $a = unserialize($s);
	return $a;
}

?>


