<?php
get_language_pack($lang, 'details');

$task_exists = $fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($_GET['id']));
$task_details = $fs->GetTaskDetails($_GET['id']);

// Only load this page if a valid task was actually requested
 if ($fs->dbCountRows($task_exists) && $task_details['project_is_active'] == '1') {

$item_summary = htmlentities($task_details['item_summary']);
$item_summary = stripslashes($item_summary);

$detailed_desc = htmlentities($task_details['detailed_desc']);
$detailed_desc = stripslashes($detailed_desc);

// Check if the user has rights to modify tasks
if (($_SESSION['can_modify_jobs'] == '1'
  OR $task_details['assigned_to'] == $_SESSION['userid'])
  && $task_details['is_closed'] != '1'
  && $_GET['edit'] == 'yep') {

///////////////////////////////////
// If the user can modify tasks, //
// and the task is still open,   //
// and we're in edit mode,       //
// then use this section.        //
///////////////////////////////////
?>

<!-- create some columns -->
<div id="taskdetails">
<form name="form1" action="index.php" method="post">
  <h2 class="severity<?php echo $task_details['task_severity'];?>">
    <?php echo "{$details_text['task']} #{$_GET['id']}";?> &mdash;
    <input class="severity<?php echo $task_details['task_severity'];?>" type="text" name="item_summary" size="50" maxlength="100" value="<?php echo $item_summary;?>">
  <input type="hidden" name="do" value="modify">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
  </h2>

  <?php echo $details_text['attachedtoproject'] . " &mdash; ";?>
  <select name="attached_to_project">
  <?php
  $get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects");
  while ($row = $fs->dbFetchArray($get_projects)) {
    if ($task_details['attached_to_project'] == $row['project_id']) {
      echo "<option value=\"{$row['project_id']}\" SELECTED>{$row['project_title']}</option>";
    } else {
      echo "<option value=\"{$row['project_id']}\">{$row['project_title']}</option>";
    };
  };
  ?>
  </select>

  <p class="fineprint">
    <?php
    // Get the user details of the person who opened this item
    if ($task_details['opened_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['opened_by']));
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);
    } else {
      $user_name = "Anonymous Submitter";
      //$real_name = "Anonymous";
    };

    $date_opened = $fs->formatDate($task_details['date_opened'], true);

    echo "{$details_text['openedby']} <a href=\"?do=admin&amp;area=users&amp;id={$task_details['opened_by']}\">$real_name ($user_name)</a> - $date_opened";


    // If it's been edited, get the details
    if ($task_details['last_edited_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['last_edited_by']));
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);

      $date_edited = $fs->formatDate($task_details['last_edited_time'], true);

      echo "<br>{$details_text['editedby']} <a href=\"?do=admin&amp;area=users&amp;id={$task_details['last_edited_by']}\">$real_name ($user_name)</a> - $date_edited";

    };
    ?>
    </p>
    <table class="taskdetails">
      <tr>
        <th><?php echo $details_text['tasktype'];?></th>
        <td>
        <select name="task_type">
        <?php
        // Get list of task types
        $get_severity = $fs->dbQuery("SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype WHERE show_in_list = ? ORDER BY list_position", array('1'));
        while ($row = $fs->dbFetchArray($get_severity)) {
          if ($row['tasktype_id'] == $task_details['task_type']) {
            echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>";
          } else {
            echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>";
          };
        };
        ?>
        </select>
        </td>
        <th><?php echo $details_text['severity'];?></th>
        <td>
        <select name="task_severity">
        <?php
        // Get list of severities
        require("lang/$lang/severity.php");
        foreach($severity_list as $key => $val) {
          if ($task_details['task_severity'] == $key) {
            echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
          } else {
            echo "<option value=\"$key\">$val</option>\n";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <th><?php echo $details_text['category'];?></th>
        <td>
        <select name="product_category">
        <?php
        $cat_list = $fs->dbQuery('SELECT category_id, category_name
                                    FROM flyspray_list_category
                                    WHERE project_id=? AND show_in_list=? AND parent_id < ?
                                    ORDER BY list_position', array($project_id, '1', '1'));
        while ($row = $fs->dbFetchArray($cat_list)) {
          $category_name = stripslashes($row['category_name']);
          if ($task_details['product_category'] == $row['category_id']) {
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
            if ($task_details['product_category'] == $subrow['category_id']) {
              echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&rarr;$subcategory_name</option>\n";
            } else {
              echo "<option value=\"{$subrow['category_id']}\">&rarr;$subcategory_name</option>\n";
            };
          };
        };
        ?>
        </select>
        </td>
        <th><?php echo $details_text['reportedversion'];?></th>
        <td>
        <!--<select name="product_version">
        <?php
        // Get list of versions
        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE project_id = ? AND show_in_list = '1' ORDER BY list_position", array($project_id));
        while ($row = $fs->dbFetchArray($get_version)) {
          if ($row['version_id'] == $task_details['product_version']) {
            echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
          };
        };
        ?>
        </select>-->
        <?php
        // Print the version name
        echo $task_details['reported_version_name'];
        ?>
        </td>
      </tr>
      <tr>
        <th><?php echo $details_text['status'];?></th>
        <td>
        <select name="item_status">
        <?php
        // let's get a list of statuses and compare it to the saved one
        require("lang/$lang/status.php");
        foreach($status_list as $key => $val) {
          if ($task_details['item_status'] == $key) {
            echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
          } else {
            echo "<option value=\"$key\">$val</option>\n";
          };
        };

        ?>
        </select></td>
        <th><?php echo $details_text['dueinversion'];?></th>
        <td>
        <select name="closedby_version">
        <?php
        // if we don't have a fix-it version, show undecided
        if (!isset($closedby)) {
          echo "<option value=\"\">{$details_text['undecided']}</option>\n";
        } else {
          echo "<option value=\"\" selected=\"selected\">{$details_text['undecided']}</option>\n";
        };
        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE project_id = ? AND show_in_list = '1' ORDER BY list_position", array($project_id));
        while ($row = $fs->dbFetchArray($get_version)) {
          if ($row['version_id'] == $task_details['closedby_version']) {
            echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
          };
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <th><?php echo $details_text['assignedto'];?></th>
        <td>
        <input type="hidden" name="old_assigned" value="<?php echo $task_details['assigned_to'];?>">
        <select name="assigned_to">
        <?php
        // Get list of users
        //$get_users = $fs->dbQuery($fs->listUserQuery());

        // see if it's been assigned
        if ($task_details['assigned_to'] == "0") {
          echo "<option value=\"0\" selected=\"selected\">{$details_text['noone']}</option>\n";
        } else {
          echo "<option value=\"0\">{$details_text['noone']}</option>\n";
        };

        $fs->ListUsers($task_details['assigned_to']);

        ?>
        </select>
        </td>
        <th><?php echo $details_text['percentcomplete'];?></th>
        <td>
        <select name="percent_complete">
          <option value="0" <?php if ($task_details['percent_complete'] == '0') { echo "selected=\"selected\"";};?>>0%</option>
          <option value="10" <?php if ($task_details['percent_complete'] == '10') { echo "selected=\"selected\"";};?>>10%</option>
          <option value="20" <?php if ($task_details['percent_complete'] == '20') { echo "selected=\"selected\"";};?>>20%</option>
          <option value="30" <?php if ($task_details['percent_complete'] == '30') { echo "selected=\"selected\"";};?>>30%</option>
          <option value="40" <?php if ($task_details['percent_complete'] == '40') { echo "selected=\"selected\"";};?>>40%</option>
          <option value="50" <?php if ($task_details['percent_complete'] == '50') { echo "selected=\"selected\"";};?>>50%</option>
          <option value="60" <?php if ($task_details['percent_complete'] == '60') { echo "selected=\"selected\"";};?>>60%</option>
          <option value="70" <?php if ($task_details['percent_complete'] == '70') { echo "selected=\"selected\"";};?>>70%</option>
          <option value="80" <?php if ($task_details['percent_complete'] == '80') { echo "selected=\"selected\"";};?>>80%</option>
          <option value="90" <?php if ($task_details['percent_complete'] == '90') { echo "selected=\"selected\"";};?>>90%</option>
          <option value="100" <?php if ($task_details['percent_complete'] == '100') { echo "selected=\"selected\"";};?>>100%</option>
        </select>
        </td>
      </tr>
      <tr>
        <th><?php echo $details_text['operatingsystem'];?></th>
        <td>
        <select name="operating_system">
        <?php
        // Get list of operating systems
        $get_os = $fs->dbQuery("SELECT os_id, os_name FROM flyspray_list_os WHERE project_id = ? AND show_in_list = '1' ORDER BY list_position", array($project_id));
        while ($row = $fs->dbFetchArray($get_os)) {
          if ($row['os_id'] == $task_details['operating_system']) {
            echo "<option value=\"{$row['os_id']}\" selected=\"selected\">{$row['os_name']}</option>";
          } else {
            echo "<option value=\"{$row['os_id']}\">{$row['os_name']}</option>";
          };
        };
        ?>
        </select>
        </td>
        <td colspan="2"></td>

      </tr>
      <tr>
        <th><?php echo $details_text['details'];?></th>
        <td colspan="3">
        <?php
        ?>
        <textarea name="detailed_desc" cols="70" rows="10"><?php echo $detailed_desc;?></textarea>
        </td>
      </tr>
      <tr>
    <td class="buttons" colspan="4">
    <input class="adminbutton" type="submit"  name="buSubmit" value="<?php echo $details_text['savedetails'];?>" onclick="Disable1()">
    <input class="adminbutton" type="reset" name="buReset">
    </td>
  </tr>
</table>
</form>
</div>


<?php
//
} elseif (($_SESSION['can_modify_jobs'] != '1'
             OR $task_details['is_closed'] == '1'
             OR !$GET['edit'])
             ) {
//////////////////////////////////////
// If the user isn't an admin,      //
// OR if the task is in VIEW mode,  //
// OR if the job is closed          //
//////////////////////////////////////
?>

<div id="taskdetails" ondblclick='openTask("?do=details&amp;id=<?php echo $task_details['task_id'];?>&amp;edit=yep")'>
    <?php if ($_SESSION['can_modify_jobs'] != '1' OR $task_details['is_closed'] == '1') { ?>
    <h2 class="severity<?php echo $task_details['task_severity'];?>">
    <?php } else { ?>
    <h2 class="severity<?php echo $task_details['task_severity'];?>">
    <?php }; ?>
    <?php echo "{$details_text['task']} #{$_GET['id']} &mdash; $item_summary";?>
    </h2>
    <?php
    if (($_SESSION['can_modify_jobs'] == '1'
    OR $_SESSION['userid'] == $task_details['assigned_to'])
    && $task_details['is_closed'] != '1') {
    ?>
    <form action="index.php" method="get" id="formedittask">
    <p>
      <input type="hidden" name="do" value="details">
      <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
      <input type="hidden" name="edit" value="yep">
      <input class="adminbutton" type="submit" value="<?php echo $details_text['edittask'];?>">
    </p>
    </form>
    <?php };

    echo "{$details_text['attachedtoproject']} &mdash; <a href=\"?project={$task_details['attached_to_project']}\">{$task_details['project_title']}</a>";

    ?>


    <p class="fineprint">
    <?php
    // Get the user details of the person who opened this item
    if ($task_details['opened_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['opened_by']));
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);
    } else {
      $user_name = $details_text['anonymous'];
      //$real_name = "Anonymous";
    };

    $date_opened = $task_details['date_opened'];
    $date_opened = $fs->formatDate($date_opened, true);

    echo "{$details_text['openedby']} <a href=\"?do=admin&amp;area=users&amp;id={$task_details['opened_by']}\">$real_name ($user_name)</a> - $date_opened";

    // If it's been edited, get the details
    if ($task_details['last_edited_by']) {
      $get_user_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['last_edited_by']));
      list($user_name, $real_name) = $fs->dbFetchArray($get_user_name);

      $date_edited = $task_details['last_edited_time'];
      $date_edited = $fs->formatDate($date_edited, true);

      echo "<br>{$details_text['editedby']} <a href=\"?do=admin&amp;area=users&amp;id={$task_details['last_edited_by']}\">$real_name ($user_name)</a> - $date_edited";

    };
    ?>
    </p>
    <table class="taskdetails">
      <tr>
        <th><?php echo $details_text['tasktype'];?></th>
        <td><?php echo $task_details['tasktype_name'];?></td>
        <th><?php echo $details_text['severity'];?></th>
        <td><?php echo $task_details['severity_name'];?></td>
      </tr>
      <tr>
        <th><?php echo $details_text['category'];?></th>
        <td>
        <?php
        if ($task_details['parent_id'] > '0') {
          $get_parent_cat = $fs->dbFetchArray($fs->dbQuery('SELECT category_name
                                          FROM flyspray_list_category
                                          WHERE category_id = ?',
                                          array($task_details['parent_id'])));
          echo $get_parent_cat['category_name'] . " &rarr; ";
        };
        echo $task_details['category_name'];?>
        </td>
        <th nowrap=""><?php echo $details_text['reportedversion'];?></th>
        <td><?php echo $task_details['reported_version_name'];?></td>
      </tr>
      <tr>
        <th><?php echo $details_text['status'];?></th>
        <td>
		<?php
		if($task_details['is_closed'] == '1') {
			echo $details_text['closed'];
		} else {
			echo $task_details['status_name'];
		};
		?>
		</td>
        <th><?php echo $details_text['dueinversion'];?></th>
        <td>
        <?php
        if (isset($task_details['due_in_version_name'])) {
          echo $task_details['due_in_version_name'];
        } else {
          echo $details_text['undecided'];
        };
        ?>
        </td>
      </tr>
      <tr>
        <th><?php echo $details_text['assignedto'];?></th>
        <td>
        <?php
        // see if it's been assigned
        if (!$task_details['assigned_to']) {
          echo $details_text['noone'];
        } else {
          // find out the username
          $getusername = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['assigned_to']));
          list ($user_name, $real_name) = $fs->dbFetchArray($getusername);
          echo "$real_name ($user_name)";
        };
        ?>
        </td>
        <th><?php echo $details_text['percentcomplete'];?></th>
        <td ><?php echo "<img src=\"themes/{$flyspray_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png\" width=\"150\" height=\"10\" alt=\"{$task_details['percent_complete']}% {$details_text['complete']}\" title=\"{$task_details['percent_complete']}% {$details_text['complete']}\"";?>></td>
      </tr>
      <tr>
        <th nowrap=""><?php echo $details_text['operatingsystem'];?></th>
        <td><?php echo $task_details['os_name'];?></td>
        <td colspan="2"></td>
      </tr>

    <!-- end of right column -->
      <tr>
        <th><?php echo $details_text['details'];?></th>
        <td class="details" colspan="3">
        <?php 
        $detailed_desc = str_replace("\n", "<br>", $detailed_desc);
        $detailed_desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\" target=\"_blank\">\\0</a>", $detailed_desc);
        echo $detailed_desc; ?>
        </td>
      </tr>
    </table>

  <?php
  if ($task_details['is_closed'] == '1') {
  ?>
  <p>
      <?php
      $get_closedby_name = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($task_details['closed_by']));
      list($closedby_username, $closedby_realname) = $fs->dbFetchArray($get_closedby_name);
      $date_closed = $task_details['date_closed'];
      $date_closed = $fs->formatDate($date_closed, true);
      echo "{$details_text['closedby']}&nbsp;&nbsp;<a href=\"?do=admin&amp;area=users&amp;id={$task_details['closed_by']}\">$closedby_realname ($closedby_username)</a><br>";
	  echo "{$details_text['date']}&nbsp;&nbsp;$date_closed.";
      ?>
      <br>
      <?php echo $details_text['reasonforclosing'];?>&nbsp;&nbsp;
      <?php echo $task_details['resolution_name'];?>
      <br>
      <?php
      if ($task_details['closure_comment'] != '') {
       echo "{$details_text['closurecomment']}&nbsp;&nbsp;";
       echo stripslashes($task_details['closure_comment']);
      };
     ?>
    </p>
    <?php
    };
    if ($_SESSION['can_modify_jobs'] == '1' && $task_details['is_closed'] == '1') { ?>
  <form name="form2" action="index.php" method="post" id="formreopentask">
  <p>
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="reopen">
      <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $details_text['reopenthistask'];?>" onclick="Disable2()">
  </p>
  </form>
    <?php
    // If the user CAN modify tasks, and the task is still open
    } elseif ($_SESSION['can_modify_jobs'] == '1' && $task_details['is_closed'] != '1') {
    ?>
    <form name="form2" action="index.php" method="post" id="formclosetask">
    <p>
        <?php echo $details_text['closetask'];?>&nbsp;
        <input type="hidden" name="do" value="modify">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="assigned_to" value="<?php echo $task_details['assigned_to'];?>">
        <!--<input type="hidden" name="item_summary" value="<?php echo $task_details['item_summary'];?>">-->
        <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
        <select class="adminlist" name="resolution_reason">
        <?php
        $get_resolution = $fs->dbQuery("SELECT resolution_id, resolution_name FROM flyspray_list_resolution ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_resolution)) {
          if ($row['resolution_id'] == $task_details['resolution_reason']) {
            echo "<option value=\"{$row['resolution_id']}\" selected=\"selected\">{$row['resolution_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['resolution_id']}\">{$row['resolution_name']}</option>\n";
          };
        };
        ?>
        </select>
        <br>
        <?php echo $details_text['closurecomment'];?>
        <br>
        <textarea class="admintext" name="closure_comment" rows="2" cols="30"></textarea>
        <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $details_text['closetask'];?>" onclick="Disable2()">
    </p>
    </form>

    <?php
    };
    ?>

    </div>

<?php
/////////////////////////////////////////////////
// End of checking if a job should be editable //
/////////////////////////////////////////////////
};
?>


<?php
if ($_GET['area']) {
  $area = $_GET['area'];
} else {
  $area = 'comments';
};
$num_comments = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_comments WHERE task_id = ?", array($task_details['task_id'])));
$num_attachments = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_attachments WHERE task_id = ?", array($task_details['task_id'])));
$num_related = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_related WHERE this_task = ?", array($task_details['task_id'])));
$num_related_to = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_related WHERE related_task = ?", array($task_details['task_id'])));
$num_notifications = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_notifications WHERE task_id = ?", array($_GET['id'])));
$num_reminders = $fs->dbCountRows($fs->dbQuery("SELECT * FROM flyspray_reminders WHERE task_id = ?", array($_GET['id'])));
?>

<p id="tabs">
    <?php if ($area == 'comments') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=comments#tabs"><?php echo "{$details_text['comments']} ($num_comments)";?></a><small> | </small>
    <?php if ($area == 'attachments') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=attachments#tabs"><?php echo "{$details_text['attachments']} ($num_attachments)";?></a><small> | </small>
   <?php if ($area == 'related') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=related#tabs"><?php echo "{$details_text['relatedtasks']} ($num_related/$num_related_to)";?></a><small> | </small>
    <?php if ($area == 'notify') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=notify#tabs"><?php echo "{$details_text['notifications']} ($num_notifications)";?></a><small> | </small>
  <?php if ($area == 'remind') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=remind#tabs"><?php echo "{$details_text['reminders']} ($num_reminders)";?></a><small> | </small>
   <?php if ($area == 'history') {
      echo "<a class=\"tabactive\"";
    } else {
      echo "<a class=\"tabnotactive\"";
    };
    ?> href="?do=details&amp;id=<?php echo $_GET['id'];?>&amp;area=history#tabs"><?php echo "{$details_text['history']}";?></a><small> | </small>
</p>

<?php
////////////////////////////
// Start of comments area //
////////////////////////////

if ($area == 'comments') { ?>
  <div class="tabentries">
    <?php
    // if there are comments, show them
    $getcomments = $fs->dbQuery("SELECT * FROM flyspray_comments WHERE task_id = ?", array($task_details['task_id']));
    while ($row = $fs->dbFetchArray($getcomments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = ?", array($row['user_id']));
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = $fs->formatDate($row['date_added'], true);

      $comment_text = htmlentities($row['comment_text']);
      $comment_text = str_replace("\n", "<br>", "$comment_text");
      $comment_text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\" target=\"_blank\">\\0</a>", $comment_text);
      $comment_text = stripslashes($comment_text);

    ?>
     <div class="tabentry"><a name="<?php echo $row['comment_id'];?>"></a>
      <em><?php echo "{$details_text['commentby']} <a href=\"?do=admin&amp;area=users&amp;id={$row['user_id']}\">$user_name</a> - $formatted_date";?></em>
      <?php
        // If the user is an admin, show the edit button
        if ($_SESSION['admin'] == '1') { ?>
        <div class="modifycomment">
        <form action="index.php" method="get">
        <p>
          <input type="hidden" name="do" value="admin">
          <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
          <input type="hidden" name="area" value="editcomment">
          <input type="hidden" name="id" value="<?php echo $row['comment_id'];?>">
          <input class="adminbutton" type="submit" value="<?php echo $details_text['edit'];?>">
        </p>
        </form>
        <form action="index.php" method="post"
        onSubmit="
        if(confirm('Really delete this comment?')) {
          return true
        } else {
          return false }
        ">
          <p>
          <input type="hidden" name="do" value="modify">
          <input type="hidden" name="action" value="deletecomment">
          <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
          <input type="hidden" name="comment_id" value="<?php echo $row['comment_id'];?>">
          <input class="adminbutton" type="submit" value="<?php echo $details_text['delete'];?>">
        </p>
        </form>
        </div>
        <?php }; ?>
      <p>
      <?php echo $comment_text;?>
      </p>
    </div>


    <?php
    };
    echo "</div>";

// Now, show a form to add a comment (but only if the user has the rights!)

if ($_SESSION['can_add_comments'] == "1" && $task_details['is_closed'] != '1') {
?>

<form action="index.php" method="post">
<p class="admin">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="addcomment">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <label><?php echo $details_text['addcomment'];?><br>
    <textarea name="comment_text" cols="72" rows="10"></textarea></label>
    <br>
    <input class="adminbutton" type="submit" value="<?php echo $details_text['addcomment'];?>">
</p>
</form>

<?php
// End of checking if the comments form should be displayed
};

// End of comments area

////////////////////////////////////
// Start of file attachments area //
////////////////////////////////////

} elseif ($area == 'attachments') {
?>
<div class="tabentries">
    <?php
    // if there are attachments, show them
    $getattachments = $fs->dbQuery("SELECT * FROM flyspray_attachments WHERE task_id = ?", array($task_details['task_id']));
    while ($row = $fs->dbFetchArray($getattachments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = ?", array($row['added_by']));
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = $fs->formatDate($row['date_added'], true);

      $file_desc = stripslashes($row['file_desc']);

    ?>
    <div class="tabentry">

<?php
//  "Deleting attachments" code contributed by Harm Verbeek <info@certeza.nl>
        if ($_SESSION['admin'] == '1') { ?>
        <div class="modifycomment">
        <form action="index.php" method="post" onSubmit="if(confirm('Really delete this attachment?')) {return true} else {return false }">
          <p>
          <input type="hidden" name="do" value="modify">
          <input type="hidden" name="action" value="deleteattachment">
          <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
          <input type="hidden" name="attachment_id" value="<?php echo $row['attachment_id'];?>">
          <input class="adminbutton" type="submit" value="<?php echo $details_text['delete'];?>">
         </p>
        </form>
        </div>
      <?php
      };

      // Divide the attachments area into two columns for display
      echo "<table><tr><td><p>";

	  // Detect if the attachment is an image
	  $pos = strpos($row['file_type'], "image/");
      if($pos===0 && $project_prefs['inline_images'] == '1') {

         // Find out the size of the image
         list($width, $height, $type, $string) = getimagesize("attachments/{$row['file_name']}");

         // If the image is too wide, let's scale it down so that it doesn't destroy the page layout
         if ($width > "200") {
            $v_fraction = 200/$width;
            $new_height = round(($height*$v_fraction),0);

			// Display the resized image, with a link to the fullsized one
            echo "<a href=\"?getfile={$row['attachment_id']}\"><img src=\"?getfile={$row['attachment_id']}\" width=\"200\" width=\"$new_height\" alt=\"\"></a>";
         } else {
			 // If the image is already small, just display it.
             echo "<br><img src=\"?getfile={$row['attachment_id']}\">";
         };

      // If the attachment isn't an image, or the inline images is OFF,
	  // show a mimetype icon instead of a thumbnail
      } else {

         // Let's strip the mimetype to get the image name
		 list($main, $specific) = split('[/]', $row['file_type']);
		 if(file_exists("themes/{$project_prefs['theme_style']}/mime/{$row['file_type']}.png")) {
            list($width, $height, $type, $string) = getimagesize("themes/{$project_prefs['theme_style']}/mime/{$row['file_type']}.png");
	        echo "<a href=\"?getfile={$row['attachment_id']}\"><img src=\"themes/{$project_prefs['theme_style']}/mime/{$row['file_type']}.png\" width=\"$width\" height=\"$height\"></a>";
         } elseif (file_exists("themes/{$project_prefs['theme_style']}/mime/$main.png")) {
            list($width, $height, $type, $string) = getimagesize("themes/{$project_prefs['theme_style']}/mime/$main.png");
	        echo "<a href=\"?getfile={$row['attachment_id']}\"><img src=\"themes/{$project_prefs['theme_style']}/mime/$main.png\" width=\"$width\" height=\"$height\"></a>";
		 };
      };

      // The second column, for the descriptions
      echo "</p></td><td>";
      echo "<table>";
      echo "<tr><td><em>{$details_text['filename']}</em></td><td><a href=\"?getfile={$row['attachment_id']}\">{$row['orig_name']}</a></td></tr>";
      echo "<tr><td><em>{$details_text['description']}</em></td><td>$file_desc</a></td></tr>";
      echo "<tr><td><em>{$details_text['fileuploadedby']}</em></td><td><a href=\"?do=admin&amp;area=users&amp;id={$row['added_by']}\">$user_name</a></td></tr>";
      echo "<tr><td><em>{$details_text['date']}</em></td><td>$formatted_date</td></tr>";
      $size = $row['file_size'];
      $sizes = Array(' B', ' KB', ' MB');
      $size_ext = $sizes[0];
      for ($i = 1; (($i < count($sizes)) && ($size >= 1024)); $i++)
      {
        $size = $size / 1024;
        $size_ext  = $sizes[$i];
      }
      echo "<tr><td><em>{$details_text['filesize']}</em></td><td>" . round($size, 2) . $size_ext . "</td></tr>";
      echo "</table>";
      echo "</td></tr></table>";

  echo "</div>";
 };
echo "</div>";
//};
// Now, show a form to attach a file (but only if the user has the rights!)

if ($_SESSION['can_attach_files'] == "1" && $task_details['is_closed'] != '1') {
?>

<form enctype="multipart/form-data" action="index.php" method="post" id="formupload">
<table class="admin">
  <tr>
    <td>
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="addattachment">
      <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
      <label><?php echo $details_text['uploadafile'];?></label>
    </td>
    <td>
      <input type="file" size="55" name="userfile">
    </td>
  </tr>
  <tr>
    <td>
      <label><?php echo $details_text['description'];?></label>
    </td>
    <td>
      <input class="admintext" type="text" name="file_desc" size="70" maxlength="100">
    </td>
  </tr>
  <tr>
    <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $details_text['uploadnow'];?>"></td>
  </tr>
</table>
</form>

<?php
// End of admin checking
};
// End of attachments area

/////////////////////////////////
// Start of related tasks area //
/////////////////////////////////

} elseif ($area == 'related') { ?>
<div class="tabentries">
  <p><em><?php echo $details_text['thesearerelated'];?></em></p>


    <?php
    $get_related = $fs->dbQuery("SELECT * FROM flyspray_related WHERE this_task = ?", array($_GET['id']));
    while ($row = $fs->dbFetchArray($get_related)) {
      $get_summary = $fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($row['related_task']));
      while ($subrow = $fs->dbFetchArray($get_summary)) {
        $summary = stripslashes($subrow['item_summary']);
        ?>
      <div class="tabentry">
       <?php
        // If the user can modify jobs, then show them a form to remove related tasks
        if ($_SESSION['can_modify_jobs'] == '1' && $task_details['is_closed'] != '1') {
          ?>
         <div class="modifycomment">
          <form action="index.php" method="post">
            <p>
            <input type="hidden" name="do" value="modify">
            <input type="hidden" name="action" value="remove_related">
            <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
            <input type="hidden" name="related_id" value="<?php echo $row['related_id'];?>">
            <input type="hidden" name="related_task" value="<?php echo $row['related_task'];?>">
            <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
            </p>
          </form>
         </div>
      </div>
        <?php
        };
        echo "<p><a href=\"?do=details&amp;id={$row['related_task']}\">#{$row['related_task']} &mdash; $summary</a></p>";
    };
   };

    if ($_SESSION['can_modify_jobs'] == "1" && $task_details['is_closed'] != '1') {
    ?>

  <form action="index.php" method="post" id="formaddrelatedtask">
    <p>
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="add_related">
    <input type="hidden" name="this_task" value="<?php echo $_GET['id'];?>">
    <label><?php echo $details_text['addnewrelated'];?>
    <input name="related_task" size="10" maxlength="10"></label>
    <input class="adminbutton" type="submit" value="<?php echo $details_text['add'];?>">
    </p>
  </form>

    <?php
    };
    ?>
 </div>
<div class="tabentries">
  <p><em><?php echo $details_text['otherrelated'];?></em></p>
  <p>
    <?php
    $get_related = $fs->dbQuery("SELECT * FROM flyspray_related WHERE related_task = ?", array($_GET['id']));
    while ($row = $fs->dbFetchArray($get_related)) {
      $get_summary = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($row['this_task']));
      while ($subrow = $fs->dbFetchArray($get_summary)) {
        $summary = stripslashes($subrow['item_summary']);
        echo "<a href=\"?do=details&amp;id={$row['this_task']}\">#{$row['this_task']} &mdash; $summary</a><br>";
      };
    };
    echo "</p></div>";

// End of related area

// Start of notifications area
} elseif ($area == 'notify') { ?>
<div class="tabentries">
<p><em><?php echo $details_text['theseusersnotify'];?></em></p>

    <?php
    $get_user_ids = $fs->dbQuery("SELECT * FROM flyspray_notifications WHERE task_id = ?", array($_GET['id']));
    while ($row = $fs->dbFetchArray($get_user_ids)) {
      $get_user = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($row['user_id']));
      while ($subrow = $fs->dbFetchArray($get_user)) {
      ?>
      <div class="tabentry">
      <?php
        // If the user can modify jobs, then show them a form to remove a notified user
        if ($_SESSION['admin'] == '1' && $task_details['is_closed'] != '1') {
          ?>
          <div class="modifycomment">
          <form action="index.php" method="post">
              <p>
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="remove_notification">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="user_id" value="<?php echo $row['user_id'];?>">
                <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
              </p>
          </form>
          </div>

        <?php

        };
                echo "<p><a href=\"?do=admin&amp;area=users&amp;id={$row['user_id']}\">{$subrow['real_name']} ({$subrow['user_name']})</a></p>";
      echo "</div>";
      };
    };
    if ($task_details['is_closed'] != '1') {
    ?>

</div>
<div class="tabentries">
  <?php if ($_SESSION['admin'] == '1') { ?>
  <div class="tabentry">
  <form action="index.php" method="post">
  <p>
    <?php echo $details_text['addusertolist'];?>
    <select class="adminlist" name="user_id">
    <?php
    // Get list of users
    $fs->listUsers();
    ?>
    </select>
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="add_notification">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <input class="adminbutton" type="submit" value="<?php echo $details_text['addtolist'];?>">
  </p>
  </form>
  </div>

  <div class="tabentry">
  <?php
  };
  if ($_SESSION['userid']) {
    $result = $fs->dbQuery("SELECT * FROM flyspray_notifications
              WHERE task_id = ?
              AND user_id = ?
              ", array($_GET['id'], $_SESSION['userid']));
    if (!$fs->dbCountRows($result)) {
  ?>
  <form action="index.php" method="post" id="addmyself">
    <p>
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="add_notification">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'];?>">
    <input class="adminbutton" type="submit" value="<?php echo $details_text['addmyself'];?>">
    </p>
  </form>
  <?php } else { ?>
  <form action="index.php" method="post" id="removemyself">
    <p>
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="remove_notification">
    <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'];?>">
    <input class="adminbutton" type="submit" value="<?php echo $details_text['removemyself'];?>">
    </p>
  </form>
  <?php
    };
  };?>
  </div>
    <?php
    };
    echo "</div>";

// End of notifications area

// Start of scheduled reminders area
} elseif ($area == 'remind') { ?>
<div class="tabentries">

  <?php
    $get_reminders = $fs->dbQuery("SELECT * FROM flyspray_reminders WHERE task_id = ? ORDER BY reminder_id", array($_GET['id']));
    while ($row = $fs->dbFetchArray($get_reminders)) {
      $get_username = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($row['to_user_id']));
      while ($subrow = $fs->dbFetchArray($get_username)) {

// If the user can modify jobs, then show them a form to remove a notified user
        if ($_SESSION['admin'] == '1' && $task_details['is_closed'] != '1') {
        ?>
          <div class="modifycomment">
          <form action="index.php" method="post">
              <p>
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="deletereminder">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="reminder_id" value="<?php echo $row['reminder_id'];?>">
                <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
              </p>
          </form>
          </div>
		  

        <?php
        }
		echo "<div class=\"tabentry\">";
        echo "<em>{$details_text['remindthisuser']}:</em> <a href=\"?do=admin&amp;area=users&amp;id={$row['to_user_id']}\">{$subrow['real_name']} ( {$subrow['user_name']})</a><br>";
		
		// Work out the unit of time to display
		if ($row['how_often'] < 86400) {
			$how_often = $row['how_often'] / 3600 . " " . $details_text['hours'];
		} elseif ($row['how_often'] < 604800) {
			$how_often = $row['how_often'] / 86400 . " " . $details_text['days'];
		} else {
			$how_often = $row['how_often'] / 604800 . " " . $details_text['weeks'];
		};
		
		echo "<em>{$details_text['thisoften']}:</em> $how_often";
		
		echo "<br>";
		
		echo "<em>{$details_text['message']}:</em> {$row['reminder_message']}";

		echo "<br><br></div>";
      };
    };
  ?>


</div>
<?php
if ($_SESSION['admin'] == '1' && $task_details['is_closed'] != '1') {
?>
<div class="tabentries">

<div class="tabentry">
 <form action="index.php" method="post" id="formaddreminder">
  <input type="hidden" name="do" value="modify">
  <input type="hidden" name="action" value="addreminder">
  <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
  <em><?php echo $details_text['remindthisuser'];?></em>
  <select class="adminlist" name="to_user_id">
    <?php
    // Get list of users
    $fs->listUsers();
    ?>
    </select>

   <br>

   <em><?php echo $details_text['thisoften'];?></em>
   <input type="admintext" name="timeamount1" size="3" maxlength="3">
   
   <select class="adminlist" name="timetype1">
     <option value="3600"><?php echo $details_text['hours'];?></option>
     <option value="86400"><?php echo $details_text['days'];?></option>
     <option value="604800"><?php echo $details_text['weeks'];?></option>
   </select>

  <br>

  <em><?php echo $details_text['startafter'];?></em>
  <input type="admintext" name="timeamount2" size="3" maxlength="3">
  
  <select class="adminlist" name="timetype2">
     <option value="3600"><?php echo $details_text['hours'];?></option>
     <option value="86400"><?php echo $details_text['days'];?></option>
     <option value="604800"><?php echo $details_text['weeks'];?></option>
  </select>
  
  <br>
  
  <textarea class="admintext" name="reminder_message" rows="7" cols="50"><?php echo "{$details_text['defaultreminder']}\n\n{$flyspray_prefs['base_url']}?do=details&amp;id={$_GET['id']}";?></textarea>
  
  <br>
  
  <input class="adminbutton" type="submit" value="<?php echo $details_text['addreminder'];?>">
  </div>
  </form>
</div>

<?php
// End of checking if a user can modify tasks
};
// End of scheduled reminders area


// Start of system log area
} elseif ($area == 'system') { ?>
<div class="tabentries">


    <?php
    $get_user_ids = $fs->dbQuery("SELECT * FROM flyspray_notifications WHERE task_id = ?", array($_GET['id']));
    while ($row = $fs->dbFetchArray($get_user_ids)) {
      $get_user = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($row['user_id']));
      while ($subrow = $fs->dbFetchArray($get_user)) {
      ?>
      <div class="tabentry">
      <?php
        // If the user can modify jobs, then show them a form to remove a notified user
        if ($_SESSION['admin'] == '1' && $task_details['is_closed'] != '1') {
          ?>
          <div class="modifycomment">
          <form action="index.php" method="post">
              <p>
                <input type="hidden" name="do" value="modify">
                <input type="hidden" name="action" value="remove_notification">
                <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>">
                <input type="hidden" name="user_id" value="<?php echo $row['user_id'];?>">
                <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>">
              </p>
          </form>
          </div>

        <?php

        };
                echo "<p>{$subrow['real_name']} ({$subrow['user_name']})</p>";
      echo "</div>";
      };
    };

    ?>

</div>

<?php
// End of system log area

// Start of History Tab
} elseif($area == 'history') { ?>

<div class="tabentries">
    <table id="history">
        <tr>
            <th><?php echo $details_text['eventdate'];?></th>
            <th><?php echo $details_text['user'];?></th>
            <th><?php echo $details_text['event'];?></b></th>
        </tr>
     <?php
        $query_history = $fs->dbQuery("SELECT h.*, u.user_name, u.real_name
                                         FROM flyspray_history h
                                         LEFT JOIN flyspray_users u ON h.user_id = u.user_id
                                         WHERE h.task_id = ?
                                         ORDER BY h.event_date ASC, h.event_type ASC", array($_GET['id']));

        if ($fs->dbCountRows($query_history) == 0) {
            ?>
        <tr>
            <td colspan="3"><?php echo $details_text['nohistory'];?></td>
        </tr>
            <?php
        };

        while ($history = $fs->dbFetchRow($query_history)) {
            ?>
        <tr>
            <td><?php echo $fs->formatDate($history['event_date'], true);?></td>
            <td><?php if ($history['user_id'] == 0) {
                          echo $details_text['anonymous'];
                      } else {
                          echo "<a href=\"?do=admin&amp;area=users&amp;id={$history['user_id']}\"> {$history['real_name']} ({$history['user_name']})</a>";
                      }?></td>
            <td><?php
            $newvalue = $history['new_value'];
            $oldvalue = $history['old_value'];
            
            //Create an event description
            if ($history['event_type'] == 0) {            //Field changed

                $field = $history['field_changed'];

                switch ($field) {
                case 'item_summary':
                    $field = $details_text['summary'];
                    $oldvalue = htmlspecialchars(stripslashes($oldvalue));
                    $newvalue = htmlspecialchars(stripslashes($newvalue));
                    break;
                case 'attached_to_project':
                    $field = $details_text['attachedtoproject'];
                    list($oldprojecttitle) = $fs->dbFetchRow($fs->dbQuery("SELECT project_title FROM flyspray_projects WHERE project_id = ?", array($oldvalue)));
                    list($newprojecttitle) = $fs->dbFetchRow($fs->dbQuery("SELECT project_title FROM flyspray_projects WHERE project_id = ?", array($newvalue)));
                    $oldvalue = "<a href=\"?project={$oldvalue}\">{$oldprojecttitle}</a>";
                    $newvalue = "<a href=\"?project={$newvalue}\">{$newprojecttitle}</a>";
                    break;
                case 'task_type':
                    $field = $details_text['tasktype'];
                    list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id = ?", array($oldvalue)));
                    list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id = ?", array($newvalue)));
                    break;
                case 'product_category':
                    $field = $details_text['category'];
                    list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id = ?", array($oldvalue)));
                    list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id = ?", array($newvalue)));
                    break;
                case 'item_status':
                    $field = $details_text['status'];
                    $oldvalue = $status_list[$oldvalue];
                    $newvalue = $status_list[$newvalue];
                    break;
                case 'operating_system':
                    $field = $details_text['operatingsystem'];
                    list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT os_name FROM flyspray_list_os WHERE os_id = ?", array($oldvalue)));
                    list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT os_name FROM flyspray_list_os WHERE os_id = ?", array($newvalue)));
                    break;
                case 'task_severity':
                    $field = $details_text['severity'];
                    $oldvalue = $severity_list[$oldvalue];
                    $newvalue = $severity_list[$newvalue];
                    break;
                case 'product_version':
                    $field = $details_text['reportedversion'];
                    list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($oldvalue)));
                    list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($newvalue)));
                    break;
                case 'closedby_version':
                    $field = $details_text['dueinversion'];
                    if ($oldvalue == '0') {
                        $oldvalue = $details_text['undecided'];
                    } else {
                        list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($oldvalue)));
                    };
                    if ($newvalue == '0') {
                        $newvalue = $details_text['undecided'];
                    } else {
                        list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($newvalue)));
                    };
                    break;
                case 'percent_complete':
                    $field = $details_text['percentcomplete'];
                    $oldvalue .= '%';
                    $newvalue .= '%';
                    break;
                case 'detailed_desc':
                    $field = $details_text['details'];
                    $oldvalue = '';
                    $newvalue = '';
                    break;
                };

                echo "{$details_text['fieldchanged']}: {$field}";
                if ($oldvalue != '' || $newvalue != '') {
                    echo " ({$oldvalue} &rarr; {$newvalue})";
                };

            } elseif ($history['event_type'] == 1) {      //Task opened
                echo $details_text['taskopened'];

            } elseif ($history['event_type'] == 2) {      //Task closed
                echo $details_text['taskclosed'];
                $res_name = $fs->dbFetchRow($fs->dbQuery("SELECT resolution_name FROM flyspray_list_resolution WHERE resolution_id = ?", array($newvalue)));
                echo " ({$res_name['resolution_name']})";

            } elseif ($history['event_type'] == 3) {      //Task edited
                echo $details_text['taskedited'];

            } elseif ($history['event_type'] == 4) {      //Comment added
                echo "<a href=\"?do=details&amp;id={$_GET['id']}&amp;area=comments#{$newvalue}\">{$details_text['commentadded']}</a>";

            } elseif ($history['event_type'] == 5) {      //Comment edited
                echo "<a href=\"?do=details&amp;id={$_GET['id']}&amp;area=comments#{$newvalue}\">{$details_text['commentedited']}</a>";
                $comment = $fs->dbQuery("SELECT user_id, date_added FROM flyspray_comments WHERE comment_id = ?", array($newvalue));
                if ($fs->dbCountRows($comment) != 0) {
                    $comment = $fs->dbFetchRow($comment);
                    echo " ({$details_text['commentby']} " . $fs->LinkedUsername($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
                };

            } elseif ($history['event_type'] == 6) {      //Comment deleted
                echo $details_text['commentdeleted'];
                if ($newvalue != '' && $oldvalue != '') {
                    echo " ({$details_text['commentby']} " . $fs->LinkedUsername($newvalue) . " - " . $fs->formatDate($oldvalue, true) . ")";    
                };

            } elseif ($history['event_type'] == 7) {      //Attachment added
                echo $details_text['attachmentadded'];
                $attachment = $fs->dbQuery("SELECT orig_name, file_desc FROM flyspray_attachments WHERE attachment_id = ?", array($newvalue));
                if ($fs->dbCountRows($attachment) != 0) {
                    $attachment = $fs->dbFetchRow($attachment);
                    echo ": <a href=\"?getfile={$newvalue}\">{$attachment['orig_name']}</a>";
                    if ($attachment['file_desc'] != '') {
                        echo " ({$attachment['file_desc']})";
                    };
                };

            } elseif ($history['event_type'] == 8) {      //Attachment deleted
                echo "{$details_text['attachmentdeleted']}: {$newvalue}";

            } elseif ($history['event_type'] == 9) {      //Notification added
                echo "{$details_text['notificationadded']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == 10) {      //Notification deleted 
                echo "{$details_text['notificationdeleted']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == 11) {      //Related task added
                list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
                echo "{$details_text['relatedadded']}: {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";      

            } elseif ($history['event_type'] == 12) {      //Related task deleted
                list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
                echo "{$details_text['relateddeleted']}: {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

            } elseif ($history['event_type'] == 13) {      //Task reopened
                echo $details_text['taskreopened'];

            } elseif ($history['event_type'] == 14) {      //Task assigned
                if ($history['old_value'] == '0') {
                    echo "{$details_text['taskassigned']} " . $fs->LinkedUsername($newvalue);
                } elseif ($history['new_value'] == '0') {
                    echo $details_text['assignmentremoved'];
                } else {
                    echo "{$details_text['taskreassigned']} " . $fs->LinkedUsername($newvalue);
                };
            } elseif ($history['event_type'] == 15) {      //Task added to related list of another task
                list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
                echo "{$details_text['addedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

            } elseif ($history['event_type'] == 16) {      //Task deleted from related list of another task
                list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
                echo "{$details_text['deletedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

            } elseif ($history['event_type'] == 17) {      //Reminder added
                echo "{$details_text['reminderadded']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == 18) {      //Reminder deleted
                echo "{$details_text['reminderdeleted']}: " . $fs->LinkedUsername($newvalue);
            };
            ?></td>
        </tr>
            <?php
        };
    ?>
    </table> 
</div>

<?php
// End of History Tab

// End of tabbed areas
};
?>

<?php
} else {
// If no task was actually requested, show an error
echo "<p><strong>{$details_text['invalidtaskid']}</strong></p>";

};
?>
