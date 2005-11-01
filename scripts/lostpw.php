<?php

/*
   ------------------------------------------------------
   | This script allows users to request a notification |
   | that contains a link to a new password             |
   ------------------------------------------------------
*/

$fs->get_language_pack('admin');
$page->uses('admin_text');

if (!Get::has('magic') && !Cookie::has('flyspray_userid')) {
    // Step One: user requests magic url
    $page->display('lostpw.step1.tpl');
}
elseif (!Get::has('magic') && !Cookie::has('flyspray_userid')) {
    // Step Two: user enters new password

    $check_magic = $db->Query("SELECT * FROM {users} WHERE magic_url = ?",
            array(Get::val('magic')));

    if (!$db->CountRows($check_magic)) {
        $_SESSION['ERROR'] = $admin_text['badmagic'];
        $fs->redirect('./');
    }
    $page->display('lostpw.step2.tpl');
}
?>
