<?php
/*
    This script gets a list of voters for a given task
    and returns them in nicely formatted HTML.
*/

$path = dirname(dirname(__FILE__));
require_once($path . '../../header.php');

$get_users = $db->Query("SELECT u.real_name, v.date_time
                           FROM {votes} v
                      LEFT JOIN {users} u ON v.user_id = u.user_id
                          WHERE v.task_id = ?
                       ORDER BY v.vote_id ASC", array(Get::val('id')));

$html = '<br /><b style="color:red;background-color:yellow;">TODO: UserLinks, FormatDate, and a nice style.</b><br />';

while ($row = $db->FetchArray($get_users))
{
    $html .= '<br />' . $row['real_name'] . ' - ' . $row['date_time'];
}

echo $html;

?>
