<?php
// This script is the admin interface for users, groups, lists and anything else I think of.

$lang = $flyspray_prefs['lang_code'];
require("lang/$lang/admin.php");

// Editing Users
if (($_SESSION['admin'] == "1" OR $_SESSION['userid'] == $_GET['id']) && ($_GET['area'] == "users")) {

// if we want a specific user
if ($_GET['id']) {

$get_user_details = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = '{$_GET['id']}'");
$user_details = $fs->dbFetchArray($get_user_details);

echo "<h3 class=\"subheading\">{$admin_text['edituser']} - {$user_details['real_name']} ({$user_details['user_name']})</h3>";
?>
  <table class="admin">
    <form action="index.php" method="post">
    <tr>
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="edituser">
      <input type="hidden" name="user_id" value="<?php echo $user_details['user_id'];?>">
      
      <td class="adminlabel"><?php echo $admin_text['realname'];?></td>
      <td><input class="admintext" type="text" name="real_name" size="50" maxlength="100" value="<?php echo $user_details['real_name'];?>"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['emailaddress'];?></td>
      <td><input class="admintext" type="text" name="email_address" size="50" maxlength="100" value="<?php echo $user_details['email_address'];?>"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['jabberid'];?></td>
      <td><input class="admintext" type="text" name="jabber_id" size="50" maxlength="100" value="<?php echo $user_details['jabber_id'];?>"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['notifytype'];?></td>
      <td align="left" class="admintext">
      <?php if ($flyspray_prefs['user_notify'] == '1') { ?>
      <select class="adminlist" name="notify_type">
        <option value="0" <?php if ($user_details['notify_type'] == "0") {echo "SELECTED";};?>>None</option>
        <option value="1" <?php if ($user_details['notify_type'] == "1") {echo "SELECTED";};?>>Email</option>
        <option value="2" <?php if ($user_details['notify_type'] == "2") {echo "SELECTED";};?>>Jabber</option>
      </select>
      <?php
      } else {
        echo $admin_text['setglobally'];
      }; ?>
      </td>
    </tr>
    <?php
    if ($_SESSION['admin'] == '1') {
    ?>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['group'];?></td>
      <td align="left">
      <select class="adminlist" name="group_in">
      <?php
      // Get the groups list
      $get_groups = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups ORDER BY group_id ASC");
      while ($row = $fs->dbFetchArray($get_groups)) {
        if ($row['group_id'] == $user_details['group_in']) {
          echo "<option value=\"{$row['group_id']}\" SELECTED>{$row['group_name']}</option>";
        } else {
          echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
        };
      };
      ?>
      </select>
      </td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['accountenabled'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="account_enabled" value="1" <?php if ($user_details['account_enabled'] == "1") {echo "CHECKED";};?>></td>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>"></td>
    </tr>
    </form>
  </table>

<?php
// Show users list
} else {

echo "<h3 class=\"subheading\">{$admin_text['usergroupmanage']}</h3>";
echo "<a class=\"admintext\" href=\"javascript:void(0)\" onClick=\"window.open('scripts/newuser.php','Register', 'width=350,height=380,toobar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1'); return false\">{$admin_text['newuser']}</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
echo "<a class=\"admintext\" href=\"javascript:void(0)\" onClick=\"window.open('scripts/newgroup.php','NewGroup', 'width=550,height=350,toobar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1'); return false\">{$admin_text['newgroup']}</a><br><br>";

$get_groups = $fs->dbQuery("SELECT * FROM flyspray_groups ORDER BY group_id ASC");
while ($group = $fs->dbFetchArray($get_groups)) {
  echo "<table class=\"admin\"><tr><td align=\center\">";
  echo "<a class=\"adminlabel\" href=\"?do=admin&amp;area=groups&id={$group['group_id']}\">{$group['group_name']}</a><br>";
  echo "<i class=\"admintext\">{$group['group_desc']}</i><br>";
  echo "<table class=\"admin\" border=\"1\" cellpadding=\"2\"><tr><td class=\"adminlabel\">{$admin_text['username']}</td><td class=\"adminlabel\">{$admin_text['realname']}</td><td class=\"adminlabel\">{$admin_text['accountenabled']}</td></tr>";

  $get_user_list = $fs->dbQuery("SELECT * FROM flyspray_users WHERE group_in = '{$group['group_id']}' ORDER BY user_name ASC");
  while ($row = $fs->dbFetchArray($get_user_list)) {
    echo "<tr>";
    echo "<td class=\"adminlabel\"><a href=\"?do=admin&amp;area=users&amp;id={$row['user_id']}\">{$row['user_name']}</a></td>";
    echo "<td class=\"admintext\">{$row['real_name']}</td>";
    if ($row['account_enabled'] == "1") {
      echo "<td class=\"admintext\">{$admin_text['yes']}</td>";
    } else {
      echo "<td class=\"admintext\">{$admin_text['no']}</td>";
    };
    echo "</tr>";
  };

  echo "</table>";
  echo "</td></tr></table><br /";
};

// End of users
};


} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "groups") {

$get_group_details = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = '{$_GET['id']}'");
$group_details = $fs->dbFetchArray($get_group_details);
?>
  <h3 class="subheading"><?php echo $admin_text['editgroup'];?></h3>
  <table class="admin">
    <form action="index.php" method="post">
    <tr>
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="editgroup">
      <input type="hidden" name="group_id" value="<?php echo $group_details['group_id'];?>">

      <td class="adminlabel"><?php echo $admin_text['groupname'];?></td>
      <td align="left"><input class="admintext" type="text" name="group_name" size="20" maxlength="20" value="<?php echo $group_details['group_name'];?>"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['description'];?></td>
      <td align="left"><input class="admintext" type="text" name="group_desc" size="50" maxlength="100" value="<?php echo $group_details['group_desc'];?>"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['admin'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="is_admin" value="1" <?php if ($group_details['is_admin'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['opennewtasks'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_open_jobs" value="1" <?php if ($group_details['can_open_jobs'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['modifytasks'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_modify_jobs" value="1" <?php if ($group_details['can_modify_jobs'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['addcomments'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_add_comments" value="1" <?php if ($group_details['can_add_comments'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['attachfiles'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_attach_files" value="1" <?php if ($group_details['can_attach_files'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['vote'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_vote" value="1" <?php if ($group_details['can_vote'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $admin_text['groupenabled'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="group_open" value="1" <?php if ($group_details['group_open'] == "1") { echo "CHECKED";};?>></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>"></td>
    </tr>
    </form>
  </table>

<?php
// End of groups

// Start of task types
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "tasktype") {
?>
  <h3 class="subheading"><?php echo $admin_text['tasktypelist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
    </tr>
    <?php
    $get_tasktypes = $fs->dbQuery("SELECT * FROM flyspray_list_tasktype ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_tasktypes)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_list">
      <input type="hidden" name="list_type" value="tasktype">
      <input type="hidden" name="id" value="<?php echo $row['tasktype_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name" value="<?php echo $row['tasktype_name'];?>"></td>
      <td title="The order these items will appear in the TaskType list"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td title="Show this item in the TaskType list" align="center"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="4"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_to_list">
      <input type="hidden" name="list_type" value="tasktype">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
// End of task types

// Start of categories
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "category") {
?>
  <h3 class="subheading"><?php echo $admin_text['categorylist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['owner'];?></td>
    </tr>
    <?php
    $get_categories = $fs->dbQuery("SELECT * FROM flyspray_list_category ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_categories)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_category">
      <input type="hidden" name="list_type" value="category">
      <input type="hidden" name="id" value="<?php echo $row['category_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name" value="<?php echo $row['category_name'];?>"></td>
      <td title="The order these items will appear in the Category list"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td title="Show this item in the Category list" align="center"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td title="This person will receive notifications when a task in this category is opened" align="center">
      <select class="adminlist" name="category_owner">
        <option value=""><?php echo $admin_text['selectowner'];?></option>
        <?php
        $dev_list = $fs->dbQuery($fs->listUserQuery());
        while ($subrow = $fs->dbFetchArray($dev_list)) {
          if ($row['category_owner'] == $subrow['user_id']) {
            echo "<option value=\"{$subrow['user_id']}\" SELECTED>{$subrow['real_name']} ({$subrow['user_name']})</option>\n";
          } else {
            echo "<option value=\"{$subrow['user_id']}\">{$subrow['real_name']} ({$subrow['user_name']})</option>\n";
          };
        };
        ?>
      </select>
      </td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="4"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_category">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td title="This person will receive notifications when a task in this category is opened" align="center">
      <select class="adminlist" name="category_owner">
        <option value=""><?php echo $admin_text['selectowner'];?></option>
        <?php
        $dev_list = $fs->dbQuery($fs->listUserQuery());
        while ($subrow = $fs->dbFetchArray($dev_list)) {
            echo "<option value=\"{$subrow['user_id']}\">{$subrow['real_name']} ({$subrow['user_name']})</option>\n";
        };
        ?>
      </select>
      </td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
// End of categories


// Start of operating systems list
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "os") {
?>
  <h3 class="subheading"><?php echo $admin_text['oslist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
    </tr>
    <?php
    $get_os = $fs->dbQuery("SELECT * FROM flyspray_list_os ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_os)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_list">
      <input type="hidden" name="list_type" value="os">
      <input type="hidden" name="id" value="<?php echo $row['os_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name" value="<?php echo $row['os_name'];?>"></td>
      <td title="The order these items will appear in the Operating System list"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td title="Show this item in the Operating System list" align="center"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="4"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_to_list">
      <input type="hidden" name="list_type" value="os">
      <input type="hidden" name="id" value="<?php echo $row['os_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
// End of operating systems

// Start of Resolutions
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "resolution") {
?>
  <h3 class="subheading"><?php echo $admin_text['resolutionlist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
    </tr>
    <?php
    $get_resolution = $fs->dbQuery("SELECT * FROM flyspray_list_resolution ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_resolution)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_list">
      <input type="hidden" name="list_type" value="resolution">
      <input type="hidden" name="id" value="<?php echo $row['resolution_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name" value="<?php echo $row['resolution_name'];?>"></td>
      <td title="The order these items will be shown in the Resolution list"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td title="Show this item in the Resolution list" align="center"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="4"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_to_list">
      <input type="hidden" name="list_type" value="resolution">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
// End of Resolutions

// Start of versions
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "version") {
?>
  <h3 class="subheading"><?php echo $admin_text['versionlist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
    </tr>
    <?php
    $get_version = $fs->dbQuery("SELECT * FROM flyspray_list_version ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_version)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_list">
      <input type="hidden" name="list_type" value="version">
      <input type="hidden" name="id" value="<?php echo $row['version_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name" value="<?php echo $row['version_name'];?>"></td>
      <td title="The order the items are shown in the Version list"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td align="center" title="Show this item in the Version list"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="4"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_to_list">
      <input type="hidden" name="list_type" value="version">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="list_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
// End of Versions

// Start of Severities
/*
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "severity") {
?>
  <h3 class="subheading"><?php echo $admin_text['severitylist'];?></h3>
  <i class="admintext"><?php echo $admin_text['listnote'];?></i>
  <table class="admin">
    <tr>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['name'];?></td>
      <td class="adminlabel"><?php echo $admin_text['order'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['back'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['text'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['highlight'];?></td>
      <td class="adminlabel" style="text-align:center;"><?php echo $admin_text['show'];?></td>
    </tr>
    <?php
    $get_severity = $fs->dbQuery("SELECT * FROM flyspray_list_severity ORDER BY list_position");
    while ($row = $fs->dbFetchArray($get_severity)) {
    ?>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="update_severity">
      <input type="hidden" name="id" value="<?php echo $row['severity_id'];?>">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="severity_name" value="<?php echo $row['severity_name'];?>"></td>
      <td title="The order the items will be shown in the Severity List"><input class="admintext" type="text" size="3" maxlength="3" name="list_position" value="<?php echo $row['list_position'];?>"></td>
      <td title="The background colour for tasks of this severity"><input name="back_colour" type="text" size="6" maxlength="6" style="background-color: #<?php echo $row['back_colour'];?>; color:#<?php echo $row['fore_colour'];?>" value="<?php echo $row['back_colour'];?>"></td>
      <td title="The text colour for tasks of this severity"><input name="fore_colour" type="text" size="6" maxlength="6" style="background-color: #<?php echo $row['back_colour'];?>; color:#<?php echo $row['fore_colour'];?>" value="<?php echo $row['fore_colour'];?>"></td>
      <td title="The highlight colour for tasks of this severity"><input name="highlight_colour" type="text" size="6" maxlength="6" style="background-color: #<?php echo $row['highlight_colour'];?>; color:#<?php echo $row['fore_colour'];?>" value="<?php echo $row['highlight_colour'];?>"></td>
      <td title="Show this item in the Severity list" align="center"><input class="admintext" type="checkbox" name="show_in_list" value="1" <?php if ($row['show_in_list'] == '1') { echo "CHECKED";};?>>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>"></td>
    </form>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td height="15" colspan="7"><hr></td>
    </tr>
    <tr>
    <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="add_severity">
      <td><input class="admintext" type="text" size="15" maxlength="20" name="severity_name"></td>
      <td><input class="admintext" type="text" size="3" maxlength="3" name="list_position"></td>
      <td><input class="admintext" type="text" size="6" maxlength="6" name="back_colour"></td>
      <td><input class="admintext" type="text" size="6" maxlength="6" name="highlight_colour"></td>
      <td><input class="admintext" type="text" size="6" maxlength="6" name="fore_colour"></td>
      <td><input class="admintext" type="checkbox" name="show_in_list" CHECKED DISABLED/></td>
      <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>"></td>
    </form>
    </tr>
  </table>


<?
*/
// End of Severities


// Start of application preferences
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "options") {
?>
<h3 class="subheading"><?php echo $admin_text['flysprayprefs'];?></h3>
  <form action="index.php" method="post">
<table class="admin">
  <tr>
    <td class="adminlabel">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="globaloptions">
    <?php echo $admin_text['projecttitle'];?></td>
    <td class="admintext"><input class="admintext" name="project_title" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['project_title'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['baseurl'];?></td>
    <td class="admintext"><input class="admintext" name="base_url" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['base_url'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['replyaddress'];?></td>
    <td class="admintext"><input class="admintext" name="admin_email" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['admin_email'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['language'];?></td>
    <td class="admintext">
    <select class="adminlist" name="lang_code">
    <?php
    // Let's get a list of the available languages by reading the ./lang/ directory.
     if ($handle = opendir('lang/')) {
      $lang_array = array();
       while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && file_exists("lang/$file/main.php")) {
          array_push($lang_array, $file);
        }
      }
      closedir($handle);
    }

    // Sort the array alphabetically
    sort($lang_array);
    // Then display them
    while (list($key, $val) = each($lang_array)) {
      // If the theme is currently being used, pre-select it in the list
      if ($val == $flyspray_prefs['lang_code']) {
        echo "<option class=\"adminlist\" SELECTED>$val</option>\n";
        // If it's not, don't pre-select it
      } else {
      echo "<option class=\"adminlist\">$val</option>\n";
      };
    };
    echo "</select></td>";
    ?>
    </select>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['themestyle'];?></td>
    <td  align="left" class="admintext"><select class="adminlist" name="theme_style">
    <?php
    // Let's get a list of the theme names by reading the ./themes/ directory
    if ($handle = opendir('themes/')) {
      $theme_array = array();
       while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && file_exists("themes/$file/theme.css")) {
          array_push($theme_array, $file);
        }
      }
      closedir($handle);
    }

    // Sort the array alphabetically
    sort($theme_array);
    // Then display them
    while (list($key, $val) = each($theme_array)) {
      // If the theme is currently being used, pre-select it in the list
      if ($val == $flyspray_prefs['theme_style']) {
        echo "<option class=\"adminlist\" SELECTED>$val</option>\n";
        // If it's not, don't pre-select it
      } else {
      echo "<option class=\"adminlist\">$val</option>\n";
      };
    };
    echo "</select></td>";
    ?>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['anonview']; ?></td>
    <td align="left" class="admintext"><input type="checkbox" name="anon_view" value="1" <?php if ($flyspray_prefs['anon_view'] == '1') { echo "CHECKED";};?>>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['allowanon'];?></td>
    <td align="left" class="admintext">
    <select class="adminlist" name="anon_open">
      <option value="0" <?php if ($flyspray_prefs['anon_open'] == "0") { echo "SELECTED";};?>><?php echo $admin_text['never'];?></option>
      <option value="1" <?php if ($flyspray_prefs['anon_open'] == "1") { echo "SELECTED";};?>><?php echo $admin_text['anonymously'];?></option>
      <option value="2" <?php if ($flyspray_prefs['anon_open'] == "2") { echo "SELECTED";};?>><?php echo $admin_text['afterregister'];?></option>
    </select>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['spamproof']; ?></td>
    <td align="left" class="admintext"><input type="checkbox" name="spam_proof" value="1" <?php if ($flyspray_prefs['spam_proof'] == '1') { echo "CHECKED";};?>>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['anongroup'];?></td>
    <td align="left" class="admintext">
    <select class="adminlist" name="anon_group">
      <?php // Get the group names
      $get_group_details = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups ORDER BY group_id ASC");
      while ($row = $fs->dbFetchArray($get_group_details)) {
        if ($flyspray_prefs['anon_group'] == $row['group_id']) {
          echo "<option value=\"{$row['group_id']}\" SELECTED>{$row['group_name']}</option>";
        } else {
          echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
        };
      };
      ?>
    </select>
    </td>
  </tr>
  <tr>
    <td class="adminlabel" valign="top"><?php echo $admin_text['groupassigned'];?></td>
    <td class="admintext">
    <?php // Get the group names
      $get_group_details = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups ORDER BY group_id ASC");
      while ($row = $fs->dbFetchArray($get_group_details)) {
        if (ereg($row['group_id'], $flyspray_prefs['assigned_groups']) ) {
          echo "<input type=\"checkbox\" name=\"assigned_groups{$row['group_id']}\" value=\"{$row['group_id']}\" CHECKED>{$row['group_name']}<br>\n";
        } else {
          echo "<input type=\"checkbox\" name=\"assigned_groups{$row['group_id']}\" value=\"{$row['group_id']}\">{$row['group_name']}<br>\n";
        };
      };
      ?>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['forcenotify'];?></td>
    <td align="left" class="admintext">
    <select class="adminlist" name="user_notify">
      <option value="0" <?php if ($flyspray_prefs['user_notify'] == "0") { echo "SELECTED";};?>><?php echo $admin_text['none'];?></option>
      <option value="1" <?php if ($flyspray_prefs['user_notify'] == "1") { echo "SELECTED";};?>><?php echo $admin_text['userchoose'];?></option>
      <option value="2" <?php if ($flyspray_prefs['user_notify'] == "2") { echo "SELECTED";};?>><?php echo $admin_text['email'];?></option>
      <option value="3" <?php if ($flyspray_prefs['user_notify'] == "3") { echo "SELECTED";};?>><?php echo $admin_text['jabber'];?></option>
    </select>
    </td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['defaultcatowner'];?>:</td>
    <td align="left" class="admintext">
    <select class="adminlist" name="default_cat_owner">
      <option value=""><?php echo $admin_text['noone'];?></option>
      <?php
      //$dev_list = $fs->dbQuery("SELECT user_id, user_name, real_name FROM flyspray_users WHERE group_in = '1' OR group_in = '2'");
      $dev_list = $fs->dbQuery($fs->listUserQuery());
      while ($row = $fs->dbFetchArray($dev_list)) {
        if ($flyspray_prefs['default_cat_owner'] == $row['user_id']) {
          echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']}</option>";
        } else {
          echo "<option value=\"{$row['user_id']}\">{$row['real_name']}</option>";
        };
      };
      ?>
    </select>
    </td>
  </tr>
  <tr>
    <td colspan="2"><hr></td>
  </tr>
  <tr>
    <td class="adminlabel" colspan="2" style="text-align: center;"><?php echo $admin_text['jabbernotify'];?></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['jabberserver'];?></td>
    <td class="admintext"><input class="admintext" name="jabber_server" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_server'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['jabberport'];?></td>
    <td class="admintext"><input class="admintext" name="jabber_port" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_port'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['jabberuser'];?></td>
    <td class="admintext"><input class="admintext" name="jabber_username" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_username'];?>"></td>
  </tr>
  <tr>
    <td class="adminlabel"><?php echo $admin_text['jabberpass'];?></td>
    <td class="admintext"><input class="admintext" name="jabber_password" type="password" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_password'];?>"></td>
  </tr>
  <tr>
    <td colspan="2"><hr></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $admin_text['saveoptions'];?>"></td>
  </tr>

</table>
  </form>


<?php
// End of application preferences

// Start of editing a comment
} elseif ($_SESSION['admin'] =="1" && $_GET['area'] == "editcomment") {

// Get the comment details
    $getcomments = $fs->dbQuery("SELECT * FROM flyspray_comments WHERE comment_id = '{$_GET['id']}'");
    while ($row = $fs->dbFetchArray($getcomments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = '{$row['user_id']}'");
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = date("l, j M Y, g:ia", $row['date_added']);

      $comment_text = str_replace("<br>", "\n", "{$row['comment_text']}");
      $comment_text = stripslashes($comment_text);
    };
?>
<h3><?php echo $admin_text['editcomment'];?></h3>
<form action="index.php" method="post">
<table class="admin">
  <tr>
    <td class="adminlabel"><?php echo "{$admin_text['commentby']} $user_name - $formatted_date";?></td>
  </tr>
  <tr>
    <td class="admintext">
    <textarea class="admintext" cols="50" rows="10" name="comment_text"><?php echo $comment_text;?></textarea>
    </td>
  </tr>
  <tr>
    <td class="admintext" align="center">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="editcomment">
    <input type="hidden" name="task_id" value="<?php echo $_GET['task_id'];?>">
    <input type="hidden" name="comment_id" value="<?php echo $_GET['id'];?>">
    <input class="adminbutton" type="submit" value="<?php echo $admin_text['saveeditedcomment'];?>">
    </td>
  </tr>
</table>
</form>

<?php
// End of areas
};
?>
