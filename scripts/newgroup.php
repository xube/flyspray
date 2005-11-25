<?php

  /*******************************************\
  | Create group (global or in project)       |
  | ~~~~~~~~~~~~                              |
  | Restricted to admins and project managers |
  \*******************************************/

if (!$user->can_create_group()) {
    $fs->redirect($fs->createUrl('error'));
}

$fs->get_language_pack('newgroup');

if (Get::val('project')) {
    $forproject = $proj->prefs['project_title'];
    $page->setTitle('Flyspray:: ' . $proj->prefs['project_title'] . ': ' . $newgroup_text['createnewgroup']);
} else {
    $forproject = $newgroup_text['globalgroups'];
    $page->setTitle('Flyspray:: ' . $newgroup_text['createnewgroup']);
}

$page->uses('newgroup_text');
$page->assign('forproject', $forproject);
$page->pushTpl('newgroup.tpl');
?>
