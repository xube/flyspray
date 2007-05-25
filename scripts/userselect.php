<?php

  /*********************************************************\
  | View a user's profile                                   |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoUserSelect extends FlysprayDo
{
    function is_accessible()
    {
        global $user;
        return $user->perms('view_userlist');
    }

    function is_projectlevel() {
        return true;
    }

    function show()
    {
        global $db, $page, $fs, $proj, $do;
        $page = new FSTpl;
        $page->setTheme($proj->prefs['theme_style']);
        $page->assign('do', $do);
        $page->pushTpl('baseheader.tpl');

        $query = 'SELECT g.group_id, g.group_name, g.group_desc,
                                    g.group_open, count(uig.user_id) AS num_users
                               FROM {groups} g
                          LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                              WHERE g.project_id = ?
                           GROUP BY g.group_id';

        $sql = $db->Execute($query, array($proj->id));
        $page->assign('groups', $sql->GetArray());

        $sql = $db->Execute($query, array(0));
        $page->assign('globalgroups', $sql->GetArray());

        // Search conditions
        $where = array();
        $params = array();
        foreach (array('user_name', 'real_name') as $key) {
            if (Post::val($key)) {
                $where[] = ' ' . $key . ' LIKE ? ';
                $params[] = '%' . Post::val($key) . '%';
            }
        }
        $where = (count($where)) ? implode(' OR ', $where) : '1=1';

        // fill the table with users
        if (Get::val('group_id', -1) > 0) {
            $order_keys = array('username' => 'user_name',
                                'realname' => 'real_name');
            $order_column = $order_keys[Filters::enum(Get::val('order', 'username'), array_keys($order_keys))];
            $sortorder  = sprintf('ORDER BY %s %s, u.user_id ASC',
                $order_column, Filters::enum(Get::val('sort', 'desc'), array('asc', 'desc')));

            $sql = $db->Execute('SELECT u.user_id, user_name, real_name, email_address
                                   FROM {users} u
                              LEFT JOIN {users_in_groups} uig ON uig.user_id = u.user_id
                                  WHERE uig.group_id = ? AND ( ' . $where . ' )' . $sortorder,
                                  array_merge(array(Get::val('group_id')), $params));
            $users = $sql->GetArray();

            // Offset and limit
            $user_list = array();
            $offset = (max(Get::num('pagenum') - 1, 0)) * 20;
            for ($i = $offset; $i < $offset + 20 && $i < count($users); $i++) {
                $user_list[] = $users[$i];
            }
            $page->assign('users', $user_list);
        } else {
            // be tricky ^^: show most assigned users
            $sql = $db->SelectLimit('SELECT a.user_id, u.user_name, u.real_name, email_address,
                                            count(a.user_id) AS a_count
                                       FROM {assigned} a
                                  LEFT JOIN {users} u ON a.user_id = u.user_id
                                  LEFT JOIN {tasks} t ON a.task_id = t.task_id
                                   WHERE t.project_id = ? AND ( ' . $where . ' )' . '
                                   GROUP BY a.user_id
                                   ORDER BY a_count DESC', 20, 0, array_merge(array($proj->id), $params));
            $page->assign('users', $users = $sql->GetArray());
        }

        $page->assign('usercount', count($users));
        $page->setTitle($fs->prefs['page_title'] . L('userselect'));
        $page->pushTpl('userselect.tpl');
        $page->finish();
    }
}

?>