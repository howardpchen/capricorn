<?php include "capricornLib.php"; ?>
<html>
<head>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<link href="chardinjs.css" rel="stylesheet">

<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>
<script src="<?php echo $URL_root; ?>js/highcharts.js"></script>
<script src="<?php echo $URL_root; ?>js/collapseTable.js"></script>
<script src="<?php echo $URL_root; ?>js/lib/d3/d3.js"></script>
<script src="<?php echo $URL_root; ?>js/d3.layout.cloud.js"></script>
<script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
</head>
<?php

include "header.php";

/*  This code doesn't work just yet.

$reportText = "";

$conn2 = sqlsrv_connect($RISName, $connectionInfo);
$table1 = "vusrExamDiagnosticReportText";

$sql = "select AccessionNumber from `exammeta` WHERE TraineeID='" . $_SESSION['traineeid'] . "' ORDER BY CompletedDTTM DESC LIMIT 0, 10";

$results = $resdbConn->query($sql) or die (mysqli_error($resdbConn));
$results = $results->fetch_all();
foreach ($results as $acc) {
    $sql = "SELECT ReportText FROM $table1 WHERE AccessionNumber='" . $acc[0] . "'";

    $result = sqlsrv_query($conn2, $sql); 
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))  {
        $reportText .= $row['ReportText'];
    }
}
$reportText = str_replace(array("<P>", "</P>", "/", "(", "?", "!", "[", ".", "=", "$", "'", "€", "%", "-", "]", ")", "\"", ":"), " ", $reportText);

$reportText = preg_split('/\s+/', $reportText);;
*/
?>
<div class="row">
<font style="font-size:12pt;">
<P>Capricorn Word Cloud constructs a <a href="https://en.wikipedia.org/wiki/Tag_cloud">word cloud</a> from your interpretation reports.</P>
<p>It is still under development.</p>
<p>Check back later!</p>
<!--
<script>
  var fill = d3.scale.category20();

  d3.layout.cloud().size([300, 300])
      .words([

        "Hello", "Hello", "Hello", "Hello", "Hello", "more", "words",
        "than", "this"
        
                
        ].map(function(d) {
        return {text: d, size: 10 + Math.random() * 90};
      }))
      .padding(5)
      .rotate(function() { return ~~(Math.random() * 2) * 90; })
      .font("Impact")
      .fontSize(function(d) { return d.size; })
      .on("end", draw)
      .start();

  function draw(words) {
    d3.select("body").append("svg")
        .attr("width", 300)
        .attr("height", 300)
      .append("g")
        .attr("transform", "translate(150,150)")
      .selectAll("text")
        .data(words)
      .enter().append("text")
        .style("font-size", function(d) { return d.size + "px"; })
        .style("font-family", "Impact")
        .style("fill", function(d, i) { return fill(i); })
        .attr("text-anchor", "middle")
        .attr("transform", function(d) {
          return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
        })
        .text(function(d) { return d.text; });
  }
</script>
-->

<a href="./checklogin.php">Back</a>
</font>
</div>
<?php include "footer.php"; ?>



