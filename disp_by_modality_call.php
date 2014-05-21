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

$dispArray = array();       // Holds all the series information, keyed by Type (modality).
$arrangement = array();    // contains all the Table Titles (modality) 

$smn = getExamCodeData('Section, Type', NULL, "ORDER BY TYPE");

// Process $dateArray here
$dateArray = explode('|', $dateArray);
$start = getShiftStart($rotationName);
$duration = getShiftDuration($rotationName);

foreach ($smn as $codeData) {
    $array = array();
    if ($cumulative) {
        $array = getIrregularCumulativeCountArray($codeData[0], $codeData[1], "", $dateArray, $start, $duration);
    } else {
        $array = getIrregularDateCountArray($codeData[0], $codeData[1], "", $dateArray, $start, $duration);
    }

    // Handle Nuclear Medicine differently
    if ($codeData[0] == 'NM') {
        $arrangement[] = $codeData[0];
        $dispArray[$codeData[0]][$codeData[1]] = $array;
    } 
    else if ($codeData[0] == 'MISC' || $codeData[1] == 'MISC') continue;
    else {
        $arrangement[] = $codeData[1];
        $dispArray[$codeData[1]][$codeData[0]] = $array;
    }
}

?>

<script>
<!--
var startDate = '<?php echo $sd ?>'
var endDate = '<?php echo $ed ?>'
var pointInt = <?php echo $dayInt?> * 24 * 3600 * 1000;
//-->
</script>

<?php 
$arrangement = array_unique($arrangement);

// Move babygrams to the end
unset($arrangement[0]);
$arrangement[] = "BABY";

foreach ($arrangement as $mod) {
    if ($cumulative) assembleGraph($mod, 'area', $dispArray[$mod]);
    else assembleGraph($mod, 'column', $dispArray[$mod]);
}

// Empty ones after the non-empty ones
$emptyArray = array();
foreach ($arrangement as $mod) {
    $isEmpty = True;
    foreach ($dispArray[$mod] as $ar) if (array_sum($ar) > 0) $isEmpty = False;
    if ($isEmpty) {
        $emptyArray[] = $mod;
        continue;
    }
    tableStartSection($mod);
    makeDIV($mod, '750px', '400px', $isEmpty);
    tableEndSection();
}

foreach ($emptyArray as $mod) {
    tableStartSection($mod);
    makeDIV($mod, '750px', '400px', True);
    tableEndSection();
}

?>
