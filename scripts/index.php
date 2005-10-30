<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

// First, set a whole bunch of DEFAULT variables if they're not
// already set. This is a whole convoluted bunch of crap, but it works.

// First, the obligatory language packs
$fs->get_language_pack('index');
$fs->get_language_pack('details');
$fs->get_language_pack('severity');
$fs->get_language_pack('status');
$fs->get_language_pack('priority');

$perpage = '20';
if (@$current_user['tasks_perpage'] > 0) {
    $perpage = $current_user['tasks_perpage'];
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
$orderby = array();
$orderby[] = $order_keys[Get::val('order',  'sev')];
$orderby[] = $order_keys[Get::val('order2', 'pri')];
$sort      = array(Get::val('sort', 'desc'), Get::val('sort2', 'desc'));
$sortorder = "{$orderby[0]} {$sort[0]}, {$orderby[1]} {$sort[1]}, t.task_id ASC";

$pagenum    = intval(Get::val('pagenum', 1));
$offset     = $perpage * ($pagenum - 1);
$where      = array();
$where[]    = 'project_is_active = ?';
$sql_params = array('1');

if (Get::val('project') === '0') {
    // If the user wants to view tasks from all projects

    if (isset($permissions['global_view']) && $permissions['global_view'] == '1') {
        // If the user has the global 'view tasks' permission, view all projects unrestricted
        $check_projects = $db->Query("SELECT  p.project_id
                                        FROM  {projects} p
                                    ORDER BY  p.project_title");
    }
    elseif (Cookie::has('flyspray_userid')) {
        // Those who aren't super users get this more restrictive query
        $check_projects = $db->Query("SELECT  p.project_id
                                        FROM  {users_in_groups} uig
                                   LEFT JOIN  {groups} g ON uig.group_id = g.group_id, {projects} p
                                       WHERE  ((uig.user_id = ?  AND g.view_tasks = '1') OR p.others_view = '1')
                                              AND p.project_is_active = '1'
                                    ORDER BY  p.project_title", array($current_user['user_id']));
    }
    else {
        // Anonymous users also need a query here
        $check_projects = $db->Query("SELECT  p.project_id
                                        FROM  {projects} p
                                       WHERE  p.others_view = '1' AND p.project_is_active = '1'
                                    ORDER BY  p.project_title");
    }

    $temp_where   = 'attached_to_project = ?';
    $sql_params[] = '0';
    while ($this_project = $db->FetchArray($check_projects)) {
        $temp_where  .= ' OR attached_to_project = ?';
        $sql_params[] = $this_project['project_id'];
    }
    $where[] = "($temp_where)";
}
else {
    // If we're not selecting all projects
    $where[]       = "attached_to_project = ?";
    $sql_params[]  = $project_id;
}

// Check for special tasks to display

$dev = Get::val('dev');
if (Get::val('tasks') == 'assigned') {
    $dev = $current_user['user_id'];
} elseif (Get::val('tasks') == 'reported') {
    $where[]      = 'opened_by = ?';
    $sql_params[] = $current_user['user_id'];
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
if (!isset($current_user)) {
    $where[] = "t.mark_private <> '1'";
}
elseif ( isset($current_user) && empty($permissions['manage_project']) ) {
    $where[]      = "(t.mark_private <> '1' OR t.assigned_to = ?)";
    $sql_params[] = $current_user['user_id'];
}

if (Get::val('project') === '0') {
    $get = "project=0";
} else {
    $get = "project={$project_id}";
}

// for 'sort by this column' links
$get = join('&amp;', array($get, $extraurl, 'task=' . Get::val('tasks')));

if (Get::val('project') !== '0'
        && $project_prefs['project_is_active'] != '1'
        || ($project_prefs['others_view'] != '1' && @$permissions['view_tasks'] != '1'))
{
    $fs->Redirect( $fs->CreateURL('error', null) );
}
?>
<!-- Query line {{{ -->
<div id="search">
<map id="projectsearchform" name="projectsearchform">
  <form action="index.php" method="get">
    <div>
      <input type="hidden" name="tasks" value="<?php echo $_GET['tasks']; ?>" />
      <input type="hidden" name="project" value="<?php if(isset($_GET['project']) && $_GET['project'] == '0') { echo '0'; } else { echo $project_id; }?>" />
      <em><?php echo $index_text['searchthisproject'];?>:</em>
      <input id="searchtext" name="string" type="text" size="20"
      maxlength="100" value="<?php echo htmlspecialchars(Get::val('string')); ?>" accesskey="q" />

      <select name="type">
        <option value=""><?php echo $index_text['alltasktypes'];?></option>
        <?php
        $tasktype_list = $db->Query("SELECT  tasktype_id, tasktype_name FROM {list_tasktype}
                                      WHERE  show_in_list = '1' AND (project_id = '0' OR project_id = ?)
                                   ORDER BY  list_position", array($project_id));
        while ($row = $db->FetchArray($tasktype_list)) {
            if (Get::val('type') == $row['tasktype_id']) {
                echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>\n";
            } else {
                echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>\n";
            }
        }
        ?>
      </select>

      <select name="sev">
        <option value=""><?php echo $index_text['allseverities'];?></option>
        <?php
        foreach($severity_list as $key => $val) {
           if (Get::val('sev') === (string)$key) {
               echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
           } else {
              echo "<option value=\"$key\">$val</option>\n";
           }
        }
        ?>
      </select>

      <select name="due" <?php if (Get::val('project') === '0') echo 'disabled="disabled"';?>>
        <option value=""><?php echo $index_text['dueanyversion'];?></option>
        <?php
        $ver_list = $db->Query("SELECT  version_id, version_name
                                  FROM  {list_version}
                                 WHERE  show_in_list = '1' AND version_tense = '3'
                                        AND (project_id = '0' OR project_id = ?)
                              ORDER BY  list_position", array($project_id,));

        while ($row = $db->FetchArray($ver_list)) {
            if (Get::val('due') == $row['version_id']) {
                echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
            } else {
                echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
            }
        }
        ?>
      </select>

      <select name="dev">
        <option value=""><?php echo $index_text['alldevelopers'];?></option>
        <option value="notassigned" <?php if ($dev == "notassigned") echo 'selected="selected"';?>><?php echo $index_text['notyetassigned'];?></option>
        <?php
        $fs->ListUsers($dev, $project_id);
        ?>
      </select>

      <select name="cat" <?php if (Get::val('project') === '0') echo 'disabled="disabled"';?>>
        <option value=""><?php echo $index_text['allcategories'];?></option>
        <?php
        $cat_list = $db->Query("SELECT  category_id, category_name
                                  FROM  {list_category}
                                 WHERE  show_in_list = '1' AND parent_id < '1'
                                        AND (project_id = '0' OR project_id = ?)
                             ORDER BY  list_position", array($project_id));

        while ($row = $db->FetchArray($cat_list)) {
           $category_name = stripslashes($row['category_name']);
           if (Get::val('cat') == $row['category_id']) {
               echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
           } else {
               echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
           }

           $subcat_list = $db->Query("SELECT  category_id, category_name
                                        FROM  {list_category}
                                       WHERE  show_in_list = '1' AND parent_id = ?
                                    ORDER BY  list_position", array($row['category_id']));

           while ($subrow = $db->FetchArray($subcat_list)) {
               $subcategory_name = stripslashes($subrow['category_name']);
               if (Get::val('cat') == $subrow['category_id']) {
                   echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&nbsp;&nbsp;&rarr;$subcategory_name</option>\n";
               } else {
                   echo "<option value=\"{$subrow['category_id']}\">&nbsp;&nbsp;&rarr;$subcategory_name</option>\n";
              }
           }
        }
        ?>
      </select>

      <select name="status">
        <option value="all" <?php if (Get::val('status') == 'all') echo 'selected="selected"';?>><?php echo $index_text['allstatuses'];?></option>
        <option value="" <?php if (!Get::val('status')) echo 'selected="selected"'; ?>><?php echo $index_text['allopentasks'];?></option>
        <?php
        foreach($status_list as $key => $val) {
            if (Get::val('status') === (string)$key) {
                echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
            } else {
                echo "<option value=\"$key\">$val</option>\n";
            }
        }
        ?>
        <option value="closed" <?php if (Get::val('status') == "closed") echo 'selected="selected"';?>><?php echo $index_text['closed'];?></option>
      </select>

      <?php
      if ($due_date = Get::val('date')) {
          $show_date = $index_text['due'] . ' ' . $due_date;
      } else {
          $due_date  = '0';
          $show_date = $index_text['selectduedate'];
      }
      ?>

      <input id="duedatehidden" type="hidden" name="date" value="<?php echo $due_date;?>" />
      <span id="duedateview"><?php echo $show_date;?></span> <small>|</small>
      <a href="#" onClick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '<?php echo $index_text['selectduedate']?>'">X</a>
     
      <script type="text/javascript">
         Calendar.setup({
            inputField  : "duedatehidden",  // ID of the input field
            ifFormat    : "%d-%b-%Y",       // the date format
            displayArea : "duedateview",    // The display field
            daFormat    : "%d-%b-%Y",
            button      : "duedateview"     // ID of the button
         });
      </script>
     
      <input class="mainbutton" type="submit" value="<?php echo $index_text['search'];?>" />
    </div>
  </form>
</map>
</div>
<!-- }}} -->
<?php
// Get the visibility state of all columns
$columns = array('id', 'project', 'tasktype', 'category', 'severity', 'priority',
                 'summary', 'dateopened', 'status', 'openedby', 'assignedto', 'lastedit',
                 'reportedin', 'dueversion', 'duedate', 'comments', 'attachments', 'progress');
$column_visible = array_map(create_function('$x', 'return false;'), $columns);

$project = isset($_GET['project']) ? $_GET['project'] : $project_id;
$visible = explode(' ', $project == '0' ? $fs->prefs['visible_columns'] : $project_prefs['visible_columns']);

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

//function list_heading($colname, $orderkey, $image = '')
function list_heading($colname, $orderkey, $defaultsort = 'desc', $image = '')
{
    global $column_visible;
    global $project_prefs;
    global $index_text;
    global $get;

    if ($column_visible[$colname]) {
        if ($orderkey) {
            if (Get::val('order') == $orderkey)
            {
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

            echo "<th$class><a title=\"$title\" href=\"$link\">";
            echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" />\n";

            // Sort indicator arrows
            if (Get::val('order') == $orderkey) {
                echo '&nbsp;&nbsp;<img src="themes/' . $project_prefs['theme_style'] . '/' . $_GET['sort'] . '.png" />';
            }

            echo "</a></th>\n";
        } else {
            echo "<th>";
            echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" alt=\"{$index_text[$colname]}\" />";
            echo "</th>\n";
        }
    }
}

/**
 * Displays data cell for report list
 *
 * @param string $colname       The name of the column
 * @param string $cellvalue     The value to display in the cell
 * @param integer $nowrap       Whether to force the cell contents not to wrap
 * @param string $url           A URL to wrap around the cell contents
 */

function list_cell($task_id, $colname, $cellvalue='', $nowrap=0, $url=0)
{
    global $column_visible;
    global $fs;

    if ($column_visible[$colname]) {
        // We have a problem with these conversions applied to the progress cell
        if($colname != 'progress') {
            $cellvalue = stripslashes(htmlspecialchars($cellvalue));
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

?>

<div id="tasklist">

<!-- This form for mass operations on tasks currently displayed -->
<form action="index.php" id="massops" method="post">
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="prev_page" value="<?php echo htmlentities($_SERVER['REQUEST_URI']);?>" />
  
    <!--  Summary headings, followed by the query results -->
    <table id="tasklist_table">
    <thead>
       <tr>
       <?php
       // Spacer for the checkboxes beneath it
       if (Cookie::has('flyspray_userid')) {
           echo '<th class="ttcolumn"></th>';
       }
  
       list_heading('id',          'id');
       list_heading('project',     'proj', 'asc');
       list_heading('tasktype',    'type', 'asc');
       list_heading('category',    'cat', 'asc');
       list_heading('severity',    'sev');
       list_heading('priority',    'pri');
       list_heading('summary',     '');
       list_heading('dateopened',  'date');
       list_heading('status',      'status');
       list_heading('openedby',    'openedby', 'asc');
       list_heading('assignedto',  'assignedto', 'asc');
       list_heading('lastedit',    'lastedit');
       list_heading('reportedin',  'reportedin');
       list_heading('dueversion',  'due');
       list_heading('duedate',     'duedate');
       list_heading('comments',    '', '', "themes/{$project_prefs['theme_style']}/comment.png");
       list_heading('attachments', '', '', "themes/{$project_prefs['theme_style']}/attachment.png");
       list_heading('progress',    'prog');
       ?>
       </tr>
    </thead>
    <?php
    
    $where = join(' AND ', $where);
    $from  = "{tasks} t";
    
    if (Get::val('tasks') == 'watched') {
        //join the notification table to get watched tasks
        $from        .= " RIGHT JOIN {notifications} fsn ON t.task_id = fsn.task_id";
        $where[]      = 'fsn.user_id = ?';
        $sql_params[] = $current_user['user_id'];
    }
    
    // This SQL courtesy of Lance Conry http://www.rhinosw.com/
    $from .= "
            LEFT JOIN  {projects}      p   ON t.attached_to_project = p.project_id
            LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
            LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id
            LEFT JOIN  {list_version}  lv  ON t.product_version = lv.version_id
            LEFT JOIN  {list_version}  lvc ON t.closedby_version = lvc.version_id
            LEFT JOIN  {users}         u   ON t.assigned_to = u.user_id
            LEFT JOIN  {users}         uo  ON t.opened_by = uo.user_id";
    
    $get_total = $db->Query("SELECT  t.task_id
                               FROM  $from
                              WHERE  $where
                           ORDER BY  $sortorder", $sql_params);
    
    $total = $db->CountRows($get_total);
    
    // Store the order of the tasks returned for the next/previous links in the task details
    $id_list = array();
    while ($row = $db->FetchRow($get_total)) {
        $id_list[] = $row['task_id'];
    }
    $_SESSION['tasklist'] = $id_list;
   
    // Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
    $get_details = $db->Query("
             SELECT
                     t.*,
                     p.project_title, p.project_is_active,
                 lt.tasktype_name AS task_type,
                 lc.category_name AS product_category,
                 lv.version_name  AS product_version,
                 lvc.version_name AS closedby_version,
                 u.real_name      AS assigned_to,
                 uo.real_name     AS opened_by
         FROM
                 $from
         WHERE
                 $where
         ORDER BY
                 $sortorder", $sql_params, $perpage, $offset);
   
    $comments = 0;
    $attachments = 0;
   
    while ($task_details = $db->FetchArray($get_details)) {
        $task_id = $task_details['task_id'];
   
        if ($task_details['is_closed'] == "1") {
            // Set the status text to 'closed' if this task is closed
            $status = $index_text['closed'];
        }
        else {
            // Get the full status name
            $status_id = $task_details['item_status'];
            $status    = $status_list[$status_id];
        }
   
        // Get the full severity name
        $severity_id = $task_details['task_severity'];
        $severity    = $severity_list[$severity_id];
   
        // Get the full priority name
        $priority_id = $task_details['task_priority'];
        $priority    = $priority_list[$priority_id];
   
        // see if it's been assigned
        if (!$task_details['assigned_to']) {
            $assigned_to = $details_text['noone'];
        }
        else {
            $assigned_to = $task_details['assigned_to'];
        }
   
        // Convert the date_opened to a human-readable format
        $date_opened = $fs->formatDate($task_details['date_opened'], false);
   
        // Convert the last_edited_time to a human-readable format
        $last_edited_time = '';
        if ($task_details['last_edited_time'] > 0) {
            $last_edited_time = $fs->formatDate($task_details['last_edited_time'], false);
        }
   
        // get the number of comments and attachments
        if ($column_visible['comments']) {
            $getcomments    = $db->Query("SELECT COUNT(*) AS num_comments FROM {comments} WHERE task_id = ?", array($task_id));
            list($comments) = $db->FetchRow($getcomments);
        }
   
        if ($column_visible['attachments']) {
            $getattachments    = $db->Query("SELECT COUNT(*) AS num_attachments FROM {attachments} WHERE task_id = ?", array($task_id));
            list($attachments) = $db->FetchRow($getattachments);
        }
   
        ////////////////////////////////////////////////////////////
        // display starts here 
        echo "<tr id=\"task$task_id\" class=\"severity{$task_details['task_severity']}\">\n";
        if (Cookie::has('flyspray_userid')) {
            echo "<td class=\"ttcolumn\"><input class=\"ticktask\" type=\"checkbox\" name=\"ids[{$task_details['task_id']}]\" value=\"1\"/></td>";
        }
   
        list_cell($task_id, 'id',          $task_id, 1, $fs->CreateURL('details', $task_id));
        list_cell($task_id, 'project',     $task_details['project_title'], 1);
        list_cell($task_id, 'tasktype',    $task_details['task_type'], 1);
        list_cell($task_id, 'category',    $task_details['product_category'], 1);
        list_cell($task_id, 'severity',    $severity, 1);
        list_cell($task_id, 'priority',    $priority, 1);
        list_cell($task_id, 'summary',     $task_details['item_summary'], 0, $fs->CreateURL('details', $task_id));
        list_cell($task_id, 'dateopened',  $date_opened);
        list_cell($task_id, 'status',      $status, 1);
        list_cell($task_id, 'openedby',    $task_details['opened_by'], 0);
        list_cell($task_id, 'assignedto',  $task_details['assigned_to'], 0);
        list_cell($task_id, 'lastedit',    $last_edited_time);
        list_cell($task_id, 'reportedin',  $task_details['product_version']);
        list_cell($task_id, 'dueversion',  $task_details['closedby_version'], 1);
        list_cell($task_id, 'duedate',     $task_details['due_date'], 1);
        list_cell($task_id, 'comments',    $comments);
        list_cell($task_id, 'attachments', $attachments);
   
        list_cell($task_id, 'progress',    $fs->ShowImg("themes/{$project_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png",
                    $task_details['percent_complete'] . '% ' . $index_text['complete']));
   
        echo "</tr>\n";
        //
        ////////////////////////////////////////////////////////////
    }
    ?>
    <!--</tbody>-->
    </table>
   
    <table id="pagenumbers">
       <tr>
       <?php
       if ($total > 0) {
           echo "<td id=\"taskrange\">";
           printf($index_text['taskrange'], $offset + 1, ($offset + $perpage > $total ? $total : $offset + $perpage), $total);
   
           if (Cookie::has('flyspray_userid') && $total > 0) {
               echo '&nbsp;&nbsp;<a href="javascript://;" onclick="ToggleSelectedTasks()">' . $index_text['toggleselected'] . '</a>';
           }
   
           echo "</td><td id=\"numbers\">" . $fs->pagenums($pagenum, $perpage, $total, $extraurl . '&amp;order=' . $_GET['order']) . "</td>";
       } else {
           echo "<td id=\"taskrange\"><strong>{$index_text['noresults']}</strong></td>";
       }
       ?>
       </tr>
    </table>
   
    <?php if (Cookie::has('flyspray_userid') && $total > 0): ?>
    <div id="massopsactions">
      <select name="action">
        <option value="add_notification"><?php echo $index_text['watchtasks'];?></option>
        <option value="remove_notification"><?php echo $index_text['stopwatching'];?></option>
        <option value="takeownership"><?php echo $index_text['assigntome'];?></option>
      </select>
   
      <input class="mainbutton" type="submit" value="<?php echo $index_text['takeaction'];?>" />
    </div>
    <?php endif ?>
    <!-- End of form to do mass operations on shown tasks -->
  </div> <!-- dummy div for xtml comliance -->
</form>

</div>
