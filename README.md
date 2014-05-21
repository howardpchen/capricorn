------------------------------------------------
Capricorn - Radiology Residency Analytics Tool
------------------------------------------------

Designed by: (Howard) Po-Hao Chen, MD, MBA, Yin Jie Chen, MD, Tessa Cook, MD, PhD, at the Hospital of University of Pennsylvania
For a general introduction to Capricorn, see http://capricornradiology.org
Licensed Under GNU General License 3.0.

------------------------------------------------
Design Decisions and Issues
------------------------------------------------

[Capricorn-MySQL.sql]

Every radiology system is different.  Capricorn is designed to be as self-contained as possible.  It consists of a dedicated MySQL database, which is defined by the table definition in Capriorn-MySQL.sql, and a frontend that is the Capricorn interface.

Provided that the Capricorn database is populated properly (see below), the remainder of the software should just work - with the exception of displayReport.php,  which retrieves the most up-to-date report from Radiology Information System.

[tools]

Almost all tools (except for updateResCount.php as below) in this folder are designed to populate the Capricorn database.  As each Radiology Information System is unique, you will have to rewrite scripts in this database for your institution.

	[updateResCount.php]

See Analytics section below for the design decision behind updateResCount.php.  This script should be run once yearly (i.e. on July 1) to update Capricorn's pre-calculated values.  However, during the early stages of development you would also have to run this script each time the structure of capricorn.ExamCodeDefinition (MySQL table) is updated.

[add_user.php, create_user.html, checklogin.php]

The code for logging in are institution-dependent.

Currently the code can be modified to use one of two methods.  Method 1 involves using Capricorn database to store login and password hashes, which has been implemented and should be operational.

Method 2 uses LDAP server, which at UPenn allows a resident to log in using his/her existing email account.  Upon logging in, the proper TraineeID is then identified using Capricorn's resident database.  For Method 2, /config/ldapconfig.php should be properly configured.


[Debugging]

A 'changeid' $_GET field has been created to allow the developer to change identity to a specific TraineeID.  This should be disabled prior to deployment to ensure residents' privacy is preserved.

Logs are stored in /log directory and is currently set to also store TraineeID who is currently logged in.  This ensures that the only people who can identify the actual trainee are those who have access to the TraineeID-to-Trainee map.  Please ensure permission for the /log directory is properly set prior to deployment.

[Scheduling Integration]

This is an optional feature.  Currently a simple on/off hook has not been implemented.  To manually turn this feature off, you simply need to edit browse.php to remove the scheduling integration code (marked as "Display Rotations Here") and simply remove references to calls.php (i.e. from login_success.php).

	[tools/qgendaImporter.php]

UPenn uses QGenda for resident scheduling.  With proper scheduling integration Capricorn can automatically identify the studies interpreted during specific studies.  Both the left side panel in browse.php and the entire functionality of call.php rely on proper function of resident scheduling.

	[calls.php]

This script displays call-related data in Capricorn.  In order to function properly, it relies on the capricorn.ResidentRotation table to be populated, which at UPenn can be done using the tools/qgendaImporter.php script.

[Analytics]

Analytics relies on a lot of pre-calculation in order to be responsive.  A design decision has been made to calculate up-to-date information (i.e. the resident's counts from THIS year) on-demand, but pre-calculate the remaining numbers.

Specifically, when calculating historical averages, Capricorn relies on capricorn.ResidentCounts table to quickly calculate (1) historical average for the given modality/section up to the most recent July 1st and (2) the logged in user's counts UP TO but NOT INCLUDING the most recent July 1st.

Capricorn then calculates in real time the user's counts between July 1st and the current date.  The same calculation should not be performed for historical averages because it would skew the average downwards (for example, on 7/2/2014, the "yearly average" for 2013-2014 academic year would be inaccurately low compared to all other years because only 1 day has elapsed for that academic year).


------------------------------------------------
Questions, Problems?
------------------------------------------------

Use http://capricornradiology.org/contact.php, or alternatively, email po-hao.chen@uphs.upenn.edu


