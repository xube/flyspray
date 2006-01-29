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
    $fs->Redirect( CreateURL('error', null) );
}

$old_project = $proj->id;
$proj = new Project(0);

$page->uses('old_project');
$page->pushTpl('admin.menu.tpl');

switch ($area = Get::val('area', 'prefs')) {
    case 'users':
        $page->assign('theuser', new User(Get::val('id')));
        $page->assign('groups', $fs->ListGroups());
    case 'cat':
    case 'editgroup':
    case 'groups':
        $page->assign('groups', $fs->ListGroups());
    case 'newproject':
    case 'os':
    case 'prefs':
    case 'res':
    case 'tt':
    case 'status':
    case 'ver':

        $page->setTitle('Flyspray:: ' . $language['admintoolboxlong']);
        $page->pushTpl('admin.'.$area.'.tpl');
        break;

    default:
        $fs->Redirect( CreateURL('error', null) );
}

?>
