<?php
// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

// You might like to uncomment the next line if you are receiving lots of
// PHP NOTICE errors.  We are in the process of making Flyspray stop making
// these errors, but this will help hide them until we are finished.

//error_reporting(E_ALL & -E_NOTICE);


// This line gets the operating system so that we know which way to put slashes in the path
strstr( PHP_OS, "WIN") ? $slash = "\\" : $slash = "/";

// Check if we're upgrading, modify the path to the config file accordingly
if (ereg("sql|scripts", $_SERVER['PHP_SELF']))
{
   $path_append = '..';
} else
{
   $path_append = '';
}

// Get the path to the Flyspray directory
$path = realpath('./' . $path_append);

// Modify PHP's include path to add the Flyspray directory
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

// This line was used in testing
//echo get_include_path();

// Define the path to the config file.  Change this line if you move flyspray.conf.php elsewhere
$conf_file = $path . $slash . "flyspray.conf.php";

// Load the config file
$conf_array = @parse_ini_file($conf_file, true);


// Set values from the config file. Once these settings are loaded a connection
// is made to the database to retrieve all the other preferences.
$basedir     = $conf_array['general']['basedir'];
$adodbpath   = $conf_array['general']['adodbpath'];
//$jpgraphpath = $conf_array['general']['jpgraphpath'];
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
include_once ( "$basedir/includes/functions.inc.php" );
include_once ( "$basedir/includes/regexp.php" );
include_once ( "$basedir/includes/db.inc.php" );
include_once ( "$basedir/includes/markdown.php" );


// Check PHP Version (Must Be at least 4.3)
if (PHP_VERSION  < '4.3.0') {
        die('Your version of PHP is not compatible with Flyspray, please upgrade to the latest version of PHP.  Flyspray requires at least PHP version 4.3.0');
};

if (isset($_COOKIE['flyspray_userid']))
{
   session_start();
};

// Define our functions class
$fs = new Flyspray;
$db = new Database;

// Open a connection to the database
$res = $db->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype);
if (!$res) {
  die("Flyspray was unable to connect to the database.  Check your settings in flyspray.conf.php");
}

// Retrieve the global application preferences
$flyspray_prefs = $fs->getGlobalPrefs();

// If we've gone directly to a task, we want to override the project_id set in the function below
if ((isset($_GET['do']) && $_GET['do'] == 'details') && (isset($_GET['id'])))
{
   list($project_id) = $db->FetchArray($db->Query("SELECT attached_to_project FROM flyspray_tasks WHERE task_id = ?", array($_GET['id'])));
   setcookie('flyspray_project', $project_id, time()+60*60*24*30, "/");
};

// Determine which project we want to see
if (!isset($project_id))
{
   if (isset($_GET['project']) && $_GET['project'] != '0')
   {
      $project_id = $_GET['project'];
      setcookie('flyspray_project', $_GET['project'], time()+60*60*24*30, "/");

  } elseif (isset($_COOKIE['flyspray_project']))
  {
      $project_id = $_COOKIE['flyspray_project'];

  } else
  {
      $project_id = $flyspray_prefs['default_project'];
      setcookie('flyspray_project', $flyspray_prefs['default_project'], time()+60*60*24*30, "/");
  };
};

// Get the preferences for the currently selected project
$project_prefs = $fs->getProjectPrefs($project_id);

?>
