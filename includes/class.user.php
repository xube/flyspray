<?php

class User
{
    var $id = -1;
    var $perms = array();
    var $infos = array();
    var $searches = array();
    var $search_keys = array('string','type','sev','due','dev','cat','status','order','sort',
                             'opened', 'search_in_comments', 'search_for_all', 'reported');

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
            $this->infos = $db->FetchArray($sql);
            $this->id = intval($uid);
        } else {
            $this->infos['real_name'] = L('anonuser');
            $this->infos['user_name'] = '';
        }
        //it not only needs to be not null 
        //it should be an object, instance of the Project class 
        if (is_a($project,'Project')) {
            $this->get_perms($project);
        }
    }

    /* misc functions {{{ */
    function didSearch() {
        if(Get::has('tasks') && Get::val('tasks') != 'last') {
            return true;
        }
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
            if(!$this->didSearch() || Get::val('tasks') == 'last') {
                $arr = unserialize($this->infos['last_search']);
                if (is_array($arr)) {
                    $_GET = array_merge($_GET, $arr);
                    $_GET['tasks'] = 'last';
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

    function get_perms($proj)
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks', 'edit_own_comments',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments', 'edit_assignments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'add_to_assignees', 'view_reports', 'add_votes', 'group_open');

        $this->perms = array();
        foreach ($fields as $key) {
            $this->perms[$key] = 0;
        }
            
        if (!$this->isAnon()) {
            $max = array_map(create_function('$x', 'return "MAX($x) AS $x";'),
                    $fields);

            // Get the global group permissions for the current user
            $sql = $db->Query("SELECT  ".join(', ', $max).", MAX(CASE WHEN g.belongs_to_project = '0' THEN 0 ELSE g.group_id END) as project_group
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                                WHERE  uig.user_id = ?  AND
                                       (g.belongs_to_project = '0' OR g.belongs_to_project = ?)",
                                array($this->id, $proj->id));

            $this->perms = $db->fetchArray($sql);
            
            $this->infos['project_group'] = $this->perms['project_group'];
            unset($this->perms['project_group']);

            if ($this->perms['is_admin']) {
                $this->perms = array_map(create_function('$x', 'return 1;'), $this->perms);
            }
        }
    }

    function check_account_ok()
    {
        global $fs, $conf;

        if (Cookie::val('flyspray_passhash') !=
                crypt($this->infos['user_pass'], $conf['general']['cookiesalt'])
                || !$this->infos['account_enabled']
                || !$this->perms['group_open'])
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

        return $this->perms['is_admin']
            || ( $this->isAnon() && !$fs->prefs['spam_proof']
                    && $fs->prefs['anon_reg']);
    }

    function can_create_group()
    {
        global $proj;
        return $this->perms['is_admin']
            || $this->perms['manage_project'];
    }

    function can_edit_comment($comment)
    {
        return $this->perms['edit_comments']
               || ($comment['user_id'] == $this->id && $this->perms['edit_own_comments']);
    }

    function can_view_project($proj)
    {
        return $proj->prefs['project_is_active']
            && ($proj->prefs['others_view'] || $this->perms['view_tasks']);
    }

    function can_view_task($task)
    {
        global $fs, $proj;
        
        if($this->isAnon() && $task['task_token'] && Get::val('task_token') == $task['task_token']) {
            return true;
        }
        
        if ($this->isAnon() && !$proj->prefs['others_view']) {
            return false;
        }

        if ($task['opened_by'] == $this->id && !$this->isAnon()
            || (!$task['mark_private'] && ($this->perms['view_tasks'] || $proj->prefs['others_view'] || $task['others_view']))
            || $this->perms['manage_project']) {
            return true;
        }
               
        return in_array($this->id, $fs->GetAssignees($task['task_id']));
    }

    function can_edit_task($task)
    {
        global $fs;
        
        return !$task['is_closed']
            && ($this->perms['modify_all_tasks'] ||
                    ($this->perms['modify_own_tasks']
                     && in_array($this->id, $fs->GetAssignees($task['task_id']))));
    }

    function can_take_ownership($task)
    {
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);
            
        return $this->perms['edit_assignments'] &&
                  (($this->perms['assign_to_self'] && empty($assignees))
               || ($this->perms['assign_others_to_self'] && !in_array($this->id, $assignees)));
    }
    
    function can_add_to_assignees($task)
	 { 
        global $fs;
         
        return ($this->perms['edit_assignments'] && $this->perms['add_to_assignees'] && !in_array($this->id, $fs->GetAssignees($task['task_id'])));
    }
	 
    function can_close_task($task)
    {
        return ($this->perms['close_own_tasks'] && $task['assigned_to'] == $this->id)
            || $this->perms['close_other_tasks'];
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
        return $this->perms['open_new_tasks'] || $proj->prefs['anon_open'];
    }

    function can_mark_private($task)
    {
        global $fs;
        
        return !$task['mark_private']
            && ($this->perms['manage_project'] || in_array($this->id, $fs->GetAssignees($task['task_id'])));
    }

    function can_mark_public($task)
    {
        global $fs;
        
        return $task['mark_private']
            && ($this->perms['manage_project'] || in_array($this->id, $fs->GetAssignees($task['task_id'])));
    }
    
    function can_vote($task_id)
    {
        global $db;
        
        // Check that the user hasn't already voted today or for this task
        $check = $db->Query('SELECT vote_id
                               FROM {votes}
                              WHERE user_id = ? AND (task_id = ? OR date_time > ?)',
                             array($this->id, $task_id, time() - 86400));

        return $this->perms['add_votes'] && !$db->CountRows($check);
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
