<?php

  /******************************************************\
  | Create user (no confirmation)                        |
  | ~~~~~~~~~~~                                          |
  | Restricted to admins (or *very* permissive projects) |
  \******************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->can_create_user()) {
    Flyspray::Redirect(CreateURL('register'));
}

if ($user->perms['is_admin']) {
    $sql = $db->Query("SELECT  group_id, group_name
                         FROM  {groups}
                        WHERE  belongs_to_project = '0'
                     ORDER BY  group_id ASC");
    $page->assign('group_names', $db->fetchAllArray($sql));
}

$page->setTitle('Flyspray:: ' . L('registernewuser'));
$page->pushTpl('newuser.tpl');
?>
