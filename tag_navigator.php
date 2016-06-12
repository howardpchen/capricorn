<?php

include_once "capricornLib.php";

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

$currentTags = getUserTags($_SESSION['traineeid'], $_GET['acc']);
$sharedTags = getSharedTags($_SESSION['traineeid'], $_GET['acc']);
$adminTagAll = getUserTags($adminTraineeID, $_GET['acc']);
$adminTags = array();
foreach ($adminTagAll as $t)  { // Remove the tags that start with * which are hardcoded as private to the Admin.
    if ($t[0] != $adminPrivateTagPrefix) $adminTags []= $t;
}
$systemTags = getSystemTags($_SESSION['traineeid'], $_GET['acc']);
$allTags = getUserTags($_SESSION['traineeid']) ;
$allSharedTags = getSharedTags();
$fav0 = getGoodCaseCount("Vote", $_GET['acc']);
?>
<script>
fav0 = <?php echo $fav0 ?>;
currentTags = <?php echo sizeof($currentTags)>0?"['".implode("', '", $currentTags)."']":'new Array();'; ?>;
sharedTags = <?php echo sizeof($sharedTags)>0?"['".implode("', '", $sharedTags)."']":'new Array();'; ?>;
systemTags = <?php echo sizeof($systemTags)>0?"['".implode("', '", $systemTags)."']":'new Array();'; ?>;
adminTags = <?php echo (sizeof($adminTags)>0 && !isAdmin())?"['".implode("', '", $adminTags)."']":'new Array();'; ?>;
allTags = <?php echo sizeof($allTags)>0?"['".implode("', '", $allTags)."']":'new Array();'; ?>;
allSharedTags = <?php echo sizeof($allSharedTags)>0?"['".implode("', '", $allSharedTags)."']":'new Array();'; ?>;
$(function() {
    for (var i=0; i<systemTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+systemTags[i].replace(/\s/g, '').replace('#', '-') + "' class='tag system'><div class='fa fa-tag'>&nbsp;</div>" + systemTags[i] + "</div>");
    }
    for (var i=0; i<adminTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+adminTags[i].replace(/\s/g, '').replace('#', '-') + "' class='tag admin' title='Tag set by program director'><div class='fa fa-tag'>&nbsp;</div>" + adminTags[i] + "</div>");
    }
    for (var i=0; i<sharedTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+sharedTags[i].replace(/\s/g, '').replace('#', '-') + "' class='tag shared' title='Shared hashtag'><div class='fa fa-tag'>&nbsp;</div>" + sharedTags[i] + "</div>");
    }
    
    for (var i=0; i<currentTags.length; i++) {
        $("#userTags").append("<div id='" + "tag_"+currentTags[i].replace(/\s/g, '').replace('#', '-') + "' class='tag" + (currentTags[i].charAt(0)=='#'?' shared':'') + "'><div class='fa fa-tag'>&nbsp;</div>" + currentTags[i] + "&nbsp;<a onClick='removeTag(\"" + currentTags[i] + "\")' class='fa fa-times nobutton' href='javascript:void(0)'></a></div>");
    }
    for (var i=0; i<allTags.length; i++) {
        $("#tagSelect").append("<option class='tagselect' id='" + allTags[i] + "' value='" + allTags[i] + "'>&nbsp;&nbsp;&nbsp;&nbsp;" + allTags[i])
    }
        $("#tagSelect").append("<option class='tagselect shared' value=''>(Shared Hashtags)");
    for (var i=0; i<allSharedTags.length; i++) {
        $("#tagSelect").append("<option class='tagselect shared' style='background:#DCF' id='" + allSharedTags[i] + "' value='" + allSharedTags[i] + "'>&nbsp;&nbsp;&nbsp;&nbsp;" + allSharedTags[i])
    }
        
        $("#tagSelect").append("<option value='#new#' id='makeNewTag'>(Create New Tag)");
    checkEnableNewTag();
    // Initialize tag button texts
    $('#VoteButton').text('Vote (' + (fav0) + ')')
});    
function checkEnableNewTag()  {
    if ($("#tagSelect").val() == '#new#')  {
        $('#newTagName').fadeIn(300);
    }
    else {
        $('#newTagName').hide();
        $('#tagValue').val($("#tagSelect").val());
    }
}
function addTag(tag)  {
    if (tag == '') return;
    if (tag == '#new#') {
        // Check validity of the tag.
        if ($('#newTagName').val() == '') {
            alert('Your tag cannot be empty.');
            return false;
        } else  {
            tag = $("#newTagName").val();
            if (tag.match(/[^#a-zA-Z\d\s:]/))  {
                alert('Only letters, numbers, underscore (_), and hashtag (#) are allowed in tag names.');
                return false;
            }
            return addTag($("#newTagName").val());
        }
    }
    else if (currentTags.indexOf(tag) >= 0)  {
        alert('This study has already been tagged with "' + tag + '"');
        return false;
    } else {
        $("#tagOperation").val('add');
        $("#tagValue").val(tag);
    }
    $.ajax({
        type: "POST",
        url: "<?php echo $URL_root; ?>/tag_ops.php",
        data: $("#tagForm").serialize(), // serializes the form's elements.
        success: function(data) {
            if (data.trim() == "successful") {
                $("#userTags").append("<div id='" + "tag_"+ tag.replace(/\s/g, '').replace('#','-') + "' class='tag" + (tag.charAt(0)=='#'?' shared':'') + "'><div class='fa fa-tag'>&nbsp;</div>" + tag + "&nbsp;<a onClick='removeTag(\"" + tag + "\")' class='fa fa-times nobutton' href='javascript:void(0)'></a></div>");
                currentTags.push(tag);
            }
            else alert("Error: " + data);
        }
    });
}

function removeTag(tag)  {
    $("#tagOperation").val('remove');
    $("#tagValue").val(tag);

    $.ajax({
        type: "POST",
        url: "<?php echo $URL_root; ?>/tag_ops.php",
        data: $("#tagForm").serialize(), // serializes the form's elements.
        success: function(data) {
            if (data.trim() == "successful") {
                $("#tag_"+tag.replace(/\s/g, '').replace('#','-')).remove();
                var i = currentTags.indexOf(tag);
                if (i != -1) {
                    currentTags.splice(i, 1);
                }
            }
            else alert("Error: " + data);
        }
    });
}

function doGoodCase(tag)  {
    var i = currentTags.indexOf(tag);
    if (i < 0)  {
        $('#' + tag + 'Button').text(tag + ' (' + (++fav0) + ')'); 
        addTag(tag); 
    } else  {
        $('#' + tag + 'Button').text(tag + ' (' + (--fav0) + ')'); 
        removeTag(tag);
    }
}
</script>

<form id="tagForm" method="post" onSubmit='return false'>
<input name="acc" type="hidden" value="<?php echo $_GET['acc'];?>">
<input id="tagTrainee" type="hidden" name="traineeid" value="<?php echo $_SESSION['traineeid']; ?>">
<input id="tagValue" type="hidden" name="tag">
<input id="tagOperation" name="operation" type="hidden" value="add">
</form>
<div id="addTagPanel">
    <form onSubmit="return false;">
    <div class='fa fa-tag'></div><select onChange="javascript:checkEnableNewTag()" id="tagSelect" name="tagSelect">
    <option style='background:#CDF;' value=''>(Personal Tags)
    </select><input type='text' id='newTagName' size=15 maxlength=25 name='newTagName'>
    <input type=button value="Add" onClick="addTag($('#tagSelect').val())">
    <a class="vote" id='VoteButton' onClick="doGoodCase('Vote')">Vote</a>
    </form>
</div>

