<!DOCTYPE HTML>
<!--
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
-->
<?php
include "capricornLib.php";
?>

<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1">

    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,700,800" rel="stylesheet" type="text/css" />
    <script src="<?php echo $URL_root; ?>js/jquery.min.js"></script>
    <script src="<?php echo $URL_root; ?>js/jquery.dropotron.min.js"></script>
    <script src="<?php echo $URL_root; ?>js/config.js"></script>
    <script src="<?php echo $URL_root; ?>js/skel.min.js"></script>
    <script src="<?php echo $URL_root; ?>js/skel-panels.min.js"></script>
    <noscript>
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/skel-noscript.css" />
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/style.css" />
        <link rel="stylesheet" href="<?php echo $URL_root; ?>css/style-desktop.css" />
    </noscript>
  <title>Capricorn - Progress made simple.</title>
</head>

<body class="homepage">

		<!-- Header Wrapper -->
			<div id="header-wrapper" >
				<div class="container">
					<div class="row">
						<div class="12u">
							<!-- Banner -->
                            <div id="banner">
                                <div class="row" style="margin-top:20%">
                                    <div class="6u">
                                        <a href="."><img alt="logo" src="logo.png"></a>
                                    </div>
                                    <div class="6u">
                                        <form name="form1" method="post" action="checklogin.php">
                                        <input name="myusername" placeholder="Login" class='login' type="text" id="myusername" size="10">&nbsp;
                                        <input name="mypassword" placeholder="Password" class='login' type="password" id="mypassword" size="10">&nbsp;
                                        <input type="submit" value="Go" name="Submit" class="login">
                                        </form>
                                    </div>
                                    <div class="12u" style="margin-top:4em">
<!--[if lte IE 9]>
You are using an older browser.  Although there are cosmetic differences, key functionalities are available.  <br><br>
<![endif]-->
                                   Your Capricorn login and password is same that you use for UPHS email.  Please email Po-Hao (Howard) Chen if you have trouble logging in.  <br> [ <a style='color:#7799ff;text-decoration:none;' href="help.php" target="_blank">Having Problems?</a> | <a style='color:#7799ff;text-decoration:none;' href="http://capricornradiology.org/" target="_blank">What is Capricorn?</a> ]</div>
                                </div>
                                <div class="row" style="margin-top:10%;margin-bottom:20%">
                                    <div class="12u">
<!--
This section can be replaced with your own update logs to inform your users.
-->
                                    <textarea class="changelog" disabled>

                                    </textarea>
                                    <br>By accessing Capricorn, you agree to its <a href='terms.php' a style='color:#7799ff;text-decoration:none;'>Terms of Service</a>
                                    </div>
                                </div>
                            </div>

						</div>
					</div>
				</div>
			</div>

		<!-- Footer Wrapper -->
            <div id="footer-wrapper">
				<footer id="footer" class="container">
					<div class="row">
						<div class="12u">
							<div id="copyright">
								Copyright 2014 Howard Chen | Design based on <a href="http://html5up.net/">HTML5 UP</a>
							</div>
						</div>
					</div>
				</footer>
			</div>


	</body>
</html>


<body style="font-family: Arial,Helvetica,sans-serif">


</body>
</html>
