<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns them in an ordered list.
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

if (Req::has('opened')) {
    $searchterm = '%' . Req::val('opened') . '%';
}

if (Req::has('dev')) {
    $searchterm = '%' . Req::val('dev') . '%';
}


// Get the list of users from the global groups above
$get_users = $db->Query("SELECT DISTINCT u.real_name, u.user_name
                         FROM {users} u
                         WHERE u.user_name LIKE ? OR u.real_name LIKE ?",
                         array($searchterm, $searchterm)
                        );

$html = '<ul class="autocomplete">';

while ($row = $db->FetchArray($get_users))
{
   $html .= '<li title="' . $row['user_name'] . '">' . $row['real_name'] . '</li>';
}

$html .= '</ul>';

echo $html;

?>
