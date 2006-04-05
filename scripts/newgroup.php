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
    Flyspray::Redirect(CreateURL('error'));
}

if (Get::val('project')) {
    $forproject = $proj->prefs['project_title'];
    $page->setTitle('Flyspray:: ' . $proj->prefs['project_title'] . ': ' . L('createnewgroup'));
} else {
    $forproject = L('globalgroups');
    $page->setTitle('Flyspray:: ' . L('createnewgroup'));
}

$page->assign('forproject', $forproject);
$page->pushTpl('newgroup.tpl');
?>
