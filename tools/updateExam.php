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
updateExamp.php

Updates Capricorn database using new RIS data.
You will have to write this script for your own institution.
The desired behavior is as follows:
1) Takes all new RIS studies and insert into Capricorn.
2) Update existing studies up to 7 days prior - as overnight studies 
    sometimes will not have a "Finalized" read time or correct 
    attending name depending on who reads out the next morning.
3) Prevents duplication of study entries - this is currently done by 
    creating an InternalID that will stay the same for each study regardless  
    of the attending or study time.
*/

include "../capricornLib.php";

$runTimeStart = date_create('NOW');
$endDTTM = date_create('NOW');
$interval = new DateInterval("P4D");
$startDTTM = date_create('NOW');
$startDTTM->sub($interval);

$count = 0;

$resTable = "ResidentIDDefinition";     // MySQL table name
$examTable = "ExamMeta";

$resdbConn = new mysqli($mysql_host, $mysql_username, $mysql_passwd, $mysql_database);
if (mysqli_connect_errno($resdbConn)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
$maxDonedate = date_create('NOW');
if ($result = $resdbConn->query("SELECT MAX(CompletedDTTM) as CompletedDTTM FROM $examTable;")) {
    $result = $result->fetch_array();
    $maxDoneDate = date_create($result['CompletedDTTM']);
} else {
    echo "Error loading trainee database.";
    exit();
}

// Comment this WHILE statement out if need to do custom-scale updates.
while ($maxDoneDate < $endDTTM) {
    $count++;
    $maxDoneDate->add($interval);
}


$conn2 = sqlsrv_connect($RISName, $connectionInfo);
if (!$conn2) {
    echo "Could not connect to 24-hour RIS mirror!\n";    
    die(print_r(sqlsrv_errors(), true));
}

$table = "vDxRptContributingResponsible";
$tableRespProvider = "vusrResponsibleProvidersByDxRptID";
$auditTable = "vDiagnosticReportAuditTrail";

while ($count > 0) {
    $sql = "SELECT $table.LastName, $table.FirstName, $table.PatientID, ProviderID,$tableRespProvider.ResponsibleID,$tableRespProvider.LastName as AttnLastName,$tableRespProvider.FirstName as AttnFirstName,ExamCode,AccessionNumber,OrganizationID, Organization, LastUpdateDTTM, LastEditedDTTM, CompletedDTTM FROM $table INNER JOIN $tableRespProvider ON $table.DiagnosticReportID=$tableRespProvider.DiagnosticReportID WHERE $table.CompletedDTTM > '" . $startDTTM->format('Y-m-d H:i:s') . "' AND $table.CompletedDTTM < '" . $endDTTM->format('Y-m-d H:i:s') . "';";

    $result = sqlsrv_query($conn2, $sql); /** or die("Can't find answer in RIS"); **/
    if ( $result ) { 
        //echo "Statement executed.<br>\n"; 
    }     
    else {    
         echo "Error in statement execution.\n";    
         die( print_r( sqlsrv_errors(), true));    
    }    

    $sqlarray = array();
    $studies = array();
    while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $sqlarray[]=$row;
    }

    foreach ($sqlarray as $value) {
        // Right now will skip all studies that do not have a trainee contributer.
        if ($value['ResponsibleID'] == $value['ProviderID']) continue;
        
        $studiesEntry = array();
        $studiesEntry['AttnLastName'] = $value['AttnLastName'];
        $studiesEntry['AttnFirstName'] = $value['AttnFirstName'];
        $studiesEntry['LastName'] = $value['LastName'];
        $studiesEntry['FirstName'] = $value['FirstName'];
        $studiesEntry['PatientID'] = $value['PatientID'];
        $studiesEntry['ExamCode'] = $value['ExamCode'];
        $studiesEntry['AccessionNumber'] = $value['AccessionNumber'];
        $studiesEntry['OrganizationID'] = $value['OrganizationID'];
        $studiesEntry['Organization'] = $value['Organization'];
        $studiesEntry['CompletedDTTM'] = $value['CompletedDTTM'];
        $studiesEntry['AttendingID'] = $value['ResponsibleID'];
        $studiesEntry['TraineeID'] = $value['ProviderID'];
        $studiesEntry['InternalID'] = $value['AccessionNumber'] . $value['ProviderID'];


        // Calculate Resident Year

        $sql = "SELECT StartDate FROM $resTable WHERE TraineeID=" . $studiesEntry['TraineeID'] . ";";
        $dateResult = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        $date = $dateResult->fetch_array();
        if ($date != NULL) {
            $resYear = $studiesEntry['CompletedDTTM']->diff(date_create($date[0]));
            $studiesEntry['ResidentYear'] = $resYear->y + 1;
        } else {
            $studiesEntry['ResidentYear'] = 99;
        }

        // Calculate Inquiry, Draft and Prelim Times
        $studiesEntry['InquiryDTTM'] = $inquirydate = NULL;
        $studiesEntry['DraftDTTM'] = $draftdate = NULL;
        $studiesEntry['PrelimDTTM'] = $prelimdate = NULL;
        
        $sql = "SELECT AuditEventType, ResultStatusCode, ChangeDTTM FROM $auditTable WHERE SecondaryAccessionNumber='" . $studiesEntry['AccessionNumber'] . "' AND (AuditByID=" . $studiesEntry['TraineeID'] . " OR AuditByID=65437979) ORDER BY ChangeDTTM ASC;";
        $dateResult = sqlsrv_query($conn2, $sql);

        $RISarray = array();
        while($row = sqlsrv_fetch_array($dateResult, SQLSRV_FETCH_ASSOC)) {
            $RISarray[]=$row;
        }

        foreach ($RISarray as $d)  {
            if ($prelimdate == NULL && trim($d['AuditEventType']) == 'TRANSCRIBE') {
                $prelimdate = $d['ChangeDTTM'];
                $studiesEntry['PrelimDTTM'] = $prelimdate->format('Y-m-d H:i:s');
            } 
            else if ($draftdate == NULL && trim($d['AuditEventType']) == 'DICTATE' || trim($d['AuditEventType']) == 'TEXTEDITED ') {
                $draftdate = $d['ChangeDTTM'];
                $studiesEntry['DraftDTTM'] = $draftdate->format('Y-m-d H:i:s');
            }
            else if (trim($d['AuditEventType']) == 'INQUIRY')  {
                $inquirydate = $d['ChangeDTTM'];
                $studiesEntry['InquiryDTTM'] = $inquirydate->format('Y-m-d H:i:s');
            }
        }
        $studies[] = $studiesEntry;        
    }

    foreach ($studies as $s) {
        $sql = "REPLACE INTO $examTable (InternalID, LastName, FirstName, PatientID, TraineeID, OrganizationID, Organization, CompletedDTTM, InquiryDTTM, DraftDTTM, PrelimDTTM, AttendingID, AccessionNumber, ExamCode, ResidentYear) VALUES (\"" . $s['InternalID'] . "\", \"" . $s['LastName'] . "\", \"" . $s['FirstName'] . "\", " . $s['PatientID'] . ", " . $s['TraineeID'] . ", " . $s['OrganizationID'] . ", '" . $s['Organization'] . "', '" . $s['CompletedDTTM']->format('Y-m-d H:i:s') . "', '" . $s['InquiryDTTM'] . "', '" . $s['DraftDTTM'] . "', '" . $s['PrelimDTTM'] . "', " . $s['AttendingID'] . ", \"" . $s['AccessionNumber'] . "\", \"" . $s['ExamCode'] . "\", " . $s['ResidentYear'] . ");";
        //print_r($sql);
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));

        $sql = "REPLACE INTO AttendingIDDefinition (AttendingID, LastName, FirstName) VALUES (" . $s['AttendingID'] . ", \"" . $s['AttnLastName'] . "\", \"" . $s['AttnFirstName']. "\");";

        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
    }


    echo "Updated studies performed from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i') . "<br>\n";

    $startDTTM->sub($interval);
    $endDTTM->sub($interval);
    $count--;
    flush_buffers();
    sleep(1);
}

$resdbConn->close();
sqlsrv_close($conn2);

$runTimeEnd = date_create('NOW');
$runTime = $runTimeStart->diff($runTimeEnd);
$runTime = $runTime->format("%h:%i:%s");
echo "All done. Run time $runTime";

?>

