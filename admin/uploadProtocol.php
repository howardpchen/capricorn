<?php 
session_start();
include "../capricornLib.php"; 
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

//====================
// Upload a resident protocol raw data and see how many protocols residents have done.
//====================

$startDate = thisJulyFirst();
$startDate = $startDate->format("Y-m-d");
$results = array();
$prog = $_SESSION['program'];



?>

<html>
<head>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/theme.blue.css" />
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.min.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery.tablesorter.widgets.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<title>File Upload - Capricorn</title>
</head>
<?php include "../header.php"; ?>

<?php tableStartSection("Upload New Protocol File",0); ?>

<form action="handler.php" method="post" enctype="multipart/form-data">
<input name="prot" type="file" id="prot" />
<input type="submit" name="Submit" value="Submit">
</form>

<?php tableEndSection() ?>


<?php
// CHECK FOR ADMIN STATUS
checkAdmin();


?>

<script>
$(function(){
    $('.results').tablesorter({
		theme: 'blue',
		widgets: ['zebra', 'filter'],
		ignoreCase:true,
		widgetOption: {
			filter_onlyAvail: 'dropdownFilter'
		}
	}); 
});
</script>

<a href="javascript:void(0)" onClick="window.history.back()">Back</a>
<?php include "../footer.php"; ob_end_flush(); ?>
