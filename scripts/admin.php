<?php

  /***********************************************\
  | Administrator's Toolbox                       |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                      |
  | This script allows members of a global Admin  |
  | group to modify the global preferences, user  |
  | profiles, global lists, global groups, pretty |
  | much everything global.                       |
  \***********************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms('is_admin')) {
    Flyspray::show_error(4);
}

$proj = new Project(0);

$page->pushTpl('admin.menu.tpl');

$areas = array('prefs', 'cat', 'editgroup', 'groups', 'users', 'newgroup',
               'newproject', 'newuser', 'os', 'res', 'status', 'tt', 'user', 'ver');

switch ($area = Req::enum('area', $areas, 'prefs')) {
    case 'cat':
    case 'editgroup':
    case 'newuser':
        $page->assign('groups', Flyspray::listGroups());
        break;
    
    case 'user':
        $id = Flyspray::username_to_id(Req::val('user_id'));
        
        $theuser = new User($id, $proj);
        if ($theuser->isAnon()) {
            Flyspray::show_error(5, true, null, $_SESSION['prev_page']);
        }
        $page->assign('all_groups', Flyspray::listallGroups($theuser->id));
        $page->assign('groups', Flyspray::listGroups());
        $page->assign('theuser', $theuser);
        break;
    
    case 'groups':
        $page->assign('group_list', Flyspray::listallGroups());    
        $sql = $db->Query('SELECT g.group_id, g.group_name, g.group_desc,
                                  g.group_open, count(uig.user_id) AS num_users
                             FROM {groups} g
                        LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                            WHERE g.project_id = 0
                         GROUP BY g.group_id');
        $page->assign('groups', $db->FetchAllArray($sql));
        break;
        
    case 'users':
        // Prepare the sorting
        $order_keys = array('username' => 'user_name',
                            'realname' => 'real_name',
                            'email'    => 'email_address',
                            'jabber'   => 'jabber_id',
                            'status'   => 'account_enabled');
        $order_column[0] = $order_keys[Filters::enum(Get::val('order', 'sev'), array_keys($order_keys))];
        $order_column[1] = $order_keys[Filters::enum(Get::val('order2', 'sev'), array_keys($order_keys))];
        $sortorder  = sprintf('%s %s, %s %s, u.user_id ASC',
                $order_column[0], Filters::enum(Get::val('sort', 'desc'), array('asc', 'desc')),
                $order_column[1], Filters::enum(Get::val('sort2', 'desc'), array('asc', 'desc')));
        
        $search_keys = array('user_name', 'real_name', 'email_address', 'jabber_id');
        $where = 'WHERE 1=1 ';
        $args = array();
        foreach ($search_keys as $key) {
            if (Get::val($key) != '') {
                $where .= ' AND ' . $key . ' LIKE ? ';
                $args[] = '%' . Get::val($key) . '%';
            }
        }
        // Search for users in a specific group
        if (Get::num('group_id')) {
            $where = ' LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id ' . $where;
            $where .= ' AND uig.group_id = ? ';
            $args[] = Get::num('group_id');
        }
                
        $sql = $db->Query('SELECT *
                             FROM {users} u '                        
                         . $where .
                        'ORDER BY ' . $sortorder, $args);

        $users = $db->FetchAllArray($sql);
        $page->assign('user_count', count($users));
        
        // Offset and limit
        $user_list = array();
        $offset = (max(Get::num('pagenum') - 1, 0)) * 50;
        for ($i = $offset; $i < $offset + 50 && $i < count($users); $i++) {
            $user_list[] = $users[$i];
        }
        
        $page->assign('all_groups', Flyspray::listallGroups());
        $page->uses('user_list');
        break;
}

$page->setTitle($fs->prefs['page_title'] . L('admintoolboxlong'));
$page->pushTpl('admin.'.$area.'.tpl');

?>
