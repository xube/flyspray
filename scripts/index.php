<?php
// set a whole bunch of DEFAULT variables if they're not already set
//  this is a whole convoluted bunch of crap, but it works.

get_language_pack($lang, 'index');

switch ($_GET['order']) {
  case "id": $orderby = 'flyspray_tasks.task_id';
  break;
  case "project_title": $orderby = 'attached_to_project';
  break;
  case "type": $orderby = 'task_type';
  break;
  case "date": $orderby = 'date_opened';
  break;
  case "sev": $orderby = 'task_severity';
  break;
  case "cat": $orderby = 'product_category';
  break;
  case "status": $orderby = 'item_status';
  break;
  case "due": $orderby = 'closedby_version';
  break;
  case "prog": $orderby = 'percent_complete';
  break;
  default: $orderby = 'task_severity';
  break;
};

switch ($_GET['sort']) {
  case "asc": $sort = "ASC";
  break;
  case "desc": $sort = "DESC";
  break;
  default: $sort = "DESC";
  break;
};

// Check that what was submitted is a numerical value; most of them should be

// page we're on
if (is_numeric($_GET['pagenum'])) {
  $pagenum = $_GET['pagenum'];
} else {
  $pagenum = "0";
};
// number of results per page
if (is_numeric($_GET['perpage'])) {
  $perpage = $_GET['perpage'];
} else {
  $perpage = "20";
};

// the mysql query offset is a combination of the num results per page and the page num
$offset = $perpage * $pagenum;

$sql_params = array();
$where = array();

$where[] = 'project_is_active = 1';

// Set the default queries
if ($_GET['project'] != '0') {
  $where[]	= "attached_to_project = ?";
  $sql_params[] = $project_id;
};


// Check for special tasks to display
$dev = $_GET['dev'];

if ($_GET['tasks'] == 'assigned') {
    $dev = $_SESSION['userid'];
} elseif ($_GET['tasks'] == 'reported') {
    $where[] = 'opened_by = ?';
    $sql_params[] = $_SESSION['userid'];
};


// developer whos bugs to show
if (is_numeric($dev)) {
  $where[]	= "assigned_to = ?";
  $sql_params[] = $dev;
} elseif ($dev == "notassigned") {
  $where[] = "assigned_to = '0'";
};


// The default task type
if (is_numeric($_GET['type'])) {
  $where[]	= "task_type = ?";
  $sql_params[] = $_GET['type'];
};

// The default severity
if (is_numeric($_GET['sev'])) {
  $where[]	= "task_severity = ?";
  $sql_params[] = $_GET['sev'];
};

// The default category
if (is_numeric($_GET['cat'])) {
  $temp_where = "(product_category = ?";
  $sql_params[] = $_GET['cat'];

  // Do some weird stuff to add the subcategories to the query
  $get_subs = $fs->dbQuery('SELECT category_id
                            FROM flyspray_list_category
                            WHERE parent_id = ?',
                            array($_GET['cat']));
  while ($row = $fs->dbFetchArray($get_subs)) {
    $temp_where = $temp_where . " OR product_category =?";
    $sql_params[] = $row['category_id'];
  };
  $where[] = $temp_where . ")";
};

// The default status
if ($_GET['status'] == "all") {
} elseif ($_GET['status'] == "closed") {
  $where[] = "is_closed = ?";
  $sql_params[] = "1";
} elseif (is_numeric($_GET['status'])) {
  $where[]	= "item_status = ?";
  $sql_params[] = $_GET['status'];
} else {
  $where[] = "is_closed != '1'";
};
// The default due in version
if (is_numeric($_GET['due'])) {
  $where[] = "closedby_version = ?";
  $sql_params[] = $_GET['due'];
};
// The default search string
if ($_GET['string']) {
  $string = $_GET['string'];
  $string = ereg_replace('\(', " ", $string);
  $string = ereg_replace('\)', " ", $string);
  $string = trim($string);

  $where[]	= "(item_summary LIKE ? OR detailed_desc LIKE ? OR task_id LIKE ?)";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
};

if ($_GET['project'] == '0') {
    $get = "&amp;project=0";
} else {
    $get = "&amp;project={$project_id}";
};
// for page numbering
$extraurl = $get . "&amp;tasks={$_GET['tasks']}&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$dev}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage";
// for 'sort by this column' links
$get = $extraurl . "&amp;pagenum=$pagenum";
$extraurl .= "&amp;order={$_GET['order']}&amp;sort={$_GET['sort']}";

?>

<!-- Query line -->
<form action="index.php" method="get">
<input type="hidden" name="tasks" value="<?php echo $_GET['tasks']; ?>">
<input type="hidden" name="project" value="<?php echo $_GET['project'] == '0' ? '0' : $project_id;?>">
<p id="search">
  <label for="searchtext"><?php echo $index_text['searchthisproject'];?>:</label>
    <input id="searchtext" name="string" type="text" size="40"
    maxlength="100" value="<?php echo $_GET['string'];?>" accesskey="q">

    <select name="type">
      <option value=""><?php echo $index_text['alltasktypes'];?></option>
      <?php
      $tasktype_list = $fs->dbQuery('SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype
                                       WHERE show_in_list = 1
                                       ORDER BY list_position');
      while ($row = $fs->dbFetchArray($tasktype_list)) {
        if ($_GET['type'] == $row['tasktype_id']) {
          echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>\n";
        } else {
          echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>\n";
        };
      };
      ?>
    </select>

    <select name="sev">
      <option value=""><?php echo $index_text['allseverities'];?></option>
      <?php
      require("lang/$lang/severity.php");
      foreach($severity_list as $key => $val) {
        if ($_GET['sev'] == $key) {
          echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
    </select>

    <select name="due">
      <option value=""><?php echo $index_text['dueanyversion'];?></option>
      <?php
      $ver_list = $fs->dbQuery("SELECT version_id, version_name
                                  FROM flyspray_list_version
                                  WHERE project_id=? AND show_in_list=?
                                  ORDER BY list_position", array($project_id, '1'));
      while ($row = $fs->dbFetchArray($ver_list)) {
        if ($_GET['due'] == $row['version_id']) {
          echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
        } else {
          echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
        };
      };
      ?>
    </select>

    <br>
    
    <select name="dev">
      <option value=""><?php echo $index_text['alldevelopers'];?></option>
      <option value="notassigned" <?php if ($dev == "notassigned") { echo "SELECTED";};?>><?php echo $index_text['notyetassigned'];?></option>
      <?php
      $fs->ListUsers($dev);
      ?>
    </select>

    <select name="cat">
      <option value=""><?php echo $index_text['allcategories'];?></option>
      <?php
      $cat_list = $fs->dbQuery('SELECT category_id, category_name
                                  FROM flyspray_list_category
                                  WHERE project_id=? AND show_in_list=? AND parent_id < ?
                                  ORDER BY list_position', array($project_id, '1', '1'));
      while ($row = $fs->dbFetchArray($cat_list)) {
        $category_name = stripslashes($row['category_name']);
        if ($_GET['cat'] == $row['category_id']) {
          echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
        } else {
          echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
        };

        $subcat_list = $fs->dbQuery('SELECT category_id, category_name
                                  FROM flyspray_list_category
                                  WHERE project_id=? AND show_in_list=? AND parent_id = ?
                                  ORDER BY list_position', array($project_id, '1', $row['category_id']));
        while ($subrow = $fs->dbFetchArray($subcat_list)) {
          $subcategory_name = stripslashes($subrow['category_name']);
          if ($_GET['cat'] == $subrow['category_id']) {
            echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&rarr;$subcategory_name</option>\n";
          } else {
            echo "<option value=\"{$subrow['category_id']}\">&rarr;$subcategory_name</option>\n";
          };
        };
      };
      ?>
    </select>

    <select name="status">
      <option value="all" <?php if ($_GET['status'] == "all") { echo "selected=\"selected\"";};?>><?php echo $index_text['allstatuses'];?></option>
      <option value="" <?php if ($_GET['status'] == "") { echo "selected=\"selected\"";};?>><?php echo $index_text['allopentasks'];?></option>
      <?php
      require("lang/$lang/status.php");
      foreach($status_list as $key => $val) {
        if ($_GET['status'] == $key) {
          echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
	  <option value="closed" <?php if($_GET['status'] == "closed") { echo "SELECTED";};?>><?php echo $index_text['closed'];?></option>
    </select>

    <select name="perpage">
      <option value="10" <?php if ($perpage == "10") { echo "selected=\"selected\"";};?>>10</option>
      <option value="20" <?php if ($perpage == "20") { echo "selected=\"selected\"";};?>>20</option>
      <option value="30" <?php if ($perpage == "30") { echo "selected=\"selected\"";};?>>30</option>
      <option value="40" <?php if ($perpage == "40") { echo "selected=\"selected\"";};?>>40</option>
      <option value="50" <?php if ($perpage == "50") { echo "selected=\"selected\"";};?>>50</option>
      <option value="75" <?php if ($perpage == "75") { echo "selected=\"selected\"";};?>>75</option>
      <option value="100" <?php if ($perpage == "100") { echo "selected=\"selected\"";};?>>100</option>
    </select>

    <input class="mainbutton" type="submit" value="<?php echo $index_text['search'];?>">
</p>
</form>

<?php
/**
 * Displays header cell for report list
 *
 * @param string $colname	The name of the column
 * @param string $orderkey	The actual key to use when ordering the list
 * @param string $image    An image to display instead of the column name
 */
function list_heading($colname, $orderkey, $image = '')
{
  global $project_prefs;
  global $index_text;
  global $get;
  
  if(ereg("$colname", $project_prefs['visible_columns']))
  { 
    if($orderkey)
    {
      echo "<th ";
      if($_GET['order'] == "$orderkey")
      {
        echo "class=\"severity3\"";
      }
      echo ">";
      echo "<a title=\"";
      echo $index_text['sortthiscolumn'];
      echo "\" href=\"?order=$orderkey";
      echo $get;
      echo "&amp;sort=";
      if (($_GET['sort'] == "desc") && ($_GET['order'] == "$orderkey"))
      {
        echo "asc";
      }
      else
      {
        echo "desc";
      }
      echo "\">";
      echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\">";
      echo "</a></th>";
    }
    else
    {
      echo "<th>";
      echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" alt=\"{$index_text[$colname]}\">";
      echo "</th>";
    }
  } 
}

/**
 * Displays data cell for report list
 *
 * @param string $colname	The name of the column
 * @param string $cellvalue	The value to display in the cell
 * @param integer $nowrap	Whether to force the cell contents not to wrap
 * @param string $url		A URL to wrap around the cell contents
 */
function list_cell($colname,$cellvalue,$nowrap=0,$url=0)
{
  global $project_prefs;

  if(ereg("$colname", $project_prefs['visible_columns']))
  {
    // We have a problem with these conversions applied to the progress cell
    if($colname != "progress")
    {
      $cellvalue = str_replace("&", "&amp;", $cellvalue);
      $cellvalue = str_replace("<", "&lt;", $cellvalue);
      $cellvalue = stripslashes($cellvalue);
    }
    
    // Check if we're meant to force this cell not to wrap
    if($nowrap)
    {
      $cellvalue = str_replace(" ", "&nbsp;", $cellvalue);
    }
    
    echo "<td class=\"project_$colname\">";
    if($url)
    {
      echo "<a href=\"$url\">$cellvalue";
    }
    else
    {
      echo "$cellvalue";
    }
    echo "</td>\n";
  }
}

?>


<?php
// Check that the requested project is active
$getproject = $fs->dbFetchArray($fs->dbQuery('SELECT * FROM flyspray_projects WHERE project_id = ?', array($project_id)));
if ($getproject['project_is_active'] == 1) {
?>

<!--  Summary headings, followed by the query results -->
<table id="tasklist">
<thead>
  <tr>

  <?php
  list_heading('id','id');
  list_heading('project','proj');
  list_heading('tasktype','type');  
  list_heading('category','cat');
  list_heading('severity','sev');
  list_heading('summary','');
  list_heading('dateopened','date');
  list_heading('status','status');
  list_heading('openedby','openedby');
  list_heading('assignedto','assignedto');
  list_heading('lastedit', 'lastedit');
  list_heading('reportedin','reportedin');
  list_heading('dueversion','due');
  list_heading('comments','', "themes/{$project_prefs['theme_style']}/comment.png");
  list_heading('attachments','', "themes/{$project_prefs['theme_style']}/attachment.png");
  list_heading('progress','prog');
  ?>

  </tr>
</thead>
<tfoot><tr><td colspan="20">
<?php
 
// SQL JOIN condition
$from = 'flyspray_tasks, flyspray_projects';

if ($_GET['tasks'] == 'watched') {
    //join the notification table to get watched tasks
    $from .= ' RIGHT JOIN flyspray_notifications ON flyspray_tasks.task_id = flyspray_notifications.task_id';
    $where[] = 'user_id = ?';
    $sql_params[] = $_SESSION['userid'];
};
    
$where[] = 'flyspray_tasks.attached_to_project = flyspray_projects.project_id';
$where = join(' AND ', $where);
$get_total = $fs->dbQuery("SELECT * FROM $from
          WHERE $where
          ORDER BY $orderby $sort", $sql_params);

$total = $fs->dbCountRows($get_total);
print $fs->pagenums($pagenum, $perpage, "6", $total, $extraurl);


?>
</td></tr></tfoot>



<!--<tbody>-->
  <?php

  $getdetails = $fs->dbQuery("SELECT * FROM $from
          WHERE $where
          ORDER BY $orderby $sort", $sql_params, $perpage, $offset);


  while ($task_details = $fs->dbFetchArray($getdetails)) {

    // Get the full project title
    $get_project_title = $fs->dbQuery("SELECT project_title FROM flyspray_projects WHERE project_id=?", array($task_details['project']));
    $project = $fs->dbFetchArray($get_project_title);

    // Get the full tasktype name
    $get_tasktype_name = $fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id=?", array($task_details['task_type']));
    $tasktype_info = $fs->dbFetchArray($get_tasktype_name);

    // Get the full category name
    $get_category_name = $fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id=?", array($task_details['product_category']));
    list($category) = $fs->dbFetchArray($get_category_name);

    // Get the full status name
    $status_id = $task_details['item_status'];
    require("lang/$lang/status.php");
    $status = $status_list[$status_id];

    // Get the full severity name
    $severity_id = $task_details['task_severity'];
    require("lang/$lang/severity.php");
    $severity = $severity_list[$severity_id];

    // Get the full reported in version name
    $get_reported_name = $fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id=?", array($task_details['product_version']));
    list($reported_in) = $fs->dbFetchArray($get_reported_name);
    
    // Get the full due-in version name
    $get_due_name = $fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id=?", array($task_details['closedby_version']));
    list($due) = $fs->dbFetchArray($get_due_name);

    // Convert the date_opened to a human-readable format
    $date_opened = $fs->formatDate($task_details['date_opened'], false);

    // see if it's been assigned
    if (!$task_details['assigned_to']) {
      $assigned_to = $details_text['noone'];
    } else {
      // find out the username
      $getusername = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['assigned_to']));
      list ($user_name, $real_name) = $fs->dbFetchArray($getusername);
      $assigned_to = "$real_name ($user_name)";
    };
    
    // find out the username
    $getusername = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['opened_by']));
    list ($user_name, $real_name) = $fs->dbFetchArray($getusername);
    $opened_by = "$real_name ($user_name)";
    
    // Convert the last_edited_time to a human-readable format
    if ($task_details['last_edited_time'] != '0') {
      $last_edited_time = $fs->formatDate($task_details['last_edited_time'], false);
    } else {
      $last_edited_time = $date_opened;
    };
        
    // Set the status text to 'closed' if this task is closed
    if ($task_details['is_closed'] == "1")
    {
      $status = $index_text['closed'];
    }
    
    // get the number of comments and attachments
    $getcomments = $fs->dbQuery("SELECT COUNT(*) AS num_comments FROM flyspray_comments WHERE task_id = ?", array($task_details['task_id']));
    list($comments) = $fs->dbFetchRow($getcomments);
    
    $getattachments = $fs->dbQuery("SELECT COUNT(*) AS num_attachments FROM flyspray_attachments WHERE task_id = ?", array($task_details['task_id']));
    list($attachments) = $fs->dbFetchRow($getattachments);    
    
    // Start displaying the cells for this row
    echo "<tr class=\"severity{$task_details['task_severity']}\"
    onclick='openTask(\"?do=details&amp;id={$task_details['task_id']}\")'
    onmouseover=\"this.className = 'severity{$task_details['task_severity']}_over';
    this.style.cursor = 'hand'\"
    onmouseout=\"this.className = 'severity{$task_details['task_severity']}';
    this.style.cursor = 'default'\">\n";

    list_cell("id",$task_details['task_id'],1,"?do=details&amp;id={$task_details['task_id']}");
    list_cell("project",$task_details['project_title'],1);
    list_cell("tasktype",$tasktype_info['tasktype_name'],1);
    list_cell("category",$category,1);
    list_cell("severity",$severity,1);
    list_cell("summary",$task_details['item_summary'],0,"?do=details&amp;id={$task_details['task_id']}");
    list_cell("dateopened",$date_opened);
    list_cell("status",$status,1);
    list_cell("openedby",$opened_by,0);
    list_cell("assigned",$assigned_to,0);
    list_cell("lastedit",$last_edited_time);
    list_cell("reportedin",$reported_in);
    list_cell("dueversion",$due,1);
    list_cell("comments",$comments);
    list_cell("attachments",$attachments);
    list_cell("progress","<img src=\"themes/{$project_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png\" width=\"45\" height=\"8\" alt=\"{$task_details['percent_complete']}% {$index_text['complete']}\" title=\"{$task_details['percent_complete']}% {$index_text['complete']}\">\n");
    
    // The end of this row
    echo "</tr>\n";
  };
  ?>
<!--</tbody>-->
</table>

<?php
// End of checking if the reqeusted project is active
};
?>
