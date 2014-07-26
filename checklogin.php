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
include "config/capricornConfig.php";
include "capricornLib.php";
?>

<html>
<head>
<link rel="stylesheet" href="<?php echo $URL_root; ?>css/jquery-ui.css" />

<script src="<?php echo $URL_root; ?>js/jquery-1.9.1.js"></script>
<script src="<?php echo $URL_root; ?>js/jquery-ui.js"></script>

</head>
<?php
session_start();
error_reporting(1);
$db = new mysqli($mysql_host, $mysql_username, $mysql_passwd, $mysql_database);
if (mysqli_connect_errno($db)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

function login($user, $password) {
    global $db;
    global $USE_LDAP;

    $result = '';
    include 'config/ldapConfig.php';

    ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7); 


    if (!$USE_LDAP)  {
        return passwordAccepted($password, $user, $db);
    }

    // Connect to LDAP service
    if (strpos($ldap['url'], 'ldaps') !== FALSE) {
        // We have an SSL URL
        $conn = ldap_connect($ldap['url']);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    } else {
        $conn = ldap_connect($ldap['server'], $port);
    }

    if ($conn === FALSE) {
        print_r("Problem connecting");
        return passwordAccepted($password, $user, $db);
    }

    // Bind as application
    $bind_status = ldap_bind($conn, $ldap['bind_username'], $ldap['bind_password']);
    if ($bind_status === FALSE) {
        print_r("Problem binding");
        return passwordAccepted($password, $user, $db);
    }
    // Find the user's DN
    $attributes = array("displayname","ou");
    $filter = "(&(samaccountName=".$user."))";
    $search_status = ldap_search($conn, $ldap['basedn'], $filter, $attributes);
    if ($search_status === FALSE) {
        print_r("Problem finding user");
        return passwordAccepted($password, $user, $db);
    }
    // Pull the search results
    $result = ldap_get_entries($conn, $search_status);
    if ($result === FALSE) {
        print_r("Problem getting search results");
        return passwordAccepted($password, $user, $db);
    }
    if ((int) @$result['count'] > 0) {
        // pull first result, maybe more? 
        $userdn = $result[0]['dn'];
    }
    if (!isset($userdn) || trim((string) $userdn) == '') {
        return passwordAccepted($password, $user, $db);
    }
    // Authenticate with the newly found DN and user-provided password
    $auth_status = ldap_bind($conn, $userdn, $password);
    if ($auth_status === FALSE) {
        return passwordAccepted($password, $user, $db);
    }

    // check that user is in required_ou
    if ($ldap['required_ou'] !== NULL) {
        if (strpos($result[0]['dn'], $ldap['required_ou']) !== false) {
            return $result;
        }        
    } else {
        return $result;
    }

    // Close the ldap connection since we're done with it
    ldap_close($conn);
    return passwordAccepted($password, $user, $db);
}

function ldapEscape($string, $dn = null) {
    $escapeDn = array('\\', '*', '(', ')', "\x00");
    $escape   = array('\\', ',', '=', '+', '<', '>', ';', '"', '#');
    $search = array();
    if ($dn === null) {
        $search = array_merge($search, $escapeDn, $escape);
    } elseif ($dn === false) {
        $search = array_merge($search, $escape);
    } else {
        $search = array_merge($search, $escapeDn);
    }
    $replace = array();
    foreach ($search as $char) {
        $replace[] = sprintf('\\%02x', ord($char));
    }
    return str_replace($search, $replace, $string);
}

function userExists($username, $database) {
    $sqlquery = "SELECT COUNT(*) as count FROM LoginMember WHERE Username LIKE \"$username\";";
    $results = $database->query($sqlquery);
    $row = $results->fetch_array();
    if ($row['count'] > 0) {
        return true;
    }
    return false;

}

function passwordAccepted($password, $username, $database) {

    if (!userExists($username, $database)) return false;
    $sqlquery = "SELECT PasswordHash FROM LoginMember WHERE Username LIKE \"$username\";";
    $results = $database->query($sqlquery);
    $row = $results->fetch_array();
    $pwhash = $row['PasswordHash'];
    if (crypt($password, $pwhash) == $pwhash) { 
        return true; 
    }
    return false;
}

function getTraineeID($username, $database) {
    global $USE_LDAP;

    $sqlquery = "SELECT TraineeID FROM LoginMember WHERE Username LIKE \"$username\";";
    $results = $database->query($sqlquery);
    $row = $results->fetch_array();
    return $row['TraineeID'];
}

// The code starts here.

if ((!isset($_POST['myusername']) || !isset($_POST['mypassword'])) && isset($_SESSION['traineeid'])) {

    if ($_SESSION['traineeid'] > 90000000) {               // INTERNAL DEFINITION OF ADMINISTRATOR
        header("location:admin/");
    }
    else {
        header("location:login_success.php");
    }
    
}
else if (!isset($_POST['myusername']) || !isset($_POST['mypassword']))  {
    header ("location:index.php");
}
else {
// username and password sent from form 
    $myusername = "";
    $mypassword = "";
    if (isset($_POST['myusername']) && isset($_POST['mypassword'])) {
        $myusername= ldapEscape($_POST['myusername']);
        $mypassword=$_POST['mypassword']; 
    }
    
    $success = login($myusername, $mypassword);
    if ($success) {
        if (passwordAccepted($mypassword, $myusername, $db))  {
            $id = getTraineeID($myusername, $db);
        }
        else if ($USE_LDAP)  {
            $fullname = $success[0]['displayname'][0];
            $fullname = explode(', ', $fullname);
            $lastname = $fullname[0];
            $firstmiddle = explode(' ', $fullname[1]);
            $firstname = $firstmiddle[0];
            //echo $lastname .  "---" . $firstname . "<BR>";
            $sqlquery = "SELECT COUNT(*) as count FROM ResidentIDDefinition WHERE FirstName LIKE \"$firstname\" AND LastName LIKE \"$lastname\" AND IsCurrentTrainee=1;";       

            $results = $db->query($sqlquery);
            $row = $results->fetch_array();

            if ($row['count'] > 1 || $row['count'] == 0)  {
                include "header_nosession.php";
                echo "<p>An error occured while associating your institutional login with Capricorn.</p><p>For example, this may happen if you are a radiologist but not (or no longer) a trainee.</p><p>Contact the administrator to resolve this issue.</p>";
                exit();
            } else  {
                $sqlquery = "SELECT FirstName, LastName, TraineeID FROM ResidentIDDefinition WHERE FirstName LIKE \"$firstname\" AND LastName LIKE \"$lastname\";";
                $results = $db->query($sqlquery);
                $row = $results->fetch_array();
                $id = $row['TraineeID'];
            }
        } else {
            $id = getTraineeID($myusername, $db);
        }
        $_SESSION['username'] = $myusername;
        $_SESSION['traineeid'] = $id;
        if ($id > 90000000) {               // INTERNAL DEFINITION OF ADMINISTRATOR
            header("location:admin/");
        }
        else {
            header("location:login_success.php");
        }
    } else {
        include "header_nosession.php";
        echo "Login failed.";
        session_destroy();
    }
}


ob_end_flush();
?>

<P>
<a href="./">Try Again</a>
<?php
include "footer.php";
ob_end_flush();
?>
</BODY>
</HTML>
