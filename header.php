<?php
// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

// You might like to uncomment the next line if you are receiving lots of
// PHP NOTICE errors.  We are in the process of making Flyspray stop making
// these errors, but this will help hide them until we are finished.

//error_reporting(E_ALL & ~E_NOTICE);

$basedir = dirname(__FILE__);
require_once ( "$basedir/includes/fix.inc.php" );
require_once ( "$basedir/includes/class.gpc.php" );
require_once ( "$basedir/includes/functions.inc.php" );
require_once ( "$basedir/includes/db.inc.php" );
require_once ( "$basedir/includes/backend.inc.php" );
require_once ( "$basedir/includes/markdown.php" );

// Change this line if you move flyspray.conf.php elsewhere
$conf_file = $basedir . DIRECTORY_SEPARATOR . 'flyspray.conf.php';
$conf      = @parse_ini_file($conf_file, true);

// If it is empty, or lacks 0.9.8 variables, take the user to the setup page
if (count($conf) == 0 || !isset($conf['general']['baseurl'])) {
    header('Location: setup/index.php');
    exit;
}

require_once ( $conf['general']['adodbpath'] );

// Set useful values from the config file.
$baseurl    = $conf['general']['baseurl'];
$cookiesalt = $conf['general']['cookiesalt'];

if ($baseurl{strlen($baseurl)-1} != '/') {
    $baseurl .= '/';
}

$fs = new Flyspray;
$db = new Database;
$be = new Backend;

require_once ( "$basedir/includes/regexp.php" );

session_start();

if (!($res = $db->dbOpenFast($conf['database']))) {
    die("Flyspray was unable to connect to the database.  Check your settings in flyspray.conf.php");
}

$flyspray_prefs = $fs->getGlobalPrefs();

// Any "do" mode that accepts a task_id or id field should be added here.
if (in_array(Req::val('do'), array('details', 'depends', 'modify')))
{
    // If we've gone directly to a task, we want to override the project_id set in the function below
    $id = Req::val('task_id', Req::val('id'));

    if (!is_null($id) && is_numeric($id)) {
        $result = $db->Query("SELECT  attached_to_project
                                FROM  {tasks} WHERE task_id = ?", array($id));
        $project_id = $db->FetchOne($result);
    }
}

// Determine which project we want to see
if (!isset($project_id)) {
    if (Req::val('project', '0') != '0' && Req::val('project')) {
        $project_id = Req::val('project');
    }
    elseif (Req::has('project_id')) {
        $project_id = Req::val('project_id');
    }
    elseif (Cookie::has('flyspray_project')) {
        $project_id = Cookie::val('flyspray_project');
    }
    else {
        $project_id = $flyspray_prefs['default_project'];
    }
}
setcookie('flyspray_project', $project_id, time()+60*60*24*30, '/');

// Check that the requested project actually exists
$proj_exists = $db->Query("SELECT  *
                             FROM  {projects}
                            WHERE  project_id = ?", array($project_id));

if (!$db->CountRows($proj_exists)) {
    $fs->redirect("index.php?project=" . $flyspray_prefs['default_project']);
}

$project_prefs = $fs->getProjectPrefs($project_id);

?>
