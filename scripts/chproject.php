<?php

include('../header.php');
Header("Location: {$flyspray_prefs['base_url']}?project={$_GET['project']}");

?>
