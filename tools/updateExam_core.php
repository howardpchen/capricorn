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
updateExam.php

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
    echo "Could not connect to PSSource mirror! $RISName\n";    
    die(print_r(sqlsrv_errors(), true));
}

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

    $sql = "
    SELECT Patient.LastName AS PatientLastName, Patient.FirstName as
    PatientFirstName, vReport.ContentText AS ContentText, Site.SiteID AS
    SiteID, Site.Name as SiteName, 
    ord.DictatorAcctID AS TraineeID,
    vVisit.PointofCare as PointofCare, pc.Abbrev AS Location, (SELECT MIN(Accession) FROM vOrder
    as vo WHERE vo.ReportID=ord.ReportID) as PrimaryAccession, Communevent.Recipient AS EDNotify, ord.* FROM
    vOrder AS ord 
    INNER JOIN vReport ON ord.ReportID=vReport.ReportID 
    INNER JOIN Patient ON ord.PatientID=Patient.PatientID 
    INNER JOIN Site ON ord.SiteID=Site.SiteID
    INNER JOIN vVisit ON ord.VisitID=vVisit.VisitID
    INNER JOIN PatientClass AS pc ON pc.PatientClassID=ord.PatientClassID
    LEFT JOIN Communevent ON ord.ReportID=Communevent.ReportID
    WHERE 
    ord.SignerAcctID > '' AND 
    OrderDate >= '" .
    $startDTTM->format('Y-m-d H:i:s') . "' AND OrderDate <= '" .
    $endDTTM->format('Y-m-d H:i:s') . "'

    UNION ALL

    SELECT Patient.LastName AS PatientLastName, Patient.FirstName as
    PatientFirstName, vReport.ContentText AS ContentText, Site.SiteID AS
    SiteID, Site.Name as SiteName, vReport.CreatorAcctID as TraineeID,
    vVisit.PointofCare as PointofCare, pc.Abbrev AS Location, (SELECT MIN(Accession) FROM vOrder
    as vo WHERE vo.ReportID=ord.ReportID) as PrimaryAccession, Communevent.Recipient AS EDNotify, ord.* FROM
    vOrder AS ord 
    INNER JOIN vReport ON ord.ReportID=vReport.ReportID 
    INNER JOIN Patient ON ord.PatientID=Patient.PatientID 
    INNER JOIN Site ON ord.SiteID=Site.SiteID
    INNER JOIN vVisit ON ord.VisitID=vVisit.VisitID
    INNER JOIN PatientClass AS pc ON pc.PatientClassID=ord.PatientClassID
    LEFT JOIN Communevent ON ord.ReportID=Communevent.ReportID
    WHERE 
    ord.SignerAcctID > '' AND 
    vReport.CreatorAcctID != ord.DictatorAcctID AND
    vReport.CreatorAcctID > '' AND
    OrderDate >= '" .
    $startDTTM->format('Y-m-d H:i:s') . "' AND OrderDate <= '" .
    $endDTTM->format('Y-m-d H:i:s') . "'
    ";
    // Union All used because both CreatorAcctID and DictatorAcctId map to the same TraineeID field in Capricorn.

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

    echo "Source DB query executed, now processing and saving source data for Capricorn database... \n";
    flush_buffers();

    while ($value = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        // This line skips all studies without a trainee interpreter.
        //if ($value['ResponsibleID'] == $value['ProviderID']) continue;

        $studiesEntry = array();
        $studiesEntry['AttnLastName'] = $value['SignerLastName'];
        $studiesEntry['AttnFirstName'] = $value['SignerFirstName'];
        $studiesEntry['LastName'] = $value['PatientLastName'];
        $studiesEntry['FirstName'] = $value['PatientFirstName'];
        $studiesEntry['PatientID'] = $value['PatientID'];
        $studiesEntry['ExamCode'] = trim($value['Procedures']);
        $studiesEntry['AccessionNumber'] = $value['Accession'];
        $studiesEntry['PrimaryAccessionNumber'] = $value['PrimaryAccession'];
        $studiesEntry['EDNotify'] = $value['EDNotify'];
        $studiesEntry['OrganizationID'] = trim($value['SiteID']);
        $studiesEntry['Organization'] = $value['SiteName'];
        $studiesEntry['CompletedDTTM'] = $value['OrderDate'];
        $studiesEntry['AttendingID'] = $value['SignerAcctID'];
        $studiesEntry['TraineeID'] = $value['TraineeID'];
        $studiesEntry['Location'] = $value['Location'];
        if ($studiesEntry['TraineeID'] == '') {
            $studiesEntry['TraineeID'] = $studiesEntry['AttendingID'];
        }
        $studiesEntry['InternalID'] = $studiesEntry['AccessionNumber'] . $studiesEntry['TraineeID'];
        $studiesEntry['LocationCode'] = $value['PointofCare'];

        $studiesEntry['Urgency'] = 'NA';

        // Calculate Resident Year
        $sql = "SELECT StartDate FROM $resTable WHERE TraineeID='" .
            $studiesEntry['TraineeID'] . "' AND IsResident=1;";
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


        /* 
        Get Final Report text.  It can be difficult to figure out what
        "Finalize" means on RIS audit, because even after attending signs
        report, billers can still audit and update the RIS data with charge
        data.  But since the finalized report text cannot change by law, we
        can just check the general report database.
        */

        $studiesEntry['FinalReportText'] = $value['ContentText'];

        $s = $studiesEntry;

        // Then insert exammeta into Capricorn.

        $sql = "REPLACE INTO $examTable (InternalID, LastName, FirstName,
            PatientID, Location, LocationCode, TraineeID, OrganizationID, Organization,
            CompletedDTTM, InquiryDTTM, DraftDTTM, PrelimDTTM, AttendingID,
            Urgency, AccessionNumber, PrimaryAccessionNumber, ExamCode,
            ResidentYear) VALUES (\"" . $s['InternalID'] . "\", \"" .
            $s['LastName'] . "\", \"" . $s['FirstName'] . "\", " .
            $s['PatientID'] . ", '" . $s['Location'] . "', '" .
            $s['LocationCode'] . "', '" . $s['TraineeID']
            . "', " . $s['OrganizationID'] . ", '" . $s['Organization'] . "', '"
            . $s['CompletedDTTM']->format('Y-m-d H:i:s') . "', '" .
            $s['InquiryDTTM'] . "', '" . $s['DraftDTTM'] . "', '" .
            $s['PrelimDTTM'] . "', '" . $s['AttendingID'] . "', \"" .
            $s['Urgency'] . "\", \"" . $s['AccessionNumber'] . "\", \"" .
            $s['PrimaryAccessionNumber'] . "\", \"" . $s['ExamCode'] . "\", " .
            $s['ResidentYear'] . ");";
        $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        if ($s['AttendingID'] != '') {
            $sql = "REPLACE INTO AttendingIDDefinition (AttendingID, LastName, FirstName) VALUES (" . $s['AttendingID'] . ", \"" . $s['AttnLastName'] . "\", \"" . $s['AttnFirstName']. "\");";
            $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        }

        // Then save the report text into Capricorn
        if ($s['AccessionNumber'] == $s['PrimaryAccessionNumber']) {
            $sql = "REPLACE INTO $examTextTable (AccessionNumber, FinalReportText) VALUES (\"" . $s['PrimaryAccessionNumber'] . "\", \"" . mysql_real_escape_string($s['FinalReportText']) . "\");";
            $resdbConn->query($sql) or die (mysqli_error($resdbConn));

            // Also, determine discrepancy.
            $autoDiscrepancy = 'None';
            foreach ($discrepancyString as $discType=>$match)  {
                if (preg_match($match, $s['FinalReportText']))  {
                    $autoDiscrepancy = $discType;
                    break;
                }
            }

//            $emtrac = isEmtracDone($conn2, $s['PrimaryAccessionNumber'])?1:0;
            $edNotify = ($value['EDNotify'] > ''?1:0);
            $sql = "INSERT INTO ExamDiscrepancy (AccessionNumber,
            TraineeID, AutoDiscrepancy, CompositeDiscrepancy, EDNotify)
            VALUES ('" . $s['PrimaryAccessionNumber'] . "', " .
            $s['TraineeID'] . ", '" . $autoDiscrepancy . "', '" .
            $autoDiscrepancy . "', $edNotify) ON DUPLICATE KEY UPDATE
            AutoDiscrepancy='$autoDiscrepancy',
            CompositeDiscrepancy=IF(AdminDiscrepancy!='',AdminDiscrepancy,'$autoDiscrepancy'), EDNotify=$edNotify;";
//            print_r($sql);
			$resdbConn->query($sql) or die (mysqli_error($resdbConn));
//            $sql = "UPDATE ExamDiscrepancy SET AutoDiscrepancy='$autoDiscrepancy', CompositeDiscrepancy=IF(AdminDiscrepancy!='',AdminDiscrepancy,'$autoDiscrepancy'), EDNotify=$emtrac WHERE AccessionNumber=" . $s['PrimaryAccessionNumber'];
//            $resdbConn->query($sql) or die (mysqli_error($resdbConn));
        }
    }
    /* 
       TEMPORARILY DISABLE THE PRELIM TIME - WE DON'T HAVE AN ACCURATE WAY OF
       ACCOUNTING FOR THIS - 2015-05-03
	$sql = "UPDATE ExamMeta SET PrelimTAT=TIMESTAMPDIFF(SECOND,CompletedDTTM, PrelimDTTM) WHERE 
		CompletedDTTM >= '". $startDTTM->format('Y-m-d H:i') . "' AND 
		CompletedDTTM <= '" . $endDTTM->format('Y-m-d H:i') . "'";
	$resdbConn->query($sql) or die (mysqli_error($resdbConn));
    */

    echo "Updated studies performed from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i') . "\n";
    writeLog("Updated studies performed from " . $startDTTM->format('Y-m-d H:i') . " to " . $endDTTM->format('Y-m-d H:i'));
    echo "--------------------------------------------------------\n";

    $startDTTM->sub($interval);
    $endDTTM->sub($interval);
    $count--;
    flush_buffers();
    print_runTime($runTimeStart);
}

#$resdbConn->close();
sqlsrv_close($conn2);

$runTimeEnd = date_create('NOW');
$runTime = $runTimeStart->diff($runTimeEnd);
$runTime = $runTime->format("%h:%i:%s");
echo "All done. Total run time $runTime";
writeLog("Update Complete.  Run time $runTime");
?>

