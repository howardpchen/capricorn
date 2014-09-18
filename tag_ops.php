<?php
include_once "capricornLib.php";
$accession = isset($_POST['acc'])?$_POST['acc']:'';
$traineeid = isset($_POST['traineeid'])?$_POST['traineeid']:'';
$tagValue = isset($_POST['tag'])?$_POST['tag']:'';
$tagOperation = isset($_POST['operation'])?$_POST['operation']:'';
$tagValue = mysql_real_escape_string($tagValue);
if ($accession != '')  {
    if ($tagOperation == 'add') {
        $success = saveUserTag($traineeid, $accession, $tagValue);
    }
    else if ($tagOperation == 'remove')  {
        $success = removeUserTag($traineeid, $accession, $tagValue);
    }

    if (isset($success))  {
        echo "successful"; // If save sucessful, return the Revised Discrepancy.
    } else  {
        echo "unsuccessful"; 
        exit(1);
    }
}
else exit(1);

?>
