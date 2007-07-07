<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns them in an ordered list.
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

require_once('../../header.php');

if (!$user->can_view_userlist()) {
    exit;
}

$searchterm = '%' . Get::val('user') . '%';

// Get the list of users from the global groups above
$get_users = $db->SelectLimit('SELECT u.user_id, u.real_name, u.user_name
                           FROM {users} u
                          WHERE u.user_name LIKE ? OR u.real_name LIKE ?', 300, 0,
                         array($searchterm, $searchterm));

header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="utf-8" ?><results>';
while ($row = $get_users->FetchRow()) {
    $row = array_map(array('Filters', 'noXSS'), $row);
    echo sprintf('<rs id="%s" info="%s">%s</rs>', $row['user_id'], $row['real_name'], $row['user_name']);
}
echo '</results>';
?>
