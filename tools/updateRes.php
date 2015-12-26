<?php
/* 
updateRes.php

Script to add new residents and fellows into ResidentIDDefinition

Format of input text file requires each col to be separated by tabs (ONE tab), the general order of features are as follows:

LastName    FirstName   F(ellow)/R(esident)    Subspecialty  StartDate    QGenda
Smith       John        F                       Abdominal    2015-07-01  SmithJ
*/

include "../capricornLib.php";

$resTable = "ResidentIDDefinition";     // MySQL table name
$sourceTable = "[PSSource].[dbo].[Account]";

$resListF = file_get_contents("reslist.txt");
$resListF = explode("\n",$resListF);
$resList = array();

$conn2 = sqlsrv_connect($RISName, $connectionInfo);
if (!$conn2) {
    echo "Could not connect to PS360 mirror!\n";
    die(print_r(sqlsrv_errors(), true));
}

foreach ($resListF as $rf) {
    $resList[] = explode("\t", $rf);
}

function print_result($sqlarray){
    foreach ($sqlarray as $value) {
        foreach ($value as $i) {
            if (is_a($i, "DateTime")){
                $i = $i->format('Y-m-d H:i:s');
            }
            echo "$i || ";
        }
        echo "<br>\n";
    }
}

foreach ($resList as $resArray)  {
    if (!isset ($resArray[1])) continue;

    $lastName = $resArray[0];
    $firstName = $resArray[1];
    $rank = $resArray[2]=="Resident"?"1, 0":"0, 1";
    $subsp = $resArray[3];
    $start = $resArray[4];
    $qgenda = isset($resArray[5])?$resArray[5]:'';

    $mssqlquery = "select top 1000 [PSSource].[dbo].[Account].accountid from [PSSource].[dbo].[Account] WHERE FirstName LIKE '$firstName' AND LastName LIKE '$lastName' ORDER BY id DESC";
    $ps360result = sqlsrv_query($conn2, $mssqlquery); /** or die("Can't find answer in PS360 database"); **/
    $value = sqlsrv_fetch_array($ps360result, SQLSRV_FETCH_ASSOC);
    $acct = $value['accountid'];
    if ($acct > 0)  {
        $sqlquery = "REPLACE INTO ResidentIDDefinition (TraineeID, FirstName, LastName, IsCurrentTrainee, IsResident, IsFellow, Program, Subspecialty, StartDate, QGendaName) 
        VALUES (
        $acct, 
        '$firstName',
        '$lastName',
        'Y',
        $rank,
        'HUP',
        '$subsp',
        '$start',
        '$qgenda'
        )
        ";
        $resdbConn->query($sqlquery) or die (mysqli_error($resdbConn));
        echo "Added/Updated $firstName $lastName in Capricorn";
    } else  {
        echo "****Cannot find $firstName $lastName in the PS360 mirror.";
    }


    echo "<BR>\r\n";
}



echo "All done."

?>
