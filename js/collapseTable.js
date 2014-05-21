$(function() {
    $( document ).tooltip();
});

function toggle_visibility(tbid,lnkid)
{
  if(document.all){
  	if (document.getElementById(tbid).style.display == "none")  {
		document.getElementById(tbid).style.display = "block";
	} else {
		document.getElementById(tbid).style.display = "none";
	}
}
  else {
  	if (document.getElementById(tbid).style.display == "none")  {
		$('#'+tbid).slideDown(250);
		//document.getElementById(tbid).style.display = "table";
	} else {
		$('#'+tbid).slideUp(250);
		//document.getElementById(tbid).style.display = "none";
	}
}

  document.getElementById(lnkid).value = document.getElementById(lnkid).value == "[-]" ? "[+]" : "[-]";
 }


function collapse_all(num) {
    for (i = 1; i <= num; i++){
        document.getElementById("tbl" + i).style.display = "none";
        document.getElementById("lnk" + i).value = "[+]";
    }
    return false;

}
function expand_all(num) {
    for (i = 1; i <= num; i++){
        if(document.all){document.getElementById("tbl" + i).style.display = "block";}
        else{document.getElementById("tbl" + i).style.display = "table";}
        document.getElementById("lnk" + i).value = "[-]";
    }
    return false;
}
