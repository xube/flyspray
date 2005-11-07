<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

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
        'id'         =>'task_id',
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

// Check for special tasks to display

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

$status = Get::val('status');
if (is_numeric($status)) {
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

// Do this to hide private tasks from the list
if ($user->isAnon()) {
    $where[] = "t.mark_private <> '1'";
}
elseif (!$user->perms['manage_project']) {
    $where[]      = "(t.mark_private <> '1' OR t.assigned_to = ?)";
    $sql_params[] = $user->id;
}

// for 'sort by this column' links
function keep($key) {
    return Get::val($key) ? $key.'='.Get::val($key) : null;
}
$keys   = array('string', 'type', 'sev', 'dev', 'due', 'cat', 'status', 'date',
        'project', 'task');
$keys   = array_map('keep', $keys);
$keys   = array_filter($keys,  create_function('$x', 'return !is_null($x);'));
$keys[] = Get::val('project') === '0' ? "project=0" : "project=".$proj->id;
$get    = htmlentities(join('&', $keys));

if (Get::val('project') !== '0'
        && $proj->prefs['project_is_active'] != '1'
        || ($proj->prefs['others_view'] != '1' && !$user->perms['view_tasks']))
{
    $fs->Redirect( $fs->CreateURL('error', null) );
}

// Get the visibility state of all columns
$columns = array('id', 'project', 'tasktype', 'category', 'severity', 'priority',
                 'summary', 'dateopened', 'status', 'openedby', 'assignedto', 'lastedit',
                 'reportedin', 'dueversion', 'duedate', 'comments', 'attachments', 'progress');
$column_visible = array_map(create_function('$x', 'return false;'), $columns);

$project = Get::val('project', $proj->id);
$visible = explode(' ', $project ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']);

foreach ($visible as $column) {
    $column_visible[$column] = true;
}

/**
 * Displays header cell for report list
 *
 * @param string $colname       The name of the column
 * @param string $orderkey      The actual key to use when ordering the list
 * @param string $defaultsort The default sort order
 * @param string $image    An image to display instead of the column name
 */
// {{{

function list_heading($colname, $orderkey, $defaultsort = 'desc', $image = '')
{
    global $column_visible;
    global $proj;
    global $index_text;
    global $get;

    $html = '';

    if (!empty($column_visible[$colname])) {
        if ($orderkey) {
            if (Get::val('order') == $orderkey) {
                $class  = ' class="orderby"';
                $sort1  = Get::val('sort', 'desc') == 'desc' ? 'asc' : 'desc';
                $sort2  = Get::val('sort2', 'desc');
                $order2 = Get::val('order2');
            }
            else {
                $class  = '';
                $sort1  = $defaultsort;
                $sort2  = Get::val('sort', 'desc');
                $order2 = Get::val('order');
            }

            $title = $index_text['sortthiscolumn'];
            $link  = "?order=$orderkey&amp;$get&amp;sort=$sort1&amp;order2=$order2&amp;sort2=$sort2";

            $html  = "<th$class><a title=\"$title\" href=\"$link\">";
            $html .= $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" />";

            // Sort indicator arrows
            if (Get::val('order') == $orderkey) {
                $html .= '&nbsp;&nbsp;<img src="themes/' .
                    $proj->prefs['theme_style'] . '/' . Get::val('sort') . '.png" />';
            }

            return $html . '</a></th>';
        } else {
            $html  = '<th>';
            $html .= $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" alt=\"{$index_text[$colname]}\" />";
            return $html.'</th>';
        }
    }
}

// }}}

/**
 * Displays data cell for report list
 *
 * @param string $colname       The name of the column
 * @param string $cellvalue     The value to display in the cell
 * @param integer $nowrap       Whether to force the cell contents not to wrap
 * @param string $url           A URL to wrap around the cell contents
 */
// {{{

function list_cell($task_id, $colname, $cellvalue='', $nowrap=0, $url=0)
{
    global $column_visible;
    global $fs;

    if (!empty($column_visible[$colname])) {
        // We have a problem with these conversions applied to the progress cell
        if($colname != 'progress') {
            $cellvalue = htmlspecialchars($cellvalue);
        }

        if ($colname == 'duedate' && !empty($cellvalue)) {
            $cellvalue = $fs->FormatDate($cellvalue, false);
        }

        // Check if we're meant to force this cell not to wrap
        if ($nowrap) {
            $cellvalue = str_replace(" ", "&nbsp;", $cellvalue);
        }

        echo "<td class=\"task_$colname\" >";
        if($url) {
            echo "<a href=\"$url\">$cellvalue</a>";
        } else {
            echo $cellvalue;
        }
        echo "</td>\n";
    }
}

// }}}

$where = join(' AND ', $where);
$from  = "{tasks} t";

if (Get::val('tasks') == 'watched') {
    //join the notification table to get watched tasks
    $from        .= " RIGHT JOIN {notifications} fsn ON t.task_id = fsn.task_id";
    $where[]      = 'fsn.user_id = ?';
    $sql_params[] = $user->id;
}

// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$from .= "
        LEFT JOIN  {projects}      p   ON t.attached_to_project = p.project_id
        LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
        LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id
        LEFT JOIN  {list_version}  lv  ON t.product_version = lv.version_id
        LEFT JOIN  {list_version}  lvc ON t.closedby_version = lvc.version_id
        LEFT JOIN  {users}         u   ON t.assigned_to = u.user_id
        LEFT JOIN  {users}         uo  ON t.opened_by = uo.user_id
";

$get_total = $db->Query("SELECT  t.task_id
                           FROM  $from
                          WHERE  $where
                       ORDER BY  $sortorder", $sql_params);

// Store the order of the tasks returned for the next/previous links in the task details
$id_list = array();
while ($row = $db->FetchRow($get_total)) {
    $id_list[] = $row['task_id'];
}
$_SESSION['tasklist'] = $id_list;
$page->assign('total', count($id_list));

// Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
$sql = $db->Query("
     SELECT  DISTINCT
             t.*,
             p.project_title, p.project_is_active,
             lt.tasktype_name         AS task_type,
             lc.category_name         AS product_category,
             lv.version_name          AS product_version,
             lvc.version_name         AS closedby_version,
             u.real_name              AS assigned_to,
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

$page->assign('tasks', $db->fetchAllArray($sql));
$page->uses('offset', 'perpage', 'pagenum', 'get');
$page->display('index.tpl');

?>
