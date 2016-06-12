
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
		//$startDate = thisJulyFirst();
		$startDate = date_create('2014-07-01'); // Hard coded to when this system went live.
        $results = getTraineeMajorUnreviewed($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Major":
        $results = getMajorChangeAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Minor":
        $results = getTraineeMinor($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Addition":
        $results = getTraineeAddition($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "GreatCall":
        $results = getGreatCallAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "EDNotify":
        $results = getAllED($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Flagged":
        $results = getTraineeFlagged($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
    case "Resolved":
        $results = getTraineeResolved($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $_SESSION['traineeid']);
        break;
	case "All":
		$results = getTraineeStudiesByDiscrepancy($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), '', True, $_SESSION['traineeid']);
		break;
	case "PrevCall":
        $startDate = date_create('NOW');
        $endDate = date_create('NOW');
        $startDate->sub(new DateInterval('P7D')); 
		$results = getPrevCall($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
		break;
    case "GreatCall4Pantel":
        if ($_SESSION['traineeid'] != '1331' && $_SESSION['traineeid'] != '1658') exit();
        $results = getGreatCallAll($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        break;
    
    default:
        $results = getTraineeStudiesByDate($startDate->format('Y-m-d'),
        $endDate->format('Y-m-d'), $_GET['sec'], $_GET['typ'],
        $_GET['notes'], $tags, False);
        break; 
}
// Takes AJAX request up here and return the calculation results.
if (isset($_GET['ajax']))  {
    echo $_GET['ajax'] . "," . $results->num_rows;
    exit();
}


if (sizeof($results) == 1000)  {
    echo "Over 1000 results.  Only the first 1000 results displayed.<p>\n";
}



if (isset($_GET['header']))  {
	echo <<< END
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/theme.blue.css" />
	<script src="$URL_root/js/jquery-1.9.1.js"></script>
	<script src="$URL_root/js/jquery.tablesorter.min.js"></script>
	<script src="$URL_root/js/jquery.tablesorter.widgets.js"></script>
END;
	include_once "header.php";
} 

else  {
	echo <<< END
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/theme.blue.css" />
	<script src="$URL_root/js/jquery.tablesorter.min.js"></script>
	<script src="$URL_root/js/jquery.tablesorter.widgets.js"></script>
END;
}

?>

Previous night's cases are overread in the morning and updated ~6:30pm. <br>
<a href="showstudy_excel.php?<?php echo http_build_query($_GET);?>">Export to Excel</a><p>

<?php


$htmlprint = getResultsHTML($results);
$accessions = array();
foreach ($results as $r) {
    $accessions []= $r['Accession'];
}

echo $htmlprint;

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


// construct the cookie which allows "Next" and "Prev" function in the report display.
document.cookie = "acc=<?php echo implode(",",$accessions);?>; path=/";

currentStudy = null;
studyList = [<?php echo implode(", ",$accessions);?>];
function updateCurrentStudy(acc)  {
    //Check the current study being displayed.
    //if (acc == null) newStudy = document.cookie.replace(/(?:(?:^|.*;\s*)currentStudy\s*\=\s*([^;]*).*$)|^.*$/, "$1");
    newStudy = acc;
    if (newStudy != null && newStudy != currentStudy)  {
        currentStudy = newStudy;
    }

    for (var i = 0; i < studyList.length; i++)  {
        if (studyList[i] == currentStudy)  {
            document.getElementById(studyList[i].toString()).className = 'currentStudy';    
        }
        else document.getElementById(studyList[i].toString()).className = 'initial';
    }
}
</script>

</body>
