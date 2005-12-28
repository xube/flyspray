<?php

  /********************************************************\
  | Task Creation                                          |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if (!$user->can_open_task($proj)) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

$fs->get_language_pack('newtask');
$fs->get_language_pack('modify');
$fs->get_language_pack('index');
$fs->get_language_pack('details');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');

$userlist = $proj->UserList();

$page->assign('userlist', $userlist);
$page->assign('assigned_users', array());

$page->uses('newtask_text', 'index_text', 'details_text', 
        'severity_list', 'priority_list','modify_text');

$page->setTitle('Flyspray:: ' . $proj->prefs['project_title'] . ': ' . $newtask_text['newtask']);
$page->pushTpl('newtask.tpl');

?>
