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
include 'capricornLib.php';
include 'header.php';
// Must find resident first

$tbl_name="LoginMember"; // Table name 
$res_tbl_name="ResidentIDDefinition";
$salt = openssl_random_pseudo_bytes(22);
$salt = '$2x$%13$' . strtr($salt, array('_' => '.', '~' => '/'));

if ($_POST['mypassword'] != $_POST['mypasswordconfirm']) {
    echo "The two repeated password entry must match! <BR> Please return to the previous page and try again.";
    exit();
}

$myusername = $_POST['myusername'] or exit();
$mypassword = $_POST['mypassword'];
$myresidentid= $_POST['myresidentid'];
$myfirstname= $_POST['myfirstname'];
$mylastname= $_POST['mylastname'];
$mypasswordhash = crypt($mypassword, $salt);

$db = $resdbConn;

// First check to see whether the account exists.

$sqlquery = "SELECT COUNT(*) as count FROM $tbl_name WHERE Username LIKE \"$myusername\"";

$results = $db->query($sqlquery);

$row = $results->fetch_array();
if ($row['count'] > 0) {    // username exists
    echo "This username is in use.  Please try again.";
    // Have to manage duplicate usernames.
    exit();
} else if ($row['count'] == 0) {        // username doesn't exist.
    // add user
    $execstring = "INSERT INTO $tbl_name (TraineeID, Username, PasswordHash) VALUES ($myresidentid, \"$myusername\", \"$mypasswordhash\");";
    $success = $db->query($execstring) or die (mysqli_error($db));
    if ($success) {
        echo "User created successfully!  <br> <A href=login.html>Log in</A>";
    } else {
        echo "Error has occurred.";
    }
}
// Select Database.

include "footer.php"
?>
