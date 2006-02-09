<?php
/*
    This script gets the history of a task and
    returns it for HTML display in a page.
*/

define('IN_FS', true);

$path = dirname(dirname(__FILE__));
require_once($path . '../../header.php');
require_once($path . '../../includes/events.inc.php');

// Initialise user
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->get_perms($proj);
    $user->check_account_ok();
}

// Check permissions
if (!$user->perms['view_history'])
{
    die();
}

/*if ($user->perms['view_history'] && Get::has('history')) {
        if (is_numeric($details = Get::val('details'))) {
            $details = " AND h.history_id = $details";
        } else {
            $details = null;
        }

        $page->assign('details', $details);

        $page->assign('histories', $db->fetchAllArray($sql));
*/

$sql = get_events(Get::val('id'));
$histories = $db->fetchAllArray($sql);

$html = '';

foreach($histories as $history)
{
    $html .= '<tr>';
    $html .= '<td>' . formatDate($history['event_date'], true) . '</td>';
    $html .= '<td>' . !tpl_userlink($history['user_id']) . '</td>';
    $html .= '<td>' . !event_description($history) . '</td>';
    $html .= '</tr>';
}

echo $html;
