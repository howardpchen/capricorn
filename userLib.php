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
userLib.php

Individual institutional level customized functions.

Here houses the functions that are generally institutionally specific 
and are not guarenteed to work at other places.

For instance, at our institution trainees read studies and are overread by
attendings who assign Major and Minor changes or Additions to the report.
Attendings can also assign Great Call to encourage trainee for job well
done.  

As this clinical system is obviously not in place everywhere, the relevant
functions are separately stored here.

*/


function getAllED($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, $traineeID, " AND EDNotify=1", True);
}

function getMajorChangeAll($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", True, $traineeID, '', True);
}
function getGreatCallAll($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "GreatCall", True, $traineeID, ' AND ecd.Section!="BREAST" AND ecd.Section!="NM" ', True);
}

function getAllEDUnreviewed($startDate, $endDate)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, "", " AND EDNotify=1 AND AdminDiscrepancy=''");
}

function getMajorChangeAllUnreviewed($startDate, $endDate)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", True, "", " AND AdminDiscrepancy=''");
}

function getFlagged($startDate, $endDate)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, "", " AND TraineeMarkAsReviewed=2");
}

function getTraineeFlagged($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, $traineeID, " AND (TraineeMarkAsReviewed=2)");
}
function getTraineeResolved($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, $traineeID, " AND (TraineeMarkAsReviewed=3)");
}

function getTraineeMajorUnreviewed($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", True, $traineeID, " AND TraineeMarkAsReviewed=0");
}

function getTraineeMinor($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", False, $traineeID, "AND (AutoDiscrepancy LIKE 'MinorChange') ");
}

function getTraineeAddition($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", False, $traineeID, "AND (AutoDiscrepancy LIKE 'Addition') ");
}

function getDiscrepancyCountsByStudy($section, $type, $traineeID, $startDate=NULL)  {
    $sql = "SELECT SUM(IF(edc.FinalDiscrepancy LIKE 'MajorChange', edc.Count, 0)) AS MajorChange, 
SUM(IF(edc.FinalDiscrepancy LIKE 'MinorChange', edc.Count, 0)) AS MinorChange, 
SUM(IF(edc.FinalDiscrepancy LIKE 'Addition', edc.Count, 0)) AS Addition, 
SUM(IF(edc.FinalDiscrepancy LIKE 'Agree', edc.Count, 0)) AS Agree, 
SUM(IF(edc.FinalDiscrepancy LIKE 'GreatCall', edc.Count, 0)) AS GreatCall
FROM ExamDiscrepancyCounts AS edc
WHERE TraineeID=$traineeID
AND Section='$section'
AND Type='$type'";
    if ($startDate != NULL)  {
        $sql .= " AND StartDate>='" . $startDate->format("Y-m-d") . "'";
    }
    return getSingleResultArray($sql, True);
}

function getMostRecentDiscrepancyCounts($section, $type, $traineeID, $top=200)  {
    global $resdbConn;

    $sql = "SELECT 
        SUM(IF(FinalDiscrepancy LIKE 'MajorChange', 1, 0)) AS MajorChange, 
        SUM(IF(FinalDiscrepancy LIKE 'MinorChange', 1, 0)) AS MinorChange,
        SUM(IF(FinalDiscrepancy LIKE 'Addition', 1, 0)) AS Addition,
        SUM(IF(FinalDiscrepancy LIKE 'Agree', 1, 0)) AS Agree,
        SUM(IF(FinalDiscrepancy LIKE 'GreatCall', 1, 0)) AS GreatCall
           FROM 
           (SELECT IF(AdminDiscrepancy>'',AdminDiscrepancy,AutoDiscrepancy) AS FinalDiscrepancy
            FROM ExamDiscrepancy AS ed INNER JOIN ExamMeta AS em on em.PrimaryAccessionNumber=ed.AccessionNumber  
            INNER JOIN ExamCodeDefinition AS ecd ON ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization WHERE ";
            if (isset($traineeID))  {
                $sql .= " em.TraineeID=$traineeID AND ";
            }
    $sql .= "ed.AutoDiscrepancy NOT LIKE 'None'
        AND ed.AdminDiscrepancy NOT LIKE 'None'
        AND ed.AutoDiscrepancy NOT LIKE 'Attest'
        AND ed.AdminDiscrepancy NOT LIKE 'Attest'
        AND ecd.Type='$type' AND ecd.Section='$section'
        ORDER BY CompletedDTTM DESC
        LIMIT 0,$top) as TopCounts
        WHERE 1;"; 
    return getSingleResultArray($sql, True);
}

/* 

getPrimaryAccessionNumber(int)

A way to access Primary accession numbers for associated studies in
real-time directly from RIS 
*/

function getPrimaryAccessionNumber($accession)  {
    global $RISNameBackup, $connectionInfo;
    if (!$accession) return NULL;
    $sql = "SELECT DISTINCT AssociatedAccessionNumber, SecondaryAccessionNumber FROM vDiagnosticReportAuditTrail WHERE SecondaryAccessionNumber='$accession';";
    $conn2 = sqlsrv_connect($RISNameBackup, $connectionInfo);  // Backup=hourly
    if (!$conn2) {
        echo "Could not connect to 24-hour RIS mirror!\n";    
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_query($conn2, $sql);
    $d = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    $primaryAcc = '';
    if ($d['AssociatedAccessionNumber'] != '' && $d['AssociatedAccessionNumber'] != NULL) {
        $primaryAcc = $d['AssociatedAccessionNumber'];
    }
    else  {
        $primaryAcc = $accession;
    }
    return $primaryAcc;
}


?>


