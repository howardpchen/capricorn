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

<?php include_once "header_nosession.php";?>
<style>
div, p, a {
    font-size:11pt;
}
p  {
    margin-top:1em;
}
</style>
<div class="row" data-intro="Help information here.">
    <div class="12u">
    <h2>Frequently Asked Questions</h2>
    <ul>

    <li> <a href="#Who">Who gets access?</a>
    <li> <a href="#How">How do I log in?</a>
    <li> <a href="#UseIE">Can I use Internet Explorer?</a>
    <li> <a href="#Important">What are the most important features I have to know?</a>
    <li> <a href="#Important">Do I have to use Capricorn?</a>
    <li> <a href="#Orion">What happened to Orion?</a>
    <li> <a href="#Updates">How often are my studies updated?</a>
    <li> <a href="#Studies">How do I get a list of specific types of studies (like coronary CTs)?</a>
    <li> <a href="http://capricornradiology.org">What exactly is Capricorn?</a>
    <li> <a href="#email">I can't find what I need.  Who do I ask for help?</a>
    </ul>
    </div>
    <div class="12u">
        <h4><a id="Who">Who gets access?</a></h4>
        Hospital of the University of Pennsylvania radiology residents and fellows.
        <h4><a id="How">How do I log in?</a></h4>
        Your Capricorn username and password are the same as those used for your UPHS email.

        <h4><a id="UseIE">Can I use Internet Explorer?</a></h4>
        The short answer is yes.<p>Penn uses an old version of Internet Explorer, so parts of the interface will look "off."  However, only animations, certain layouts, and some minor features are affected.

        <h4><a id="Important">What are the most important features I have to know?</a></h4>       
        Capricorn gives you a customizable case log which you can generate by first clicking on Utility Tools at home screen.<p>
        Residents and fellows are now required to review major discrepancies using Capricorn, which can be done by first clicking on Discrepancy Worklist after logging in.

        <h4><a id="Important">Do I have to use Capricorn?</a></h4>
        Official Penn Radiology policy is for residents and fellows to use Capricorn to review "Major Change" discrepancies. When appropriate, comments and tags should be assigned to these misses.  This is the only feature that is attached to a requirement.<p>Please contact a chief resident or Dr. Mary Scanlon for questions on details of official requirements.

        <h4><a id="Orion">What happened to Orion?</a></h4>
        Over time Orion slowly became dysfunctional and is now no longer being used.

        <h4><a href="#Updates">How often are my studies updated?</a></h4>
        Capricorn updates its database to reflect changes in the Radiology Information System once an hour.  Generally this means there is up to a one-hour delay for both preliminary reports and attending final interpretations to become visible.

        <h4><a href="#Studies">How do I get a list of specific types of studies (like coronary CTs)?</a></h4>
        You can do this by first logging in, then go to Utility Tools &gt; More Options.

         <h4><a href="http://capricornradiology.org">What exactly is Capricorn?</a></h4>
        Capricorn is an evolving informatics project.  It is an open source project meaning anyone can download and modify the source code for free.  You can find out more <a href="http://capricornradiology.org">here</a>.
         
         <h4><a href="#email">I can't find what I need.  Who do I ask for help?</a></h4>
         Email <a href='mailto:Po-Hao.Chen@uphs.upenn.edu'>Po-Hao.Chen@uphs.upenn.edu</a> with questions.


    </div>
</div>

<?php include_once "footer.php";?>

