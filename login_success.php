<?php include "capricornLib.php"; ?>
<!doctype html>
<html>
<head>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<link href="<?php echo $URL_root; ?>css/chardinjs.css" rel="stylesheet">
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
<Title>Capricorn - Home</title>
</head>
<?php include "header.php"; ?>


<div class="row" data-intro="Click on a button to view the data.">
<div class="4u" align="center"> <a href="browse.php"><img src="browse.png"><br>Explore Progress</a></div>
<div class="4u" align="center"><a href="calls.php"><img src="calls.png"><br>Review 2013-2014 Calls</a></div> 
<div class="4u" align="center"><a href="statistics.php"><img src="statistics.png"><br>Historical Statistics</a></div>
</div>
<!--
<div class="row">
<div class="4u" align="center"><a href="wordcloud.php"><img src="wordcloud.png"><br>Your WordCloud</a></div>
-->
</div>
<div class="row"><div class="12u">
<p align=center>Note: Currently Capricorn includes only HUP, PMC, PAH, and VF interpretations.  <br>CHOP and VA are notably <u>not</u> included.</p>
<p align=center><A HREF="logout.php">Log Out</A></P></div>

<?php include "footer.php"; ob_end_flush(); ?>
