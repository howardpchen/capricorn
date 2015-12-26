<pre>
ORG	ExamCode	Description	Section	Type	Notes
<?php
include "../capricornLib.php";

$ecdTable = "ExamCodeDefinition";     // MySQL table name
$sourceTable = "[PSSource].[dbo].[vOrder]";

$conn2 = sqlsrv_connect($RISName, $connectionInfo);
if (!$conn2) {
    echo "Could not connect to PS360 mirror!\n";
    die(print_r(sqlsrv_errors(), true));
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

$sqlquery = "
SELECT DISTINCT Organization as `ORG`, AccessionNumber, table.ExamCode, ecd2.Section, ecd2.Type, ecd2.Notes from 
(select em.AccessionNumber, em.ExamCode, em.Organization, ecd.Description from `exammeta`    as em
LEFT JOIN ExamCodeDefinition as ecd ON (ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization)
WHERE CompletedDTTM > '2015-7-1'
AND ResidentYear < 99
GROUP BY em.ExamCode, em.Organization
) AS `table`
LEFT JOIN ExamCodeDefinition ecd2 ON table.ExamCode=ecd2.ExamCode
WHERE table.Description IS NULL
";

if ($result = $resdbConn->query($sqlquery)) {
    $result = $result->fetch_all(MYSQL_ASSOC);
} else {
    echo "Error loading exam code database.";
    die (mysqli_error($resdbConn));
    exit();
}

foreach ($result as $resArray)  {

    $accessionNumber = $resArray['AccessionNumber'];
    $mssqlquery = "select top 1 * from [PSSource].[dbo].[vOrder] WHERE Accession='$accessionNumber'";
    $ps360result = sqlsrv_query($conn2, $mssqlquery); /** or die("Can't find answer in PS360 database"); **/
    $value = sqlsrv_fetch_array($ps360result, SQLSRV_FETCH_ASSOC);
  
    echo $resArray['ORG'] . "\t" . $resArray['ExamCode'] . "\t";
    echo $value['ProcedureDesc'] . "\t";
    echo $resArray['Section'] . "\t" . $resArray['Type'] . "\t" . $resArray['Notes'];
    echo "\r\n";
}

?>
</pre>
"All done."


