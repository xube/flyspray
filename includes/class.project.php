<?php

class Project
{
    var $id = 0;
    var $prefs = array();

    function Project($id)
    {
        global $db, $fs;

        if (is_numeric($id) && $id > 0) {
            $sql = $db->Query("SELECT p.*, c.content AS pm_instructions, c.last_updated AS cache_update
                                 FROM {projects} p
                            LEFT JOIN {cache} c ON c.topic = p.project_id AND c.type = 'msg'
                                WHERE p.project_id = ?", array($id));
            if ($db->countRows($sql)) {
                $this->prefs = $db->FetchRow($sql);
                $this->id    = (int) $this->prefs['project_id'];
                return;
            }
        }

        $this->id = 0;
        $this->prefs['project_title'] = L('allprojects');
        $this->prefs['theme_style']   = $fs->prefs['global_theme'];
        $this->prefs['lang_code']   = $fs->prefs['lang_code'];
        $this->prefs['others_view'] = 1;
        $this->prefs['intro_message'] = '';
        $this->prefs['anon_open'] = 0;
        $this->prefs['feed_description']  = L('feedforall');
        $this->prefs['feed_img_url'] = '';
        $this->prefs['default_entry'] = 'index';
        $this->prefs['notify_reply'] = '';
    }

    function setCookie()
    {
        Flyspray::setCookie('flyspray_project', $this->id);
    }

    /* cached list functions {{{ */

    // helpers {{{

    function get_edit_list($list_id)
    {
        global $db;
        
        //Get the column names of list tables for the group by statement
        $groupby = $db->GetColumnNames('{list_items}',  'lb.list_item_id', 'lb.');
        
        $sql = $db->Query('SELECT lb.*, count(t.task_id) AS used_in_tasks
                             FROM {list_items} lb
                        LEFT JOIN {tasks} t ON lb.list_item_id IN (t.item_status, t.resolution_reason, t.operating_system, t.task_type)
                            WHERE list_id = ?
                         GROUP BY ' . $groupby . '
                         ORDER BY list_position',
                          array($list_id));
        return $db->FetchAllArray($sql);
    }

    /**
     * _list_sql
     *
     * @param mixed $type
     * @param mixed $where
     * @access protected
     * @return string
     * @notes The $where parameter is dangerous, think twice what you pass there..
     */

    function _list_sql($type, $where = null)
    {
        // sanity check.
        if (preg_match('![^A-Za-z0-9_]!', $type)) {
            return '';
        }

        return "SELECT  list_item_id, item_name
                  FROM  {list_items} lb
             LEFT JOIN  {lists} ls ON lb.list_id = ls.list_id
                 WHERE  show_in_list = 1 AND list_name = '" . $type . "'
                        AND (project_id = ? OR project_id = 0)
                        $where
              ORDER BY  list_position";
    }

    // }}}
    // PM dependant functions {{{

    function get_list($type)
    {
        global  $db;
        return $db->cached_query($type, $this->_list_sql($type), array($this->id));
    }

    function listVersions($tense = null, $reported_version = null)
    {
        global $db;

        $params = array($this->id);
        $where = '';

        if (!is_null($tense)) {
            $where = 'AND version_tense = ?';
            $params[] = $tense;
        }

        if (!is_null($reported_version)) {
            $where .= ' OR list_item_id = ?';
            $params[] = $reported_version;
        }
        
        return $db->cached_query('version_' . intval($tense), $this->_list_sql('version', $where), $params);
    }


    function listCategories($project_id = null, $hide_hidden = true, $remove_root = true, $depth = true)
    {
        global $db;

        // start with a empty arrays
        $right = array();
        $cats = array();
        $g_cats = array();

        // null = categories of current project + global project, int = categories of specific project
        if (is_null($project_id)) {
            $project_id = $this->id;
            if ($this->id != 0) {
                $g_cats = $this->listCategories(0);
            }
        }

        // retrieve the left and right value of the root node
        $result = $db->Query("SELECT lft, rgt
                                FROM {list_category} lc
                           LEFT JOIN {lists} l ON lc.list_id = l.list_id
                               WHERE category_name = 'root' AND lft = 1 AND project_id = ?",
                             array($project_id));
        $row = $db->FetchRow($result);

        $groupby = $db->GetColumnNames('{list_category}', 'c.category_id', 'c.');

        // now, retrieve all descendants of the root node
        $result = $db->Query('SELECT c.category_id, c.category_name, c.*, count(t.task_id) AS used_in_tasks
                                FROM {list_category} c
                           LEFT JOIN {tasks} t ON (t.product_category = c.category_id)
                           LEFT JOIN {lists} l ON c.list_id = l.list_id
                               WHERE l.project_id = ? AND lft BETWEEN ? AND ?
                            GROUP BY ' . $groupby . '
                            ORDER BY lft ASC',
                             array($project_id, intval($row['lft']), intval($row['rgt'])));

        while ($row = $db->FetchRow($result)) {
            if ($hide_hidden && !$row['show_in_list'] && !$row['lft'] == 1) {
                continue;
            }

           // check if we should remove a node from the stack
           while (count($right) > 0 && $right[count($right)-1] < $row['rgt']) {
               array_pop($right);
           }
           $cats[] = $row + array('depth' => count($right)-1);

           // add this node to the stack
           $right[] = $row['rgt'];
        }

        // Adjust output for select boxes
        if ($depth) {
            foreach ($cats as $key => $cat) {
                if ($cat['depth'] > 0) {
                    $cats[$key]['category_name'] = str_repeat('...', $cat['depth']) . $cat['category_name'];
                    $cats[$key]['1'] = str_repeat('...', $cat['depth']) . $cat['1'];
                }
            }
        }

        if ($remove_root) {
            unset($cats[0]);
        }

        return array_merge($cats, $g_cats);
    }

    // }}}

    function listAttachments($cid)
    {
        global $db;
        return $db->cached_query(
                'attach_'.intval($cid),
                "SELECT  *
                   FROM  {attachments}
                  WHERE  comment_id = ?
               ORDER BY  attachment_id ASC",
               array($cid));
    }

    function listTaskAttachments($tid)
    {
        global $db;
        return $db->cached_query(
                'attach_'.intval($tid),
                "SELECT  *
                   FROM  {attachments}
                  WHERE  task_id = ? AND comment_id = 0
               ORDER BY  attachment_id ASC",
               array($tid));
    }
    /* }}} */
}

?>
