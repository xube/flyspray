<?php

class User
{
    var $id = -1;
    var $perms = array();
    var $infos = array();
    var $searches = array();
    var $search_keys = array('string', 'sev', 'dev', 'order', 'sort', 'percent', 'changedfrom', 'closedfrom',
                             'opened', 'closed', 'search_in_comments', 'search_for_all', 'only_primary', 'only_watched', 'closedto',
                             'changedto', 'duedatefrom', 'duedateto', 'openedfrom', 'openedto', 'has_attachment');

    function User($uid = 0)
    {
        global $db;

        if ($uid > 0) {
            $this->infos = $db->x->getRow(
                              'SELECT *, g.group_id AS global_group, uig.record_id AS global_record_id
                                 FROM {users} u, {users_in_groups} uig, {groups} g
                                WHERE u.user_id = ? AND uig.user_id = ? AND g.project_id = 0
                                      AND uig.group_id = g.group_id', null,
                                array($uid, $uid));
        }

        if ($uid > 0 && is_array($this->infos)) {
            $this->id = intval($uid);
        } else {
            $this->infos = array('real_name' => L('anonuser'), 'user_name' => '');
            // Get a users default language, based on HTTP data
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                if (preg_match('/(\w+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $languages)) {
                    $this->infos['lang_code'] = $languages[1];
                }
            }
        }

        $this->get_perms();
    }

    /**
     * save_search
     *
     * @param string $do
     * @access public
     * @return void
     */
    function save_search($do = 'index')
    {
        global $db, $proj;

        if ($this->isAnon()) {
            return;
        }

        // Only logged in users get to use the 'last search' functionality
        if ($do == 'index') {
            $arr = array();
            foreach ($this->search_keys as $key) {
                $arr[$key] = Get::val($key);
            }
            foreach ($proj->fields as $field) {
                $arr['field' . $field->id] = Get::val('field' . $field->id);
            }

            if (Get::val('search_name')) {
                $fields = array('search_string'=> array('value' => serialize($arr)),
                                'time'=> array('value' => time()),
                                'user_id'=> array('value' => $this->id, 'key' => true),
                                'name'=> array('value' => Get::val('search_name'), 'key' => true));

                $db->Replace('{searches}', $fields);
            }
        }

        $this->searches = $db->x->getAll('SELECT * FROM {searches} WHERE user_id = ? ORDER BY name ASC', null, array($this->id));
    }

    function perms($name, $project = null) {
        if (is_null($project)) {
            global $proj;
            $project = $proj->id;
        }

        if (isset($this->perms[$project][$name])) {
            return $this->perms[$project][$name];
        } else {
            return 0;
        }
    }

    function get_perms()
    {
        global $db, $fs;

        $fields = array_merge($fs->perms, array('is_admin', 'group_open'));

        $this->perms = array(0 => array());
        // Get project settings which are important for permissions
        $sql = $db->query('SELECT project_id, others_view, anon_open, comment_closed,
                                  anon_view_tasks
                             FROM {projects}');
        while ($row = $sql->fetchRow()) {
            $this->perms[$row['project_id']] = $row;
        }
        // Fill permissions for global project
        $this->perms[0] = array_map(create_function('$x', 'return 1;'), end($this->perms));

        if (!$this->isAnon()) {
            // Get the global group permissions for the current user
            $sql = $db->x->getAll("SELECT  ".join(', ', $fields).", g.project_id, uig.record_id,
                                       g.group_open, g.group_id AS project_group
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                            LEFT JOIN  {projects} p ON g.project_id = p.project_id
                                WHERE  uig.user_id = ?
                             ORDER BY  g.project_id, g.group_id ASC", null,
                                array($this->id));

            foreach ($sql as $row) {
                if (!isset($this->perms[$row['project_id']])) {
                    // should not happen, so clean up the DB
                    $db->x->execParam('DELETE FROM {users_in_groups} WHERE record_id = ?', array($row['record_id']));
                    continue;
                }

                $this->perms[$row['project_id']] = array_merge($this->perms[$row['project_id']], $row);
            }

            // Set missing permissions and attachments
            foreach ($this->perms as $proj_id => $value) {
                foreach ($fields as $key) {
                    if ($key == 'project_group') {
                        continue;
                    }

                    $this->perms[$proj_id][$key] = max($this->perms[0]['is_admin'], @$this->perms[$proj_id][$key], $this->perms[0][$key]);
                    if ($proj_id) {
                        $this->perms[$proj_id][$key] = max($this->perms[$proj_id]['manage_project'], $this->perms[$proj_id][$key]);
                    }
                }

                // nobody can upload files if uploads are disabled at the system level..
                if (!$fs->max_file_size || !is_writable(FS_ATTACHMENTS_DIR)) {
                    $this->perms[$proj_id]['create_attachments'] = 0;
                }
            }
        }

        // project list of $fs
		$projects = $db->x->getAll(
		        'SELECT  project_id, project_title, others_view, project_prefix,
		                 upper(project_title) AS sort_names
		           FROM  {projects}
		       ORDER BY  sort_names');

		$fs->projects = array_filter($projects, array(&$this, 'can_view_project'));
    }

    function isAnon()
    {
        return $this->id < 0;
    }

    /* }}} */
    /* permission related {{{ */

    function can_edit_comment($comment)
    {
        return $this->perms('edit_comments')
               || ($comment['user_id'] == $this->id && $this->perms('edit_own_comments'));
    }

    function can_view_project($proj)
    {
        if (is_array($proj) && isset($proj['project_id'])) {
            $proj = $proj['project_id'];
        }

        return $this->perms('view_tasks', $proj)
             || $this->perms('others_view', $proj) || $this->perms('project_group', $proj);
    }

    function can_view_task($task)
    {
        if ($task['task_token'] && Get::val('task_token') == $task['task_token']) {
            return true;
        }

        if (!$this->can_view_project($task['project_id'])) {
            return false;
        }

        if ($task['opened_by'] == $this->id && !$this->isAnon()
            || (!$task['mark_private'] && ($this->perms('view_tasks', $task['project_id']) || $this->perms('anon_view_tasks', $task['project_id'])))
            || $this->perms('manage_project', $task['project_id']) || $this->perms('view_private', $task['project_id'])) {
            return true;
        }

        return !$this->isAnon() && in_array($this->id, Flyspray::GetAssignees($task['task_id']));
    }

    function can_edit_task($task)
    {
        return !$task['is_closed']
            && ($this->perms('modify_all_tasks', $task['project_id']) ||
                    ($this->perms('modify_own_tasks', $task['project_id'])
                     && in_array($this->id, Flyspray::GetAssignees($task['task_id']))));
    }

    function can_correct_task($task)
    {
        return $this->id == $task['opened_by'] && ($task['date_opened'] > time() - 60*60*24);
    }

    function can_take_ownership($task)
    {
        $assignees = Flyspray::GetAssignees($task['task_id']);

        return ($this->perms('assign_to_self', $task['project_id']) && empty($assignees))
               || ($this->perms('assign_others_to_self', $task['project_id']) && !in_array($this->id, $assignees));
    }

    function can_add_to_assignees($task)
	 {
        return ($this->perms('add_to_assignees', $task['project_id']) && !in_array($this->id, Flyspray::GetAssignees($task['task_id'])));
    }

    function can_close_task($task)
    {
        return ($this->perms('close_own_tasks', $task['project_id']) && in_array($this->id, $task['assigned_to']))
                || $this->perms('close_other_tasks', $task['project_id']);
    }

    function can_self_register()
    {
        global $fs;
        return $this->isAnon() && !$fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_register()
    {
        global $fs;
        return $this->isAnon() && $fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_open_task($proj)
    {
        $pid = (is_int($proj) ? $proj : (!is_object($proj) ? $proj['project_id'] : $proj->id));

        return $pid && ($this->perms('manage_project', $pid) || $this->perms('open_new_tasks', $pid)
                || $this->perms('anon_open', $pid));
    }

    function can_change_private($task)
    {
        return !$task['is_closed'] && ($this->perms('manage_project', $task['project_id'])
                || $this->perms('edit_private', $task['project_id'])
                || in_array($this->id, Flyspray::GetAssignees($task['task_id'])));
    }

    function can_view_userlist()
    {
        global $fs;
        return $fs->prefs['anon_userlist'] || $this->perms('view_userlist');
    }

    function can_vote($task)
    {
        global $db;

        if (!$this->perms('add_votes', $task['project_id'])) {
            return -1;
        }

        // Check that the user hasn't already voted this task
        $check = $db->x->GetOne('SELECT vote_id
                                FROM {votes}
                               WHERE user_id = ? AND task_id = ?',
                              null, array($this->id, $task['task_id']));
        if ($check) {
            return -2;
        }

        // Check that the user hasn't voted more than twice this day
        $check = $db->x->GetOne('SELECT count(*)
                                FROM {votes}
                               WHERE user_id = ? AND date_time > ?',
                              null, array($this->id, time() - 86400));
        if ($check > 2) {
            return -3;
        }

        return 1;
    }

    function logout()
    {
        // Set cookie expiry time to the past, thus removing them
        Flyspray::setcookie('flyspray_userid',   '', time()-60);
        Flyspray::setcookie('flyspray_passhash', '', time()-60);
        if (Cookie::has(session_name())) {
            Flyspray::setcookie(session_name(), '', time()-60);
        }

        // Unset all of the session variables.
        $_SESSION = array();
        session_destroy();

        return !$this->isAnon();
    }

    /* }}} */
}

?>
