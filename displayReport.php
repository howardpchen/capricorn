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
include "capricornLib.php";
session_start();
?>
<body>
<link rel="stylesheet" href="css/style.css" />
<div id='main-wrapper' style="margin:2em">
<header class='login'>Accession <?php echo $_GET['acc']; ?></header>
<?php

if (!isset($_SESSION['traineeid']))  {
    header('location:./');
}
$conn2 = sqlsrv_connect($RISName, $connectionInfo);
if (!$conn2) {
    echo "Could not connect to 24-hour RIS mirror!\n";    
    die(print_r(sqlsrv_errors(), true));
}

$table1 = "vusrExamDiagnosticReportText";
$table2 = "vDxRptContributingResponsible";
if (!isset($_GET['acc'])) die (print_r("Falty parameters."));

$sql = "SELECT * FROM $table1 WHERE AccessionNumber='" . $_GET['acc'] . "'";
$result = sqlsrv_query($conn2, $sql); /** or die("Can't find answer in RIS"); **/

$sql2 = "SELECT * FROM $table2 WHERE AccessionNumber='" . $_GET['acc'] . "'";
$result2 = sqlsrv_query($conn2, $sql2); /** or die("Can't find answer in RIS"); **/

if ( $result )   { 
        $sqlarray = array();
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $report = str_replace("\n", "<BR>", $row['ReportText']);
        echo $report;
        echo "<hr>";
        echo "Contributing Provider(s):<br>";
        $count = 0;
        while ($row2 = sqlsrv_fetch_array($result2, SQLSRV_FETCH_ASSOC))  {
            echo ++$count . ". " . $row2['ProviderFirst'] . " " . $row2['ProviderLast'] . ", " . $row2['ProviderTitle'] . "<br>";
    //        echo $row['ReportText'];
        }
        echo "<br>Responsible Provider: " . $row['Interp1FirstName'] . " " . $row['Interp1LastName'] . ", " . $row['Interp1TitleName'];
        

    }     
else {    
     echo "An error occurred while commmunicating / querying the radiology information system.  Specific error message is as follows: <BR><BR>";    
     die( print_r( sqlsrv_errors(), true));    
}    
sqlsrv_free_stmt($result);
sqlsrv_close($conn2);

?>
</div>
</body>

