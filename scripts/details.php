
<?php
// Time to try a super funky mysql query to get everything in one go
// Thanks to Lance Conry for this.  http://www.rhinosw.com/

require("lang/$lang/details.php");


$task_exists = $fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = '{$_GET['id']}'");

// Only load this page if a valid task was actually requested
 if ($fs->dbCountRows($task_exists)) {

$task_details = $fs->GetTaskDetails($_GET['id']);

$item_summary = str_replace("&", "&amp;", $task_details['item_summary']);
$item_summary = str_replace("<", "&lt;", $item_summary);
$item_summary = stripslashes($item_summary);

$detailed_desc = str_replace("&", "&amp;", $task_details['detailed_desc']);
$detailed_desc = str_replace("<br>", "\n", $detailed_desc);
$detailed_desc = stripslashes($detailed_desc);



// Check if the user is an admin
if ($_SESSION['can_modify_jobs'] == '1' && $task_details['item_status'] != '8' && $_GET['edit'] == 'yep') {
?>

<!--<h3 class="subheading">Edit Task</h3>-->

<!-- create some columns -->
<table class="admin" width="98%" cellspacing="0">
  <tr>
  <form name="form1" action="index.php" method="post">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <td class="severity<?php echo $task_details['task_severity'];?>" colspan="2" nowrap>
    <?php echo "{$details_text['task']} #{$_GET['id']}";?>&nbsp;&nbsp;
    <input class="severity<?php echo $task_details['task_severity'];?>" type="text" name="item_summary" size="50" maxlength="100" value="<?php echo $item_summary;?>">
    </td>
  </tr>
  <tr>
    <td align="left" class="admintext">
    <?php
    // Get the user details of the person who opened this item
    if ($task_details['opened_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['opened_by']}'");
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);
    } else {
      $user_name = "Anonymous Submitter";
      //$real_name = "Anonymous";
    };

    $date_opened = date("j M Y", $task_details['date_opened']);

    echo "{$details_text['openedby']} $real_name ($user_name) - $date_opened";


    // If it's been edited, get the details
    if ($task_details['last_edited_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['last_edited_by']}'");
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);

      $date_edited = date("j M Y", $task_details['last_edited_time']);

      echo "<br>{$details_text['editedby']} $real_name ($user_name) - $date_edited<br>";

    };
    ?>
    </td>
  </tr>
  <tr>
    <!-- left column -->
    <td align="left" width="50%" valign="top">

    <!-- content for left column -->
    <table>
      <tr>
        <td class="adminlabel"><?php echo $details_text['tasktype'];?></td>
        <td>
        <select class="adminlist" name="task_type">
        <?php
        // Get list of task types
        $get_severity = $fs->dbQuery("SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_severity)) {
          if ($row['tasktype_id'] == $task_details['task_type']) {
            echo "<option value=\"{$row['tasktype_id']}\" SELECTED>{$row['tasktype_name']}</option>";
          } else {
            echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $details_text['category'];?></td>
        <td>
        <select class="adminlist" name="product_category">
        <?php
        // Get list of categories
        $get_categories = $fs->dbQuery("SELECT category_id, category_name FROM flyspray_list_category WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_categories)) {
          if ($row['category_id'] == $task_details['product_category']) {
            echo "<option value=\"{$row['category_id']}\" SELECTED>{$row['category_name']}</option>";
          } else {
            echo "<option value=\"{$row['category_id']}\">{$row['category_name']}</option>";
          };
        };
        ?>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $details_text['status'];?></td>
        <td>
        <select class="adminlist" name="item_status">
        <?php
        // let's get a list of statuses and compare it to the saved one
        /*$get_statuses = $fs->dbQuery("SELECT status_id, status_name FROM flyspray_list_status WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_statuses)) {
          if ($row['status_id'] == $task_details['item_status']) {
            echo "<option value=\"{$row['status_id']}\" SELECTED>{$row['status_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['status_id']}\">{$row['status_name']}</option>\n";
          };
        };*/
        require("lang/$lang/status.php");
        foreach($status_list as $key => $val) {
          if ($task_details['item_status'] == $key) {
            echo "<option value=\"$key\" SELECTED>$val</option>\n";
          } else {
            echo "<option value=\"$key\">$val</option>\n";
          };
        };

        ?>
        </select></td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $details_text['assignedto'];?></td>
        <input type="hidden" name="old_assigned" value="<?php echo $task_details['assigned_to'];?>">
        <td>
        <select class="adminlist" name="assigned_to">
        <?php
        // Get list of users
        $get_users = $fs->dbQuery($fs->listUserQuery());

        // see if it's been assigned
        if ($task_details['assigned_to'] == "0") {
          echo "<option value=\"0\" SELECTED>{$details_text['noone']}</option>\n";
        } else {
          echo "<option value=\"0\">{$details_text['noone']}</option>\n";
        };
        while ($row = $fs->dbFetchArray($get_users)) {
          if ($row['user_id'] == $task_details['assigned_to']) {
            echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']} ({$row['user_name']})</option>\n";
          } else {
            echo "<option value=\"{$row['user_id']}\">{$row['real_name']} ({$row['user_name']})</option>\n";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $details_text['operatingsystem'];?></td>
        <td>
        <select class="adminlist" name="operating_system">
        <?php
        // Get list of operating systems
        $get_os = $fs->dbQuery("SELECT os_id, os_name FROM flyspray_list_os WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_os)) {
          if ($row['os_id'] == $task_details['operating_system']) {
            echo "<option value=\"{$row['os_id']}\" SELECTED>{$row['os_name']}</option>";
          } else {
            echo "<option value=\"{$row['os_id']}\">{$row['os_name']}</option>";
          };
        };
        ?>
        </select>
        </td>
      </tr>

    </table>

    <!-- end of left column and start of right column -->
    </td>
    <td align="left" width="50%" valign="top">
    <table>
      <tr>
        <td class="adminlabel"><?php echo $details_text['severity'];?></td>
        <td>
        <select class="adminlist" name="task_severity">
        <?php
        // Get list of severities
        /*$get_severity = $fs->dbQuery("SELECT severity_id, severity_name FROM flyspray_list_severity WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_severity)) {
          if ($row['severity_id'] == $task_details['task_severity']) {
            echo "<option value=\"{$row['severity_id']}\" SELECTED>{$row['severity_name']}</option>";
          } else {
            echo "<option value=\"{$row['severity_id']}\">{$row['severity_name']}</option>";
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
        <td class="adminlabel"><?php echo $details_text['reportedversion'];?></td>
        <td>
        <select class="adminlist" name="product_version">
        <?php
        // Get list of versions
        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_version)) {
          if ($row['version_id'] == $task_details['product_version']) {
            echo "<option value=\"{$row['version_id']}\" SELECTED>{$row['version_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $details_text['dueinversion'];?></td>
        <td>
        <select class="adminlist" name="closedby_version">
        <?php
        // if we don't have a fix-it version, show undecided
        if (!isset($closedby)) {
          echo "<option value=\"\">{$details_text['undecided']}</option>\n";
        } else {
          echo "<option value=\"\" SELECTED>{$details_text['undecided']}</option>\n";
        };
        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_version)) {
          if ($row['version_id'] == $task_details['closedby_version']) {
            echo "<option value=\"{$row['version_id']}\" SELECTED>{$row['version_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <!--<tr>
        <td class="adminlabel">Related Task:</td>
        <td>
        <input class="admintext" type="text" name="related_task_id" size="10" maxlength="10" value="<?php if ($task_details['related_task_id'] != "0") { echo $task_details['related_task_id'];};?>">
        </td>
      </tr>-->
      <tr>
        <td class="adminlabel"><?php echo $details_text['percentcomplete'];?></td>
        <td>
        <select class="adminlist" name="percent_complete">
          <option value="0" <?php if ($task_details['percent_complete'] == '0') { echo "SELECTED";};?>>0%</option>
          <option value="10" <?php if ($task_details['percent_complete'] == '10') { echo "SELECTED";};?>>10%</option>
          <option value="20" <?php if ($task_details['percent_complete'] == '20') { echo "SELECTED";};?>>20%</option>
          <option value="30" <?php if ($task_details['percent_complete'] == '30') { echo "SELECTED";};?>>30%</option>
          <option value="40" <?php if ($task_details['percent_complete'] == '40') { echo "SELECTED";};?>>40%</option>
          <option value="50" <?php if ($task_details['percent_complete'] == '50') { echo "SELECTED";};?>>50%</option>
          <option value="60" <?php if ($task_details['percent_complete'] == '60') { echo "SELECTED";};?>>60%</option>
          <option value="70" <?php if ($task_details['percent_complete'] == '70') { echo "SELECTED";};?>>70%</option>
          <option value="80" <?php if ($task_details['percent_complete'] == '80') { echo "SELECTED";};?>>80%</option>
          <option value="90" <?php if ($task_details['percent_complete'] == '90') { echo "SELECTED";};?>>90%</option>
          <option value="100" <?php if ($task_details['percent_complete'] == '100') { echo "SELECTED";};?>>100%</option>
        </select>
        </td>
      </tr>
    </table>
    <!-- end of right column -->
    </td>
  </tr>
  <tr>
    <td align="left" colspan="2">
    <table>
      <tr>
        <td align="left" class="adminlabel" valign="top"><?php echo $details_text['details'];?></td>
        <td>
        <?php
        ?>
        <textarea class="admintext" name="detailed_desc" cols="70" rows="10"><?php echo $detailed_desc;?></textarea>
        </td>
      </tr>
    </table>
    </td>
  </tr>
  <tr>
    <td align="right">
    <input class="adminbutton" type="submit"  name="buSubmit" value="<?php echo $details_text['savedetails'];?>" onclick="Disable1()">&nbsp;&nbsp;
    </td>
    </form>

    <form action="index.php" method="get">
    <td>
      <input type="hidden" name="do" value="details">
      <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
      <input class="adminbutton" type="submit" value="<?php echo $details_text['canceledit'];?>">
    </td>
    </form>
  </tr>
</table>
<br><br>



<?php
//
} elseif ($_SESSION['can_modify_jobs'] != '1' OR $task_details['item_status'] == '8' OR !$GET['edit']) {
// ####################################################################################
// ####################################################################################
// If the user isn't an admin OR if the task is in VIEW mode, or if the job is closed


?>

<!--<h3 class="subheading">View Task</h3>-->

<!-- create some columns -->
<table class="main" width="98%" cellspacing="0">
  <tr>
    <?php if ($_SESSION['can_modify_jobs'] != '1' OR $task_details['item_status'] == '8') { ?>
    <td class="severity<?php echo $task_details['task_severity'];?>" colspan="2">
    <?php } else { ?>
    <td class="severity<?php echo $task_details['task_severity'];?>">
    <?php }; ?>
    <?php echo "{$details_text['task']} #{$_GET['id']} &nbsp;&nbsp; $item_summary";?>
    </td>
    <?php
    if ($_SESSION['can_modify_jobs'] == '1' && $task_details['item_status'] != '8') {
    ?>
    <form action="index.php" method="get">
    <td align="right" width="20%" class="severity<?php echo $task_details['task_severity'];?>">
      <input type="hidden" name="do" value="details">
      <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
      <input type="hidden" name="edit" value="yep">
      <input class="adminbutton" type="submit" value="<?php echo $details_text['edittask'];?>">
    </td>
    </form>
    <?php };?>
  </tr>
  <tr>
    <td class="fineprint" colspan="2">
    <?php
    // Get the user details of the person who opened this item
    if ($task_details['opened_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['opened_by']}'");
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);
    } else {
      $user_name = $details_text['anonymous'];
      //$real_name = "Anonymous";
    };

    $date_opened = $task_details['date_opened'];
    $date_opened = date("j M Y", $date_opened);

    echo "{$details_text['openedby']} $real_name ($user_name) - $date_opened";

    // If it's been edited, get the details
    if ($task_details['last_edited_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['last_edited_by']}'");
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);

      $date_edited = $task_details['last_edited_time'];
      $date_edited = date("j M Y", $date_edited);

      echo "<br>{$details_text['editedby']} $real_name ($user_name) - $date_edited<br>";

    };
    ?>
    </td>
  </tr>
  <tr>
    <!-- left column -->
    <td width="50%" valign="top">

    <!-- content for left column -->
    <table>
      <tr>
        <td class="mainlabel"><?php echo $details_text['tasktype'];?></td>
        <td class="maintext"><?php echo $task_details['tasktype_name'];?></td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['category'];?></td>
        <td class="maintext"><?php echo $task_details['category_name'];?></td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['status'];?></td>
        <td class="maintext"><?php echo $task_details['status_name'];?></td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['assignedto'];?></td>
        <td class="maintext">
        <?php
        // see if it's been assigned
        if (!$task_details['assigned_to']) {
          echo $details_text['noone'];
        } else {
          // find out the username
          $getusername = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['assigned_to']}'");
          list ($user_name, $real_name) = $fs->dbFetchArray($getusername);
          echo "$real_name ($user_name)";
        };
        ?>
        </td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['operatingsystem'];?></td>
        <td class="maintext"><?php echo $task_details['os_name'];?></td>
      </tr>

    </table>

    <!-- end of left column and start of right column -->
    </td>
    <td width="50%" valign="top">
    <table>
      <tr>
        <td class="mainlabel"><?php echo $details_text['severity'];?></td>
        <td class="maintext"><?php echo $task_details['severity_name'];?></td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['reportedversion'];?></td>
        <td class="maintext"><?php echo $task_details['reported_version_name'];?></td>
      </tr>
      <tr>
        <td class="mainlabel"><?php echo $details_text['dueinversion'];?></td>
        <td class="maintext">
        <?php
        if (isset($task_details['due_in_version_name'])) {
          echo $task_details['due_in_version_name'];
        } else {
          echo $details_text['undecided'];
        };
        ?>
        </td>
      </tr>
      <!--<tr>
        <td class="mainlabel">Related Task:</td>
        <td class="maintext">
        <?php /*
        if ($task_details['related_task_id'] != "0") {
          echo "<a href=\"?do=details&amp;id={$task_details['related_task_id']}\">{$task_details['related_task_id']}</a>";
        } else {
          echo "None";
        };*/
        ?>
        </td>
      </tr>-->
      <tr>
        <td class="mainlabel"><?php echo $details_text['percentcomplete'];?></td>
        <td class="maintext"><?php echo "<img src=\"themes/{$flyspray_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png\" width=\"150\" height=\"10\" alt=\"{$task_details['percent_complete']}% {$details_text['complete']}\" title=\"{$task_details['percent_complete']}% {$details_text['complete']}\"";?></td>
      </tr>
    </table>
    <!-- end of right column -->
    </td>
  </tr>
  <tr>
    <td colspan="2">
    <br>
    <table>
      <tr>
        <td class="mainlabel" valign="top"><?php echo $details_text['details'];?></td>
        <td class="maintext">
        <?php
        $detailed_desc = str_replace("&", "&amp;", $task_details['detailed_desc']);
        $detailed_desc = str_replace("<", "&lt;", "$detailed_desc");
        $detailed_desc = str_replace("\n", "<br>", $detailed_desc);
        $detailed_desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\">\\0</a>", $detailed_desc);
        echo stripslashes($detailed_desc);
        ?>
        </td>
      </tr>
    </table>
    </td>
  </tr>
  <tr>
  <td colspan="2">
  <br>

  <?php
  if ($task_details['item_status'] == '8') {
  ?>
  <table width="100%">
    <tr>
      <td class="admintext">
      <?php
      $get_closedby_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$task_details['closed_by']}'");
      list($closedby_username, $closedby_realname) = $fs->dbFetchArray($get_closedby_name);
      $date_closed = $task_details['date_closed'];
      $date_closed = date("j M Y", $date_closed);
      echo "{$details_text['closedby']} $closedby_realname ($closedby_username) on $date_closed.";
      ?>
      <br>
      <?php echo $details_text['reasonforclosing'];?>&nbsp;&nbsp;
      <?php echo $task_details['resolution_name'];?>
      </td>
    </tr>
  </table>
    <?php
    };
    if ($_SESSION['can_modify_jobs'] == '1' && $task_details['item_status'] == '8') { ?>
  <form name="form2" action="index.php" method="post">
  <table>
    <tr>
      <td align="right" colspan="5">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="reopen">
      <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $details_text['reopenthistask'];?>" onclick="Disable2()">
      </td>
    </tr>
  </table>
  </form>
    <?php
    } elseif ($_SESSION['can_modify_jobs'] == '1' && $task_details['item_status'] != '8') {
    ?>
    <form name="form2" action="index.php" method="post">
    <table>
      <tr>
        <td class="adminlabel" align="right" colspan="3" nowrap><?php echo $details_text['closetask'];?>&nbsp;
        <input type="hidden" name="do" value="modify">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="assigned_to" value="<?php echo $task_details['assigned_to'];?>">
        <input type="hidden" name="item_summary" value="<?php echo $task_details['item_summary'];?>">
        <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
        <select class="adminlist" name="resolution_reason">
        <?php
        $get_resolution = $fs->dbQuery("SELECT resolution_id, resolution_name FROM flyspray_list_resolution ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_resolution)) {
          if ($row['resolution_id'] == $task_details['resolution_reason']) {
            echo "<option value=\"{$row['resolution_id']}\" SELECTED>{$row['resolution_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['resolution_id']}\">{$row['resolution_name']}</option>\n";
          };
        };
        ?>
        </select>
        <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $details_text['closetask'];?>" onclick="Disable2()">
        </td>
      </tr>
    </table>
    </form>

    <?php
    };
    ?>

  </td>
  </tr>
</table>
<br>
<?php
// End of checking if a job should be editable
};
?>


<?php
if ($_GET['area']) {
  $area = $_GET['area'];
} else {
  $area = 'comments';
};
$num_comments = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_comments WHERE task_id = '{$task_details['task_id']}'"));
$num_attachments = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_attachments WHERE task_id = '{$task_details['task_id']}'"));
$num_related = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_related WHERE this_task = '{$task_details['task_id']}'"));
$num_notifications = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_notifications WHERE task_id = '{$_GET['id']}'"));

?>

<table cellspacing="0" cellpadding="0">
  <tr>
    <td>
    <a name="tabs"></a>
    <?php if ($area == 'comments') {
      echo "<table class=\"tabactive\">";
    } else {
      echo "<table class=\"tabnotactive\">";
    };
    ?>
      <tr>
      <td class="admintext" style="border:1px;"><a href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=comments#tabs"><?php echo "{$details_text['comments']} ($num_comments)";?></a></td>
      </tr>
    </table>
    </td>
    <td>
    <?php if ($area == 'attachments') {
      echo "<table class=\"tabactive\">";
    } else {
      echo "<table class=\"tabnotactive\">";
    };
    ?>
      <tr>
      <td class="admintext" style="border:1px;"><a href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=attachments#tabs"><?php echo "{$details_text['attachments']} ($num_attachments)";?></a></td>
      </tr>
    </table>
    </td>
    <td>
    <?php if ($area == 'related') {
      echo "<table class=\"tabactive\">";
    } else {
      echo "<table class=\"tabnotactive\">";
    };
    ?>
      <tr>
      <td class="admintext" style="border:1px;"><a href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=related#tabs"><?php echo "{$details_text['relatedtasks']} ($num_related)";?></a></td>
      </tr>
    </table>
    </td>
    <td>
    <?php if ($area == 'notify') {
      echo "<table class=\"tabactive\">";
    } else {
      echo "<table class=\"tabnotactive\">";
    };
    ?>
      <tr>
      <td class="admintext" style="border:1px;"><a href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=notify#tabs"><?php echo "{$details_text['notifications']} ($num_notifications)";?></a></td>
      </tr>
    </table>
    </td>
  </tr>
</table>

<?php
// Start of comments area
if ($area == 'comments') { ?>
<table class="tabarea" width="98%">
  <tr>
    <td>
    <?php
    // if there are comments, show them
    $getcomments = $fs->dbQuery("SELECT * FROM flyspray_comments WHERE task_id = '{$task_details['task_id']}'");
    while ($row = $fs->dbFetchArray($getcomments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = '{$row['user_id']}'");
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = date("l, j M Y, g:ia", $row['date_added']);

      $comment_text = str_replace("&", "&amp;", "{$row['comment_text']}");
      $comment_text = str_replace("<", "&lt;", "$comment_text");
      $comment_text = str_replace("\n", "<br>", "$comment_text");
      $comment_text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\">\\0</a>", $comment_text);
      $comment_text = stripslashes($comment_text);

    ?>
    <table width="100%" border="0">
      <tr>
        <td class="mainlabel" colspan="2"><?php echo "{$details_text['commentby']} $user_name - $formatted_date";?></td>
      </tr>
      <tr>
        <td class="maintext"><?php echo $comment_text;?><br><br></td>
        <?php
        // If the user is an admin, show the edit button
        if ($_SESSION['admin'] == '1') { ?>
        <form action="index.php" method="get">
        <td align="right" valign="top">
          <input type="hidden" name="do" value="admin">
          <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
          <input type="hidden" name="area" value="editcomment">
          <input type="hidden" name="id" value="<?php echo $row['comment_id'];?>">
          <input class="adminbutton" type="submit" value="<?php echo $details_text['edit'];?>">

        </form>
        <form action="index.php" method="post"
        onSubmit="
        if(confirm('Really delete this comment?')) {
          return true
        } else {
          return false }
        ">

          <input type="hidden" name="do" value="modify">
          <input type="hidden" name="action" value="deletecomment">
          <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
          <input type="hidden" name="comment_id" value="<?php echo $row['comment_id'];?>">
          <input class="adminbutton" type="submit" value="<?php echo $details_text['delete'];?>">
        </td>
        </form>
        <?php }; ?>
      </tr>
    </table>


    <?php
    };
    echo "</td></tr></table>";

// Now, show a form to add a comment (but only if the user has the rights!)
echo "<br>";

if ($_SESSION['can_add_comments'] == "1" && $task_details['item_status'] != '8') {
?>

<table class="admin" width="98%">
  <tr>
  <form action="index.php" method="post">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="addcomment">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <td  class="adminlabel" align="right" valign="top"><?php echo $details_text['addcomment'];?></td>
    <td><textarea class="admintext" name="comment_text" cols="50" rows="10"></textarea></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $details_text['addcomment'];?>"></td>
  </form>
  </tr>
</table>

<?php
// End of checking if the comments form should be displayed
};

// End of comments area

// Start of file attachments area
} elseif ($area == 'attachments') {
?>
<table class="tabarea" width="98%">
  <tr>
    <td>
    <?php
    // if there are attachments, show them
    $getattachments = $fs->dbQuery("SELECT * FROM flyspray_attachments WHERE task_id = '{$task_details['task_id']}'");
    while ($row = $fs->dbFetchArray($getattachments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = '{$row['added_by']}'");
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = date("l, j M Y, g:ia", $row['date_added']);

      $file_desc = stripslashes($row['file_desc']);

    ?>
    <table width="100%">
      <tr>
        <td class="mainlabel"><?php echo "{$details_text['fileuploadedby']} $user_name - $formatted_date";?></td>
      </tr>
      <tr>
        <td class="maintext">
        <?php echo "<a href=\"?getfile={$row['attachment_id']}\">{$row['orig_name']} - $file_desc</a>";?>
        <br><br>
        </td>
      </tr>
    </table>


<?php
};
echo "</td></tr></table>";
//};
// Now, show a form to attach a file (but only if the user has the rights!)
echo "<br>";

if ($_SESSION['can_attach_files'] == "1" && $task_details['item_status'] != '8') {
?>

<table class="admin" width="98%">
  <tr>
  <form enctype="multipart/form-data" action="index.php" method="post">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="addattachment">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <td align="right" class="adminlabel"><?php echo $details_text['uploadafile'];?></td>
    <td align="left"><input class="admintext" type="file" size="35" name="userfile"></td>
  </tr>
  <tr>

    <td align="right" class="adminlabel"><?php echo $details_text['description'];?></td>
    <td align="left"><input class="admintext" type="text" name="file_desc" size="50" maxlength="100"></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $details_text['uploadnow'];?>"></td>
  </form>
  </tr>
</table>

<?php
// End of admin checking
};
// End of attachments area

// Start of related tasks area
} elseif ($area == 'related') { ?>
<table class="tabarea" width="98%">
  <tr>
    <td class="mainlabel" colspan="2" valign="top"><?php echo $details_text['thesearerelated'];?></td>
  </tr>

    <?php
    $get_related = $fs->dbQuery("SELECT * FROM flyspray_related WHERE this_task = '{$_GET['id']}'");
    while ($row = $fs->dbFetchArray($get_related)) {
      $get_summary = $fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = '{$row['related_task']}'");
      while ($subrow = $fs->dbFetchArray($get_summary)) {
        $summary = stripslashes($subrow['item_summary']);
        echo "<tr><td class=\"maintext\"><a href=\"{$flyspray_prefs['base_url']}?do=details&amp;id={$row['related_task']}\">#{$row['related_task']}&nbsp;-&nbsp;$summary</a></td>";

        // If the user can modify jobs, then show them a form to remove related tasks
        if ($_SESSION['can_modify_jobs'] == '1' && $task_details['item_status'] != '8') {
          ?>
          <form action="index.php" method="post">
          <td class="admintext">
            <input type="hidden" name="do" value="modify">
            <input type="hidden" name="action" value="remove_related">
            <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
            <input type="hidden" name="related_id" value="<?php echo $row['related_id'];?>">
            <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
          </td>
          </form>
          

        <?php
        };
        echo "</tr>";
      };
    };
    if ($_SESSION['can_modify_jobs'] == "1" && $task_details['item_status'] != '8') {
    ?>
      <tr>
        <td height="10"></td>
      </tr>
      <tr>
        <td colspan="5">
        <table>
        <form action="index.php" method="post">
        <input type="hidden" name="do" value="modify">
        <input type="hidden" name="action" value="add_related">
        <input type="hidden" name="this_task" value="<?php echo $_GET['id'];?>">
        <td class="adminlabel" align="right" valign="top"><?php echo $details_text['addnewrelated'];?></td>
        <td><input class="admintext" name="related_task" size="10" maxlength="10"></td>
        <td colspan="2" align="left"><input class="adminbutton" type="submit" value="<?php echo $details_text['add'];?>"></td>
        </form>
        </table>
        </td>
      </tr>
    <?php
    };
    ?>
    </table>

    <br>

    <table class="main" width="98%"><tr><td class="mainlabel" valign="top"><?php echo $details_text['otherrelated'];?></td></tr><tr><td class="maintext">
    <?php
    $get_related = $fs->dbQuery("SELECT * FROM flyspray_related WHERE related_task = '{$_GET['id']}'");
    while ($row = $fs->dbFetchArray($get_related)) {
      $get_summary = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = '{$row['this_task']}'");
      while ($subrow = $fs->dbFetchArray($get_summary)) {
        $summary = stripslashes($subrow['item_summary']);
        echo "<a href=\"{$flyspray_prefs['base_url']}?do=details&amp;id={$row['this_task']}\">#{$row['this_task']}&nbsp;-&nbsp;$summary</a><br>";
      };
    };
    echo "<br></td></tr></table>";

// End of related area

// Start of notifications area
} elseif ($area == 'notify') { ?>
<table class="tabarea" width="98%">
  <tr>
    <td class="mainlabel" colspan="2"><?php echo $details_text['theseusersnotify'];?></td>


    <?php
    $get_user_ids = $fs->dbQuery("SELECT * FROM flyspray_notifications WHERE task_id = '{$_GET['id']}'");
    while ($row = $fs->dbFetchArray($get_user_ids)) {
      $get_user = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = '{$row['user_id']}'");
      while ($subrow = $fs->dbFetchArray($get_user)) {
        echo "<tr><td class=\"maintext\">{$subrow['real_name']} ({$subrow['user_name']})</td>";

        // If the user can modify jobs, then show them a form to remove related tasks
        if ($_SESSION['admin'] == '1' && $task_details['item_status'] != '8') {
          ?>
          <form action="index.php" method="post">
              <td class="admintext">
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="remove_notification">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="user_id" value="<?php echo $row['user_id'];?>">
                <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
              </td>
          </form>

        <?php
        };
      echo "</tr>";
      };
    };
    if ($task_details['item_status'] != '8') {
    ?>
      <tr>
        <td height="10"></td>
      </tr>
      <tr>
        <td colspan="2">
        <?php if ($_SESSION['admin'] == '1') { ?>
        <table>
          <tr>
            <td class="mainlabel"><?php echo $details_text['addusertolist'];?></td>
            <form action="index.php" method="post">
            <input type="hidden" name="do" value="modify">
            <input type="hidden" name="action" value="add_notification">
            <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
            <td>
            <select class="adminlist" name="user_id">
            <?php
            // Get list of users
            $get_users = $fs->dbQuery($fs->listUserQuery());
            while ($row = $fs->dbFetchArray($get_users)) {
              echo "<option value=\"{$row['user_id']}\">{$row['real_name']} ({$row['user_name']})</option>\n";
            };
            ?>
            </select>
            </td>
            <td><input class="adminbutton" type="submit" value="<?php echo $details_text['addtolist'];?>"></td>
          </tr>
        </table>
        </form>
          
          <?php
          }; 
          if ($_SESSION['userid']) {
            $result = $fs->dbQuery("SELECT * FROM flyspray_notifications
                  WHERE task_id = '{$_GET['id']}'
                  AND user_id = '{$_SESSION['userid']}'
                  ");
            if (!$fs->dbCountRows($result)) {
            ?>
            </td><td>
            <table>
              <tr>
                <form action="index.php" method="post">
                <td width="30"></td>
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="add_notification">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'];?>">
                <td align="center"><input class="adminbutton" type="submit" value="<?php echo $details_text['addmyself'];?>"></td>
            </form>
            </tr>
            </table>
            <?php } else { ?>
            </td><td>
            <table>
              <tr>
              <form action="index.php" method="post">
                <td class="admintext">
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="remove_notification">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'];?>">
                <input class="adminbutton" type="submit" value="<?php echo $details_text['removemyself'];?>">
                </td>
              </form>
              </tr>
            </table>
            <?php
            };
          };?>
        </td>
      </tr>
    <?php
    };
    echo "</table>";

// End of notifications area

// End of tabbed areas
};
?>

<?php
} else {
// If no task was actually requested, show an error
echo "<table><tr><td class=\"maintext\">{$details_text['invalidtaskid']}</td></tr></table><br>";

};
?>
