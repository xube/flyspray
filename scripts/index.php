<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

if ($proj->id !== '0' && !$user->can_view_project($proj)) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

if ($proj->id === '0' && !$user->perms['global_view']) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

// First, the obligatory language packs
$fs->get_language_pack('index');
$fs->get_language_pack('details');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');

$page->uses('index_text', 'details_text', 'severity_list', 'priority_list',
        'status_list');

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
);
$sortorder  = sprintf("%s %s, %s %s, t.task_id ASC",
        $order_keys[Get::val('order',  'sev')],  Get::val('sort', 'desc'),
        $order_keys[Get::val('order2',  'sev')], Get::val('sort2', 'desc'));

$pagenum    = intval(Get::val('pagenum', 1));
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
$keys[] = $proj->id === '0' ? "project=0" : "project=".$proj->id;
$get    = htmlspecialchars(join('&', $keys));

// Get the visibility state of all columns
$visible = explode(' ', $proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']);

$page->uses('offset', 'perpage', 'pagenum', 'get', 'visible');

/* build SQL statement {{{ */
// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$from   = '             {tasks}         t
             LEFT JOIN  {projects}      p   ON t.attached_to_project = p.project_id
             LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
             LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id
             LEFT JOIN  {list_version}  lv  ON t.product_version = lv.version_id
             LEFT JOIN  {list_version}  lvc ON t.closedby_version = lvc.version_id
             LEFT JOIN  {list_os}       los ON t.operating_system = los.os_id
             LEFT JOIN  {list_status}   lst ON t.item_status = lst.status_id
             LEFT JOIN  {users}         u   ON t.assigned_to = u.user_id
             LEFT JOIN  {users}         uo  ON t.opened_by = uo.user_id
             LEFT JOIN  {history}       h   ON t.task_id = h.task_id ';

$where      = array('project_is_active = ?');
$sql_params = array('1');

if ($proj->id == '0') {
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

/// pre-process selected users {{{
if (Get::val('tasks') == 'assigned') {
    $_GET['dev'] = $user->id;
} elseif (Get::val('tasks') == 'reported') {
    $where[]      = 'opened_by = ?';
    $sql_params[] = $user->id;
}
/// }}}

/// process search-conditions {{{
$submits = array('type' => 'task_type', 'sev' => 'task_severity', 'due' => 'closedby_version',
                 'cat' => 'product_category', 'status' => 'item_status',
                 'dev' => array('a.user_id', 'us.user_name', 'us.real_name'),
                 'opened' => array('opened_by', 'usopen.user_name', 'usopen.real_name'));
foreach ($submits as $key => $db_key) {
    $type = Get::val($key, ($key == 'status') ? 'open' : '');
    settype($type, 'array');
    
/*    if($key == 'dev' || $key == 'opened') {
        $type = explode(' ', Get::val($key));
    }*/
    
    if (in_array('', $type)) continue;
    
    if($key == 'dev') {
        $from .= 'LEFT JOIN {assigned} a  ON t.task_id = a.task_id ';
        $from .= 'LEFT JOIN {users} us  ON a.user_id = us.user_id ';
    }
    
    if($key == 'opened') {
        $from .= 'LEFT JOIN {users} usopen  ON t.opened_by = usopen.user_id ';
    }
    
    $temp = '';
    foreach ($type as $val) {
        // add conditions for the status selection
        if ($key == 'status' && $val == '8') {
            $temp  .= " is_closed = '1' AND";
        } elseif ($key == 'status') {
            $temp .= " is_closed <> '1' AND";
        }
        if (is_numeric($val) && !is_array($db_key) && !($key == 'status' && $val == '8')) {
            $temp .= ' ' . $db_key . ' = ?  OR';
            $sql_params[] = $val;
        } elseif (is_array($db_key)) {
            if(!is_numeric($val)) $val = '%' . $val . '%';
            foreach($db_key as $value) {
                $temp .= ' ' . $value . ' LIKE ?  OR';
                $sql_params[] = $val;
            }
        } elseif ($val == 'notassigned') {
            $temp .= ' a.user_id is NULL  OR';
        }
        
        // Do some weird stuff to add the subcategories to the query
        if ($key == 'cat') {
            $get_subs = $db->Query('SELECT  category_id
                                      FROM  {list_category}
                                     WHERE  parent_id = ?', array($val));

            while ($row = $db->FetchArray($get_subs)) {
                $temp  .= ' product_category = ?  OR';
                $sql_params[] = $row['category_id'];
            }
        }
    }

    if ($temp) $where[] = '(' . substr($temp, 0, -3) . ')';
}
/// }}}

if ($date = Get::val('date')) {
    $where[]      = "(due_date < ? AND due_date <> '0' AND due_date <> '')";
    $sql_params[] = strtotime("$date +24 hours");
}

if ($date = Get::val('changedsincedate')) {
    $where[]      = "(event_date >= ? AND event_date <> '0' AND event_date <> '')";
    $sql_params[] = strtotime($date);
}

if (Get::val('string')) {
    $words = explode(' ', strtr(Get::val('string'), '()', '  '));
    $comments = '';
    $where_temp = array();
    
    if (Get::has('search_in_comments')) {
        $from .= ' LEFT JOIN  {comments}         c  ON t.task_id = c.task_id';
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

if (!$user->perms['manage_project']) {
    $where[]      = "(t.mark_private <> '1' OR t.opened_by = ? OR t.assigned_to = ?)";
    array_push($sql_params, $user->id, $user->id);
}

if (Get::val('tasks') == 'watched') {
    //join the notification table to get watched tasks
    $from        .= " LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id";
    $where[]      = 'fsn.user_id = ?';
    $sql_params[] = $user->id;
}

$where = join(' AND ', $where);

/* }}} */

$sql = $db->Query("SELECT  t.task_id
                     FROM  $from
                    WHERE  $where
                 GROUP BY  t.task_id
                 ORDER BY  $sortorder", $sql_params);

// Store the order of the tasks returned for the next/previous links in the task details
$_SESSION['tasklist'] = $id_list = $db->fetchCol($sql);
$page->assign('total', count($id_list));

// Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
$sql = $db->Query("
     SELECT
             t.*, max(h.event_date) AS event_date,
             p.project_title, p.project_is_active,
             lt.tasktype_name         AS task_type,
             lc.category_name         AS category_name,
             lv.version_name          AS product_version,
             lvc.version_name         AS closedby_version,
             lst.status_name          AS status_name,
             u.real_name              AS assigned_to_name,
             los.os_name              AS os_name,
             uo.real_name             AS opened_by_name,
             COUNT(DISTINCT com.comment_id)    AS num_comments,
             COUNT(DISTINCT att.attachment_id) AS num_attachments
     FROM
             $from
             LEFT JOIN  {comments}      com ON t.task_id = com.task_id
             LEFT JOIN  {attachments}   att ON t.task_id = att.task_id
     WHERE
             $where
     GROUP BY
             t.task_id
     ORDER BY
             $sortorder", $sql_params, $perpage, $offset);

// tpl function that Displays a header cell for report list {{{

function tpl_list_heading($colname, $format = "<th%s>%s</th>")
{
    global $proj , $index_text, $get;

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
    );

    $imgbase = '<img src="themes/'.$proj->prefs['theme_style'].'/%s.png" alt="%s" />';
    $class   = '';
    $html    = $index_text[$colname];
    if ($colname == 'comments' || $colname == 'attachments') {
        $html = sprintf($imgbase, substr($colname, 0, -1), $html);
    }

    if ($orderkey = $keys[$colname]) {
        if (Get::val('order') == $orderkey) {
            $class  = ' class="orderby"';
            $sort1  = Get::val('sort', 'desc') == 'desc' ? 'asc' : 'desc';
            $sort2  = Get::val('sort2', 'desc');
            $order2 = Get::val('order2');
            $html  .= '&nbsp;&nbsp;'.sprintf($imgbase, Get::val('sort'), Get::val('sort'));
        }
        else {
            $sort1  = 'desc';
            if (in_array($orderkey,
                        array('proj', 'type', 'cat', 'openedby', 'assignedto')))
            {
                $sort1 = 'asc';
            }
            $sort2  = Get::val('sort', 'desc');
            $order2 = Get::val('order');
        }

        $link = "?order=$orderkey&amp;$get&amp;sort=$sort1&amp;order2=$order2&amp;sort2=$sort2";
        $html = sprintf('<a title="%s" href="%s">%s</a>',
                $index_text['sortthiscolumn'], $link, $html);
    }

    return sprintf($format, $class, $html);
}

// }}}
// tpl function that draws a cell {{{

function tpl_draw_cell($task, $colname, $format = "<td class='%s'>%s</td>") {
    global $fs, $proj, $index_text, $priority_list,
           $severity_list;

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
            $value = $fs->formatDate($task[$indexes[$colname]], false);
            break;

        case 'status':
            if ($task['is_closed']) {
                $value = $index_text['closed'];
            } else {
                $value = htmlspecialchars($task[$indexes[$colname]], ENT_QUOTES, 'utf-8');
            }
            break;

        case 'progress':
            $value = tpl_img("themes/".$proj->prefs['theme_style']
                    ."/percent-".$task['percent_complete'].".png",
                    $task['percent_complete'].'% '.$index_text['complete']);
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
} else if (isset($conf['general']['update_check']) && $user->perms['is_admin']
           && $fs->prefs['last_update_check'] < time()-60*60*24*3) {
    if (!isset($_SESSION['latest_version'])) {
        $handle = fopen('http://flyspray.rocks.cc/version.txt', 'rb');
        socket_set_timeout($handle, 8);
        $_SESSION['latest_version'] = fgets($handle, 10);
    }
    if (version_compare($fs->version, $_SESSION['latest_version'] . ' pl') === -1) {
        $page->assign('updatemsg', true);
    }
}
// }}}

$page->assign('tasks', $db->fetchAllArray($sql));
$page->setTitle("Flyspray :: {$proj->prefs['project_title']}: {$index_text['tasklist']} ");
$page->pushTpl('index.tpl');

?>
