<div id="detailspage">

<?php

// Check if the user is an admin

require("lang/$lang/newtask.php");

if ($_SESSION['can_open_jobs'] == "1" OR $flyspray_prefs['anon_open'] == "1") {
?>

<h3 class="subheading"><?php echo $newtask_text['createnewtask'];?></h3>

<!-- create some columns -->
    <form name="form1" action="index.php" method="post">
<table class="admin" width="98%">
  <tr>
    <td class="adminlabel" colspan="2" style="text-align: left;">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="newtask">

    <?php echo $newtask_text['summary'];?>&nbsp;&nbsp;<input class="admintext" type="text" name="item_summary" size="50" maxlength="100">
    </td>
  </tr>
  <tr>
    <!-- left column -->
    <td width="50%" valign="top">

    <!-- content for left column -->
    <table>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['tasktype'];?></td>
        <td>
        <select class="adminlist" name="task_type">
        <?php
        // Get list of task types
        $get_severity = $fs->dbQuery("SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_severity)) {
            echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>";
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['category'];?></td>
        <td>
        <select class="adminlist" name="product_category">
        <?php
        // Get list of categories
        $get_categories = $fs->dbQuery("SELECT category_id, category_name FROM flyspray_list_category WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_categories)) {
          echo "<option value=\"{$row['category_id']}\">{$row['category_name']}</option>";
        };
        ?></select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['status'];?></td>
        <td>
        <select class="adminlist" name="item_status" <?php if ($_SESSION['can_modify_jobs'] != "1") { echo "style=\"background-color: #969696\" DISABLED";};?>>
        <?php
        // let's get a list of statuses and compare it to the saved one
        /*$get_statuses = $fs->dbQuery("SELECT status_id, status_name FROM flyspray_list_status WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_statuses)) {
          echo "<option value=\"{$row['status_id']}\">{$row['status_name']}</option>\n";
        };*/
        require("lang/$lang/status.php");
        foreach($status_list as $key => $val) {
          echo "<option value=\"$key\">$val</option>\n";
        };
        ?>
        </select></td>
      </tr>
      <?php
      // If the user can't modify jobs, we will have to set a hidden field for the status
      if ($_SESSION['can_modify_jobs'] != "1") {
        echo "<input type=\"hidden\" name=\"item_status\" value=\"1\">";
      };
      ?>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['assignedto'];?></td>
        <td>
        <select class="adminlist" name="assigned_to" <?php if ($_SESSION['can_modify_jobs'] != "1") { echo "style=\"background-color: #969696\" DISABLED";};?>>
        <?php
        // Get list of users
        $get_users = $fs->dbQuery($fs->listUserQuery());

        echo "<option value=\"0\">No-one</option>\n";
        while ($row = $fs->dbFetchArray($get_users)) {
          echo "<option value=\"{$row['user_id']}\">{$row['real_name']} ({$row['user_name']})</option>\n";
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['operatingsystem'];?></td>
        <td>
        <select class="adminlist" name="operating_system">
        <?php
        // Get list of operating systems
        $get_os = $fs->dbQuery("SELECT os_id, os_name FROM flyspray_list_os WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_os)) {
          echo "<option value=\"{$row['os_id']}\">{$row['os_name']}</option>";
        };
        ?>
        </select>
        </td>
      </tr>

    </table>

    <!-- end of left column and start of right column -->
    </td>
    <td width="50%" valign="top">
    <table>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['severity'];?></td>
        <td>
        <select class="adminlist" name="task_severity">
        <?php
        // Get list of severities
        /*$get_severity = $fs->dbQuery("SELECT severity_id, severity_name FROM flyspray_list_severity WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_severity)) {
          echo "<option value=\"{$row['severity_id']}\">{$row['severity_name']}</option>";
        };*/
      require("lang/$lang/severity.php");
      foreach($severity_list as $key => $val) {
          echo "<option value=\"$key\">$val</option>\n";
      };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['reportedversion'];?></td>
        <td>
        <select class="adminlist" name="product_version">
        <?php
        // Get list of versions
        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_version)) {
          echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
        };
        ?>
        </select>
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $newtask_text['dueinversion'];?></td>
        <td>
        <select class="adminlist" name="closedby_version" <?php if ($_SESSION['can_modify_jobs'] != "1") { echo "style=\"background-color: #969696\" DISABLED";};?>>
        <?php
        echo "<option value=\"\">Undecided</option>\n";

        $get_version = $fs->dbQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE show_in_list = '1' ORDER BY list_position");
        while ($row = $fs->dbFetchArray($get_version)) {
          echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
        };
        ?>
        </select>
        </td>
      </tr>
      <!--<tr>
        <td class="adminlabel">Related task:</td>
        <td>
        <input class="admintext" type="text" name="related_task_id" size="10" maxlength="10">
        </td>
      </tr>-->
    </table>
    <!-- end of right column -->
    </td>
  </tr>
  <tr>
    <td colspan="2">
    <table>
      <tr>
        <td class="adminlabel" valign="top"><?php echo $newtask_text['details'];?></td>
        <td>
        <textarea class="admintext" name="detailed_desc" cols="60" rows="10"></textarea>
        </td>
      </tr>
    </table>
    </td>
  </tr>
  <tr>
    <td class="admintext" align="right">
    <br><br>
    <?php echo $newtask_text['addanother'];?>&nbsp;&nbsp;<input class="admintext" type="checkbox" name="addmore" value="1">
    </td>
    <td align="left">
    <br><br>
    <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $newtask_text['addthistask'];?>" onclick="Disable1()">
    </td>
  </tr>
</table>
    </form>
<br><br>

<?php
// ####################################################################################
// ####################################################################################
// If the user isn't an admin
} else {

echo $newtask_text['nopermission'];

// End of checking admin status
};
?>
</div>
