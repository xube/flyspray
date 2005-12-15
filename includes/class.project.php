<?php

class Project
{
    var $id;
    var $prefs = array();
    var $cache = array();

    function Project($id)
    {
        global $db,$language,$fs;

        $sql = $db->Query("SELECT * FROM {projects} WHERE project_id = ?", array($id));
        if ($db->countRows($sql)) {
            $this->prefs = $db->fetchArray($sql);
            $this->id    = $id;
        } else {
            $this->id    = 0;
            $this->prefs['project_title'] = $language['allprojects'];
            $this->prefs['theme_style']   = $fs->prefs['global_theme'];
        }
    }

    function checkExists()
    {
        global $fs;
        return !is_null($this->id);
        $fs->redirect('index.php?project=' . $fs->prefs['default_project']);
    }

    function setCookie()
    {
        global $fs;
        $fs->setCookie('flyspray_project', $this->id, time()+60*60*24*30);
    }

    /* cached list functions {{{ */

    // helpers {{{

    function _cached_query($idx, $sql, $sqlargs = array())
    {
        global $db;

        if (isset($this->cache[$idx])) {
            return $this->cache[$idx];
        }

        $sql = $db->Query($sql, $sqlargs);
        return ($this->cache[$idx] = $db->fetchAllArray($sql));
    }

    function _pm_list_sql($type, $join)
    {
        settype($join, 'array');
        $join = 't.'.join(" = l.{$type}_id OR t.", $join)." = l.{$type}_id";
        return "SELECT  l.*, count(t.task_id) AS used_in_tasks
                  FROM  {list_{$type}} l
             LEFT JOIN  {tasks}        t  ON ($join)
                            AND t.attached_to_project = l.project_id
                 WHERE  project_id = ?
              GROUP BY  l.{$type}_id
              ORDER BY  list_position";
    }

    function _list_sql($type, $where = null)
    {
        return "SELECT  {$type}_id, {$type}_name
                  FROM  {list_{$type}}
                 WHERE  show_in_list = '1' AND ( project_id = ? OR project_id = '0' )
                        $where
              ORDER BY  list_position";
    }

    // }}}
    // PM dependant functions {{{

    function listTaskTypes($pm = false)
    {
        if ($pm) {
            return $this->_cached_query(
                    'pm_task_types',
                    $this->_pm_list_sql('tasktype', 'task_type'),
                    array($this->id));
        } else {
            return $this->_cached_query(
                    'task_types', $this->_list_sql('tasktype'), array($this->id));
        }
    }

    function listOs($pm = false)
    {
        if ($pm) {
            return $this->_cached_query(
                    'pm_os',
                    $this->_pm_list_sql('os', 'operating_system'),
                    array($this->id));
        } else {
            return $this->_cached_query('os', $this->_list_sql('os'),
                    array($this->id));
        }
    }

    function listVersions($pm = false, $tense = 2, $reported_version = null)
    {
        if ($pm) {
            return $this->_cached_query(
                    'pm_version',
                    $this->_pm_list_sql('version', array('product_version', 'closedby_version')),
                    array($this->id));
        } elseif(is_null($reported_version)) {
            return $this->_cached_query(
                    'version_'.$tense,
                    $this->_list_sql('version', "AND version_tense = '$tense'"),
                    array($this->id));
        } else {
            return $this->_cached_query(
                    'version_'.$tense,
                    $this->_list_sql('version', "AND version_tense = '$tense' OR version_id = '$reported_version' "),
                    array($this->id));
        }
    }

    function listCatsIn($pm = false, $mother_cat = null)
    {
        if ($pm) {
            if (is_null($mother_cat)) {
                return $this->_cached_query(
                        'pm_cats_in'.$mother_cat,
                        "SELECT  c.*, count(t.task_id) AS used_in_tasks
                           FROM  {list_category} c
                      LEFT JOIN  {tasks} t ON (t.product_category = c.category_id)
                          WHERE  project_id = ? AND parent_id < 1
                       GROUP BY  c.category_id, c.project_id, c.category_name,
                                 c.list_position, c.show_in_list, c.category_owner, c.parent_id
                       ORDER BY  list_position",
                        array($this->id));
            } else {
                return $this->_cached_query(
                        'pm_cats_in'.$mother_cat,
                        "SELECT  c.*, count(t.task_id) AS used_in_tasks
                           FROM  {list_category} c
                      LEFT JOIN  {tasks} t ON (t.product_category = c.category_id)
                          WHERE  project_id = ? AND parent_id = ?
                       GROUP BY  c.category_id, c.project_id, c.category_name,
                                 c.list_position, c.show_in_list, c.category_owner, c.parent_id
                       ORDER BY  list_position",
                        array($this->id, $mother_cat));
            }
        } else {
            return $this->_cached_query('cats_in',
                    "SELECT  a.category_id,
                             IF(a.parent_id,
                                 CONCAT('...', a.category_name),
                                 a.category_name) AS category_name,
                             IF(a.parent_id, b.list_position, a.list_position) AS main_pos
                       FROM  {list_category} a
                  LEFT JOIN  {list_category} b ON a.parent_id = b.category_id
                      WHERE  a.show_in_list = '1'
                             AND ( a.project_id = ? OR a.project_id = '0' )
                   ORDER BY  main_pos, a.list_position",
                       array($this->id));
        }
    }

    function listResolutions($pm = false)
    {
        if ($pm) {
            return $this->_cached_query(
                    'pm_resolutions',
                    $this->_pm_list_sql('resolution', 'resolution_reason'),
                    array($this->id));
        } else {
            return $this->_cached_query('resolution',
                    $this->_list_sql('resolution'), array($this->id));
        }
    }

    // }}}

    function listUsersIn($group_id = null)
    {
        return $this->_cached_query(
                'users_in'.$group_id,
                "SELECT  u.*
                   FROM  {users}           u
             INNER JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
             INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                  WHERE  g.group_id = ?
               ORDER BY  u.user_name ASC",
                array($group_id));
    }

    function listGroups($admin = false)
    {
        if(!$admin) {
            return $this->_cached_query(
                'groups',
                "SELECT  * FROM {groups}
                  WHERE  belongs_to_project = ?
                  ORDER  BY group_id ASC",
                  array($this->id));
        } else {
            return $this->_cached_query(
                'admingroups',
                "SELECT  * FROM {groups}
                  WHERE  belongs_to_project = 0 
                  ORDER  BY group_id ASC");
        }        
    }

    function listAttachments($cid)
    {
        return $this->_cached_query(
                'attach_'.$cid,
                "SELECT  *
                   FROM  {attachments}
                  WHERE  comment_id = ?
               ORDER BY  attachment_id ASC",
               array($cid));
    }

    function listTaskAttachments($tid)
    {
        return $this->_cached_query(
                'attach_'.$tid,
                "SELECT  *
                   FROM  {attachments}
                  WHERE  task_id = ? AND comment_id = 0
               ORDER BY  attachment_id ASC",
               array($tid));
    }
    
    // It returns an array of user ids and usernames/fullnames/groups
    function UserList($excluded = array(), $all = false)
    {
      global $db, $fs;
      global $conf;
      
      $id = ($all) ? 0 : $this->id;
      
      // Create an empty array to put our users into
      $users = array();
    
      // Retrieve all the users in this project or from global groups.  A tricky query is required...
      $get_project_users = $db->Query("SELECT uig.user_id, u.real_name, u.user_name, g.group_name
                                       FROM {users_in_groups} uig
                                       LEFT JOIN {users} u ON uig.user_id = u.user_id
                                       LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                       LEFT JOIN {projects} p ON g.belongs_to_project = p.project_id
                                       WHERE g.belongs_to_project = ?
                                       ORDER BY g.group_id ASC",
                                       array($id)
                                     );
    
      while ($row = $db->FetchArray($get_project_users))
      {
         if (!in_array($row['user_id'], $users) && !in_array($row['user_id'],$excluded))
               $users = $users + array($row['user_id'] => '[' . $row['group_name'] . '] ' . $row['real_name'] . ' (' . $row['user_name'] . ')');
      }
    
      // Get the list of global groups that can be assigned tasks
      $these_groups = explode(' ', $fs->prefs['assigned_groups']);
      foreach ($these_groups AS $key => $val)
      {
         // Get the list of users from the global groups above
         $get_global_users = $db->Query("SELECT uig.user_id, u.real_name, u.user_name, g.group_name
                                         FROM {users_in_groups} uig
                                         LEFT JOIN {users} u ON uig.user_id = u.user_id
                                         LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                         WHERE uig.group_id = ?",
                                         array($val)
                                       );
    
         // Cycle through the global userlist, adding each user to the array
         while ($row = $db->FetchArray($get_global_users))
         {
            if (!in_array($row['user_id'], $users) && !in_array($row['user_id'],$excluded))
               $users = $users + array($row['user_id'] => '[' . $row['group_name'] . '] ' . $row['real_name'] . ' (' . $row['user_name'] . ')');
         }
      }
    
      return $users;
    
    // End of UserList() function
    }
    /* }}} */
}

?>
