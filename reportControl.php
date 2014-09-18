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

/*
    This script is included by displayReport.php when it detects that the 
    administrator is interested in reviewing discrepancy reports.
*/

$autoDisc = getDiscrepancyByAccession($_GET['acc']);
$adminDisc = getDiscrepancyByAccession($_GET['acc'], "AdminDiscrepancy");
$adminComment = getDiscrepancyByAccession($_GET['acc'], "AdminComment");
$traineeMark = getDiscrepancyByAccession($_GET['acc'], "TraineeMarkAsReviewed");
$traineeComment = getDiscrepancyByAccession($_GET['acc'], "TraineeComment");
?>


<script>
accession = "<?php echo $_GET['acc']; ?>";
discrepancy = "<?php echo $autoDisc; ?>";
adminDiscrepancy = "<?php echo $adminDisc; ?>";
traineeMark = <?php echo isset($traineeMark)?$traineeMark:0; ?>;

$(function() {
    var t = true;
    for (var i = 0; i < cookieList.length; i++)  {
        if (cookieList[i] == accession)  {
            t = false;
        }
    }
    if (t) {
        $("#prevbutton").remove();
        $("#nextbutton").remove();
    }
});

cookieList = document.cookie.replace(/(?:(?:^|.*;\s*)acc\s*\=\s*([^;]*).*$)|^.*$/, "$1");
cookieList = cookieList.split(',');

function go(index)  {
    for (var i = 0; i < cookieList.length; i++)  {
        if (cookieList[i] == accession)  {
            newIndex = (cookieList.length+i+index)%cookieList.length;
            location.href=location.protocol + '//' + location.host + location.pathname + "?acc=" + cookieList[newIndex];
        }
    }
}
function saveInput(frm, lbl)  {
    $.ajax({
        type: "POST",
        url: "<?php echo $URL_root; ?>/traineeReviewSave.php",
        data: $("#traineeInput").serialize(), // serializes the form's elements.
        success: function(data) {
            if ($.trim(data) == "Save Success")  {
                $("#saveLabel").show();
                $("#saveLabel").html('Saved!');
                $("#saveLabel").fadeOut(1500);
            }
            else {
                $("#saveLabel").show();
                $("#saveLabel").html('Error when saving.');
            }
        }
    });
}

</script>
<center><input id='prevbutton' type=button value='Prev' onClick='go(-1)'>
<input id='nextbutton' type=button value='Next' onClick='go(1)'></center>
<?php tableStartSection("Tag and Review", 0, (in_array($autoDisc, $discrepancyReviewRequired) || in_array($adminDisc, $discrepancyReviewRequired))?False:False);?><br><!--Set to False:True if you want the tags menu to collapse -->
<div class='control' style='background:none' id="review">
<?php 

include "tag_navigator.php";
if (interptedByTrainee($_GET['acc'], $_SESSION['traineeid']))  {
    $acc = $_GET['acc'];
    echo <<< END
<form id="traineeInput" method="post" action="$URL_root/traineeReviewSave.php" onSubmit="return false;">
    <input type="hidden" name="acc" value="$acc">
END;

    if (in_array($autoDisc, $discrepancyReviewRequired) || in_array($adminDisc, $discrepancyReviewRequired))  {
        $selected = ['', '' ,'' ,''];
        $selected[$traineeMark] = 'selected';
        echo <<< END
            Review: <select id='traineeMark' name='traineeMark' onChange='saveInput()'>
            <option value=0 $selected[0]>Unreviewed
            <option value=1 $selected[1]>I have reviewed this study
            <option value=2 $selected[2]>Flag this study for second review 
            <option value=3 $selected[3]>Resolved - Marked by PD after second look
            </select>
            <input type=button value='Review' onClick="$('#traineeMark').val(1)&&saveInput();">
            <br>
END;
    }
    echo <<< END
    Comments: <textarea name='traineeComment' maxlength=255 onChange='saveInput()'>$traineeComment</textarea><br>
</form>
END;
    if (trim($adminComment) != '') echo "<strong>Director Comments:</strong> " . $adminComment."<br>";

    echo <<< END
        <label id="saveLabel"></label><br>
        <input type=button value='Save' onClick='saveInput()'>
END;
}
?>
</div>


<?php tableEndSection();?>

