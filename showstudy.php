<body>
<link rel="stylesheet" href="css/style.css" />
<a href="showstudy_excel.php?<?php echo http_build_query($_GET);?>">Export to Excel</a>
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
include "capricornLib.php";

session_start();
if (!isset($_SESSION['traineeid']))  {
    header("location:./");
}

$startDate = date_create($_GET['from']);

$endDate = clone $startDate;
$endDate->add(new DateInterval('P' . $_GET['day'] . 'D'));

$results = getTraineeStudiesByDate($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_GET['sec'], $_GET['typ'], $_GET['notes']);


$htmlprint = getResultsHTML($results);
echo $htmlprint;
?>
</body>
