<?php
/*
    This script is the AJAX callback that deletes a user's saved search
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

$user->save_search();

?>
