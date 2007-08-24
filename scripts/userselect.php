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
        return $user->can_view_userlist();
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
                         g.group_open, count(u.user_id) AS num_users
                    FROM {groups} g
               LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
               LEFT JOIN {users} u ON (uig.user_id = u.user_id AND (g.show_as_assignees = 1 OR g.is_admin = 1))
                   WHERE g.project_id = ?
                GROUP BY g.group_id';

        $page->assign('groups', $db->x->getAll($query, null, $proj->id));

        $page->assign('globalgroups',  $db->x->getAll($query, null, 0));

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

            $users = $db->x->getAll('SELECT u.user_id, user_name, real_name, email_address
                                       FROM {users} u
                                  LEFT JOIN {users_in_groups} uig ON uig.user_id = u.user_id
                                  LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                      WHERE uig.group_id = ? AND (g.show_as_assignees = 1 OR g.is_admin = 1) AND ( ' . $where . ' )' . $sortorder,
                                  null, array_merge(array(Get::val('group_id')), $params));

            // Offset and limit
            $user_list = array();
            $offset = (max(Get::num('pagenum') - 1, 0)) * 20;
            for ($i = $offset; $i < $offset + 20 && $i < count($users); $i++) {
                $user_list[] = $users[$i];
            }
            $page->assign('users', $user_list);
        } else {
            // be tricky ^^: show most assigned users
            $db->setLimit(20);
            $users = $db->x->getAll('SELECT a.user_id, u.user_name, u.real_name, email_address,
                                            count(a.user_id) AS a_count, CASE WHEN t.project_id = ? THEN 1 ELSE 0 END AS my_project
                                       FROM {assigned} a
                                  LEFT JOIN {users} u ON a.user_id = u.user_id
                                  LEFT JOIN {tasks} t ON a.task_id = t.task_id
                                      WHERE ( ' . $where . ' )' . '
                                   GROUP BY a.user_id
                                   ORDER BY my_project DESC, a_count DESC', null, array_merge(array($proj->id), $params));
            $page->assign('users', $users);
        }

        $page->assign('usercount', count($users));
        $page->setTitle($fs->prefs['page_title'] . L('userselect'));
        $page->pushTpl('userselect.tpl');
        $page->finish();
    }
}

?>
