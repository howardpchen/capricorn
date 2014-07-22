<?php include "../capricornLib.php"; 
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

?>

<html>
<head>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>

<?php include "../header.php"; 
// CHECK FOR ADMIN STATUS
if ($_SESSION['traineeid'] < 90000000)  {
    header("location:$URL_root");
}
?>
</head>

<BR><BR>
<p><center>

See the resident view for (opens new windows): <form target=_blank action="<?php echo $URL_root; ?>browse.php" method=GET>

<select name='changeid'>
<?php

$sql = "SELECT TraineeID, LastName, FirstName FROM `ResidentIDDefinition` WHERE IsCurrentTrainee=1 ORDER BY StartDate ASC";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all(MYSQL_ASSOC);
foreach ($results as $r)  {
    echo "<option value=\"" . $r['TraineeID'] . "\">" . $r['FirstName']  . " " . $r['LastName'];
}
?>
</select>

<input type=submit value="Go"></form>

<BR><BR>

<P><A HREF="/capricorn/logout.php">Log Out</A></P>

<?php include "../footer.php"; ob_end_flush(); ?>

