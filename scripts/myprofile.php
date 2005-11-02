<?php
/*
   -------------------------------------------------------
   | This script allows users to edit their user profile |
   -------------------------------------------------------
*/

if ($user->isAnon()) {
    echo $admin_text['nopermission'];
    exit;
}

$fs->get_language_pack('admin');
$page->uses('admin_text');
$page->display('myprofile.tpl');

?>
