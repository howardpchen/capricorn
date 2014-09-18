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
if(!isset($_SESSION)){session_start();}

if (isset($_GET['changeid'])) {
    $_SESSION['traineeid'] = $_GET['changeid'];
    unset($_SESSION['FullName']);
    unset($_SESSION['LastName']);
    getLoginUserLastName();
    getLoginUserFullName();
}

/**************************************
 Check Login
 **************************************/

if(!isset($_SESSION['username'])) {
    header("location:".$URL_root);
}
// Auto logout after 30 mins.  This way don't have to rely on 
// Session expiration date.
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
	// last request was more than 30 minutes ago
	session_unset();     // unset $_SESSION variable for the run-time 
	session_destroy();   // destroy session data in storage
	header("location: $URL_root");
}
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp


writeLog("Loaded by " . $_SESSION['traineeid']);


include "header_nosession.php";
?>


