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
                                        <a href="."><img id="header" alt="logo" src="logo.png"></a>
                                    </div>
                                    <div class="6u">
                                        <form name="form1" method="post" action="checklogin.php">
                                        <input name="myusername" placeholder="UPHS Login" class='login' type="text" id="myusername" size="10">&nbsp;
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
9/15/2015:
 - Added 170 new exam codes previously not counted.  Notably this affects some breast imaging studies from PAH, as well as selected procedures/biopsies.

5/17/2015:
 - Readapted functionalities using the PowerScribe 360 database
 - Data is up to date including those since 3/20/2015.
 - Discrepancy and volumetric are back online.
 - However, turnaround time and some analytics are no longer functional.

1/24/2015:
- Unified Exam Code is now supported.
- Minor bug fixes.

10/18/2014:
- Updated the exam code database. 

10/10/2014:
- Updated interface now allows sorting and filtering of study lists.
- Misc. bug fixes.

9/9/2014:
- Interface updates including restructuring the discrepancy worklist and optimized AJAX notifications.
- Database schema update to now include Emergency, Inpatient, and Outpatient designations.
- Great Call is now available.
- Minor and Addition are now two separate lists.

8/26/2014:
- Minor bug fixes.

8/19/2014:
- Added browse functionality and discrepancy support for fellows.

8/12/2014:
- Discrepancy worklist now functional.
- Tags functionality implemented.
- Shared tags functionality implemented.

7/10/2014:
- New feature which tracks changes between Prelim and Final reports is implemented.

6/29/2014:
- Created a search-by-category function for your studies.
- You can now see the report of a study by entering the accession number.

[Pearls: Did You Know]
 * In Browse, you can click on the legend labels on the right.
 * In Browse, you can click on a bar in the bargraph.
 * You can click on an accession number to view the associated report text.

6/24/2014:
- Export to Excel available for daily or weekly interpreted study lists.

5/18/2014:
- Capricorn won the SIIM 2014 Open Source Leadership Award!
- Improved overall response time. 

4/30/2014:
- Corrected accounting of certain rotations and modalities (such as MSK MR) in Analysis.
- Changed the labeling of "PET" to "PET (without CT)" in some places for clarity.
- Added change log on login screen.
- Thanks for feedback, encouragements, and constructive criticisms.  They're what makes this project fun, so please keep them coming.

4/28/2014:
- Spine MR and CT now have their own section, appropriately named SPINE.
- Historical averages now display correctly - used to be doubled.
- Fixed PET/CT counts.
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
