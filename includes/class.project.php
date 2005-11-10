<?php

class Project
{
    var $id;
    var $prefs = array();
    var $cache = array();

    function Project($id)
    {
        global $db;

        $sql = $db->Query("SELECT * FROM {projects} WHERE project_id = ?", array($id));
        if ($db->countRows($sql)) {
            $this->prefs = $db->fetchArray($sql);
            $this->id    = $id;
        } else {
            $this->id    = 0;
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

    function listVersions($pm = false, $tense = 2)
    {
        if ($pm) {
            return $this->_cached_query(
                    'pm_version',
                    $this->_pm_list_sql('version', array('product_version', 'closedby_version')),
                    array($this->id));
        } else {
            return $this->_cached_query(
                    'version_'.$tense,
                    $this->_list_sql('version', "AND version_tense = '$tense'"),
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
        if (is_null($group_id)) {
            // list of users in no group specific to that project

            return $this->_cached_query(
                    'users_in'.$group_id,
                    "SELECT  u.*, MAX(g.group_id) AS gid
                       FROM  {users}           u
                  LEFT JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
                  LEFT JOIN  {groups}          g   ON uig.group_id = g.group_id
                                                      AND g.belongs_to_project = ?
                   GROUP BY  u.user_id
                     HAVING  gid IS NULL
                   ORDER BY  u.user_name ASC",
                    array($this->id));
        } else {
            return $this->_cached_query(
                    'users_in'.$group_id,
                    "SELECT  u.*
                       FROM  {users}           u
                 INNER JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
                 INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                      WHERE  g.belongs_to_project = ? AND g.group_id = ?
                   ORDER BY  u.user_name ASC",
                    array($this->id, $group_id));
        }
    }

    function listGroups()
    {
        return $this->_cached_query(
                'groups',
                "SELECT  * FROM {groups}
                  WHERE  belongs_to_project = ?
                  ORDER  BY group_id ASC",
                  array($this->id));
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
                  WHERE  task_id = ?
               ORDER BY  attachment_id ASC",
               array($tid));
    }
    /* }}} */
}

?>
