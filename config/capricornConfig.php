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

/**************************************
System-Wide Configurations
 **************************************/

$log_flag = True; // set to false to disable logging.

$timezone_string = 'America/New_York';  // Standard timezone string.
$file_root = "/xampp/htdocs/capricorn/";  // the local file path
$URL_root = "/capricorn/";  // With trailing slash (/)
// This is the Capricorn Database information
$mysql_host = 'localhost';
$mysql_username = 'capricorn_username';
$mysql_passwd = 'capricorn_password';
$mysql_database = 'capricorn';

// This portion of the code is for updating Capricorn using RIS.
// Also used to display actual study report by displayReport.php
$RISName = "blahblah.uphs.upenn.edu";
$RISLogin = "risusername";
$RISPwd = "rispassword";

// Theme colors.
$schemaColor[0] = '#000072';          // Darkest
$schemaColor[1] = '#CCDDFF';          // Table Headers
$schemaColor[2] = '#F0F0F7';          //Table background

// Graph colors based on section.
$graphColor['CHEST'] = '#088DC7';
$graphColor['BODY'] = '#50B432';
$graphColor['MSK'] = '#ED561B';
$graphColor['NEURO'] = '#24CBFF';
$graphColor['BREAST'] = '#EE3399';
$graphColor['BABY'] = '#FFF263';
$graphColor['CVI'] = '#6AF999';
$graphColor['IR'] = '#AA0000';
$graphColor['GI'] = '#008888';
$graphColor['GU'] = '#880088';
$graphColor['SPINE'] = '#8888CC';
?>
