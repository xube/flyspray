<?php

class User
{
    var $id = null;
    var $perms = array();
    var $infos = array();

    function User($uid = -1)
    {
        global $db;

        if ($uid) {
            $sql = $db->Query("SELECT * FROM {users} WHERE user_id = ?", array($uid));
            if ($db->countRows($sql)) {
                $this->infos = $db->FetchArray($sql);
            }
        }
        $this->id = $uid;
    }

    function get_perms($project)
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'view_reports', 'group_open');

        $max = array_map(create_function('$x', 'return "MAX($x) AS $x";'),
                $fields);

        // Get the global group permissions for the current user
        $sql = $db->Query("SELECT  ".join(', ', $max).",
                                   MAX(IF(g.belongs_to_project, view_tasks, 0)) AS global_view
                             FROM  {groups} g
                        LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                            WHERE  uig.user_id = ?  AND 
                                   (g.belongs_to_project = '0' OR g.belongs_to_project = ?)",
                            array($this->id, $project->id));

        $this->perms = $db->fetchArray($sql);
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
}

?>
