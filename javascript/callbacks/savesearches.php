<?php
/*
    This script is the AJAX callback that deletes a user's saved search
*/

define('IN_FS', true);

$path = dirname(dirname(__FILE__));
require_once($path . '../../header.php');

if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->get_perms($proj);
    $user->check_account_ok();
    $user->save_search();
}

?>
