<?php

class User
{
    var $id = null;
    var $perms = array();
    var $infos = array();

    function User($uid = 0)
    {
        global $db;

        $sql = $db->Query("SELECT  *, g.group_id AS global_group
                             FROM  {users}           u
                       INNER JOIN  {users_in_groups} uig
                       INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                            WHERE  u.user_id = ? AND g.belongs_to_project = '0'",
                    array($uid));
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
        // Only logged in users get to use the 'last search' functionality
        foreach (array('string','type','sev','due','dev','cat','status') as $key) {
            if (Get::has($key)) {
                $db->Query("UPDATE  {users}
                               SET  last_search = ?
                             WHERE  user_id = ?",
                        array($_SERVER['REQUEST_URI'], $user_id)
                );
                break;
            }
        }
    }

    function get_perms($proj)
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'view_reports', 'group_open');

        $this->perms = array();

        if ($this->isAnon()) {
            foreach ($fields as $key) {
                $this->perms[$key] = false;
            }
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
        // TODO : do we want users to be able to edit their own comments ?
        //  || (isset($comment['user_id']) && $comment['user_id'] == $this->id);
    }

    function can_register()
    {
        global $fs;
        return $this->isAnon() && $fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_open_task()
    {
        global $proj;
        return $user->perms['open_new_tasks'] || $proj->prefs['anon_open'];
    }

    /* }}} */
}

?>
