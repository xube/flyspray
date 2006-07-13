<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->can_view_project($proj)) {
    $proj = new Project(0);
    $user->get_perms($proj);
}

$page->uses('severity_list', 'priority_list');

$perpage = '20';
if (@$user->infos['tasks_perpage'] > 0) {
    $perpage = $user->infos['tasks_perpage'];
}

$order_keys = array (
        'id'         => 'task_id',
        'proj'       => 'project_title',
        'type'       => 'tasktype_name',
        'date'       => 'date_opened',
        'sev'        => 'task_severity',
        'cat'        => 'lc.category_name',
        'status'     => 'item_status',
        'due'        => 'lvc.list_position',
        'duedate'    => 'due_date',
        'prog'       => 'percent_complete',
        'event_date' => 'event_date',
        'pri'        => 'task_priority',
        'openedby'   => 'uo.real_name',
        'reportedin' => 't.product_version',
        'assignedto' => 'u.real_name',
        'dateclosed' => 't.date_closed',
        'os'         => 'los.os_name',
        'votes'      => 'num_votes',
);
$sortorder  = sprintf("%s %s, %s %s, t.task_id ASC",
        $order_keys[Get::enum('order', array_keys($order_keys), 'sev')],  Get::enum('sort', array('asc', 'desc'), 'desc'),
        $order_keys[Get::enum('order2', array_keys($order_keys), 'sev')], Get::enum('sort2', array('asc', 'desc'), 'desc'));

$pagenum    = Get::num('pagenum', 1);
if ($pagenum < 1) {
  $pagenum = 1;
}
$offset     = $perpage * ($pagenum - 1);

// for 'sort by this column' links
function keep($key) {
    if (is_array(Get::val($key))) {
        $return = '';
        foreach (Get::val($key) as $value) {
            $return .= $key . '[]=' . $value . '&';
        }
        return substr($return, 0, -1);
    }    
    return Get::val($key) ? $key.'='.Get::val($key) : null;
}
$keys   = array('string', 'type', 'sev', 'dev', 'due', 'cat', 'status', 'date',
        'project', 'task', 'opened', 'changedsincedate');
$keys   = array_map('keep', $keys);
$keys   = array_filter($keys,  create_function('$x', 'return !is_null($x);'));
$keys[] = $proj->id == 0 ? 'project=0' : "project=".$proj->id;
$get    = htmlspecialchars(join('&', $keys));

// Get the visibility state of all columns
$visible = explode(' ', $proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']);

$page->uses('offset', 'perpage', 'pagenum', 'get', 'visible');

/* build SQL statement {{{ */
// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$select = '';
$groupby = '';
$from   = '             {tasks}         t
             LEFT JOIN  {projects}      p   ON t.attached_to_project = p.project_id
             LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
             LEFT JOIN  {list_status}   lst ON t.item_status = lst.status_id
             LEFT JOIN  {groups}        g   ON g.belongs_to_project = p.project_id OR g.belongs_to_project = 0
             LEFT JOIN  {users_in_groups} uig ON uig.group_id = g.group_id AND uig.user_id = ?';
// Only join tables which are really necessary to speed up the db-query
if (Get::has('cat') || in_array('category', $visible)) {
    $from   .= ' LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id ';
    $select .= ' lc.category_name               AS category_name, ';
    $groupby .= 'lc.category_name, ';
}
if (in_array('votes', $visible)) {
    $from   .= ' LEFT JOIN  {votes} vot         ON t.task_id = vot.task_id ';
    $select .= ' COUNT(DISTINCT vot.vote_id)    AS num_votes, ';
}
if (Get::val('changedfrom') || Get::val('changedto') || in_array('lastedit', $visible)) {
    $from   .= ' LEFT JOIN  {history} h         ON t.task_id = h.task_id ';
    $select .= ' max(h.event_date)              AS event_date, ';
}
if (Get::has('search_in_comments') || in_array('comments', $visible)) {
    $from   .= ' LEFT JOIN  {comments} c        ON t.task_id = c.task_id ';
    $select .= ' COUNT(DISTINCT c.comment_id)   AS num_comments, ';
}
if (in_array('reportedin', $visible)) {
    $from   .= ' LEFT JOIN  {list_version} lv   ON t.product_version = lv.version_id ';
    $select .= ' lv.version_name                AS product_version, ';
    $groupby .= 'lv.version_name, ';
}
if (Get::has('opened') || in_array('openedby', $visible)) {
    $from   .= ' LEFT JOIN  {users} uo          ON t.opened_by = uo.user_id ';
    $select .= ' uo.real_name                   AS opened_by_name, ';
    $groupby .= 'uo.real_name, ';
}
if (Get::has('closed')) {
    $from   .= ' LEFT JOIN  {users} uc          ON t.closed_by = uc.user_id ';
    $select .= ' uc.real_name                   AS closed_by_name, ';
    $groupby .= 'uc.real_name, ';
}
if (Get::has('due') || in_array('dueversion', $visible)) {
    $from   .= ' LEFT JOIN  {list_version} lvc  ON t.closedby_version = lvc.version_id ';
    $select .= ' lvc.version_name               AS closedby_version, ';
    $groupby .= 'lvc.version_name, ';
}
if (in_array('os', $visible)) {
    $from   .= ' LEFT JOIN  {list_os} los       ON t.operating_system = los.os_id ';
    $select .= ' los.os_name                    AS os_name, ';
    $groupby .= 'los.os_name, ';
}
if (in_array('attachments', $visible) || Get::has('has_attachment')) {
    $from   .= ' LEFT JOIN  {attachments} att   ON t.task_id = att.task_id ';
    $select .= ' COUNT(DISTINCT att.attachment_id) AS num_attachments, ';
}

$from   .= ' LEFT JOIN  {assigned} ass      ON t.task_id = ass.task_id ';
$from   .= ' LEFT JOIN  {users} u           ON ass.user_id = u.user_id ';
if (Get::has('dev') || in_array('assignedto', $visible)) {
    $select .= ' min(u.real_name)               AS assigned_to_name, ';
    $select .= ' COUNT(DISTINCT ass.user_id)    AS num_assigned, ';
}


$where      = array('project_is_active = 1');
$where[]    = '(p.others_view = 1 AND t.mark_private = 0
                OR (uig.user_id = ?
                   AND (t.opened_by = ?
                        OR (t.mark_private = 0 AND g.view_tasks = 1)
                        OR ass.user_id = ?
                        OR g.manage_project = 1
                        OR g.is_admin = 1)
                        )
                )';
$sql_params = array($user->id, $user->id, $user->id, $user->id);

if (Get::has('only_primary')) {
    $from   .= ' LEFT JOIN  {dependencies} dep  ON dep.dep_task_id = t.task_id ';
    $where[] = 'dep.depend_id IS NULL';
}
if (Get::has('has_attachment')) {
    $where[] = 'att.attachment_id IS NOT NULL';
}

if ($proj->id == 0) {
    // If the user wants to view tasks from all projects
    // XXX take $project_list from index.php

    $temp_where   = 'attached_to_project = ?';
    $sql_params[] = '0';
    foreach ($project_list as $this_project) {
        $temp_where  .= ' OR attached_to_project = ?';
        $sql_params[] = $this_project['project_id'];
    }
    $where[] = "($temp_where)";
}
else {
    // If we're not selecting all projects
    $where[]       = "attached_to_project = ?";
    $sql_params[]  = $proj->id;
}

/// process search-conditions {{{
$submits = array('type' => 'task_type', 'sev' => 'task_severity', 'due' => 'closedby_version', 'reported' => 'product_version',
                 'cat' => 'product_category', 'status' => 'item_status', 'percent' => 'percent_complete',
                 'dev' => array('a.user_id', 'us.user_name', 'us.real_name'),
                 'opened' => array('opened_by', 'uo.user_name', 'uo.real_name'),
                 'closed' => array('closed_by', 'uc.user_name', 'uc.real_name'));
foreach ($submits as $key => $db_key) {
    $type = Get::val($key, ($key == 'status') ? 'open' : '');
    settype($type, 'array');
 
    if (in_array('', $type)) continue;
    
    if ($key == 'dev') {
        $from .= 'LEFT JOIN {assigned} a  ON t.task_id = a.task_id ';
        $from .= 'LEFT JOIN {users} us  ON a.user_id = us.user_id ';
    }
    
    $temp = '';
    foreach ($type as $val) {
        // add conditions for the status selection
        if ($key == 'status' && $val == 'closed') {
            $temp  .= " is_closed = '1' AND";
        } elseif ($key == 'status') {
            $temp .= " is_closed <> '1' AND";
        }
        if (is_numeric($val) && !is_array($db_key) && !($key == 'status' && $val == '8')) {
            $temp .= ' ' . $db_key . ' = ?  OR';
            $sql_params[] = $val;
        } elseif (is_array($db_key)) {
            if ($key == 'dev' && ($val == 'notassigned' || $val == '0' || $val == '-1')) {
                $temp .= ' a.user_id is NULL  OR';
            } else {
                if (!is_numeric($val)) $val = '%' . $val . '%';
                foreach ($db_key as $value) {
                    $temp .= ' ' . $value . ' LIKE ?  OR';
                    $sql_params[] = $val;
                }
            }
        }
        
        // Add the subcategories to the query
        if ($key == 'cat') {
            $result = $db->Query('SELECT  *
                                    FROM  {list_category}
                                   WHERE  category_id = ?',
                                  array($val));
            $cat_details = $db->FetchArray($result);
        
            $result = $db->Query('SELECT  *
                                    FROM  {list_category}
                                   WHERE  lft > ? AND rgt < ? AND project_id  = ?',
                                   array($cat_details['lft'], $cat_details['rgt'], $cat_details['project_id']));
            while ($row = $db->FetchRow($result)) {
                $temp  .= ' product_category = ?  OR';
                $sql_params[] = $row['category_id'];
            }
        }
    }

    if ($temp) $where[] = '(' . substr($temp, 0, -3) . ')';
}
/// }}}

$dates = array('duedate' => 'due_date', 'changed' => 'event_date',
               'opened' => 'date_opened', 'closed' => 'date_closed');
foreach ($dates as $post => $db_key) {
    if ($date = Get::val($post . 'from')) {
        $where[]      = '(' . $db_key . ' >= ?)';
        $sql_params[] = strtotime($date);
    }
    if ($date = Get::val($post . 'to')) {
        $where[]      = '(' . $db_key . ' <= ? AND ' . $db_key . ' > 0)';
        $sql_params[] = strtotime($date);
    }
}

if (Get::val('string')) {
    $words = explode(' ', strtr(Get::val('string'), '()', '  '));
    $comments = '';
    $where_temp = array();
    
    if (Get::has('search_in_comments')) {
        $comments = 'OR c.comment_text LIKE ?';
    }
    
    foreach ($words as $word) {
        $word = '%' . str_replace('+', ' ', trim($word)) . '%';
        $where_temp[] = "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ? $comments)";
        array_push($sql_params, $word, $word, $word);
        if(Get::has('search_in_comments')) {
            array_push($sql_params, $word);
        }
    }
      
    $where[] = '(' . implode( (Req::has('search_for_all') ? ' AND ' : ' OR '), $where_temp) . ')';
}

if (Get::val('only_watched')) {
    //join the notification table to get watched tasks
    $from        .= " LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id";
    $where[]      = 'fsn.user_id = ?';
    $sql_params[] = $user->id;
}

$where = join(' AND ', $where);

//Get the column names of table tasks for the group by statement
if (!strcasecmp($conf['database']['dbtype'], 'pgsql')) {
    $groupby .= "p.project_title, p.project_is_active, lst.status_name, lt.tasktype_name, ";
}
$groupby .= $db->GetColumnNames('{tasks}', 't.task_id', 't.');

// Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
$sql = $db->Query("
                  SELECT   t.*, $select
                           p.project_title, p.project_is_active,
                           lst.status_name AS status_name,
                           lt.tasktype_name AS task_type
                  FROM     $from
                  WHERE    $where 
                  GROUP BY $groupby
                  ORDER BY $sortorder", $sql_params);

$tasks = $db->fetchAllArray($sql);
$id_list = array();
foreach ($tasks as $key => $task) {
    $id_list[] = $task['task_id'];
    if ($key < $offset || ($key > $offset - 1 + $perpage)) {
        unset($tasks[$key]);
    }
}
// List of task IDs for next/previous links
$_SESSION['tasklist'] = $id_list;
$page->assign('total', count($id_list));

// tpl function that Displays a header cell for report list {{{

function tpl_list_heading($colname, $format = "<th%s>%s</th>")
{
    global $proj, $get, $page;

    $keys = array (
            'id'         => 'id',
            'project'    => 'proj',
            'tasktype'   => 'type',
            'category'   => 'cat',
            'severity'   => 'sev',
            'priority'   => 'pri',
            'summary'    => '',
            'dateopened' => 'date',
            'status'     => 'status',
            'openedby'   => 'openedby',
            'assignedto' => 'assignedto',
            'lastedit'   => 'event_date',
            'reportedin' => 'reportedin',
            'dueversion' => 'due',
            'duedate'    => 'duedate',
            'comments'   => '',
            'attachments'=> '',
            'progress'   => 'prog',
            'dateclosed' => 'dateclosed',
            'os'         => 'os',
            'votes'      => 'votes',
    );

    $imgbase = '<img src="%s" alt="%s" />';
    $class   = '';
    $html    = L($colname);
    if ($colname == 'comments' || $colname == 'attachments') {
        $html = sprintf($imgbase, $page->get_image(substr($colname, 0, -1)), $html);
    }

    if ($orderkey = $keys[$colname]) {
        if (Get::val('order') == $orderkey) {
            $class  = ' class="orderby"';
            $sort1  = Get::safe('sort', 'desc') == 'desc' ? 'asc' : 'desc';
            $sort2  = Get::safe('sort2', 'desc');
            $order2 = Get::safe('order2');
            $html  .= '&nbsp;&nbsp;'.sprintf($imgbase, $page->get_image(Get::val('sort')), Get::safe('sort'));
        }
        else {
            $sort1  = 'desc';
            if (in_array($orderkey,
                        array('proj', 'type', 'cat', 'openedby', 'assignedto')))
            {
                $sort1 = 'asc';
            }
            $sort2  = Get::safe('sort', 'desc');
            $order2 = Get::safe('order');
        }

        $link = "?order=$orderkey&amp;$get&amp;sort=$sort1&amp;order2=$order2&amp;sort2=$sort2";
        $html = sprintf('<a title="%s" href="%s">%s</a>',
                L('sortthiscolumn'), $link, $html);
    }

    return sprintf($format, $class, $html);
}

// }}}
// tpl function that draws a cell {{{

function tpl_draw_cell($task, $colname, $format = "<td class='%s'>%s</td>") {
    global $fs, $proj, $priority_list, $page, $severity_list;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'tasktype'   => 'task_type',
            'category'   => 'category_name',
            'severity'   => '',
            'priority'   => '',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'status'     => 'status_name',
            'openedby'   => 'opened_by_name',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'event_date',
            'reportedin' => 'product_version',
            'dueversion' => 'closedby_version',
            'duedate'    => 'due_date',
            'comments'   => 'num_comments',
            'votes'      => 'num_votes',
            'attachments'=> 'num_attachments',
            'dateclosed' => 'date_closed',
            'progress'   => '',
            'os'         => 'os_name',
    );

    switch ($colname) {
        case 'id':
            $value = tpl_tasklink($task, $task['task_id']);
            break;
        case 'summary':
            $value = tpl_tasklink($task, $task['item_summary']);
            break;

        case 'severity':
            $value = $severity_list[$task['task_severity']];
            break;

        case 'priority':
            $value = $priority_list[$task['task_priority']];
            break;

        case 'duedate':
        case 'dateopened':
        case 'dateclosed':
        case 'lastedit':
            $value = formatDate($task[$indexes[$colname]]);
            break;

        case 'status':
            if ($task['is_closed']) {
                $value = L('closed');
            } else {
                $value = htmlspecialchars($task[$indexes[$colname]], ENT_QUOTES, 'utf-8');
            }
            break;

        case 'progress':
            $value = tpl_img($page->get_image('percent-' . $task['percent_complete'], false),
                    $task['percent_complete'] . '% ' . L('complete'));
            break;

        case 'assignedto':
            $value = htmlspecialchars($task[$indexes[$colname]], ENT_QUOTES, 'utf-8');
            if ($task['num_assigned'] > 1) {
                $value .= ', +' . ($task['num_assigned'] - 1);
            }
            break;
            
        default:
            $value = htmlspecialchars($task[$indexes[$colname]], ENT_QUOTES, 'utf-8');
            break;
    }

    return sprintf($format, 'task_'.$colname, $value);
}

// }}}

// Update check {{{
if(Get::has('hideupdatemsg')) {
    $db->Query('UPDATE {prefs} SET pref_value = ? WHERE pref_id = 23', array(time()));
    unset($_SESSION['latest_version']);
} else if ($conf['general']['update_check'] && $user->perms['is_admin']
           && $fs->prefs['last_update_check'] < time()-60*60*24*3) {
    if (!isset($_SESSION['latest_version'])) {
		$fs_server  = @fsockopen('flyspray.rocks.cc', 80, $errno, $errstr, 8);
		if($fs_server) {

			$out = "GET /version.txt HTTP/1.0\r\n";
		    $out .= "Host: flyspray.rocks.cc\r\n";
		    $out .= "Connection: Close\r\n\r\n";

			fwrite($fs_server, $out);
			while (!feof($fs_server)) {
				$latest = fgets($fs_server, 10);
			}
			fclose($fs_server);
		}
		//if for some silly reason we get and empty response, we use the actual version
 		$_SESSION['latest_version'] = empty($latest) ? $fs->version : $latest ; 
	}
    if (version_compare($fs->version, $_SESSION['latest_version'] , '<') ) {
        $page->assign('updatemsg', true);
    }
}
// }}}

$page->uses('tasks');
$page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('tasklist'));
$page->pushTpl('index.tpl');

?>
