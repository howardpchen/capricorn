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
include_once "capricornLib.php";
$mod = $_GET['mod'];

$dispArray = array();       // Holds all the series information, keyed by Type (modality).
$arrangement = array();    // contains all the Table Titles (modality) 
if ($mod != 'NM') {
    $filter = array(
        "Type" => $mod
    );
} else  {
    $filter = array(
        "Section" => $mod
    );
}

$smn = getExamCodeData("Section, Type", $filter, "ORDER BY TYPE");

foreach ($smn as $codeData) {
    $array = array();
    if ($cumulative) {
        $array = getCumulativeCountArray($codeData[0], $codeData[1], "", $sd, $ed, $intvl);
    } else {
        $array = getCountArray($codeData[0], $codeData[1], "", $sd, $ed, $intvl);
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
if ($arrangement[0] == "BABY") {
    unset($arrangement[0]);
    $arrangement[] = "BABY";
}

foreach ($arrangement as $moda) {
    if ($cumulative) assembleGraph($moda, 'area', $dispArray[$moda]);
    else assembleGraph($moda, 'column', $dispArray[$moda]);
}

// Empty ones after the non-empty ones
$emptyArray = array();
$first = True;

foreach ($arrangement as $moda) {
    makeDIV($moda, '850px', '400px', false);
}

?>


