<?php

include('config.inc.php');
include('functions.inc.php');

session_start();

$fs = new Flyspray;

$fs->dbOpen($dbhost, $dbuser, $dbpass, $dbname);

$flyspray_prefs = $fs->getGlobalPrefs();

?>
