<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns them in an ordered list.
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

$names = array('opened', 'dev', 'uid', 'user_id', 'to_user_id');

foreach ($names as $name) {
    if (Req::has($name)) {
        $searchterm = '%' . Req::val($name) . '%';
    }
}

// Get the list of users from the global groups above
$get_users = $db->Query('SELECT u.real_name, u.user_name
                         FROM {users} u
                         WHERE u.user_name LIKE ? OR u.real_name LIKE ?',
                         array($searchterm, $searchterm), 20);

$html = '<ul class="autocomplete">';

while ($row = $db->FetchArray($get_users))
{
   $html .= '<li title="' . $row['real_name'] . '">' . $row['user_name'] . '</li>';
}

$html .= '</ul>';

echo $html;

?>
