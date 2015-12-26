<pre>
<?php
header( 'Content-type: text/html; charset=utf-8' );

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

/*
updateExam_custom.php
*/

include "../../capricornLib.php";

$runTimeStart = date_create('NOW');
$endDTTM = date_create('NOW');

// Uncomment this if you want to start updating from a specific date in the format of "YYYY-MM-DD H:MM"
$endDTTM = date_create("2015-1-23"); 

$interval = new DateInterval("P1D");  // Set interval to 1 day.
$startDTTM = clone $endDTTM;
$startDTTM->sub($interval);

$count = 2;     //Number of days.

$resTable = "ResidentIDDefinition";     // MySQL table names
$examTable = "ExamMeta";
$examTextTable = "ExamReportText";
#$RISName = $RISNameBackup;
include "updateExam_core_attnstudies.php";

?>
</pre>

