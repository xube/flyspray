<?php
/*
    This script gets a list of voters for a given task
    and returns them in nicely formatted HTML.
*/

define('IN_FS', true);

$path = dirname(dirname(__FILE__));
require_once($path . '../../header.php');

$get_users = $db->Query("SELECT u.user_id, v.date_time
                           FROM {votes} v
                      LEFT JOIN {users} u ON v.user_id = u.user_id
                          WHERE v.task_id = ?
                       ORDER BY v.date_time DESC", array(Get::val('id')));

if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->get_perms($proj);
    $user->check_account_ok();
}

$html = '<ul class="reports">';

while ($row = $db->FetchArray($get_users))
{
    $html .= '<li>' . formatDate($row['date_time']) . ': ' . tpl_userlink($row['user_id']) . '</li>';
}

$html .= '</ul>';

echo $html;

?>
