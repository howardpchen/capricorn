<?php
include "../capricornLib.php";
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


/******************************************
Run this once a year to update the ResidentYear counts (residentcounts)
Capricorn will calculate in real time counts since the most recent July 1, 
And use this table for previous years' counts.

Also run this script when the examcodedefinition changes

If $startFromYear and $endAtYear is set too aggressively, the script 
may run for longer than your server allows and time out.  In those 
cases dividing up the years helps.

*******************************************/

$startFromYear = 2014;
$endAtYear = 2015;

$runTimeStart = date_create('NOW');

$smn = getExamCodeData('Section, Type, Notes');

foreach ($smn as $codeData) {
    $section = $codeData[0];
    $type = $codeData[1];
    $notes = $codeData[2];

    $today = date_create('Now');
    $year = intval($today->format('Y'));
    $thisJulyFirst = date_create($year . "-07-01");
    if ($thisJulyFirst > $today) $thisJulyFirst->sub(new DateInterval("P1Y"));
    //$endYear = intval($thisJulyFirst->format("Y"));
    $endYear = $endAtYear;
   
    $startYear = $startFromYear;
    while ($startYear < $endYear) {
        $returnArray = array();
        $startDate = $startYear . "-07-01";
        $startYear++;
        $endDate = $startYear . "-07-01";
        $sql = "SELECT em.InternalID,TraineeID,ResidentYear FROM exammeta as em INNER JOIN examcodedefinition as ecd on em.ExamCode=ecd.ExamCode AND em.Organization=ecd.ORG WHERE ecd.Type='$type' AND ecd.Section='$section' AND ecd.Notes='$notes' AND CompletedDTTM > '$startDate' AND CompletedDTTM < '$endDate'";

        $results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        $results = $results->fetch_all(MYSQL_ASSOC);
        foreach ($results as $r)  {
            $resY = $r['ResidentYear'];
            $tID = $r['TraineeID'];
            if (isset($returnArray[$resY][$tID])) {
                $returnArray[$resY][$tID]++;
            }
            else {
                $returnArray[$resY][$tID] = 1;
            }
        }


        foreach ($returnArray as $ry=>$r) {
            if ($ry == 99) continue;
            foreach ($r as $trID=>$cnt) {
                $uid = hash('md5', $trID . $ry . $startDate . $section . $type . $notes);
                $sql = "REPLACE INTO residentcounts (UniqueID, TraineeID, ResidentYear, CountDT, Section, Type, Notes, Count) VALUES ('$uid', $trID, $ry, '$startDate','$section', '$type', '$notes', $cnt)";
                echo "<!-- $sql -->\n";
                $resdbConn->query($sql) or die (mysqli_error($resdbConn));
            }
        }
        $runTimeEnd = date_create('NOW');
        $runTime = $runTimeStart->diff($runTimeEnd);
        $runTime = $runTime->format("%h:%i:%s");
        echo "Finished $section $type $notes $startDate.  So far run time is $runTime.\n<BR>";
        flush_buffers();
        
    }
}
