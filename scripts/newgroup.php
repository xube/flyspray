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
} else {
    $forproject = $newgroup_text['globalgroups'];
}

$page->uses('newgroup_text');
$page->assign('forproject', $forproject);
$page->display('newgroup.tpl');
?>
