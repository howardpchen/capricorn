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
include 'header.php';

// Must find resident first

$db_name="localhost"; // Database name 
$tbl_name="LoginMember"; // Table name 
$res_tbl_name="ResidentIDDefinition";

$myfirstname = $_POST['myfirstname'] or exit();
$mylastname = $_POST['mylastname'];
//$myaccession= $_POST['myaccession'];
$myusername = "";

$db = $resdbConn; 

$myfirstname = $db->real_escape_string($myfirstname);
$mylastname = $db->real_escape_string($mylastname);
$myresidentid = -1;

// $sqlquery = "SELECT ResidentFirstName, ResidentLastName, ResidentID FROM ResidentIDDefinition WHERE ResidentFirstName=\"$myfirstname\" AND ResidentLastName=\"$mylastname\";";

$sqlquery = "SELECT COUNT(*) as count FROM $res_tbl_name WHERE FirstName LIKE \"$myfirstname\" AND LastName LIKE \"$mylastname\" AND IsCurrentTrainee='Y';";

$results = $db->query($sqlquery);

$row = $results->fetch_array();
if ($row['count'] > 1) {
    echo "Please note that there are more than one radiologists by the same name in our system.  Contact system administrator for more details.";
    // Have to manage duplicate names.
    exit();
} else if ($row['count'] == 0) {
    echo "An active resident by this exact name is not found in our system.  Please note that you must be an active resident and that you must enter the first name as shown in your interpreted radiologic reports." ;
    exit();
}

else {
    $sqlquery = "SELECT TraineeID FROM $res_tbl_name WHERE FirstName LIKE \"$myfirstname\" AND LastName LIKE \"$mylastname\";";
    $results = $db->query($sqlquery);
    $row = $results->fetch_array();
    $myresidentid = $row['TraineeID'];

    $sqlquery = "SELECT COUNT(*) as count FROM $tbl_name WHERE TraineeID=$myresidentid;";
    $results = $db->query($sqlquery);
    $row = $results->fetch_array();
    if ($row['count'] > 0) {
        echo "Our records indicate that you already have an account.  Contact the administrator to reset your password with any problems.";
        exit();
    }
}

?>

<table width="400" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="form1" method="post" action="add_user.php">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">
<tr>
<td colspan="3"><strong>Capricorn - Create User</strong></td>
</tr>
<tr>
<td colspan="3">

<?php 
echo "Welcome, <strong>Dr. $myfirstname $mylastname.</strong>  Your records have been identified.<br>";
?>
Please enter a desired username and password.</td>

</tr>
<tr>
<td width="120">Username</td>
<td>&nbsp;</td>
<td><input name="myusername" type="text" id="myusername"></td>
</tr>
<tr>
<td width="120">Password</td>
<td>&nbsp;</td>
<td><input name="mypassword" type="password" id="mypassword"></td>
</tr>
<tr>
<td width="120">Confirm Password</td>
<td>&nbsp;</td>
<td><input name="mypasswordconfirm" type="password" id="mypasswordconfirm"></td>

<?php
echo "<input name='myresidentid' type='hidden' id='myresidentid' value='$myresidentid'>";
echo "<input name='myfirstname' type='hidden' id='myfirstname' value='$myfirstname'>";
echo "<input name='mylastname' type='hidden' id='mylastname' value='$mylastname'>";
?>

</tr>
<tr>
<td colspan=3 align=center><input type="Submit" value="Submit">
</tr>
</table>
</td>
</form>
</tr>
</table>

<?php
include "footer.php"
?>

