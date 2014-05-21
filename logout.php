<?php
include "capricornLib.php"; ?>


<html>
<head>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<link href="chardinjs.css" rel="stylesheet">

<script src="js/jquery-1.9.1.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/collapseTable.js"></script>
<script type='text/javascript' src="js/chardinjs.min.js"></script>
</head>
<?php
include "header.php";

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
$_SESSION = NULL;
session_destroy();
echo "<p>You have been logged out.</P>";


include "footer.php";
?>


