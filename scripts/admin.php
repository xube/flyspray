<?php

  /***********************************************\
  | Administrator's Toolbox                       |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                      |
  | This script allows members of a global Admin  |
  | group to modify the global preferences, user  |
  | profiles, global lists, global groups, pretty |
  | much everything global.                       |
  \***********************************************/

if (!$user->perms['is_admin']) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

$fs->get_language_pack('admin');
$fs->get_language_pack('index');
$fs->get_language_pack('newproject');
$fs->get_language_pack('newuser');

$proj = new Project(0);

$page->uses('admin_text', 'index_text', 'newproject_text', 'newuser_text');
$page->pushTpl('admin.menu.tpl');

switch ($area = Get::val('area', 'prefs')) {
    case 'users':
        $sql = $db->Query("SELECT  group_id, group_name
                             FROM  {groups}
                            WHERE  belongs_to_project = '0'
                         ORDER BY  group_id ASC");
        $page->assign('group_names', $db->fetchAllArray($sql));
        $page->assign('theuser', new User(Get::val('id')));

    case 'cat':
    case 'editgroup':
    case 'groups':
    case 'newproject':
    case 'os':
    case 'prefs':
    case 'res':
    case 'tt':
    case 'ver':

        $page->setTitle('Flyspray:: ' . $admin_text['admintoolbox']);
        $page->pushTpl('admin.'.$area.'.tpl');
        break;

    default:
        $fs->Redirect( $fs->CreateURL('error', null) );
}

?>
