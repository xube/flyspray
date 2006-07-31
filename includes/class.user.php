<?php

class User
{
    var $id = -1;
    var $perms = array();
    var $infos = array();
    var $searches = array();
    var $search_keys = array('string','type','sev','due','dev','cat','status','order','sort', 'percent', 'changedfrom',
                             'opened', 'search_in_comments', 'search_for_all', 'reported', 'only_primary', 'only_watched',
                             'changedto', 'duedatefrom', 'duedateto', 'openedfrom', 'openedto', 'has_attachment');

    function User($uid = 0, $project = null)
    {
        global $db;

        if ($uid > 0) {
            $sql = $db->Query('SELECT *, g.group_id AS global_group, uig.record_id AS global_record_id
                                 FROM {users} u, {users_in_groups} uig, {groups} g
                                WHERE u.user_id = ? AND uig.user_id = ? AND g.belongs_to_project = 0
                                      AND uig.group_id = g.group_id',
                                array($uid, $uid));
        }
        
        if ($uid > 0 && $db->countRows($sql)) {
            $this->infos = $db->FetchRow($sql);
            $this->id = intval($uid);
        } else {
            $this->infos['real_name'] = L('anonuser');
            $this->infos['user_name'] = '';
        }
        
        $this->get_perms();
    }

    /* misc functions {{{ */
    function didSearch() {
        foreach ($this->search_keys as $key) {
            if (Get::has($key)) {
                return true;
            }
        }
        return false;
    }
    
    function save_search($do = 'index')
    {
        global $db, $baseurl;
        
        if($this->isAnon()) {
            return;
        }
        
        // Only logged in users get to use the 'last search' functionality     
        if ($do == 'index') {
            if(!$this->didSearch() && $this->infos['last_search']) {
                $arr = unserialize($this->infos['last_search']);
                if (is_array($arr)) {
                    $_GET = array_merge($_GET, $arr);
                }
            }
            
            $arr = array();
            foreach ($this->search_keys as $key) {
                $arr[$key] = Get::val($key);
            }            
            
            $db->Query('UPDATE  {users}
                           SET  last_search = ?
                         WHERE  user_id = ?',
                        array(serialize($arr), $this->id));
                        
            if (Get::val('search_name') && $this->didSearch()) {
                $fields = array('search_string'=> serialize($arr), 'time'=> time(),
                                'user_id'=> $this->id , 'name'=> Get::val('search_name'));

                $keys = array('name','user_id');

                $db->Replace('{searches}', $fields, $keys);
            }
        }
        
        $sql = $db->Query('SELECT * FROM {searches} WHERE user_id = ? ORDER BY time DESC', array($this->id));
        $this->searches = $db->FetchAllArray($sql);
    }
    
    function perms($name, $project = null) {      
        if (is_null($project)) {
            global $proj;
            $project = $proj->id;
        }
        
        if (isset($this->perms[$project][$name])) {
            return $this->perms[$project][$name];
        } else if (isset($this->perms[0][$name])) {
            return $this->perms[0][$name];
        } else {
            return 0;
        }
    }

    function get_perms()
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks', 'edit_own_comments',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments', 'edit_assignments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'add_to_assignees', 'view_reports', 'add_votes', 'group_open');

        $this->perms = array(0 => array());
        // Get project settings which are important for permissions
        $sql = $db->Query('SELECT project_id, others_view, project_is_active, anon_open, comment_closed
                             FROM {projects}');
        while ($row = $db->FetchRow($sql)) {
            $this->perms[$row['project_id']] = $row;
        }
        $this->perms[0] = array_map(create_function('$x', 'return 1;'), end($this->perms));
            
        if (!$this->isAnon()) {
            // Get the global group permissions for the current user
            $sql = $db->Query("SELECT  ".join(', ', $fields).", g.belongs_to_project, g.group_id AS project_group
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                            LEFT JOIN  {projects} p ON g.belongs_to_project = p.project_id
                                WHERE  uig.user_id = ?
                             ORDER BY  g.belongs_to_project ASC",
                                array($this->id));

            while ($row = $db->FetchRow($sql)) {
                $this->perms[$row['belongs_to_project']] = array_merge($this->perms[$row['belongs_to_project']], $row);
                if ($row['belongs_to_project']) {
                    foreach ($fields as $key) {
                        $this->perms[$row['belongs_to_project']][$key] = max($this->perms[$row['belongs_to_project']][$key], $this->perms['0'][$key]);
                    }
                }
            }
            
            // Admin permissions
            foreach ($this->perms as $key => $value) {
                foreach ($fields as $perm) {
                    $this->perms[$key][$perm] = max($this->perms[0]['is_admin'], @$this->perms[$key][$perm]);
                }
            }
        }
    }

    function check_account_ok()
    {
        global $fs, $conf;

        if (Cookie::val('flyspray_passhash') !=
                crypt($this->infos['user_pass'], $conf['general']['cookiesalt'])
                || !$this->infos['account_enabled']
                || !$this->perms('group_open'))
        {
            $fs->setcookie('flyspray_userid',   '', time()-60);
            $fs->setcookie('flyspray_passhash', '', time()-60);
            Flyspray::Redirect(CreateURL('logout', null));
        }
    }

    function isAnon()
    {
        return $this->id < 0;
    }

    /* }}} */
    /* permission related {{{ */

    function can_create_user()
    {
        global $fs;

        return $this->perms('is_admin')
            || ( $this->isAnon() && !$fs->prefs['spam_proof']
                    && $fs->prefs['anon_reg']);
    }

    function can_create_group()
    {
        return $this->perms('is_admin')
            || $this->perms('manage_project');
    }

    function can_edit_comment($comment)
    {
        return $this->perms('edit_comments')
               || ($comment['user_id'] == $this->id && $this->perms('edit_own_comments'));
    }

    function can_view_project($proj)
    {
        return $this->perms('view_tasks')
          || ($this->perms('project_is_active', $proj)
              && ($this->perms('others_view', $proj) || @$this->perms('project_group', $proj)));
    }

    function can_view_task($task)
    {
        global $fs;

        if ($this->isAnon() && $task['task_token'] && Get::val('task_token') == $task['task_token']) {
            return true;
        }
        
        if ($this->isAnon() && !$this->perms('others_view', $task['attached_to_project'])) {
            return false;
        }

        if ($task['opened_by'] == $this->id && !$this->isAnon()
            || (!$task['mark_private'] && ($this->perms('view_tasks') || $this->perms('others_view', $task['attached_to_project']) || $task['others_view']))
            || $this->perms('manage_project')) {
            return true;
        }
               
        return in_array($this->id, $fs->GetAssignees($task['task_id']));
    }

    function can_edit_task($task)
    {
        global $fs;
        
        return !$task['is_closed']
            && ($this->perms('modify_all_tasks') ||
                    ($this->perms('modify_own_tasks')
                     && in_array($this->id, $fs->GetAssignees($task['task_id']))));
    }

    function can_take_ownership($task)
    {
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);

        return ($this->perms('assign_to_self', $task['attached_to_project']) && empty($assignees))
               || ($this->perms('assign_others_to_self', $task['attached_to_project']) && !in_array($this->id, $assignees));
    }
    
    function can_add_to_assignees($task)
	 { 
        global $fs;
         
        return ($this->perms('add_to_assignees', $task['attached_to_project']) && !in_array($this->id, $fs->GetAssignees($task['task_id'])));
    }
	 
    function can_close_task($task)
    {
        return ($this->perms('close_own_tasks', $task['attached_to_project']) && in_array($this->id, $task['assigned_to']))
                || $this->perms('close_other_tasks', $task['attached_to_project']);
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
        return $proj->id && ($this->perms('manage_project') ||
                 $this->perms('project_is_active', $proj->id) && ($this->perms('open_new_tasks') || $this->perms('anon_open', $proj->id)));
    }

    function can_change_private($task)
    {
        global $fs;
        
        return !$task['is_closed'] && ($this->perms('manage_project') || in_array($this->id, $fs->GetAssignees($task['task_id'])));
    }
    
    function can_vote($task)
    {
        global $db;
        
        if (!$this->perms('add_votes', $task['attached_to_project'])) {
            return -1;
        }
        
        // Check that the user hasn't already voted this task
        $check = $db->Query('SELECT vote_id
                               FROM {votes}
                              WHERE user_id = ? AND task_id = ?',
                             array($this->id, $task['task_id']));
        if ($db->CountRows($check)) {
            return -2;
        }
        
        // Check that the user hasn't voted more than twice this day
        $check = $db->Query('SELECT vote_id
                               FROM {votes}
                              WHERE user_id = ? AND date_time > ?',
                             array($this->id, time() - 86400));
        if ($db->CountRows($check) > 2) {
            return -3;
        }

        return 1;
    }
    
    function logout()
    {
        global $fs;
        // Set cookie expiry time to the past, thus removing them
        $fs->setcookie('flyspray_userid',   '', time()-60);
        $fs->setcookie('flyspray_passhash', '', time()-60);
        $fs->setcookie('flyspray_project',  '', time()-60);
        if (Cookie::has(session_name())) {
            $fs->setcookie(session_name(), '', time()-60);
        }

        // Unset all of the session variables.
        $_SESSION = array();
        session_destroy();
        
        return !$this->isAnon();
    }

    /* }}} */
}

?>
