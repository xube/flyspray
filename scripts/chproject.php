<?php

include('../header.php');
Header("Location: {$flyspray_prefs['base_url']}?project={$_GET['project']}");
setcookie('flyspray_project', $_GET['project'], time()+60*60*24*30, "/");

?>
