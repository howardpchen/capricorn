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

$conn2 = sqlsrv_connect($RISName, $connectionInfo);  // Backup=hourly
if (!$conn2) {
    echo "Could not connect to 24-hour RIS mirror!\n";    
    die(print_r(sqlsrv_errors(), true));
}

$table = "vDxRptContributingResponsible";
$tableRespProvider = "vusrResponsibleProvidersByDxRptID";
$auditTable = "vDiagnosticReportAuditTrail";
$patientLocation = "vInterpretationWithLocation"; // Works well for recent studies.  Use a different table (vsurAuditExam) for older exams.



function print_runTime($start, $prefix="Continuing... Run time so far: ") {
    $runTimeEnd = date_create('NOW');
    $runTime = $start->diff($runTimeEnd);
    $runTime = $runTime->format("%h:%I:%S");
    echo "$prefix $runTime.\n";
    flush_buffers();
    sleep(0.1);
}

while ($count > 0) {
    echo "Starting to update exams from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i') . "\n";

    $sql = "SELECT $table.LastName, $table.FirstName, $table.IsStatExamFlag, $table.IsStatReadingFlag, $table.PatientID, ProviderID,$tableRespProvider.ResponsibleID,$tableRespProvider.LastName as AttnLastName,$tableRespProvider.FirstName as AttnFirstName,ExamCode,$table.AccessionNumber,$table.OrganizationID, Organization, LastUpdateDTTM, LastEditedDTTM, $table.CompletedDTTM, PatientStatusCode FROM $table INNER JOIN $tableRespProvider ON $table.DiagnosticReportID=$tableRespProvider.DiagnosticReportID INNER JOIN $patientLocation ON $table.ActivityHeaderID=$patientLocation.ActivityHeaderID WHERE $table.CompletedDTTM > '" . $startDTTM->format('Y-m-d H:i:s') . "' AND $table.CompletedDTTM < '" . $endDTTM->format('Y-m-d H:i:s') . "'
    AND ProviderID=$tableRespProvider.ResponsibleID ";

    $result = sqlsrv_query($conn2, $sql) or die("Can't find answer in RIS");
    if ( $result ) { 
        //echo "Statement executed.<br>\n"; 
    }     
    else {    
        echo "Error in statement execution.\n";    
        die( print_r( sqlsrv_errors(), true));    
    }    

    $sqlarray = array();
    $studies = array();

    echo "RIS query executed, now processing RIS data for prelim time, ResidentYear, report text, and saving into Capricorn database... \n";
    flush_buffers();
	$count = 0;
    while ($value = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        // This line skips all studies without a trainee interpreter.
        //if ($value['ResponsibleID'] == $value['ProviderID']) continue;
		$count++;	
        $studiesEntry = array();
        $studiesEntry['AttnLastName'] = $value['AttnLastName'];
        $studiesEntry['AttnFirstName'] = $value['AttnFirstName'];
        $studiesEntry['LastName'] = $value['LastName'];
        $studiesEntry['FirstName'] = $value['FirstName'];
        $studiesEntry['PatientID'] = $value['PatientID'];
        $studiesEntry['ExamCode'] = trim($value['ExamCode']);
        $studiesEntry['AccessionNumber'] = $value['AccessionNumber'];
        $studiesEntry['OrganizationID'] = trim($value['OrganizationID']);
        $studiesEntry['Organization'] = $value['Organization'];
        $studiesEntry['CompletedDTTM'] = $value['CompletedDTTM'];
        $studiesEntry['AttendingID'] = $value['ResponsibleID'];
        $studiesEntry['TraineeID'] = $value['ProviderID'];
        $studiesEntry['InternalID'] = $value['AccessionNumber'] . $value['ProviderID'];
        $studiesEntry['Location'] = $value['PatientStatusCode'];

		$statRead = $value['IsStatReadingFlag']=='Y'?True:False;
		$statExam = $value['IsStatExamFlag']=='Y'?True:False;

		$studiesEntry['Urgency'] = '';

		if ($statRead || $statExam) $studiesEntry['Urgency'] .= 'S';
		if ($statExam) $studiesEntry['Urgency'] .= 'E';
		if ($statRead) $studiesEntry['Urgency'] .= 'I';
		if ($studiesEntry['Urgency'] == '') $studiesEntry['Urgency'] = 'R';

        // Calculate Resident Year

        $sql = "SELECT StartDate FROM $resTable WHERE TraineeID=" . $studiesEntry['TraineeID'] . " AND IsResident=1;";
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

        $sql = "SELECT * FROM $auditTable WHERE SecondaryAccessionNumber='" . $studiesEntry['AccessionNumber'] . "' AND (AuditByID=" . $studiesEntry['TraineeID'] . " OR AuditByID=65437979) ORDER BY ChangeDTTM ASC;";
        $dateResult = sqlsrv_query($conn2, $sql);

        $RISarray = array();
        while($row = sqlsrv_fetch_array($dateResult, SQLSRV_FETCH_ASSOC)) {
            $RISarray[]=$row;
        }

        $studiesEntry['PreliminaryReportText'] = "NOTE: No preliminary report.";    // Default.  Update if found prelim report.

        foreach ($RISarray as $d)  {
            if ($d['AssociatedAccessionNumber'] != '' && $d['AssociatedAccessionNumber'] != NULL) {
                $studiesEntry['PrimaryAccessionNumber'] = $d['AssociatedAccessionNumber'];
            }

            if ($prelimdate == NULL && trim($d['AuditEventType']) == 'TRANSCRIBE') {
   				// This prelim date is not reliable, so have to perform another SQL query for the real prelim DTTM

				$prelimSql = "SELECT * FROM vExamStatusInfo WHERE ActivityHeaderID=" . $d['ActivityHeaderID'] . " AND ActivityStatusCode='P'";
				$prelimResult = sqlsrv_query($conn2, $prelimSql);
				$prelimRow = sqlsrv_fetch_array($prelimResult, SQLSRV_FETCH_ASSOC);
				$prelimdate = $prelimRow['RecordedDTTM'];
                $studiesEntry['PrelimDTTM'] = $prelimdate->format('Y-m-d H:i:s');
				
                // If this is not the primary accession number, then get the primary accession number for ExamMeta table, but don't bother getting the report because report will be obtained when processing the primary accession.
                if ($d['AssociatedAccessionNumber'] == '' || $d['AssociatedAccessionNumber'] == NULL) {
                    // Get the report text associated with this prelim event.
                    $rptSql = "SELECT ReportText FROM vAllDiagnosticReportText WHERE DiagnosticReportID=" . $d['DiagnosticReportID'];
                    $rptResult = sqlsrv_query($conn2, $rptSql);
                    $rptRow = sqlsrv_fetch_array($rptResult, SQLSRV_FETCH_ASSOC);
                    $studiesEntry['PreliminaryReportText'] = $rptRow['ReportText'];
                }
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
        if (!isset($studiesEntry['PrimaryAccessionNumber'])) $studiesEntry['PrimaryAccessionNumber'] = $studiesEntry['AccessionNumber'];

        /* 
           Get Final Report text.  It can be difficult to figure out what "Finalize" means on RIS audit, because 
           even after attending signs report, billers can still audit and update the RIS data with charge data.
           But since the finalized report text cannot change by law, we can just check the general report database.
         */
        $finalRptsql = "SELECT ReportStatusCode, ReportText FROM vusrExamDiagnosticReportText WHERE AccessionNumber='" . $studiesEntry['PrimaryAccessionNumber'] . "'";
        $reportResult = sqlsrv_query($conn2, $finalRptsql);
        if ($reportResult) {
            $finalReportRow = sqlsrv_fetch_array($reportResult, SQLSRV_FETCH_ASSOC);
            if (trim($finalReportRow['ReportStatusCode']) == 'F') $studiesEntry['FinalReportText'] = $finalReportRow['ReportText'];            
            else $studiesEntry['FinalReportText'] = "NOTE: No final report available yet.";
        }
        else  {
            $studiesEntry['FinalReportText'] = "NOTE: No final report available yet.";
        }

        $s = $studiesEntry;

        // Then insert exammeta into Capricorn.
        $sql = "REPLACE INTO $examTable (InternalID, LastName, FirstName, PatientID, Location, TraineeID, OrganizationID, Organization, CompletedDTTM, InquiryDTTM, DraftDTTM, PrelimDTTM, AttendingID, Urgency, AccessionNumber, PrimaryAccessionNumber, ExamCode, ResidentYear) VALUES (\"" . $s['InternalID'] . "\", \"" . $s['LastName'] . "\", \"" . $s['FirstName'] . "\", " . $s['PatientID'] . ", '" . $s['Location'] . "', " . $s['TraineeID'] . ", " . $s['OrganizationID'] . ", '" . $s['Organization'] . "', '" . $s['CompletedDTTM']->format('Y-m-d H:i:s') . "', '" . $s['InquiryDTTM'] . "', '" . $s['DraftDTTM'] . "', '" . $s['PrelimDTTM'] . "', " . $s['AttendingID'] . ", \"" . $s['Urgency'] . "\", \"" . $s['AccessionNumber'] . "\", \"" . $s['PrimaryAccessionNumber'] . "\", \"" . $s['ExamCode'] . "\", " . $s['ResidentYear'] . ");";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));

        $sql = "REPLACE INTO AttendingIDDefinition (AttendingID, LastName, FirstName) VALUES (" . $s['AttendingID'] . ", \"" . $s['AttnLastName'] . "\", \"" . $s['AttnFirstName']. "\");";

        $resdbConn->query($sql) or die (mysqli_error($resdbConn));

        // Then save the report text into Capricorn
        if ($s['AccessionNumber'] == $s['PrimaryAccessionNumber']) {
            $sql = "REPLACE INTO $examTextTable (AccessionNumber, PreliminaryReportText, FinalReportText) VALUES (\"" . $s['PrimaryAccessionNumber'] . "\", \"" . mysql_real_escape_string($s['PreliminaryReportText']) . "\", \"" . mysql_real_escape_string($s['FinalReportText']) . "\");";
            $resdbConn->query($sql) or die (mysqli_error($resdbConn));

            // Also, determine discrepancy.
            $autoDiscrepancy = 'None';
            foreach ($discrepancyString as $discType=>$match)  {
                if (preg_match($match, $s['FinalReportText']))  {
                    $autoDiscrepancy = $discType;
                    break;
                }
            }

            $emtrac = isEmtracDone($conn2, $s['PrimaryAccessionNumber'])?1:0;

            $sql = "INSERT INTO ExamDiscrepancy (AccessionNumber, TraineeID, AutoDiscrepancy, CompositeDiscrepancy,EDNotify) VALUES (" . $s['PrimaryAccessionNumber'] . ", " . $s['TraineeID'] . ", \"" . $autoDiscrepancy . "\", \"" . $autoDiscrepancy . "\", $emtrac) ON DUPLICATE KEY UPDATE AutoDiscrepancy='$autoDiscrepancy', CompositeDiscrepancy=IF(AdminDiscrepancy!='',AdminDiscrepancy,'$autoDiscrepancy'), EDNotify=$emtrac;";
            $resdbConn->query($sql) or die (mysqli_error($resdbConn));
//            $sql = "UPDATE ExamDiscrepancy SET AutoDiscrepancy='$autoDiscrepancy', CompositeDiscrepancy=IF(AdminDiscrepancy!='',AdminDiscrepancy,'$autoDiscrepancy'), EDNotify=$emtrac WHERE AccessionNumber=" . $s['PrimaryAccessionNumber'];
//            $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        }
    }
	
	$sql = "UPDATE ExamMeta SET PrelimTAT=TIMESTAMPDIFF(SECOND,CompletedDTTM, PrelimDTTM) WHERE 
		CompletedDTTM >= '". $startDTTM->format('Y-m-d H:i') . "' AND 
		CompletedDTTM <= '" . $endDTTM->format('Y-m-d H:i') . "'";
	$resdbConn->query($sql) or die (mysqli_error($resdbConn));
	echo "$count studies processed.";
    echo "Updated studies performed from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i') . "\n";
    //writeLog("Updated studies performed from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i'));
    echo "--------------------------------------------------------\n";

    $startDTTM->sub($interval);
    $endDTTM->sub($interval);
    $count--;
    flush_buffers();
    print_runTime($runTimeStart);
}

$resdbConn->close();
sqlsrv_close($conn2);

$runTimeEnd = date_create('NOW');
$runTime = $runTimeStart->diff($runTimeEnd);
$runTime = $runTime->format("%h:%i:%s");
echo "All done. Total run time $runTime";
writeLog("Update Complete.  Run time $runTime");
?>

