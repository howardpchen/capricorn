<body>
        <link href="<?php echo $URL_root; ?>css/chardinjs.css" rel="stylesheet">
		<script src="<?php echo $URL_root; ?>js/jquery.dropotron.min.js"></script>
		<script src="<?php echo $URL_root; ?>js/config.js"></script>
		<script src="<?php echo $URL_root; ?>js/skel.min.js"></script>
		<script src="<?php echo $URL_root; ?>js/skel-panels.min.js"></script>
        <script type='text/javascript' src="<?php echo $URL_root; ?>js/chardinjs.min.js"></script>
   
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/skel-noscript.css" />
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/style.css" />
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/style-desktop.css" />
		<!--[if IE 9]><link rel="stylesheet" href="<?php echo $URL_root; ?>css/ie9.css" /><![endif]-->
		<!--[if IE 8]><script src="<?php echo $URL_root; ?>js/html5shiv.js"></script><link rel="stylesheet" href="<?php echo $URL_root; ?>css/ie8.css" /><![endif]-->
		<!--[if IE 7]><link rel="stylesheet" href="<?php echo $URL_root; ?>css/ie7.css" /><![endif]-->

<script>
$(document).ready(function(){
    $('#loading').fadeOut(200);
});
</script>
<div id="loading">
  <img id="loading-image" src="<?php echo $URL_root; ?>css/images/loader.gif" alt="Loading..." />
</div>

							<!-- Header -->
			<div id="header-wrapper">
				<div class="container">
					<div class="row">
						<div class="12u">
								<header id="header">
									<div class="inner">
									
										<!-- Logo -->
											<h1><a href="<?php echo $URL_root; ?>checklogin.php" id="logo"><img src='<?php echo $URL_root; ?>logo_small.png' height=35></a></h1>
										
										<!-- Nav -->
											<nav id="nav">
												<ul>
													<li><a href="<?php echo $URL_root; ?>checklogin.php">Home</a></li>
													<li><a href="#" onclick="$('body').chardinJs('toggle')" >Help</a></li>
													<li><a href="<?php echo $URL_root; ?>logout.php">Logout</a></li>
												</ul>
											</nav>
									
									</div>
								</header>
<article>
	<header class="login">
<h3 align=left>
<?php
if (isset($_SESSION['traineeid'])) {
    echo getLoginUserFullName();
} else echo "Welcome";
?>
</h3>
	</header>
</article>
						</div>
					</div>
				</div>
			</div>
			<div id="main-wrapper">
				<div class="main-wrapper-style2">
					<div class="inner">
						<div class="container">
<!--[if lte IE 9]>
You are using an older browser.  Some features may not work as expected. Please consider updating your browser to the newest version.
<![endif]-->
<!-- <h4 align=center><font color="gray">Data is ficticious and for demonstration purpose only.</font></h4> -->

							<div class="row">
								<div class="12u skel-cell-important">
									<div id="content">




<table width="1020" border="0" align="center" cellpadding="0" cellspacing="1">
<tr><td>


