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

checkAdmin();
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
traineeMark = <?php echo $traineeMark; ?>;

$(function() {
    $(".control").tabs();
    $("#admin" + discrepancy).next().addClass("highlight");
    $("#admin" + adminDiscrepancy).prop("checked", "true");
});

function saveInput(frm, lbl)  {
    $.ajax({
        type: "POST",
        url: "<?php echo $URL_root; ?>/admin/discrepancyAdminSave.php",
        data: $("#adminInput").serialize(), // serializes the form's elements.
        success: function(data) {
            if (typeof(window.opener.updateCurrentStudy) === "function") window.opener.updateCurrentStudy(<?php echo $_GET['acc']; ?>, data);
            $("#saveLabel").show();
            $("#saveLabel").html('Saved!');
            $("#saveLabel").fadeOut(1500);
        }
    });
}

function go(index)  {
    cookieList = document.cookie.replace(/(?:(?:^|.*;\s*)acc\s*\=\s*([^;]*).*$)|^.*$/, "$1");
    cookieList = cookieList.split(',');
    for (var i = 0; i < cookieList.length; i++)  {
        if (cookieList[i] == accession)  {
            newIndex = (cookieList.length+i+index)%cookieList.length;
            location.href=location.protocol + '//' + location.host + location.pathname + "?acc=" + cookieList[newIndex];
        }
    }
}

</script>
<div class='control'>
<ul>
<li><a href="#review">Review</a></li>
<li><a href="#annotate">Tags</a></li>
</ul>
<div id="review">
        <?php if (trim($adminComment) != '') echo "Admin Comments: " . $adminComment."<br>"; ?>
   
        <?php if (trim($traineeComment) != '') echo "Trainee Comments: " . $traineeComment."<br>"; ?>
        Trainee Review Status: 
		<?php if($traineeMark==0) echo 'Unreviewed';
		else if($traineeMark==1) echo 'Reviewed';
		else if($traineeMark==2) echo 'Flagged for attention';
		else if($traineeMark==3) echo 'Resolved';
		?>	
        <br> 
        <label id="saveLabel"></label><br>
        <input type=button value='Prev' onClick='go(-1)'>
        <input type=button value='Next' onClick='go(1)'>
        </form>
</div>

</div>
<p>
