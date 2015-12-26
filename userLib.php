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
userLib.php
Individual institutional level customized functions.
 **************************************/

function isEmtracDone($connection, $acc)  {
    $sql = "SELECT audit.SecondaryAccessionNumber, fax.Destination FROM vFaxDetails as fax INNER JOIN  vDiagnosticReportOutputAuditTrail as output ON fax.ReportOutputID=output.ReportOutputID INNER JOIN vDiagnosticReportAuditTrail as audit ON audit.AuditEventID=output.AuditEventID WHERE audit.SecondaryAccessionNumber='$acc' AND fax.Destination='emtrac@emrad.uphs.upenn.edu' AND AuditEventTypeName='Email sent';";

    $result = sqlsrv_query($connection, $sql) or die("Can't find answer in RIS");
    $sqlarray = array();
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    if (sizeof($row) > 0) return True;
    else return False;
}

function getPrevCall($startDate, $endDate)  {
    global $resdbConn;
    
	$sqlquery = "SELECT ed.AccessionNumber AS 'Accession', CompositeDiscrepancy as `Discrepancy`, Section, Type, Description, CompletedDTTM as `Completed` FROM ExamDiscrepancy AS ed 
    INNER JOIN ExamMeta as em ON ed.AccessionNumber=em.PrimaryAccessionNumber 
    INNER JOIN ExamCodeDefinition AS ecd ON ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization 
    INNER JOIN ResidentIDDefinition as rid ON ed.TraineeID=rid.TraineeID 
    WHERE 
    # em.CompletedDTTM >= '$startDate' AND em.CompletedDTTM < '$endDate' AND 
    (CompositeDiscrepancy LIKE 'MajorChange' OR CompositeDiscrepancy LIKE 'MinorChange' OR CompositeDiscrepancy LIKE 'GreatCall')
    AND (rid.LastName='Lazor' OR rid.LastName='Mulugeta' OR rid.LastName='Ware')
    GROUP BY ed.AccessionNumber ORDER BY CompositeDiscrepancy, CompletedDTTM DESC ";

    $results = $resdbConn->query($sqlquery) or die (mysqli_error($resdbConn));
    return $results;

}

function getAllED($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", True, $traineeID, " AND EDNotify=1", True);
}
function getMajorChangeAll($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", False, $traineeID, '', True);
}
function getGreatCallAll($startDate, $endDate, $traineeID='')  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "GreatCall", True, $traineeID, ' AND ecd.Section!="BREAST" AND ecd.Section!="NM" ', True);
}

function getAllEDUnreviewed($startDate, $endDate)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "", False, "", " AND EDNotify=1 AND AdminDiscrepancy=''");
}

function getMajorChangeAllUnreviewed($startDate, $endDate)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", False, "", " AND AdminDiscrepancy=''");
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
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MajorChange", True, $traineeID, " AND TraineeMarkAsReviewed=0 ", False, True);
}

function getTraineeMinor($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "MinorChange", False, $traineeID, '', True);
}

function getTraineeAddition($startDate, $endDate, $traineeID)  {
    return getTraineeStudiesByDiscrepancy($startDate, $endDate, "Addition", False, $traineeID, '', True);
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

// A way to access Primary accession numbers for associated studies in real-time directly from RIS>
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

function printHTML($results)  {
    global $URL_root;
    $output = "<table id='resultsTable' class='results'>\n";
    // Header
    
    $first = True;

    while ($row = $results->fetch_array(MYSQL_ASSOC))  {
        if ($first)  {
            $first = False;
            $headers = array_keys($row);
            $output .= "<thead><tr>";
            foreach ($headers as $h)  {
				if ($h == "TraineeID") continue;
                $output .= "<th align=left><strong>$h</strong></th>";
            }
            $output .= "</tr></thead>\n<tbody>\n";
        }
        $output .= "<tr>";
		$traineeid = '';
		$type = '';
        foreach($row as $k=>$col) {
            if (is_a($col, "DateTime")){
                $col = $col->format('Y-m-d H:i:s');
            }
			if ($k == "Type")  {
				$type = $col;
			}
			if ($k == "TraineeID")  {
				$traineeid = $col;
				continue;
			}
            else if ($k == "LastName")  {
                $output .= "<td id='" . $col . "'>";
                $output .= "<a href='$URL_root/reviewByGroup.php?group=" . $traineeid . "'>$col</a>";
            }
			else if (isset($_GET['class']) && ($k == "ED TAT" || $k == "Inpt STAT TAT"))  {
				$output .= "<td id='" . $col . "'>";
				$output .= "<a href='reviewByTAT.php?group=" . $traineeid . "'>$col</a>";
			}
			else if ($k == "Major %" && (isset($_SESSION['adminid']) && $_SESSION['adminid'] > 9000000))  {
				$color = '#CFC';
				if ($type == 'CR' && (float)$col > 1.5)  {
					$color = '#FCC';
				} else if ($type == 'CT' || $type == 'US' || $type == 'MR')  {
					if ((float)$col > 4) $color = '#FCC';
				} else if ((float)$col > 1.7)  {
					$color = '#FCC';
				} 
            	$output .= "<td style='background:$color'>" . $col;
			}
            else $output .= "<td>" . $col;
            /************************/
            //$output .= $col;

        }
        $output .= "</tr>\n";
    }
    if ($first)  {
        echo "<tbody><tr><td>No study satisfies the search crtieria.</tr></tbody>";
    }
    $output .= "\n</tbody>\n</table>";
    return $output;
}


?>


