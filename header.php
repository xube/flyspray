<?php

// Directory with Flyspray scripts. It's the directory where this file is
// located.
$basedir = '/home/tony/public_html/flyspray';

// Flyspray uses ADODB for database access.  You will need to install
// it somewhere on your server for Flyspray to function.  It can be installed
// inside the Flyspray directory if you wish. The next line needs to be the
// correct path to your adodb.inc.php file.
include_once ( "/usr/share/adodb/adodb.inc.php" );

//  Modify this next line to reflect the correct path to your Flyspray
//  functions.inc.php file.
include ( "$basedir/functions.inc.php" );

//  Modify this next line to reflect the correct path to your Flyspray
//  regexp.php file
include ( "$basedir/regexp.php" );

// The dbtype must be a valid adodb database type
// See the ADODB homepage for a list of supported database types. 
$dbtype = 'mysql';  

$dbhost = 'localhost';  // Name or IP of Database Server
$dbname = 'flyspray-test';  // The name of the database.
$dbuser = 'root';   // The user to access the database.
$dbpass = 'dLAr0f3';   // The password to go with that username above.


// This is the key that your cookies are encrypted against.
// It is recommended that you change this immediately after installation to make
// it harder for people to hack their cookies and try to take over someone else's 
// account.  Changing it will log out all users, but there are no other consequences.
$cookiesalt = '4t6dcHiefIkeYcn48B';  



///////////////////////////////////////////////////////////
//  DO NOT EDIT BELOW THIS LINE! //
//////////////////////////////////////////////////////////

session_start();

$fs = new Flyspray;

$fs->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype);

$flyspray_prefs = $fs->getGlobalPrefs();

if ($_GET['project']) {
  $project_id = $_GET['project'];
} elseif ($_COOKIE['flyspray_project']) {
  $project_id = $_COOKIE['flyspray_project'];
} else {
  $project_id = $flyspray_prefs['default_project'];
};

if (!(ereg("upgrade", $_SERVER['PHP_SELF']))) {
  $project_prefs = $fs->getProjectPrefs($project_id);
};

?>
