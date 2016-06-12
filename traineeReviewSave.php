<?php
include_once "capricornLib.php";
$accession = isset($_POST['acc'])?$_POST['acc']:'';
$traineeComment = isset($_POST['traineeComment'])?$_POST['traineeComment']:'';
$traineeMark = isset($_POST['traineeMark'])?$_POST['traineeMark']:NULL;
$comment = mysql_real_escape_string($traineeComment);
if ($accession != '')  {
    $saveSuccess = saveTraineeComment($accession, $comment, $traineeMark);
    if ($saveSuccess)  {
        echo "Save Success"; // If save sucessful, return the Revised Discrepancy.
    } else  {
        echo "Accession found.  Save Failed";
    }
}
else {
    echo "Save Failed";
}

?>
