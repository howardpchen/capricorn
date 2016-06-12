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

function escapeReportStr($str)  {
    $str = str_replace('"', '\'', $str);
    $str = nl2br($str);
    $str = str_replace(array("\r\n", "\r"), "\n", $str);
    $lines = explode("\n", $str);
    $new_lines = array();

    foreach ($lines as $i => $line) {
        if(!empty($line))
            $new_lines[] = trim($line);
    }
    return implode($new_lines);
}

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

//RIS tables.  These are used to display the most up-to-date final report.

if (!isset($_GET['acc'])) die (print_r("Faulty parameters."));

//If available, will attempt to get Prelim report from Capricorn.
/*
$sql = "SELECT PreliminaryReportText FROM ExamReportText INNER JOIN ExamMeta ON ExamReportText.AccessionNumber=ExamMeta.PrimaryAccessionNumber WHERE ExamMeta.AccessionNumber='" . $_GET['acc'] . "'";

$result = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$prelimText = '';
$row = $result->fetch_all(MYSQL_ASSOC);
if ($row) $prelimText = $row[0]['PreliminaryReportText'];
else  {
    $prelimText = "Preliminary report for this study is not currently stored in Capricorn's database.";
}
*/

$sql = "
SELECT ResidentIDDefinition.FirstName AS ResFirstName, ResidentIDDefinition.TraineeID AS TraineeID, ResidentIDDefinition.LastName AS ResLastName, AttendingIDDefinition.FirstName AS AttFirstName, AttendingIDDefinition.LastName AS AttLastName, ExamMeta.FirstName AS PtFirstName, ExamMeta.LastName AS PtLastName, ExamMeta.CompletedDTTM AS CompletedDTTM, ExamCodeDefinition.ExamCode AS ExamCode, ExamCodeDefinition.Description AS ExamDesc, PreliminaryReportText, FinalReportText 
FROM ExamReportText 
INNER JOIN ExamMeta ON ExamReportText.AccessionNumber=ExamMeta.PrimaryAccessionNumber 
LEFT JOIN ResidentIDDefinition ON ExamMeta.TraineeID=ResidentIDDefinition.TraineeID 
LEFT JOIN AttendingIDDefinition ON ExamMeta.AttendingID=AttendingIDDefinition.AttendingID 
LEFT JOIN ExamCodeDefinition ON ExamCodeDefinition.ExamCode=ExamMeta.ExamCode 
AND ExamCodeDefinition.ORG=ExamMeta.Organization 
WHERE ExamMeta.AccessionNumber='" . $_GET['acc'] . "'";

$result = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$prelimText = "Preliminary report for this study is not currently stored in Capricorn's database.";
$finalText = '';
$row = $result->fetch_all(MYSQL_ASSOC);
if ($row)  {
    $prelimText = $row[0]['PreliminaryReportText'];
    $prelimText = escapeReportStr($prelimText);
    $finalText = $row[0]['FinalReportText'];
    $finalText = escapeReportStr($finalText);
}
else  {
    $finalText= "Report for this study is not currently stored in Capricorn's database.";
}

if (!$row) {    
    echo "<br><br>Accession number not found.";
	exit();
}    
else { 
    echo "<div class='row'><div class='6u reportHeader'>Accession " . $_GET['acc'] . "</div><div class='6u' id='userTags'></div></div>";
    $assoc = getAllAssociatedStudies($_GET['acc']);
    $assocString = implode(", ", $assoc);
    if (sizeof($assoc) > 1)  {
        echo "<div class='reportSubheader'>Associated: $assocString</div>";
    }

    //  Insert code for control panel depending on admin status.
    if (isAdmin() && $_SESSION['adminid'] > 99000000)  {
        include_once "admin/discrepancyControl.php";
    } else if (isAdmin() && $_SESSION['adminid'] >98000000)  {
		include_once "admin/discrepancyControlView.php";
	} else  {
        include_once "reportControl.php";
    }
    $patientDemo = array();
    $patientDemo['Name'] = $row[0]['PtLastName'] . ', ' .
    $row[0]['PtFirstName'];
    $patientDemo['Exam'] = $row[0]['ExamDesc'];
    $patientDemo['Completed'] = $row[0]['CompletedDTTM'];
    if (isAdmin() || $_SESSION['traineeid'] == $row[0]['TraineeID'])  {
        tableStartSection('Exam Metadata', 0, isAdmin());
        foreach ($patientDemo as $k=>$p)  {
            echo "$k: $p<br>\n";
        }
        tableEndSection();
    }
}

//$finalText = $row['ReportText'];

/* END UPHS SPECIFIC CODE */

?>

<div id="tabs">
<ul>
<?php 
if ($prelimText > '')  {
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
var prelimrpt = "<?php echo escapeReportStr($prelimText);?>";
var finalrpt = "<?php echo escapeReportStr($finalText);?>";
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
    if (isAdmin() || $_SESSION['traineeid'] == $row[0]['TraineeID'])  {
        echo "Resident: ";
        echo $row[0]['ResFirstName'] . " " . $row[0]['ResLastName'];
    }
    echo "<p>Attending: ";
    echo $row[0]['AttFirstName'] . " " . $row[0]['AttLastName'];

    tableEndSection();

}
?>
</div>


</body>


