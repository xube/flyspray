<?php

  /*******************************************\
  | Create group (global or in project)       |
  | ~~~~~~~~~~~~                              |
  | Restricted to admins and project managers |
  \*******************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->can_create_group()) {
    Flyspray::show_error(14);
}

if (Req::val('project')) {
    $forproject = $proj->prefs['project_title'];
    $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('createnewgroup'));
} else {
    $forproject = L('globalgroups');
    $page->setTitle($fs->prefs['page_title'] . L('createnewgroup'));
}

$page->assign('forproject', $forproject);
$page->pushTpl('newgroup.tpl');
?>
