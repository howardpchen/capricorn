<pre>
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

/*
updateDiscrepancyCounts.php

This is run daily to update the counts of Major/Minor/Addition/etc.
*/

include_once "../capricornLib.php";
$runTimeStart = date_create('NOW');
$startDTTM = thisJulyFirst();
//$startDTTM = date_create('2010-07-01');

$endDTTM = clone $startDTTM;
$endDTTM->add(new DateInterval('P1Y'));


$dateString = $startDTTM->format('Y-m-d');
if (isset($endDTTM)) {
    $endDateString = $endDTTM->format('Y-m-d');
    $extraString = "AND CompletedDTTM<'$endDateString' ";
}
else $extraString = " " ;

$sql = "
SELECT ed.TraineeID, ecd.Type, ecd.Section, ed.CompositeDiscrepancy AS `FinalDiscrepancy`, COUNT(*) AS Count FROM ExamDiscrepancy as ed 
INNER JOIN ExamMeta as em ON ed.AccessionNumber=em.PrimaryAccessionNumber
INNER JOIN ExamCodeDefinition as ecd ON ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization
WHERE CompletedDTTM>'$dateString' 
$extraString 
GROUP BY ed.TraineeID, ecd.Type, ecd.Section, ed.CompositeDiscrepancy
";
$count = 0;

$results = $resdbConn->query($sql) 
    or die (mysqli_error($resdbConn));

while ($row = $results->fetch_array(MYSQL_ASSOC))  {
    $sql = "INSERT INTO ExamDiscrepancyCounts (TraineeID, Type, Section, FinalDiscrepancy, StartDate, Count) VALUES ("
    . $row['TraineeID'] . ", "
    . "'" . $row['Type'] . "', "
    . "'" . $row['Section'] . "', "
    . "'" . $row['FinalDiscrepancy'] . "', "
    . "'$dateString'" . ", "
    . $row['Count'] . ")"
    . " ON DUPLICATE KEY UPDATE Count=" . $row['Count'];
    $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    $count ++;
    if ($count % 1000 == 0) echo "$count categories updated. Total of $results->num_rows expected.  Continuing...\n";
}
echo "Updated discrepancy statistics for the year starting $dateString";

$resdbConn->close();

?>
</pre>
