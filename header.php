<?php

include('config.inc.php');
include('functions.inc.php');

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

$project_prefs = $fs->getProjectPrefs($project_id);

?>
