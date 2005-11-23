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
$fs->get_language_pack('status');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');

$userlist = $fs->UserList($project_id);

$page->assign('userlist', $userlist);
$page->assign('assigned_users', array());

$page->uses('newtask_text', 'index_text', 'details_text', 'status_list',
        'severity_list', 'priority_list','modify_text');
$page->display('newtask.tpl');

?>
