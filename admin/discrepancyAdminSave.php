<?php
include_once "../capricornLib.php";
$accession = isset($_POST['acc'])?$_POST['acc']:'';
$adminDisc = isset($_POST['adminDisc'])?$_POST['adminDisc']:'';
$adminComment = isset($_POST['adminComment'])?$_POST['adminComment']:'';
$traineeMark = isset($_POST['traineeMark'])?$_POST['traineeMark']:'';
$comment = mysql_real_escape_string($adminComment);
if ($accession != '')  {
    $saveSuccess = saveAdminComment($accession, $adminDisc, $comment, $traineeMark);
    if ($saveSuccess)  {
        echo "$adminDisc||$comment"; // If save sucessful, return the Revised Discrepancy.
    }
}
else exit(1);

?>
