<?php include "capricornLib.php";?>
<!doctype html>
<html>
<head>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />
<link href="<?php echo $URL_root; ?>css/chardinjs.css" rel="stylesheet">
<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
<Title>Capricorn - Home </title>
</head>
<?php include "header.php"; 
$adminTagAll = getAdminTagsForUser($_SESSION['traineeid']);
$adminTags = array();
foreach ($adminTagAll as $t)  { // Remove the tags that start with * which are hardcoded as private to the Admin, and start with # which are hardcoded as Shared tag
    if ($t[0] != '*' && $t[0] != '#') $adminTags []= $t;
}
$userTags = getUserTags($_SESSION['traineeid']) ;
$sharedTags = getSharedTags();
?>

<?php //include "stats_classcomparison.php"; ?>

<script>


ajaxQueue = new Object();
ajaxQueue["majorAttestCount"] = "MajorAttest";
ajaxQueue["majorCount"] = "Major";
ajaxQueue["minorCount"] = "Minor";
ajaxQueue["additionCount"] = "Addition";
ajaxQueue["ednotifyCount"] = "EDNotify";
ajaxQueue["flagCount"] = "Flagged";
ajaxQueue["resolvedCount"] = "Resolved";
ajaxQueue["gcCount"] = "GreatCall";

ajaxVars = new Array();

loadingString = '<img src="<?php echo $URL_root;?>/css/images/loader_small.gif">';


adminTags = <?php echo sizeof($adminTags)>0?"['".implode("', '", $adminTags)."']":'new Array();'; ?>;
userTags = <?php echo sizeof($userTags)>0?"['".implode("', '", $userTags)."']":'new Array();'; ?>;
sharedTags = <?php echo sizeof($sharedTags)>0?"['".implode("', '", $sharedTags)."']":'new Array();'; ?>;

$(function() {
	$("#userTags").append("<div id='NoTag' class='tag' onClick='toggleAndCheckTag()'><div class='fa fa-tag'>&nbsp;</div>All</div>");
    for (var i=0; i<userTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+userTags[i].replace(/\s/g, '').replace('#','-') + "' class='tag unselected' onClick='toggleAndCheckTag(\"" + userTags[i] + "\")'><div class='fa fa-tag'>&nbsp;</div>" + userTags[i] + "</div>");
    }
    for (var i=0; i<adminTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+adminTags[i].replace(/\s/g, '').replace('#','-') + "' class='tag admin unselected' onClick='toggleAndCheckTag(\"" + adminTags[i] + "\")'><div class='fa fa-tag'>&nbsp;</div>" + adminTags[i] + "</div>");
    }

    $("#userTags").append("<br>");

    for (var i=0; i<sharedTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+sharedTags[i].replace(/\s/g, '').replace('#','-') + "' class='tag shared unselected' onClick='toggleAndCheckTag(\"" + sharedTags[i] + "\")'><div class='fa fa-tag'>&nbsp;</div>" + sharedTags[i] + "</div>");
    }
    toggleAndCheckTag();
$( "#from" ).datepicker({
changeMonth: true,
numberOfMonths: 1,
onClose: function( selectedDate ) {
$( "#to" ).datepicker( "option", "minDate", selectedDate);
}
});
$("#from").datepicker('setDate', -1);
$( "#callFrom" ).datepicker({
changeMonth: true,
numberOfMonths: 1
});
$("#callFrom").datepicker('setDate', -2);

$( "#to" ).datepicker({
changeMonth: true,
numberOfMonths: 1,
onClose: function( selectedDate ) {
$( "#from" ).datepicker( "option", "maxDate", selectedDate );
}
});

$("#to").datepicker('setDate', 1);
//updateAjax();


// Major Discrepancy count - display by the main screen on login.
$("#majorWarnCount").html(loadingString);

var aj = $.ajax({
	url: "<?php echo $URL_root; ?>/showstudy.php?ajax=majorWarnCount&mode=MajorAttest",
	success: function(data) {
		var d = $.trim(data).split(',');
		if (d[1] == "0") d[1] = "no"
		$("#majorWarnCount").text("You have " + d[1] + " required studies pending review.");
	}
});

//-----------

});

function loadList(keyword)  {
	from = $('#discSince').val();
	day = "9999";
    loadDialog('Discrepancies', '<?php echo $URL_root;?>showstudy.php?mode=' + escape(keyword) + "&from=" + from + "&day=" + day, false);

}

function updateAjax()  {
	ajaxVars = [];
    for (var key in ajaxQueue)  {
        if (ajaxQueue.hasOwnProperty(key))  {
            var val = ajaxQueue[key];
			from = $('#discSince').val();
			day = "9999";
            $("#"+key).html(loadingString);
            var aj = $.ajax({
                url: "<?php echo $URL_root; ?>/showstudy.php?ajax="+key+"&mode="+val+"&day="+day+"&from="+from,
                success: function(data) {
                    var d = $.trim(data).split(',');
					if (d[0] == 'majorAttestCount' && Number(d[1])>0)  {
						$("#"+d[0]).css("color", "#C00");
					}
					if (d[0] == 'flagCount' && Number(d[1])>0)  {
						$("#"+d[0]).css("color", "#C00");
					}
                    $("#"+d[0]).text("Studies: " + d[1]);
                }
            });
			ajaxVars.push(aj);
        }
    }
}

function toggleAndCheckTag(tag)  {
    if (tag != null) {
        var tagName = tag.replace(/\s/g, '').replace('#','-');
        if ( $("#tag_" + tagName).hasClass("unselected") ) $("#tag_" + tagName).removeClass("unselected");
        else $("#tag_" + tagName).addClass("unselected");
    } else  {
        var tagElements = $('[id^=tag_]');
        tagElements.each(function () {
            if (!$(this).hasClass('unselected')) {
                $(this).addClass('unselected');
            }
        });
    }
    selectedTagNames = new Array();
    for (var i = 0; i < adminTags.length; i++)  {
        var tagName = adminTags[i].replace(/\s/g, '').replace('#','-');
        if (!$("#tag_" + tagName).hasClass('unselected'))  {
            selectedTagNames.push(adminTags[i]);
        }
    }
    for (var i = 0; i < userTags.length; i++)  {
        var tagName = userTags[i].replace(/\s/g, '').replace('#','-');
        if (!$("#tag_" + tagName).hasClass('unselected'))  {
            selectedTagNames.push(userTags[i]);
        }
    }
    for (var i = 0; i < sharedTags.length; i++)  {
        var tagName = sharedTags[i].replace(/\s/g, '').replace('#','-');
        if (!$("#tag_" + tagName).hasClass('unselected'))  {
            selectedTagNames.push(sharedTags[i]);
        }
    }    
    if (selectedTagNames.length == 0)  {
        $('#NoTag').removeClass('unselected');
    } else  {
        $('#NoTag').addClass('unselected');

    }
    $('#tags').val(selectedTagNames.join(","));
}

function loadSearch(formName) {
    $.ajax({
        type: "GET",
        url: "<?php echo $URL_root; ?>/showStudy.php",
        data: $("#" + formName).serialize(), // serializes the form's elements.
        success: function(data) {
            loadDialog('Review My Studies', data, true);
        }
    });
}

function loadReport(access) {
    var win = window.open("<?php echo $URL_root;?>displayReport.php?acc=" + access, "rep", "scrollbars=yes, toolbar=no, status=no, menubar=no, width=1000, height=768"); win.focus();
}

function loadDialog(myTitle, data, isHTML) {
    var tos_dlg = $("<div id='dia'></div>")
        .dialog({
            autoOpen: true,
            title: myTitle,
            width: 1000,
            height: 600,
            modal: true,
            closeOnEscape: true,
            open: function() {
            jQuery('.ui-widget-overlay').bind('click', function() {
                $("#dia").remove();
                });
            jQuery('.ui-dialog-titlebar-close').blur();
            }
        }); 
    if(isHTML) tos_dlg.html(data);
    else tos_dlg.load(data);
}

function clickInterval(a) {
/*    if (a < -90)  {
        document.getElementById('mod').value = document.getElementById('mod').value==''?'CR':document.getElementById('mod').value;
    }
*/
    $("#from").datepicker('setDate',a);
    $("#to").datepicker('setDate', 1);
    $("#range").submit();
}


</script>

<?php
tableStartSection("Discrepancy Worklist",0);
?><br>
<div class="row" data-intro="Click on a button to view the data.">
<div class="6u">
<center>
<a class="mainMenuButton" href="javascript:loadSearch('reviewAll');"><div class='fa fa-check'>&nbsp;</div>Review Cases From</a>
<form id='reviewAll'>
    <input type="text" style="font-size:12pt;background-color:#cdf;border-radius:5px;border-color:#AAF;font-weight:900; color:#000;" size=10 id="callFrom" name="from" />
	<select name="day" title="Need more options? Use Advanced functions below.">
		<option value='2' selected>over 2 days
		<option value='7'>over 7 days
		<option value='14'>over 14 days
		<option value='99999'>everything since

	</select>
	<input type="hidden" value='All' name="mode" />
	<input type="hidden" value='' name="sec" />
	<input type="hidden" value='' name="typ" />
	<input type="hidden" value='' name="notes" />

</form>
</center>
</div>
<div class="6u">
<center><a class="mainMenuButton" href="javascript:$('#discrep').slideDown(500)&&updateAjax();"><div class='fa fa-sort-amount-asc'>&nbsp;</div>By Discrepancy Types</a><br>
<form><select id='discSince'>
<option value='<?php echo thisJulyFirst()->format('Y-m-d');?>'>This Academic Year Only (July 1)
<option value='1990-01-01'>Show Everything
</select></form>
<div id="majorWarnCount"></div>
</center>
</div>
</div>

<div id="discrep" class="row" style="display:none">
<div class="3u">
<center><a class="mainMenuButton" href="javascript:loadList('MajorAttest');">Pending Review</a>
<p id="majorAttestCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Major');"><div class='fa fa-exclamation-circle'>&nbsp;</div>Major</a>
<p id="majorCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('EDNotify');"><div class='fa fa-ambulance'>&nbsp;</div>ED Notify</a>
<p id="ednotifyCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('GreatCall');"><div class='fa fa-thumbs-o-up'>&nbsp;</div>Great Call</a>
<p id="gcCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Minor');">Minor</a>
<p id="minorCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Addition');">Addition</a>
<p id="additionCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Flagged');">Flagged</a>
<p id="flagCount"></p>
</center>
</div>
<div class="3u">
<center>
<a class="mainMenuButton" href="javascript:loadList('Resolved');">Resolved</a>
<p id="resolvedCount"></p>
</center>
</div>
</div>
<div class="row">
<div class="6u">
<center><a class="mainMenuButton" href="reviewByGroup.php"><div class='fa fa-bar-chart-o'>&nbsp;</div>Analyze</a><br></center>
</div>
<div class="6u">
<center>
<a class="mainMenuButton" href="<?php echo $URL_root;?>showstudy.php?sec=&typ=&notes=&tags=%23CallTeachingFiles&header=Y"><div class='fa fa-star-o'>&nbsp;</div>Teaching Files</a>
</center>
</div>

</div>
<?php
tableEndSection();
?>

<p>
<?php
tableStartSection("Volumetrics",0, False);
?><br>
<div class="row" data-intro="Click on a button to view the data.">
<div class="4u" align="center"> <a class="mainMenuButton" href="browse.php"><div class='fa fa-folder-open-o'>&nbsp;</div>Rotation Volume</a></div>
<?php
if (isResident()) {
echo <<< END
<div class="4u" align="center"><a class="mainMenuButton" href="calls.php"><div class='fa fa-phone'>&nbsp;</div>Call Volume</a><br>Want to review call reports? See above.</div> 
<div class="4u" align="center"><a class="mainMenuButton" href="statistics.php"><div class='fa fa-bar-chart-o'>&nbsp;</div>Analyze</a></div>
END;

}

?>
</div>
<div class="row">
<div class="6u" align="center"> <a class="mainMenuButton"
href="studylog.php"><div class='fa fa-list'>&nbsp;</div>Full UPHS Case Log</a><br><a href="studylog_txt.php">Text Version (faster)</a></div>

</div>

</div>
<?php
tableEndSection();
?>
<p>

<?php 
tableStartSection("Advanced", 0, False);
?>
<div class="row">
<div class="6u">
<strong>Review My Cases / Teaching Cases </strong>
<form id='searchForm'>
    <label for="from" >From</label>
    <input type="text" size=10 id="from" name="from" />
    <label for="to">to</label>
    <input type="text" size=10 id="to" name="to"/> 
    <input type=button onClick="loadSearch('searchForm')" value="Go"><br>
    <input name="tags" id="tags" type="hidden" value="">
    Preset Dates: [<a href="javascript:void(0)" onclick="clickInterval(-1)">2 days</a> | <a href="javascript:void(0)" onclick="clickInterval(-365)">1 year</a> | <a href="javascript:void(0)" onclick="clickInterval(-1460)">4 years</a>]
    <br>

    Legend:
    <div class="tag">Personal</div>
    <div class="tag admin">Program Director</div>
    <div class="tag shared">Shared Hashtag</div>
    <div style='width:100%' id='userTags'></div>
    
<?php
tableStartSection("More Options",0, True);
?>

    Section: <select id='searchSec' name='sec'>
    <option value='' selected>All</option>
<?php

$examSelection = getExamCodeData('Section', NULL, 'ORDER BY Section ASC');

foreach ($examSelection as $sel) {
    $short = $sel[0];
    $long = codeToEnglish($short);
    echo "<option value='" . $short . "'>" . $long . "</option>\n";
}

?></select><br>
    Modality: <select id='searchTyp' name='typ'>
    <option value='' selected>All</option>
<?php

$examSelection = getExamCodeData('Type', NULL, 'ORDER BY Type ASC');

foreach ($examSelection as $sel) {
    $short = $sel[0];
    $long = codeToEnglish($short);
    echo "<option value='" . $short . "'>" . $long . "</option>\n";
}

?></select><br>

    Special Notation:<select id='searchNotes' name='notes'>
    <option value='' selected>All</option>
<?php

$examSelection = getExamCodeData('Notes', NULL, 'ORDER BY Notes ASC');

foreach ($examSelection as $sel) {
    $short = $sel[0];
    echo "<option value='" . $short . "'>" . $short. "</option>\n";
}

?></select><br>
    Hint: If you are not getting the right results, check your date range.<br>
    <input type=button onClick="loadSearch('searchForm')" value="Go">

    
    </form>
    <p style='font-size:8pt'><strong>Examples (Section - Modality - Special):</strong><br>
<u>Coronary CTA</u>: CVI - CT Angiography - CARDIAC <br>
<u>PE Chest CT</u>: Chest - All - PULM EMB<br>
<u>Drainage</u>: All - All - DRAINAGE <br>
<u>I-131 Tx</u>: Nuclear Medicine - Procedures - All <br>
</p>
<?php tableEndSection(); ?>
</div>
<div class="6u"><strong>Report by Accession </strong>
    <form action="javascript:loadReport(document.getElementById('reportByAcc').value)"><input type=text id='reportByAcc' size=15 maxlength=15 name='acc'><input type=button onClick="loadReport(document.getElementById('reportByAcc').value)" value="Go"></form>
</div>
</div>
<?php tableEndSection(); ?>
<p>
<div class="row"><div class="12u">
<p align=center><?php echo $inclusionNote ?></p>
<p align=center><A HREF="logout.php">Log Out</A></P></div>
<?php include "footer.php"; ob_end_flush(); ?>
