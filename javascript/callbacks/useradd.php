<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns them in an ordered list.
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

$searchterm = reset($_POST);

// Get the list of users from the global groups above
$get_users = $db->Query('SELECT u.real_name, u.user_name, u.user_id, g.group_name
                           FROM {users} u
                      LEFT JOIN {users_in_groups} uig on uig.user_id = u.user_id
                      LEFT JOIN {groups} g ON uig.group_id = g.group_id
                          WHERE u.user_name LIKE ? OR u.user_id LIKE ?
                       ORDER BY g.group_id ASC',
                         array($searchterm, $searchterm), 20);

if ($row = $db->FetchRow($get_users)) {
    echo '[' . $row['group_name'] . '] ' . $row['real_name'] . ' (' . $row['user_name'] . ')|' . $row['user_id'];
}

?>
