<?php

  /******************************************************\
  | Create user (no confirmation)                        |
  | ~~~~~~~~~~~                                          |
  | Restricted to admins (or *very* permissive projects) |
  \******************************************************/

if (!$user->can_create_user()) {
    $fs->redirect( $fs->createUrl('error') );
}

$fs->get_language_pack('newuser');
$page->uses('newuser_text');

if ($user->perms['is_admin']) {
    $sql = $db->Query("SELECT  group_id, group_name
                         FROM  {groups}
                        WHERE  belongs_to_project = '0'
                     ORDER BY  group_id ASC");
    $page->assign('group_names', $db->fetchAllArray($sql));
}
$page->pushTpl('newuser.tpl');
?>
