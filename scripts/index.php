<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

if (Get::val('project') !== '0' && !$user->can_view_project($proj)) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

if (Get::val('project') === '0' && !$user->perms['global_view']) {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

// First, the obligatory language packs
$fs->get_language_pack('index');
$fs->get_language_pack('details');
$fs->get_language_pack('severity');
$fs->get_language_pack('status');
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
        'lastedit'   => 'last_edited_time',
        'pri'        => 'task_priority',
        'openedby'   => 'uo.real_name',
        'reportedin' => 't.product_version',
        'assignedto' => 'u.real_name',
);
$sortorder  = sprintf("%s %s, %s %s, t.task_id ASC",
        $order_keys[Get::val('order',  'sev')],  Get::val('sort', 'desc'),
        $order_keys[Get::val('order2',  'sev')], Get::val('sort2', 'desc'));

$pagenum    = intval(Get::val('pagenum', 1));
$offset     = $perpage * ($pagenum - 1);

// for 'sort by this column' links
function keep($key) {
    return Get::val($key) ? $key.'='.Get::val($key) : null;
}
$keys   = array('string', 'type', 'sev', 'dev', 'due', 'cat', 'status', 'date',
        'project', 'task');
$keys   = array_map('keep', $keys);
$keys   = array_filter($keys,  create_function('$x', 'return !is_null($x);'));
$keys[] = Get::val('project') === '0' ? "project=0" : "project=".$proj->id;
$get    = htmlspecialchars(join('&', $keys));

// Get the visibility state of all columns
$project = Get::val('project', $proj->id);
$visible = explode(' ', $project ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']);

$page->uses('offset', 'perpage', 'pagenum', 'get', 'visible');

/* build SQL statement {{{ */

$where      = array();
$where[]    = 'project_is_active = ?';
$sql_params = array('1');

if (Get::val('project') == '0') {
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

$dev = Get::val('dev');
if (Get::val('tasks') == 'assigned') {
    $dev = $user->id;
} elseif (Get::val('tasks') == 'reported') {
    $where[]      = 'opened_by = ?';
    $sql_params[] = $user->id;
}

if (is_numeric($dev)) {
    $where[]      = 'assigned_to = ?';
    $sql_params[] = $dev;
} elseif ($dev == 'notassigned') {
    $where[]      = 'assigned_to = ?';
    $sql_params[] = '0';
}

if (is_numeric(Get::val('type'))) {
    $where[]      = 'task_type = ?';
    $sql_params[] = Get::val('type');
}

if (is_numeric(Get::val('sev'))) {
    $where[]      = 'task_severity = ?';
    $sql_params[] = Get::val('sev');
}

if (is_numeric($cat = Get::val('cat'))) {
    $temp_where   = 'product_category = ?';
    $sql_params[] = $cat;

    // Do some weird stuff to add the subcategories to the query
    $get_subs = $db->Query('SELECT  category_id
                              FROM  {list_category}
                             WHERE  parent_id = ?', array($cat));

    while ($row = $db->FetchArray($get_subs)) {
        $temp_where  .= ' OR product_category =?';
        $sql_params[] = $row['category_id'];
    }
    $where[] = "($temp_where)";
}

if (is_numeric($status = Get::val('status'))) {
    $where[]      = "item_status = ? AND is_closed <> '1'";
    $sql_params[] = $status;
}
elseif ($status != 'all') {
    if ($status == 'closed') {
        $where[]  = 'is_closed = ?';
    } else {
        $where[]  = 'is_closed != ?';
    }
    $sql_params[] = '1';
}

if (is_numeric($due = Get::val('due'))) {
    $where[]      = 'closedby_version = ?';
    $sql_params[] = $due;
}

if ($date = Get::val('date')) {
    $where[]      = "(due_date < ? AND due_date <> '0' AND due_date <> '')";
    $sql_params[] = strtotime("$date +24 hours");
}

if ($str = Get::val('string')) {
    $str = strtr($str, '()', '  ');
    $str = '%'.trim($str).'%';

    $where[] = "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
    array_push($sql_params, $str, $str, $str);
}

if (!$user->perms['manage_project']) {
    $where[]      = "(t.mark_private <> '1' OR t.assigned_to = ?)";
    $sql_params[] = $user->id;
}

if (Get::val('tasks') == 'watched') {
    //join the notification table to get watched tasks
    $from        .= " RIGHT JOIN {notifications} fsn ON t.task_id = fsn.task_id";
    $where[]      = 'fsn.user_id = ?';
    $sql_params[] = $user->id;
}

// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$from  = "
                        {tasks}         t
             LEFT JOIN  {projects}      p   ON t.attached_to_project = p.project_id
             LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
             LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id
             LEFT JOIN  {list_version}  lv  ON t.product_version = lv.version_id
             LEFT JOIN  {list_version}  lvc ON t.closedby_version = lvc.version_id
             LEFT JOIN  {users}         u   ON t.assigned_to = u.user_id
             LEFT JOIN  {users}         uo  ON t.opened_by = uo.user_id
";
$where = join(' AND ', $where);

/* }}} */

$sql = $db->Query("SELECT  t.task_id
                     FROM  $from
                    WHERE  $where
                 ORDER BY  $sortorder", $sql_params);

// Store the order of the tasks returned for the next/previous links in the task details
$_SESSION['tasklist'] = $id_list = $db->fetchCol($sql);
$page->assign('total', count($id_list));

// Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
$sql = $db->Query("
     SELECT
             t.*,
             p.project_title, p.project_is_active,
             lt.tasktype_name         AS task_type,
             lc.category_name         AS category_name,
             lv.version_name          AS product_version,
             lvc.version_name         AS closedby_version,
             u.real_name              AS assigned_to_name,
             uo.real_name             AS opened_by,
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
            'lastedit'   => 'lastedit',
            'reportedin' => 'reportedin',
            'dueversion' => 'due',
            'duedate'    => 'duedate',
            'comments'   => '',
            'attachments'=> '',
            'progress'   => 'prog',
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
           $severity_list, $status_list;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'tasktype'   => 'task_type',
            'category'   => 'category_name',
            'severity'   => '',
            'priority'   => '',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'status'     => '',
            'openedby'   => 'opened_by',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'last_edited_time',
            'reportedin' => 'product_version',
            'dueversion' => 'closedby_version',
            'duedate'    => 'due_date',
            'comments'   => 'num_comments',
            'attachments'=> 'num_attachments',
            'progress'   => '',
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
        case 'lastedit':
            $value = $fs->formatDate($task[$indexes[$colname]], false);
            break;

        case 'status':
            if ($task['is_closed']) {
                $value = $index_text['closed'];
            } else {
                $value = $status_list[$task['item_status']];
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

$page->assign('tasks', $db->fetchAllArray($sql));
$page->setTitle("Flyspray :: {$proj->prefs['project_title']}: {$index_text['tasklist']} ");
$page->pushTpl('index.tpl');

?>
