<?php
// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you 
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

// You might like to uncomment the next line if you are receiving lots of
// PHP NOTICE errors.  We are in the process of making Flyspray stop making
// these errors, but this will help hide them until we are finished.

//error_reporting(E_ALL & -E_NOTICE);

// Load the config file
$conf_array = @parse_ini_file("flyspray.conf.php", true);


// Set values from the config file. Once these settings are loaded a connection
// is made to the database to retrieve all the other preferences.
$basedir     = $conf_array['general']['basedir'];
$adodbpath   = $conf_array['general']['adodbpath'];
$jpgraphpath = $conf_array['general']['jpgraphpath'];
$cookiesalt  = $conf_array['general']['cookiesalt'];
$dbtype      = $conf_array['database']['dbtype'];
$dbhost      = $conf_array['database']['dbhost'];
$dbname      = $conf_array['database']['dbname'];
$dbuser      = $conf_array['database']['dbuser'];
$dbpass      = $conf_array['database']['dbpass'];


// Check that the config file has been created.  If not, redirect to the setup script.
if (!isset($basedir)
   OR $basedir == '') {
      Header("Location: sql/install-0.9.7.php");
};


include_once ( "$adodbpath" );
include ( "$basedir/includes/functions.inc.php" );
include ( "$basedir/includes/regexp.php" );

// Check PHP Version (Must Be > 4.2)
if (PHP_VERSION  < '4.2.0') {
	die('Your version of PHP is not compatable with Flyspray, please upgrade to the latest version of PHP.  Flyspray requires at least PHP version 4.3.0');
};

session_start();

// Define our functions class
$fs = new Flyspray;

// Open a connection to the database
$res = $fs->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype);
if (!$res) {
  die("Flyspray was unable to connect to the database.  Check your settings in flyspray.conf.php");
}

// Retrieve the global application preferences
$flyspray_prefs = $fs->getGlobalPrefs();

//$project_id = 0;

// If we've gone directly to a task, we want to override the project_id set in the function below
if (($_GET['do'] == 'details') && ($_GET['id'])) {
  list($project_id) = $fs->dbFetchArray($fs->dbQuery("SELECT attached_to_project FROM flyspray_tasks WHERE task_id = {$_GET['id']}"));
};

// Determine which project we want to see
if (!$project_id) {
  if ($_GET['project']) {
    $project_id = $_GET['project'];
    setcookie('flyspray_project', $_GET['project'], time()+60*60*24*30, "/");
  } elseif ($_COOKIE['flyspray_project']) {
    $project_id = $_COOKIE['flyspray_project'];
  } else {
    $project_id = $flyspray_prefs['default_project'];
    setcookie('flyspray_project', $flyspray_prefs['default_project'], time()+60*60*24*30, "/");
  };
};

// This was only used for upgrading from 0.9.4 to 0.9.5
// Should not be necessary in later versions of Flyspray.  
//if (!(ereg("upgrade", $_SERVER['PHP_SELF']))) {
  $project_prefs = $fs->getProjectPrefs($project_id);
//};

?>
