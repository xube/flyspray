<?php
// set a whole bunch of DEFAULT variables if they're not already set
//  this is a whole convoluted bunch of crap, but it works.

require("lang/$lang/index.php");

switch ($_GET['order']) {
  case "id": $orderby = 'task_id';
  break;
  case "date": $orderby = 'date_opened';
  break;
  case "sev": $orderby = 'task_severity';
  break;
  case "cat": $orderby = 'product_category';
  break;
  case "status": $orderby = 'item_status';
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

// Set the default queries

// developer whos bugs to show
if (is_numeric($_GET['dev'])) {
  $dev_sql = "AND assigned_to = '{$_GET['dev']}'";
} else {
  $dev_sql = "";
};

// The default task type
if (is_numeric($_GET['type'])) {
  $type_sql = "AND task_type = '{$_GET['type']}'";
} else {
  $type_sql = "";
};

// The default severity
if (is_numeric($_GET['sev'])) {
  $sev_sql = "AND task_severity = '{$_GET['sev']}'";
} else {
  $sev_sql = "";
};
// The default category
if (is_numeric($_GET['cat'])) {
  $cat_sql = "AND product_category = '{$_GET['cat']}'";
} else {
  $cat_sql = "";
};
// The default status
if ($_GET['status'] == "all") {
  $status_sql = "";
} elseif (is_numeric($_GET['status'])) {
  $status_sql = "AND item_status = '{$_GET['status']}'";
} else {
  $status_sql = "AND item_status != '8'";
};
// The default search string
if ($_GET['string']) {
  $string = $_GET['string'];
  $string = ereg_replace('\(', " ", $string);
  $string = ereg_replace('\)', " ", $string);
  $string = trim($string);

  $string_sql = "AND item_summary LIKE '%$string%' OR detailed_desc LIKE '%$string%' OR task_id LIKE '%$string%'";
} else {
  $string_sql = "";
};

?>

<!-- Query line -->
<form action="index.php" method="get">
<table class="main" width="100%">
  <tr>
    <td class="mainlabel"><?php echo $index_text['search'];?>:</td>
    <td align="left">

    <input class="maintext" name="string" type="text" size="40" maxlength="100" value="<?php echo $_GET['string'];?>">

    <select class="mainlist" name="type">
      <option value=""><?php echo $index_text['alltasktypes'];?></option>
      <?php
      $severity_list = $fs->dbQuery("SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype WHERE show_in_list = '1' ORDER BY list_position");
      while ($row = $fs->dbFetchArray($severity_list)) {
        if ($_GET['type'] == $row['tasktype_id']) {
          echo "<option value=\"{$row['tasktype_id']}\" SELECTED>{$row['tasktype_name']}</option>\n";
        } else {
          echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>\n";
        };
      };
      ?>
    </select>

    <select class="mainlist" name="sev">
      <option value=""><?php echo $index_text['allseverities'];?></option>
      <?php
      /*$severity_list = $fs->dbQuery("SELECT severity_id, severity_name FROM flyspray_list_severity WHERE show_in_list = '1' ORDER BY list_position");
      while ($row = $fs->dbFetchArray($severity_list)) {
        if ($_GET['sev'] == $row['severity_id']) {
          echo "<option value=\"{$row['severity_id']}\" SELECTED>{$row['severity_name']}</option>\n";
        } else {
          echo "<option value=\"{$row['severity_id']}\">{$row['severity_name']}</option>\n";
        };
      };*/
      require("lang/$lang/severity.php");
      foreach($severity_list as $key => $val) {
        if ($task_details['task_severity'] == $key) {
          echo "<option value=\"$key\" SELECTED>$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
    </select>
    </td>

  </tr>

  <tr>
    <td></td>
    <td>
    <select class="mainlist" name="dev">
      <option value=""><?php echo $index_text['alldevelopers'];?></option>
      <option value="notassigned" <?php if ($_GET['dev'] == "notassigned") { echo "SELECTED";};?>><?php echo $index_text['notyetassigned'];?></option>
      <?php
      $dev_list = $fs->dbQuery($fs->listUserQuery());
      while ($row = $fs->dbFetchArray($dev_list)) {
        if ($_GET['dev'] == $row['user_id']) {
          echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']}</option>";
        } else {
          echo "<option value=\"{$row['user_id']}\">{$row['real_name']}</option>";
        };
      };
      ?>
    </select>

    <select class="mainlist" name="cat">
      <option value=""><?php echo $index_text['allcategories'];?></option>
      <?php
      $cat_list = $fs->dbQuery("SELECT category_id, category_name FROM flyspray_list_category WHERE show_in_list = '1' ORDER BY list_position");
      while ($row = $fs->dbFetchArray($cat_list)) {
        if ($_GET['cat'] == $row['category_id']) {
          echo "<option value=\"{$row['category_id']}\" SELECTED>{$row['category_name']}</option>";
        } else {
          echo "<option value=\"{$row['category_id']}\">{$row['category_name']}</option>";
        };
      };
      ?>
    </select>

    <select class="mainlist" name="status">
      <option value="all" <?php if ($_GET['status'] == "all") { echo "SELECTED";};?>><?php echo $index_text['allstatuses'];?></option>
      <option value="" <?php if ($_GET['status'] == "") { echo "SELECTED";};?>><?php echo $index_text['allopentasks'];?></option>
      <?php
      /*$status_list = $fs->dbQuery("SELECT status_id, status_name FROM flyspray_list_status WHERE show_in_list = '1' ORDER BY list_position");
      while ($row = $fs->dbFetchArray($status_list)) {
        if ($_GET['status'] == $row['status_id']) {
          echo "<option value=\"{$row['status_id']}\" SELECTED>{$row['status_name']}</option>";
        } else {
          echo "<option value=\"{$row['status_id']}\">{$row['status_name']}</option>";
        };
      };*/
      require("lang/$lang/status.php");
      foreach($status_list as $key => $val) {
        if ($_GET['status'] == $key) {
          echo "<option value=\"$key\" SELECTED>$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
    </select>

    <select class="mainlist" name="perpage">
      <option value="10" <?php if ($perpage == "10") { echo "SELECTED";};?>>10</option>
      <option value="20" <?php if ($perpage == "20") { echo "SELECTED";};?>>20</option>
      <option value="30" <?php if ($perpage == "30") { echo "SELECTED";};?>>30</option>
      <option value="40" <?php if ($perpage == "40") { echo "SELECTED";};?>>40</option>
      <option value="50" <?php if ($perpage == "50") { echo "SELECTED";};?>>50</option>
      <option value="75" <?php if ($perpage == "75") { echo "SELECTED";};?>>75</option>
      <option value="100" <?php if ($perpage == "100") { echo "SELECTED";};?>>100</option>
    </select>

    <input class="mainbutton" type="submit" value="<?php echo $index_text['search'];?>">
    </td>
  </tr>
</table>
</form>



<!--  Summary headings, followed by the query results -->
<table class="main" cellpadding="5" cellspacing="0" width="100%">
  <tr>
  <td class="mainlabel" align="center">
  <a title="<?php echo $index_text['sortthiscolumn'];?>" href="?order=id<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "id") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['id'];?></a></td>
  <td class="mainlabel" align="left">
  <a title="<?php echo $index_text['sortthiscolumn'];?>" href="?order=type<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "sev") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['tasktype'];?></a></td>
  <td class="mainlabel" align="left">
  <a title="<?php echo $index_text['sortthiscolumn'];?>" href="?order=sev<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "sev") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['severity'];?></a></td>
  <td class="mainlabel" align="left"><?php echo $index_text['summary'];?></td>
  <td class="mainlabel" align="center">
  <a title="<?php echo $index_text['sortthiscolumn'];?>" href="?order=date<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "date") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['dateopened'];?></a></td>
  <!--<td class="mainlabel" align="left">
  <a title="Sort by this column"  href="?order=cat<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "cat") { echo "asc"; } else { echo "desc";};?>">Category</a></td>
  --><td class="mainlabel" align="left">
  <a title="<?php echo $index_text['sortthiscolumn'];?>"  href="?order=status<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "status") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['status'];?></a></td>
  <td class="mainlabel" align="left">
  <a title="<?php echo $index_text['sortthiscolumn'];?>"  href="?order=prog<?php echo "&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage&amp;pagenum=$pagenum";?>&amp;sort=<?php if ($_GET['sort'] == "desc" && $_GET['order'] == "prog") { echo "asc"; } else { echo "desc";};?>"><?php echo $index_text['progress'];?></a></td>

  </tr>
  <?php

  $getsummary = $fs->dbQuery("SELECT * FROM flyspray_tasks
          WHERE task_id != ''
          $type_sql
          $dev_sql
          $sev_sql
          $cat_sql
          $status_sql
          $string_sql
          ORDER BY $orderby $sort
          LIMIT $offset,$perpage");


  while ($task_details = $fs->dbFetchArray($getsummary)) {

    // Get the full tasktype name
    $get_tasktype_name = $fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id = '{$task_details['task_type']}'");
    $tasktype_info = $fs->dbFetchArray($get_tasktype_name);

    // Get the full category name
    $get_category_name = $fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id = '{$task_details['product_category']}'");
    list($category) = $fs->dbFetchArray($get_category_name);

    // Get the full status name
    $status_id = $task_details['item_status'];
    require("lang/$lang/status.php");
    $status = $status_list[$status_id];

    // Get the full severity name
    $severity_id = $task_details['task_severity'];
    require("lang/$lang/severity.php");
    $severity = $severity_list[$severity_id];

    echo "<tr class=\"severity{$task_details['task_severity']}\"
    onclick='openTask(\"?do=details&amp;id={$task_details['task_id']}\")'
    onmouseover=\"this.className = 'severity{$task_details['task_severity']}_over';
    this.style.cursor = 'hand'\"
    onmouseout=\"this.className = 'severity{$task_details['task_severity']}';
    this.style.cursor = 'default'\">\n";


    echo "<td class=\"maintext\" align=\"center\">\n";
    echo "<a href=\"?do=details&amp;id={$task_details['task_id']}\">{$task_details['task_id']}</a>\n</td>\n";

    $tasktype_info = str_replace(" ", "&nbsp;", $tasktype_info['tasktype_name']);
    echo "<td class=\"maintext\" align=\"left\">$tasktype_info\n";
    echo "\n</td>\n";

    $severity = str_replace(" ", "&nbsp;", $severity);
    echo "<td class=\"maintext\" align=\"left\">$severity\n";
    echo "\n</td>\n";

    echo "<td class=\"maintext\" align=\"left\">\n";
    $item_summary = stripslashes($task_details['item_summary']);
    echo "<a href=\"?do=details&amp;id={$task_details['task_id']}\">$item_summary</a>\n</td>\n";

    $date_opened = $task_details['date_opened'];
    $date_opened = date("Y-m-j", $date_opened);
    echo "<td class=\"maintext\" align=\"center\">\n";
    echo "$date_opened\n</td>\n";

    $status = str_replace(" ", "&nbsp;", $status);
    echo "<td class=\"maintext\" align=\"left\">\n";
    echo "$status\n</td>\n";

    echo "<td class=\"maintext\" align=\"left\">\n";
    echo "<img src=\"themes/{$flyspray_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png\" width=\"45\" height=\"8\" alt=\"{$task_details['percent_complete']}% {$index_text['complete']}\" title=\"{$task_details['percent_complete']}% {$index_text['complete']}\" border=\"0\">\n</td>\n";

    echo "</tr>\n";
  };
  ?>
</table>


<br>

<div align="center" class="admintext">
<?php

$get_total =  $fs->dbQuery("SELECT * FROM flyspray_tasks
          WHERE task_id != ''
          $type_sql
          $dev_sql
          $sev_sql
          $cat_sql
          $status_sql
          $string_sql
          ORDER BY $orderby $sort");

$total = $fs->dbCountRows($get_total);
$extraurl = "&amp;order={$_GET['order']}&amp;sort={$_GET['sort']}&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;string={$_GET['string']}&amp;perpage=$perpage";
print $fs->pagenums($pagenum, $perpage, "6", $total, $extraurl);


?>

</div>
