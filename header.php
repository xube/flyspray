<?php

// Directory with Flyspray scripts. It's the directory where this file is
// located.
$basedir = '/var/www/flyspray';

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
$dbname = 'flyspray';  // The name of the database.
$dbuser = 'USERNAME';   // The user to access the database.
$dbpass = 'PASSWORD';   // The password to go with that username above.


// This is the key that your cookies are encrypted against.
// It is recommended that you change this immediately after installation to make
// it harder for people to hack their cookies and try to take over someone else's 
// account.  Changing it will log out all users, but there are no other consequences.
$cookiesalt = '4t';  

// You might like to uncomment the next line if you are receiving lots of
// PPHP NOTICE errors

//error_reporting(E_ALL & -E_NOTICE);


///////////////////////////////////////////////////////////
//  DO NOT EDIT BELOW THIS LINE! //
//////////////////////////////////////////////////////////

// Check PHP Version (Must Be > 4.2)
if (PHP_VERSION  < '4.2.0') {
	die('Your version of PHP is not compatable with FlySpray, please upgrade to the latest version of PHP.  Flyspray requires at least PHP version 4.2.0');
};

session_start();

$fs = new Flyspray;

$res = $fs->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype);
if (!$res) {
  die("Database disconnected");
}

$flyspray_prefs = $fs->getGlobalPrefs();

if ($_GET['project']) {
  $project_id = $_GET['project'];
  setcookie('flyspray_project', $_GET['project'], time()+60*60*24*30, "/");
} elseif ($_COOKIE['flyspray_project']) {
  $project_id = $_COOKIE['flyspray_project'];
} else {
  $project_id = $flyspray_prefs['default_project'];
  setcookie('flyspray_project', $flyspray_prefs['default_project'], time()+60*60*24*30, "/");
};

if (!(ereg("upgrade", $_SERVER['PHP_SELF']))) {
  $project_prefs = $fs->getProjectPrefs($project_id);
};

?>
