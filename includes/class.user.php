<?php

class User
{
    var $id = null;
    var $perms = array();
    var $infos = array();

    function User($uid = 0)
    {
        global $db;

        $sql = $db->Query("SELECT  *, g.group_id AS global_group,
                                   uig.record_id AS global_record_id
                             FROM  {users}           u
                       INNER JOIN  {users_in_groups} uig
                       INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                            WHERE  u.user_id = ? AND uig.user_id = ? AND g.belongs_to_project = '0'",
                    array($uid, $uid));
        if ($db->countRows($sql)) {
            $this->infos = $db->FetchArray($sql);
            $this->id = $uid;
        } else {
            $this->id = -1;
        }
    }

    /* misc functions {{{ */

    function save_search()
    {
        global $db;
        // Only logged in users get to use the 'last search' functionality
        foreach (array('string','type','sev','due','dev','cat','status','order','sort') as $key) {
            if (Get::has($key) && !Get::val('do')) {
                $db->Query("UPDATE  {users}
                               SET  last_search = ?
                             WHERE  user_id = ?",
                        array($_SERVER['REQUEST_URI'], $this->id)
                );
                $this->infos['last_search'] = $_SERVER['REQUEST_URI'];
                break;
            }
        }
    }

    function get_perms(&$proj)
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'add_to_assignees', 'view_reports', 'group_open');

        $this->perms = array();

        if ($this->isAnon()) {
            foreach ($fields as $key) {
                $this->perms[$key] = 0;
            }
            $this->perms['global_view'] = 0;
        } else {
            $max = array_map(create_function('$x', 'return "MAX($x) AS $x";'),
                    $fields);

            // Get the global group permissions for the current user
            $sql = $db->Query("SELECT  ".join(', ', $max).",
                                       MAX(IF(g.belongs_to_project, view_tasks, 0)) AS global_view
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                                WHERE  uig.user_id = ?  AND
                                       (g.belongs_to_project = '0' OR g.belongs_to_project = ?)",
                                array($this->id, $proj->id));

            $this->perms = $db->fetchArray($sql);
            if ($this->perms['is_admin']) {
                $this->perms = array_map(create_function('$x', 'return 1;'), $this->perms);
            }
        }
        if(!$this->can_view_project($proj)) {
            $proj = new Project(0);
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
            $fs->Redirect($fs->CreateURL('logout', null));
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
        return $this->perms['is_admin']
            || ($this->perms['manage_project'] && !Get::val('project'));
    }

    function can_edit_comment($comment)
    {
        return $this->perms['edit_comments'];
        /*  || (isset($comment['user_id']) && $comment['user_id'] == $this->id);
         *
         * TODO : do we want users to be able to edit their own comments ?
         *
         * Tony says: not really, as it destroys the proper flow of conversation
         *            between users and developers.
         *            perhaps this could be made an project-level option in the future.
         */
    }

    function can_view_project($proj)
    {
        return $proj->prefs['project_is_active']
            && ($proj->prefs['others_view'] || $this->perms['view_tasks']);
    }

    function can_view_task($task)
    {
        global $fs, $proj;        
        if ($this->isAnon() && !$proj->prefs['others_view'])
            return 0;
        
        $assignees = $fs->GetAssignees($task['task_id']);
        
        return $this->perms['manage_project'] || !$task['mark_private']
                    || in_array($this->id, $assignees);
    }

    function can_edit_task($task)
    {
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);
        
        return !$task['is_closed']
            && ($this->perms['modify_all_tasks'] ||
                    ($this->perms['modify_own_tasks']
                     && in_array($this->id, $assignees)));
    }

    function can_take_ownership($task)
    {
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);
            
        return ($this->perms['assign_to_self'] && empty($assignees))
            || ($this->perms['assign_others_to_self'] && !in_array($this->id, $assignees));
    }
    
    function can_add_to_assignees($task)
	 { 
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);
         
        return ($this->perms['add_to_assignees'] && !in_array($this->id, $assignees)
            && !empty($assignees));
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
        $assignees = $fs->GetAssignees($task['task_id']);
        
        return !$task['mark_private']
            && ($this->perms['manage_project'] || in_array($this->id, $assignees));
    }

    function can_mark_public($task)
    {
        global $fs;
        $assignees = $fs->GetAssignees($task['task_id']);
        
        return $task['mark_private']
            && ($this->perms['manage_project'] || in_array($this->id, $assignees));
    }

    /* }}} */
}

?>
