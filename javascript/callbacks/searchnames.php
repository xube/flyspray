<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns true if the user_name is not given.
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

if (Get::has('name')) {
    $searchterm = strtolower(Get::val('name'));
}

// Get the list of users from the global groups above
$get_users = $db->x->getRow('  SELECT  count(u.user_name) AS anz_u_user, 
                                   count(r.user_name) AS anz_r_user 
                             FROM  {users} u
                        LEFT JOIN  {registrations} r ON u.user_name = r.user_name
                            WHERE  Lower(u.user_name) = ? 
                                   OR
                                   Lower(r.user_name) = ?',
                          null, array($searchterm, $searchterm));


if ($get_users)
{
    if ($get_users['anz_u_user'] > '0' || $get_users['anz_r_user'] > '0') {
         $html = 'false|' . eL('usernametaken');
    } else {
         $html = 'true';
    }
}

echo $html;

?>
