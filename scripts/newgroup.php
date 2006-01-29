<?php

  /*******************************************\
  | Create group (global or in project)       |
  | ~~~~~~~~~~~~                              |
  | Restricted to admins and project managers |
  \*******************************************/

if (!$user->can_create_group()) {
    $fs->redirect(CreateURL('error'));
}

if (Get::val('project')) {
    $forproject = $proj->prefs['project_title'];
    $page->setTitle('Flyspray:: ' . $proj->prefs['project_title'] . ': ' . $language['createnewgroup']);
} else {
    $forproject = $language['globalgroups'];
    $page->setTitle('Flyspray:: ' . $language['createnewgroup']);
}

$page->assign('forproject', $forproject);
$page->pushTpl('newgroup.tpl');
?>
