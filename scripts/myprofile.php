<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if ($user->isAnon()) {
    Flyspray::show_error(13);
}

$page->assign('groups', $fs->ListGroups());

$page->assign('project_groups', $fs->ListGroups($proj->id));
        
$page->assign('theuser', $user);

$page->setTitle($fs->prefs['page_title'] . L('editmydetails'));
$page->pushTpl('myprofile.tpl');

?>
