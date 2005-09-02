<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

// First, set a whole bunch of DEFAULT variables if they're not
// already set. This is a whole convoluted bunch of crap, but it works.

// First, the obligatory language packs
$fs->get_language_pack($lang, 'index');
$fs->get_language_pack($lang, 'details');

$orderby = array();
$dev = null;

// This generates an URL so that action scripts take us back to this page
$this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
$this_page = str_replace('&', '&amp;', $this_page);

if ( isset($_COOKIE['flyspray_userid']) && isset($current_user['tasks_perpage']) && $current_user['tasks_perpage']>0 )
{
   $perpage = $current_user['tasks_perpage'];
} else
{
   $perpage = '20';
}

if (isset($_GET['order']) && !empty($_GET['order']))
{
   $orderby[] = $_GET['order'];
} else
{
   $orderby[] = 'sev';
}

if (isset($_GET['order2']) && !empty($_GET['order2'])) {
  $orderby[] = $_GET['order2'];
} else {
  $orderby[] = 'pri';
}

foreach ( $orderby as $key => $val )
{
   switch ($orderby[$key])
   {
      case "id": $orderby[$key] = 'task_id';
      break;
      case "proj": $orderby[$key] = 'project_title';
      break;
      case "type": $orderby[$key] = 'tasktype_name';
      break;
      case "date": $orderby[$key] = 'date_opened';
      break;
      case "sev": $orderby[$key] = 'task_severity';
      break;
      case "cat": $orderby[$key] = 'lc.category_name';
      break;
      case "status": $orderby[$key] = 'item_status';
      break;
      case "due": $orderby[$key] = 'lvc.list_position';
      break;
      case "duedate": $orderby[$key] = 'due_date';
      break;
      case "prog": $orderby[$key] = 'percent_complete';
      break;
      case "lastedit": $orderby[$key] = 'last_edited_time';
      break;
      case "pri": $orderby[$key] = 'task_priority';
      break;
      case "openedby": $orderby[$key] = 'uo.real_name';
      break;
      case "reportedin": $orderby[$key] = 't.product_version';
      break;
      case "assignedto": $orderby[$key] = 'u.real_name';
      break;
      default: $orderby[$key] = 'task_severity';
      break;
   }
}

$sort = array($_GET['sort'], $_GET['sort2']);
foreach ( $sort as $key => $val ) {
  switch ($sort[$key]) {
    case "asc": $sort[$key] = "ASC";
    break;
    case "desc": $sort[$key] = "DESC";
    break;
    default: $sort[$key] = "DESC";
    break;
  }
}

$sortorder = "{$orderby[0]} {$sort[0]}, {$orderby[1]} {$sort[1]}, t.task_id ASC";

// Check that what was submitted is a numerical value; most of them should be

// page we're on
if (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']))
{
   $pagenum = $_GET['pagenum'];
} else {
   $pagenum = "1";
}

// the mysql query offset is a combination of the num results per page and the page num
$offset = $perpage * ($pagenum - 1);

$sql_params = array();
$where = array();

$where[] = 'project_is_active = ?';
$sql_params[] = '1';


// If the user wants to view tasks from all projects
if (isset($_GET['project']) && $_GET['project'] == '0')
{
   // If the user has the global 'view tasks' permission, view all projects unrestricted
   if (isset($permissions['global_view']) && $permissions['global_view'] == '1')
   {
      $check_projects = $db->Query("SELECT p.project_id
                                       FROM {$dbprefix}projects p"
                                    );

   // Those who aren't super users get this more restrictive query
   } elseif (isset($_COOKIE['flyspray_userid']))
   {
       $check_projects = $db->Query("SELECT p.project_id
                                     FROM {$dbprefix}users_in_groups uig
                                     LEFT JOIN {$dbprefix}groups g ON uig.group_id = g.group_id
                                     LEFT JOIN {$dbprefix}projects p ON g.belongs_to_project = p.project_id
                                     WHERE ((uig.user_id = ?
                                     AND g.view_tasks = '1')
                                     OR p.others_view = '1')
                                     AND p.project_is_active = '1'",
                                     array($current_user['user_id'])
                                   );

   // Anonymous users also need a query here
   } else
   {
      $check_projects = $db->Query("SELECT p.project_id
                                       FROM {$dbprefix}projects p
                                       WHERE p.others_view = '1'
                                       AND p.project_is_active = '1'"
                                    );

   // End of checking Admin status for the query
   }

   $temp_where = "(attached_to_project = ?";
   $sql_params[] = '0';

   // Cycle through the projects, selecting the ones that the user is allowed to view
   while ($this_project = $db->FetchArray($check_projects))
   {
      $temp_where = $temp_where . " OR attached_to_project = ?";
      $sql_params[] = $this_project['project_id'];
   }

   $where[] = $temp_where . ")";


// If we're not selecting all projects
} else
{
   $where[]       = "attached_to_project = ?";
   $sql_params[]  = $project_id;
}

// Check for special tasks to display
if (isset($_GET['dev']))
   $dev = $_GET['dev'];

if ($_GET['tasks'] == 'assigned') {
    $dev = $current_user['user_id'];
} elseif ($_GET['tasks'] == 'reported') {
    $where[] = 'opened_by = ?';
    $sql_params[] = $current_user['user_id'];
}

// developer whose bugs to show
if (isset($dev) && is_numeric($dev))
{
   $where[] = "assigned_to = ?";
   $sql_params[] = $dev;
} elseif (isset($dev) && $dev == "notassigned")
{
   $where[] = "assigned_to = '0'";
}

// The default task type
if (isset($_GET['type']) && is_numeric($_GET['type'])) {
  $where[] = "task_type = ?";
  $sql_params[] = $_GET['type'];
}

// The default severity
if (isset($_GET['sev']) && is_numeric($_GET['sev'])) {
  $where[] = "task_severity = ?";
  $sql_params[] = $_GET['sev'];
}

// The default category
if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
  $temp_where = "(product_category = ?";
  $sql_params[] = $_GET['cat'];

  // Do some weird stuff to add the subcategories to the query
  $get_subs = $db->Query("SELECT category_id
                            FROM {$dbprefix}list_category
                            WHERE parent_id = ?",
                            array($_GET['cat']));
  while ($row = $db->FetchArray($get_subs)) {
    $temp_where = $temp_where . " OR product_category =?";
    $sql_params[] = $row['category_id'];
  }
  $where[] = $temp_where . ")";
}

// The default status
if (isset($_GET['status']) && $_GET['status'] == "all") {
} elseif (isset($_GET['status']) && $_GET['status'] == "closed") {
  $where[] = "is_closed = ?";
  $sql_params[] = "1";
} elseif (isset($_GET['status']) && is_numeric($_GET['status'])) {
  $where[]      = "item_status = ? AND is_closed <> '1'";
  $sql_params[] = $_GET['status'];
} else {
  $where[] = "is_closed != ?";
  $sql_params[] = '1';
}
// The default due in version
if (isset($_GET['due']) && is_numeric($_GET['due']))
{
   $where[] = "closedby_version = ?";
   $sql_params[] = $_GET['due'];
}
// The due by date
if (isset($_GET['date']) && !empty($_GET['date']))
{
   $where[] = "(due_date < ? AND due_date <> '0' AND due_date <> '')";
   $sql_params[] = strtotime("{$_GET['date']} +24 hours");
}
// The default search string
if (isset($_GET['string']) && $_GET['string']) {
  $string = $_GET['string'];
  $string = ereg_replace('\(', " ", $string);
  $string = ereg_replace('\)', " ", $string);
  $string = trim($string);

  $where[]      = "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
}

// Do this to hide private tasks from the list
if (!isset($current_user))
{
   $where[] = "t.mark_private <> '1'";

} elseif ( isset($current_user) && empty($permissions['manage_project']) )
{
   $where[] = "(t.mark_private <> '1' OR t.assigned_to = ?)";
   $sql_params[] = $current_user['user_id'];
}

if (isset($_GET['project']) && $_GET['project'] == '0') {
    $get = "&amp;project=0";
} else {
    $get = "&amp;project={$project_id}";
}

// for 'sort by this column' links
$get = $get . '&amp;tasks=' . $_GET['tasks'] . $extraurl;

// for page numbering
//$extraurl = $get . '&amp;order=' . $_GET['order'];
?>

<?php
// Check that the requested project is active
//$getproject = $db->FetchArray($db->Query('SELECT * FROM {$dbprefix}projects WHERE project_id = ?', array($project_id)));

if ($project_prefs['project_is_active'] == '1'
  && ($project_prefs['others_view'] == '1' OR @$permissions['view_tasks'] == '1')
  OR $_GET['project'] == '0')
{
?>


<!-- Query line -->
<div id="search">
<map id="projectsearchform" name="projectsearchform">
<form action="index.php" method="get">
<div>
<input type="hidden" name="tasks" value="<?php echo $_GET['tasks']; ?>" />
<input type="hidden" name="project" value="<?php if(isset($_GET['project']) && $_GET['project'] == '0') { echo '0'; } else { echo $project_id; }?>" />
  <em><?php echo $index_text['searchthisproject'];?>:</em>
    <input id="searchtext" name="string" type="text" size="20"
    maxlength="100" value="<?php if(isset($_GET['string'])) echo $_GET['string'];?>" accesskey="q" />

    <select name="type">
      <option value=""><?php echo $index_text['alltasktypes'];?></option>
      <?php
      $tasktype_list = $db->Query("SELECT tasktype_id, tasktype_name FROM {$dbprefix}list_tasktype
                                   WHERE show_in_list = '1'
                                   AND (project_id = '0'
                                   OR project_id = ?)
                                   ORDER BY list_position",
                                   array($project_id)
                                 );
      while ($row = $db->FetchArray($tasktype_list))
      {
         if (isset($_GET['type']) && $_GET['type'] == $row['tasktype_id'])
         {
            echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>\n";
         } else
         {
            echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>\n";
         }
      }
      ?>
    </select>

    <select name="sev">
      <option value=""><?php echo $index_text['allseverities'];?></option>
      <?php
      require("lang/$lang/severity.php");
      foreach($severity_list as $key => $val)
      {
         if (isset($_GET['sev']) && $_GET['sev'] == $key)
         {
            echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
         } else
         {
            echo "<option value=\"$key\">$val</option>\n";
         }
      }
      ?>
    </select>

    <select name="due" <?php if(isset($_GET['project']) && $_GET['project'] == '0') { echo 'DISABLED';}?>>
      <option value=""><?php echo $index_text['dueanyversion'];?></option>
      <?php
      $ver_list = $db->Query("SELECT version_id, version_name
                              FROM {$dbprefix}list_version
                              WHERE show_in_list = '1' AND version_tense = '3'
                              AND (project_id = '0'
                              OR project_id = ?)
                              ORDER BY list_position",
                              array($project_id,)
                            );

      while ($row = $db->FetchArray($ver_list))
      {
         if (isset($_GET['due']) && $_GET['due'] == $row['version_id'])
         {
            echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
         } else
         {
            echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
         }
      }
      ?>
    </select>

    <select name="dev">
      <option value=""><?php echo $index_text['alldevelopers'];?></option>
      <option value="notassigned" <?php if ($dev == "notassigned") { echo "SELECTED";}?>><?php echo $index_text['notyetassigned'];?></option>
      <?php
      $fs->ListUsers($dev, $project_id);
      ?>
    </select>

    <select name="cat" <?php if(isset($_GET['project']) && $_GET['project'] == '0') echo 'DISABLED';?>>
      <option value=""><?php echo $index_text['allcategories'];?></option>
      <?php
      $cat_list = $db->Query("SELECT category_id, category_name
                              FROM {$dbprefix}list_category
                              WHERE show_in_list = '1' AND parent_id < '1'
                              AND (project_id = '0'
                                   OR project_id = ?)
                              ORDER BY list_position",
                              array($project_id)
                            );

      while ($row = $db->FetchArray($cat_list))
      {
         $category_name = stripslashes($row['category_name']);
         if (isset($_GET['cat']) && $_GET['cat'] == $row['category_id'])
         {
            echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
         } else
         {
            echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
         }

         $subcat_list = $db->Query("SELECT category_id, category_name
                                    FROM {$dbprefix}list_category
                                    WHERE show_in_list = '1' AND parent_id = ?
                                    ORDER BY list_position",
                                    array($row['category_id'])
                                  );

         while ($subrow = $db->FetchArray($subcat_list))
         {
            $subcategory_name = stripslashes($subrow['category_name']);
            if (isset($_GET['cat']) && $_GET['cat'] == $subrow['category_id'])
            {
               echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&nbsp;&nbsp;&rarr;$subcategory_name</option>\n";
            } else
            {
               echo "<option value=\"{$subrow['category_id']}\">&nbsp;&nbsp;&rarr;$subcategory_name</option>\n";
            }
         }
      }
      ?>
    </select>

    <select name="status">
      <option value="all" <?php if (isset($_GET['status']) && $_GET['status'] == 'all') echo 'selected="selected"';?>><?php echo $index_text['allstatuses'];?></option>
      <option value="" <?php if ((isset($_GET['status']) && empty($_GET['status'])) OR !isset($_GET['status'])) { echo "selected=\"selected\"";}?>><?php echo $index_text['allopentasks'];?></option>
      <?php
      require("lang/$lang/status.php");
      foreach($status_list as $key => $val)
      {
         if (isset($_GET['status']) && $_GET['status'] == $key)
         {
            echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
         } else
         {
            echo "<option value=\"$key\">$val</option>\n";
         }
      }
      ?>
      <option value="closed" <?php if(isset($_GET['status']) && $_GET['status'] == "closed") { echo "SELECTED";}?>><?php echo $index_text['closed'];?></option>
    </select>

   <select id="due_date" name="date">
   <option value=""><?php echo $index_text['dueanytime'];?></option>
   <option id="date_d"<?php if (!empty($_GET['date'])) { echo ' selected="1">' . $_GET['date'];}else{echo '>' . $index_text['selectduedate'];};?></option>
   </select>
   <script type="text/javascript">
   Calendar.setup(
   {
      inputField  : "date_d",         // ID of the input field
      ifFormat    : "%d-%b-%Y",    // the date format
      displayArea : "date_d",       // The display field
      daFormat    : "%d-%b-%Y",
      button      : "date_d"       // ID of the button
   }
   );
   </script>

    <input class="mainbutton" type="submit" value="<?php echo $index_text['search'];?>" />
</div>
</form>
</map>
</div>

<?php
// Get the visibility state of all columns
$column_visible = array();
$columns = array('id', 'project', 'tasktype', 'category', 'severity', 'priority',
                 'summary', 'dateopened', 'status', 'openedby', 'assignedto', 'lastedit',
                 'reportedin', 'dueversion', 'duedate', 'comments', 'attachments', 'progress');

foreach ($columns as $column)
{
    $column_visible[$column] = false;
}

$project = isset($_GET['project']) ? $_GET['project'] : $project_id;
$visible = explode(' ', $project == '0' ? $flyspray_prefs['visible_columns'] : $project_prefs['visible_columns']);

foreach ($visible as $column)
{
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

  if($column_visible[$colname])
  {
      if($orderkey)
      {
         $class = '';
         if(isset($_GET['order']) && $_GET['order'] == "$orderkey")
         {
            $class = 'class="orderby"';

            $order2 = $_GET['order2'];
            $sort2 = $_GET['sort2'];

            if (isset($_GET['sort']) && $_GET['sort'] == 'desc')
            {
               $sort1 = "asc";
            } else
            {
               $sort1 = "desc";
            }
         } else
         {
            $sort1 = $defaultsort;

            //if (isset($_GET['order']))
               $order2 = $_GET['order'];
            //if (isset($_GET['sort']))
               $sort2 = $_GET['sort'];
         }

         $sort1 = ( $sort1 == 'asc' ? 'asc' : 'desc' );
         $sort2 = ( $sort2 == 'asc' ? 'asc' : 'desc' );

         echo "<th $class>";
         $title = $index_text['sortthiscolumn'];
         $link = "?order=$orderkey$get&amp;sort=$sort1&amp;order2=$order2&amp;sort2=$sort2";
         echo "<a title=\"$title\" href=\"$link\">";
         echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" />\n";

         // Sort indicator arrows
         if(isset($_GET['order']) && $_GET['order'] == $orderkey)
         {
            echo '&nbsp;&nbsp;<img src="themes/' . $project_prefs['theme_style'] . '/' . $_GET['sort'] . '.png" />';
         }

         echo "</a></th>\n";
      } else
      {
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

function list_cell($task_id, $colname,$cellvalue,$nowrap=0,$url=0)
{
   global $column_visible;
   global $fs;

   if($column_visible[$colname])
   {
      // We have a problem with these conversions applied to the progress cell
      if($colname != "progress")
      {
         $cellvalue = str_replace("&", "&amp;", $cellvalue);
         $cellvalue = str_replace("<", "&lt;", $cellvalue);
         $cellvalue = stripslashes($cellvalue);
      }

      if ($colname == 'duedate')
      {
         if (!empty($cellvalue))
         {
            $cellvalue = $fs->FormatDate($cellvalue, false);
         } else
         {
            $cellvalue = '';
         }
      }

       // Check if we're meant to force this cell not to wrap
       if($nowrap)
      {
         $cellvalue = str_replace(" ", "&nbsp;", $cellvalue);
      }

      echo "<td class=\"task_$colname\" >";
      if($url)
      {
         echo "<a href=\"$url\">$cellvalue</a>";
      }
      else
      {
         echo "$cellvalue";
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
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />

   <!--  Summary headings, followed by the query results -->
   <table id="tasklist_table">
   <thead>
      <tr>

      <?php
      // Spacer for the checkboxes beneath it
      if (isset($_COOKIE['flyspray_userid']))
         echo '<th class="ttcolumn"></th>';

      list_heading('id','id');
      list_heading('project','proj','asc');
      list_heading('tasktype','type','asc');
      list_heading('category','cat','asc');
      list_heading('severity','sev');
      list_heading('priority','pri');
      list_heading('summary','');
      list_heading('dateopened','date');
      list_heading('status','status');
      list_heading('openedby','openedby','asc');
      list_heading('assignedto','assignedto','asc');
      list_heading('lastedit', 'lastedit');
      list_heading('reportedin','reportedin');
      list_heading('dueversion','due');
      list_heading('duedate','duedate');
      list_heading('comments','','', "themes/{$project_prefs['theme_style']}/comment.png");
      list_heading('attachments','','', "themes/{$project_prefs['theme_style']}/attachment.png");
      list_heading('progress','prog');
      ?>

      </tr>
   </thead>

<?php

// SQL JOIN condition
$from = "{$dbprefix}tasks t";

if (isset($_GET['tasks']) && $_GET['tasks'] == 'watched') {
    //join the notification table to get watched tasks
    $from .= " RIGHT JOIN {$dbprefix}notifications fsn ON t.task_id = fsn.task_id";
    $where[] = 'fsn.user_id = ?';
    $sql_params[] = $current_user['user_id'];
}

// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$from .= "

        LEFT JOIN {$dbprefix}projects p ON t.attached_to_project = p.project_id
        LEFT JOIN {$dbprefix}list_tasktype lt ON t.task_type = lt.tasktype_id
        LEFT JOIN {$dbprefix}list_category lc ON t.product_category = lc.category_id
        LEFT JOIN {$dbprefix}list_version lv ON t.product_version = lv.version_id
        LEFT JOIN {$dbprefix}list_version lvc ON t.closedby_version = lvc.version_id
        LEFT JOIN {$dbprefix}users u ON t.assigned_to = u.user_id
        LEFT JOIN {$dbprefix}users uo ON t.opened_by = uo.user_id
        ";

//$where[] = 't.attached_to_project = {$dbprefix}projects.project_id';
$where = join(' AND ', $where);
$get_total = $db->Query("SELECT t.task_id FROM $from
          WHERE $where
          ORDER BY $sortorder", $sql_params);

$total = $db->CountRows($get_total);

// Store the order of the tasks returned for the next/previous links in the task details
$id_list = array();
while ($row = $db->FetchRow($get_total))
{
    $id_list[] = $row['task_id'];
}
$_SESSION['tasklist'] = $id_list;

// Parts of this SQL courtesy of Lance Conry http://www.rhinosw.com/
$get_details = $db->Query("
SELECT
        t.*,
        p.project_title,
        p.project_is_active,
        lt.tasktype_name AS task_type,
        lc.category_name AS product_category,
        lv.version_name AS product_version,
        lvc.version_name AS closedby_version,
        u.real_name AS assigned_to,
        uo.real_name AS opened_by
FROM
        $from
WHERE
        $where
ORDER BY
        $sortorder", $sql_params, $perpage, $offset);

   $comments = 0;
   $attachments = 0;

   while ($task_details = $db->FetchArray($get_details))
   {
      // Set the status text to 'closed' if this task is closed
      if ($task_details['is_closed'] == "1")
      {
         $status = $index_text['closed'];
      } else
      {
         // Get the full status name
         $status_id = $task_details['item_status'];
         require("lang/$lang/status.php");
         $status = $status_list[$status_id];
      }

      // Get the full severity name
      $severity_id = $task_details['task_severity'];
      require("lang/$lang/severity.php");
      $severity = $severity_list[$severity_id];

      // Get the full priority name
      $priority_id = $task_details['task_priority'];
      require("lang/$lang/priority.php");
      $priority = $priority_list[$priority_id];

      // see if it's been assigned
      if (!$task_details['assigned_to'])
      {
         $assigned_to = $details_text['noone'];
      } else
      {
         $assigned_to = $task_details['assigned_to'];
      }

      // Convert the date_opened to a human-readable format
      $date_opened = $fs->formatDate($task_details['date_opened'], false);

      // Convert the last_edited_time to a human-readable format
      if ($task_details['last_edited_time'] > 0)
      {
         $last_edited_time = $fs->formatDate($task_details['last_edited_time'], false);
      } else
      {
         $last_edited_time = '';
      }

      // get the number of comments and attachments
      if ($column_visible['comments'])
      {
         $getcomments = $db->Query("SELECT COUNT(*) AS num_comments FROM {$dbprefix}comments WHERE task_id = ?", array($task_details['task_id']));
         list($comments) = $db->FetchRow($getcomments);
      }

      if ($column_visible['attachments'])
      {
         $getattachments = $db->Query("SELECT COUNT(*) AS num_attachments FROM {$dbprefix}attachments WHERE task_id = ?", array($task_details['task_id']));
         list($attachments) = $db->FetchRow($getattachments);
      }

      // Start displaying the cells for this row
      echo "<tr id=\"task{$task_details['task_id']}\" class=\"severity{$task_details['task_severity']}\">\n";

      // Checkbox for mass operations
      if (isset($_COOKIE['flyspray_userid']))
         echo "<td class=\"ttcolumn\"><input class=\"ticktask\" type=\"checkbox\" name=\"ids[{$task_details['task_id']}]\" value=\"1\"/></td>";

      list_cell($task_details['task_id'], "id",$task_details['task_id'],1, $fs->CreateURL('details', $task_details['task_id']));
      list_cell($task_details['task_id'], "project",$task_details['project_title'],1);
      list_cell($task_details['task_id'], "tasktype",$task_details['task_type'],1);
      list_cell($task_details['task_id'], "category",$task_details['product_category'],1);
      list_cell($task_details['task_id'], "severity",$severity,1);
      list_cell($task_details['task_id'], "priority",$priority,1);
      list_cell($task_details['task_id'], "summary",$task_details['item_summary'],0, $fs->CreateURL('details', $task_details['task_id']));
      list_cell($task_details['task_id'], "dateopened",$date_opened);
      list_cell($task_details['task_id'], "status",$status,1);
      list_cell($task_details['task_id'], "openedby",$task_details['opened_by'],0);
      list_cell($task_details['task_id'], "assignedto",$task_details['assigned_to'],0);
      list_cell($task_details['task_id'], "lastedit",$last_edited_time);
      list_cell($task_details['task_id'], "reportedin",$task_details['product_version']);
      list_cell($task_details['task_id'], "dueversion",$task_details['closedby_version'],1);
      list_cell($task_details['task_id'], "duedate",$task_details['due_date'],1);
      list_cell($task_details['task_id'], "comments",$comments);
      list_cell($task_details['task_id'], "attachments",$attachments);
      list_cell($task_details['task_id'], "progress",$fs->ShowImg("themes/{$project_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png", $task_details['percent_complete'] . '% ' . $index_text['complete']));

      // The end of this row
      echo "</tr>\n";
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

         if (isset($_COOKIE['flyspray_userid']) && $total > 0)
         echo '&nbsp;&nbsp;<a href="javascript://;" onclick="ToggleSelectedTasks()">' . $index_text['toggleselected'] . '</a>';

         echo "</td><td id=\"numbers\">" . $fs->pagenums($pagenum, $perpage, $total, $extraurl . '&amp;order=' . $_GET['order']) . "</td>";
      } else
      {
         echo "<td id=\"taskrange\"><strong>{$index_text['noresults']}</strong></td>";
      }
      ?>
      </tr>
   </table>

   <?php
   if (isset($_COOKIE['flyspray_userid']) && $total > 0) { ?>
   <div id="massopsactions">
      <select name="action">
         <option value="add_notification"><?php echo $index_text['watchtasks'];?></option>
         <option value="remove_notification"><?php echo $index_text['stopwatching'];?></option>
         <option value="takeownership"><?php echo $index_text['assigntome'];?></option>
      </select>

      <input class="mainbutton" type="submit" value="<?php echo $index_text['takeaction'];?>" />
   </div>
   <?php } ?>
<!-- End of form to do mass operations on shown tasks -->
</div> <!-- dummy div for xtml comliance -->
</form>

</div>

<?php
// End of checking if the reqeusted project is active,
// and that the user has permission to view it
}
?>
