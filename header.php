<?php
// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you 
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED)

// You might like to uncomment the next line if you are receiving lots of
// PPHP NOTICE errors
//error_reporting(E_ALL & -E_NOTICE);

// Load the config file
$conf_array = @parse_ini_file("flyspray.conf.php", true);
$conf_array = array_merge($conf_array, 
	      @parse_ini_file("../flyspray.conf.php", true));

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

include_once ( "$adodbpath" );
include ( "$basedir/functions.inc.php" );
include ( "$basedir/regexp.php" );

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

$project_id = 0;
if (($_GET['do'] == 'details') && ($_GET['id'])) {
  list($project_id) = $fs->dbFetchArray($fs->dbQuery("SELECT attached_to_project FROM flyspray_tasks WHERE task_id = {$_GET['id']}"));
};
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

if (!(ereg("upgrade", $_SERVER['PHP_SELF']))) {
  $project_prefs = $fs->getProjectPrefs($project_id);
};

?>
