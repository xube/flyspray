<?php
/*
    This script is the AJAX callback that deletes a user's saved search
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

$db->Execute('DELETE FROM {searches} WHERE id = ? AND user_id = ?', array(Get::num('id'), $user->id));

?>
