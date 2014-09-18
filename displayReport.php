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
 * displayReport.php
 *
 * This file displays the report text of the selected Accession Number.  
 * 
 * A functional displayReport.php simply needs to retrieve the preliminary 
 * and final text from Capricorn and feed them into the scripts towards the 
 * bottom of the page to show difference.
 * 
 * Currently it uses the institutional RIS to obtain demographics information and 
 * a more updated final report (i.e. possibly with addenda).
 *
 *
 */


include_once "capricornLib.php";
session_start();
$_GET['acc'] = mysql_real_escape_string($_GET['acc']);

?>

<script src="<?php echo $URL_root;?>js/jsdiff.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.dropotron.min.js"></script>
<script src="<?php echo $URL_root; ?>js/config.js"></script>
<script src="<?php echo $URL_root; ?>js/skel.min.js"></script>
<script src="<?php echo $URL_root; ?>js/skel-panels.min.js"></script>

<body>
  <link rel="stylesheet" href="<?php echo $URL_root;?>css/jquery-ui.css">
  <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css" />
<div id='main-wrapper' style="margin:2em">
<?php
/*
 * This PHP portion can be commented out and replaced with your own institutional code.
 * You only need to load $prelimText and $finalText with the appropriate strings for the 
 * reminder of the code to work.
 *
 * BEGIN UPHS SPECIFIC CODE
 * 
 */
if (!isset($_SESSION['traineeid']))  {
    header('location:./');
} 

$conn2 = sqlsrv_connect($RISNameBackup, $connectionInfo);
if (!$conn2) {
    $conn2 = sqlsrv_connect($RISName, $connectionInfo);
    if (!$conn2)  {
        echo "Capricorn depends on the RIS mirror, which is currently refreshing.  This usually lasts a few minutes.  Try again soon.\n";    
        exit();
    }
    //die(print_r(sqlsrv_errors(), true));
}
//RIS tables.  These are used to display the most up-to-date final report.
$table1 = "vusrExamDiagnosticReportText";
$table2 = "vDxRptContributingResponsible";
$table3 = "vusrExamLog";

if (!isset($_GET['acc'])) die (print_r("Faulty parameters."));

//If available, will attempt to get Prelim report from Capricorn.

$sql = "SELECT PreliminaryReportText FROM ExamReportText INNER JOIN ExamMeta ON ExamReportText.AccessionNumber=ExamMeta.PrimaryAccessionNumber WHERE ExamMeta.AccessionNumber='" . $_GET['acc'] . "'";

$result = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$prelimText = '';
$row = $result->fetch_all(MYSQL_ASSOC);
if ($row) $prelimText = $row[0]['PreliminaryReportText'];
else  {
    $prelimText = "Preliminary report for this study is not currently stored in Capricorn's database.";
}

// Get final report as well as demographic information.
$sql = "SELECT * FROM vusrExamDiagnosticReportText INNER JOIN vDiagnosticReportText as dxtext ON vusrExamDiagnosticReportText.PrimaryInternalExamID=dxtext.ActivityHeaderID WHERE AccessionNumber='" . $_GET['acc'] . "'";
$result = sqlsrv_query($conn2, $sql); /** or die("Can't find answer in RIS"); **/
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

$sql2 = "SELECT * FROM $table2 WHERE AccessionNumber='" . $_GET['acc'] . "'";
$result2 = sqlsrv_query($conn2, $sql2); /** or die("Can't find answer in RIS"); **/

$sql3 = "SELECT * FROM $table3 WHERE AccessionNumber='" . $_GET['acc'] . "'";
$result3 = sqlsrv_query($conn2, $sql3); /** or die("Can't find answer in RIS"); **/
if (!$row)  {
    $sql = "SELECT * FROM vusrExamDiagnosticReportText WHERE AccessionNumber='" . $_GET['acc'] . "'";
    $result = sqlsrv_query($conn2, $sql); /** or die("Can't find answer in RIS"); **/
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
}
if (!$row) {    
    echo "Invalid accession number.";    
}    
else { 
    echo "<div class='row'><div class='6u reportHeader'>Accession " . $_GET['acc'] . "</div><div class='6u' id='userTags'></div></div>";
    $assoc = getAllAssociatedStudies($_GET['acc']);
    $assocString = implode(", ", $assoc);
    if (sizeof($assoc) > 1)  {
        echo "<div class='reportSubheader'>Associated: $assocString</div>";
    }

    //  Insert code for control panel depending on admin status.
    if (isAdmin())  {
        include_once "admin/discrepancyControl.php";
    } else  {
        include_once "reportControl.php";
    }

    $sqlarray = array();
    $row3 = sqlsrv_fetch_array($result3, SQLSRV_FETCH_ASSOC);
    if (!$row3)  {
        $patientDemo = array();
        $patientDemo['Exam'] = $row['ExamDesc'];
        foreach ($patientDemo as $k=>$p)  {
            echo "$k: $p<br>\n";
        }

        echo "Accession number found, but no interpretation report is currently available.  This means either it has not been interpreted, or Capricorn has not yet syncrhonized it with the database.<P><strong>However, you can still assign tags to this study.<P>Your input will be synchronized once the report becomes available.</strong>";
        exit();
    } else {
        tableStartSection('Exam Metadata', 0, isAdmin());    
		 
        $patientDemo = array();
        $patientDemo['Name'] = $row3['PatientLastName'] . ', ' . $row3['PatientFirstName'];
        $patientDemo['DOB'] = $row3['PatientDOB']->format('m/d/Y');
        $birthDate = explode("/", $patientDemo['DOB']);
        $patientDemo['Age'] = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
                ? ((date("Y") - $birthDate[2]) - 1)
                : (date("Y") - $birthDate[2]));
        $patientDemo['Sex'] = $row3['PatientSex'];
        $patientDemo['Exam'] = $row3['ExamDesc'];
        $patientDemo['Completed'] = $row3['CompletedDTTM']->format('m/d/Y H:i:s');
        $patientDemo['Requested By'] = $row3['RequestingProviderName'];

        $report = str_replace("\n", "<BR>", $row['ReportText']);
        foreach ($patientDemo as $k=>$p)  {
            echo "$k: $p<br>\n";
        }
        tableEndSection();
    }
}

$finalText = $row['ReportText'];

/* END UPHS SPECIFIC CODE */

?>

<div id="tabs">
<ul>
<?php 
    if (isAdmin())  {
        echo <<< END
        <li><a href="#diff">Show Changes</a></li>
        <li><a href="#final">Final</a></li>
        <li><a href="#prelim">Preliminary</a></li>
END;
    } else  {
        echo <<< END
        <li><a href="#final">Final</a></li>
        <li><a href="#prelim">Preliminary</a></li>
        <li><a href="#diff">Show Changes</a></li>
END;
    }
?>
</ul>
<div id="diff">
</div>
<div id="final">
</div>
<div id="prelim">
</div>
</div>

<script>
var prelimrpt = "<?php echo str_replace('"', '\'', $prelimText);?>";
var finalrpt = "<?php echo str_replace('"', '\'', $finalText);?>";
var diff = "<p>" + diffString(prelimrpt, finalrpt) + "</p>";
if (prelimrpt == finalrpt)  {
    diff = prelimrpt = "<strong>The preliminary and final reports are identical.  This occurs when the attending dictates an examination directly, or when the attending signs a trainee report off the Draft queue rather than Preliminary Report queue.</strong>";
}
else if (prelimrpt == "Preliminary report for this study is not currently stored in Capricorn's database.") {
    diff = prelimrpt;
}
document.getElementById("diff").innerHTML = diff;
document.getElementById("final").innerHTML = finalrpt;
document.getElementById("prelim").innerHTML = prelimrpt;
$(function() {
    $( "#tabs" ).tabs();
});

//document.cookie = "currentStudy=<?php echo $_GET['acc']; ?>; path=/";
if (typeof(window.opener.updateCurrentStudy) === "function")  {
    window.opener.updateCurrentStudy(<?php echo $_GET['acc']; ?>);
}
</script>


<?php
if ($row)  {
    tableStartSection('Providers', 0, isAdmin());
    echo "Contributing Provider(s):<br>";
    $count = 0;
    while ($row2 = sqlsrv_fetch_array($result2, SQLSRV_FETCH_ASSOC))  {
        echo ++$count . ". " . $row2['ProviderFirst'] . " " . $row2['ProviderLast'] . ", " . $row2['ProviderTitle'] . "<br>";
        //        echo $row['ReportText'];
    }

    echo "<br>Responsible Provider: " . $row['Interp1FirstName'] . " " . $row['Interp1LastName'] . ", " . $row['Interp1TitleName'];
    tableEndSection();
}
sqlsrv_free_stmt($result);
sqlsrv_close($conn2);

?>
</div>


</body>


