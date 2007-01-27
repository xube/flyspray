<?php

  /********************************************************\
  | Project Managers Toolbox                               |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  | This script is for Project Managers to modify settings |
  | for their project, including general permissions,      |
  | members, group permissions, and dropdown list items.   |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms('manage_project') || !$proj->id) {
    Flyspray::show_error(16);
}

$areas = array('prefs', 'cat', 'editgroup', 'groups', 'users', 'newgroup',
               'pendingreq', 'os', 'res', 'status', 'tt', 'user', 'ver');

switch ($area = Req::enum('area', $areas, 'prefs')) {
    case 'pendingreq':
        $sql = $db->Query("SELECT  *
                             FROM  {admin_requests} ar
                        LEFT JOIN  {tasks} t ON ar.task_id = t.task_id
                        LEFT JOIN  {users} u ON ar.submitted_by = u.user_id
                            WHERE  ar.project_id = ? AND resolved_by = 0
                         ORDER BY  ar.time_submitted ASC", array($proj->id));

        $page->assign('pendings', $db->fetchAllArray($sql));
        break;

    case 'groups':
        $sql = $db->Query('SELECT g.group_id, g.group_name, g.group_desc,
                                  count(uig.user_id) AS num_users
                             FROM {groups} g
                        LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                            WHERE g.project_id = ?
                         GROUP BY g.group_id', array($proj->id));
        $page->assign('groups', $db->FetchAllArray($sql));
        break;

    case 'users':
        // Prepare the sorting
        // XXX: keep in sync with admin.php
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
            $where = substr(str_repeat(' uig.user_id = ? OR ', count($user_list)), 0, -3);
            $sql = $db->Query('SELECT user_id, g.group_id, g.group_name, g.project_id
                                 FROM {groups} g
                            LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                                WHERE ' . $where, array_map(create_function('$x', 'return $x[0];'), $user_list));
            $user_groups = $db->GroupBy($sql, 'user_id', array('group_id', 'group_name', 'project_id'), !REINDEX);
        }

        $page->uses('user_list');
        $page->uses('user_groups');
        break;
}

$page->setTitle($fs->prefs['page_title'] . L('pmtoolbox'));
$page->pushTpl('pm.menu.tpl');
$page->pushTpl('pm.'.$area.'.tpl');
?>
