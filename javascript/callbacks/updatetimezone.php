<?php
/*
    This script updates a user's timezone if necessary
*/

define('IN_FS', true);

require_once('../../header.php');

if (!$user->isAnon()) {
    $db->x->execParam('UPDATE {users} SET time_zone = ? WHERE user_id = ?', array(Get::num('timezone'), $user->id));
}
?>
