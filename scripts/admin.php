<?php

  /***********************************************\
  | Administrator's Toolbox                       |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                      |
  | This script allows members of a global Admin  |
  | group to modify the global preferences, user  |
  | profiles, global lists, global groups, pretty |
  | much everything global.                       |
  \***********************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms['is_admin']) {
    Flyspray::Redirect( CreateURL('error', null) );
}

$old_project = $proj->id;
$proj = new Project(0);

$page->uses('old_project');
$page->pushTpl('admin.menu.tpl');

switch ($area = Get::val('area', 'prefs')) {
    case 'users':
        $id = Get::val('uid');
        if (!is_numeric($id)) {
            $sql = $db->Query('SELECT user_id FROM {users} WHERE user_name = ?', array($id));
            $id = $db->FetchOne($sql);
        }
        $theuser = new User($id, $proj);
        if ($theuser->isAnon()) {
            $_SESSION['ERROR'] = L('usernotexist');
            Flyspray::Redirect(Req::val('prev_page', $baseurl));
        }
        $page->assign('theuser', $theuser);
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

        $page->setTitle('Flyspray:: ' . L('admintoolboxlong'));
        $page->pushTpl('admin.'.$area.'.tpl');
        break;

    default:
        Flyspray::Redirect( CreateURL('error', null) );
}

?>
