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
$filename = "Capricorn_export.xls";
header("Content-Disposition: attachment; filename=\"$filename\""); 
header("Content-Type: application/vnd.ms-excel"); 

session_start();
if (!isset($_SESSION['traineeid']))  {
    header("location:./");
}

if (isset($_GET['from'])) {
    $startDate = date_create($_GET['from']);
    if (isset($_GET['to']))  {
        $endDate = date_create($_GET['to']);
        $endDate->add(new DateInterval('P1D'));
    }
    else {
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . $_GET['day'] . 'D'));
    }
} else  {
    $endDate = date_create('NOW');
    $endDate->add(new DateInterval('P1D'));
    $startDate = clone $endDate;
    $startDate->sub(new DateInterval('P1825D'));
//    $startDate->sub(new DateInterval('P365D'));
}

$tags = array();
if (isset($_GET['tags']) && $_GET['tags'] != '')  {
    $tags = explode(',', $_GET['tags']);
} 

if (!isset($_GET['mode'])) $_GET['mode'] = '';
switch ($_GET['mode'])  {
    case "MajorAttest":
        $results = getTraineeMajorUnreviewed($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Major":
        $results = getMajorChangeAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "MinorAddition":
        $results = getTraineeMinorAddition($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "GreatCall":
        $results = getGreatCallAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "GreatCall4Pantel":
		if ($_SESSION['traineeid'] != '65404784' && $_SESSION['traineeid'] != '63859737') exit();
        $results = getGreatCallAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        break;
    case "Emtrac":
        $results = getAllED($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Flagged":
        $results = getTraineeFlagged($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Resolved":
        $results = getTraineeResolved($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    default:
        $results = getTraineeStudiesByDate($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_GET['sec'], $_GET['typ'], $_GET['notes'], $tags);
        break; 
}

if (sizeof($results) == 1000)  {
    echo "Over 1000 results.  Only the first 1000 results displayed.<p>\n";
}

$textprint = getResultsTabDelimited($results);
echo $textprint;

?>
