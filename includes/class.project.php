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
            $this->id    = null;
        }
    }

    function exists()
    {
        return !is_null($this->id);
    }

    function setCookie()
    {
        global $fs;
        $fs->setCookie('flyspray_project', $this->id, time()+60*60*24*30);
    }

    /* cached list functions {{{ */

    function _cached_query($idx, $sql, $sqlargs = array())
    {
        global $db;

        if (isset($this->cache[$idx])) {
            return $this->cache[$idx];
        }

        $sql = $db->Query($sql, $sqlargs);
        return ($this->cache[$idx] = $db->fetchAllArray($sql));
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

    function listTaskTypes()
    {
        return $this->_cached_query(
                'task_types',
                "SELECT  tt.*, count(t.task_id) AS used_in_tasks
                   FROM  {list_tasktype} tt
              LEFT JOIN  {tasks}         t  ON ( t.task_type = tt.tasktype_id )
                  WHERE  project_id = ?
               GROUP BY  tt.tasktype_id
               ORDER BY  list_position",
                array($this->id));
    }

    function listOs()
    {
        return $this->_cached_query(
                'os',
                "SELECT  os.*, count(t.task_id) AS used_in_tasks
                   FROM  {list_os} os
              LEFT JOIN  {tasks} t ON (t.operating_system = os.os_id
                                       AND t.attached_to_project = os.project_id)
                  WHERE  os.project_id = ?
               GROUP BY  os.os_id, os.project_id, os.os_name, os.list_position, os.show_in_list
               ORDER BY  list_position",
                array($this->id));
    }

    function listVersions()
    {
        return $this->_cached_query(
                'version',
                "SELECT  v.*, count(t.task_id) AS used_in_tasks
                   FROM  {list_version} v
              LEFT JOIN  {tasks} t ON ( ( t.product_version = v.version_id
                                        OR t.closedby_version = v.version_id )
                                      AND t.attached_to_project = v.project_id )
                  WHERE  v.project_id = ?
               GROUP BY  v.version_id
               ORDER BY  list_position",
                array($this->id));
    }

    function listResolutions()
    {
        return $this->_cached_query(
                'resolutions',
                "SELECT  r.*, count(t.task_id) AS used_in_tasks
                   FROM  {list_resolution} r
              LEFT JOIN  {tasks} t ON ( t.resolution_reason = r.resolution_id )
                  WHERE  project_id = ?
               GROUP BY  r.resolution_id
               ORDER BY  list_position",
                array($this->id));
    }

    function listCatsIn($mother_cat = null)
    {
        if (is_null($mother_cat)) {
            return $this->_cached_query(
                    'cats_in'.$mother_cat,
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
                    'cats_in'.$mother_cat,
                    "SELECT  c.*, count(t.task_id) AS used_in_tasks
                       FROM  {list_category} c
                  LEFT JOIN  {tasks} t ON (t.product_category = c.category_id)
                      WHERE  project_id = ? AND parent_id = ?
                   GROUP BY  c.category_id, c.project_id, c.category_name,
                             c.list_position, c.show_in_list, c.category_owner, c.parent_id
                   ORDER BY  list_position",
                    array($this->id, $mother_cat));
        }
    }

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

    /* }}} */
}

?>
