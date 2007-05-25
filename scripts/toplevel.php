<?php

  /********************************************************\
  | Top level project overview                             |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoToplevel extends FlysprayDo
{
    function is_accessible()
    {
        return true;
    }

    function is_projectlevel() {
        return true;
    }

    function show()
    {
        global $page, $user, $proj, $db, $fs;

        if ($proj->id && $user->can_view_project($proj->prefs)) {
            $projects = array(0 => array('project_id' => $proj->id, 'project_title' => $proj->prefs['project_title']));
        } else {
            $projects = $fs->projects;
        }

        $most_wanted = array();
        $stats = array();

        // Most wanted tasks for each project
        foreach ($projects as $project) {
            $sql = $db->SelectLimit('SELECT v.task_id, count(*) AS num_votes
                                 FROM {votes} v
                            LEFT JOIN {tasks} t ON v.task_id = t.task_id AND t.project_id = ?
                                WHERE t.is_closed = 0
                             GROUP BY v.task_id
                             ORDER BY num_votes DESC', 5, 0,
                             array($project['project_id']));

            $most_wanted[$project['project_id']] = ($arr = $sql->GetArray()) ? $arr : array();
        }

        // Project stats
        foreach ($projects as $project) {
            $sql = $db->GetOne('SELECT count(*) FROM {tasks} WHERE project_id = ?',
                                array($project['project_id']));
            $stats[$project['project_id']]['all'] = $sql;
            $sql = $db->GetOne('SELECT count(*) FROM {tasks} WHERE project_id = ? AND is_closed = 0',
                                array($project['project_id']));
            $stats[$project['project_id']]['open'] = $sql;
            $sql = $db->GetOne('SELECT avg(percent_complete) FROM {tasks} WHERE project_id = ? AND is_closed = 0',
                              array($project['project_id']));
            $stats[$project['project_id']]['average_done'] = round($sql, 0);
            $sql = $db->GetCol('SELECT u.user_id
                                  FROM {users} u
                             LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                             LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                 WHERE g.manage_project = 1 AND g.project_id = ?',
                                array($project['project_id']));
            $stats[$project['project_id']]['project_managers'] = implode(', ', array_map('tpl_userlink', $sql));
        }

        $page->assign('most_wanted', $most_wanted);
        $page->assign('stats', $stats);
        $page->assign('projects', $projects);
        $feed_auth = ($user->isAnon() ? '' : '&user_id=' . $user->id . '&auth=' . md5($user->infos['user_pass'] . $user->infos['register_date']));

        $page->assign('feed_auth', $feed_auth);
        $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('toplevel'));
        $page->pushTpl('toplevel.tpl');
    }
}

?>
