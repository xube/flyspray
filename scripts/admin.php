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

$areas = array('prefs', 'editgroup', 'groups', 'users', 'newgroup', 'fields',
               'newproject', 'newuser', 'lists', 'user', 'list', 'system');

switch ($area = Req::enum('area', $areas, 'prefs')) {
    case 'editgroup':
    case 'newuser':
        $page->assign('groups', Flyspray::listGroups());
        break;

    case 'user':
        $id = Flyspray::username_to_id(Req::val('user_id'));

        $theuser = new User($id);
        if ($theuser->isAnon()) {
            Flyspray::show_error(5);
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
        // XXX: keep in sync with pm.php
        $order_keys = array('username' => 'user_name',
                            'realname' => 'real_name',
                            'email'    => 'email_address',
                            'jabber'   => 'jabber_id',
                            'regdate'  => 'register_date',
                            'status'   => 'account_enabled');
        $order_column[0] = $order_keys[Filters::enum(Get::val('order', 'sev'), array_keys($order_keys))];
        $order_column[1] = $order_keys[Filters::enum(Get::val('order2', 'sev'), array_keys($order_keys))];
        $sortorder  = sprintf('%s %s, %s %s, u.user_id ASC',
                $order_column[0], Filters::enum(Get::val('sort', 'desc'), array('asc', 'desc')),
                $order_column[1], Filters::enum(Get::val('sort2', 'desc'), array('asc', 'desc')));

        // Search options
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
        $groups = Get::val('group_id');
        if (is_array($groups) && count($groups) && !in_array(0, $groups)) {
            $where = ' LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id ' . $where;
            $where .= ' AND (' . substr(str_repeat(' uig.group_id = ? OR ', count($groups)), 0, -3) . ' ) ';
            $args = array_merge($args, $groups);
        }

        $sql = $db->Query('SELECT u.user_id, u.user_name, u.real_name, u.register_date,
                                  u.jabber_id, u.email_address, u.account_enabled
                             FROM {users} u '
                         . $where .
                        'ORDER BY ' . $sortorder, $args);

        $users = $db->GroupBy($sql, 'user_id');
        $page->assign('user_count', count($users));

        // Offset and limit
        $user_list = array();
        $offset = (max(Get::num('pagenum') - 1, 0)) * 50;
        for ($i = $offset; $i < $offset + 50 && $i < count($users); $i++) {
            $user_list[] = $users[$i];
        }

        // Get the user groups in a separate query because groups may be hidden
        // because of search options which are disregarded here
        if (count($user_list)) {
            $in = implode(',', array_map(create_function('$x', 'return $x[0];'), $user_list));
            $sql = $db->Query('SELECT user_id, g.group_id, g.group_name, g.project_id
                                 FROM {groups} g
                            LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                                WHERE user_id IN ('. $in .')');
            $user_groups = $db->GroupBy($sql, 'user_id', array('group_id', 'group_name', 'project_id'), !REINDEX);
        }


        $page->assign('all_groups', Flyspray::listallGroups());
        $page->uses('user_list');
        $page->uses('user_groups');
        break;

        case 'prefs':
            $sql = $db->Query('SELECT * FROM {lists}
                                WHERE project_id = 0
                             ORDER BY list_type, list_name');
            $page->assign('lists', $db->FetchAllArray($sql));
            break;

        case 'fields':
            $sql = $db->Query('SELECT * FROM {lists}
                             ORDER BY project_id, list_type, list_name');
            $page->assign('lists', $db->FetchAllArray($sql));
            break;

        case 'lists':
            $sql = $db->Query('SELECT * FROM {lists}
                                WHERE project_id = 0
                             ORDER BY list_type, list_name');
            $page->assign('lists', $db->FetchAllArray($sql));
            break;

        case 'list':
            // Which type of list?
            $sql = $db->Query('SELECT list_type, list_name FROM {lists} WHERE list_id = ?',
                              array(Req::val('list_id')));
            $row = $db->fetchRow($sql);
            $list_type = $row[0];
            $list_name = $row[1];

            if ($list_type == LIST_CATEGORY) {
                $area = 'cat';
            } else {
                $page->assign('rows', $proj->get_edit_list(Req::val('list_id')));
            }
            $page->uses('list_type', 'list_name');
            break;

        case 'system':
            $sql = $db->Query('SELECT pref_value FROM {prefs} WHERE pref_name = ?', array('fs_ver'));

            $page->assign('db_version', $db->FetchOne($sql));
            break;
}

$page->setTitle($fs->prefs['page_title'] . L('admintoolboxlong'));
$page->pushTpl('admin.'.$area.'.tpl');

?>
