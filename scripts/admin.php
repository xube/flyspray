<?php
/*
   --------------------------------------------------
   | Administrator's Toolbox                        |
   | =======================                        |
   | This script allows members of a global Admin   |
   | group to modify the global preferences, user   |
   | profiles, global lists, global groups, pretty  |
   | much everything global.                        |
   --------------------------------------------------
*/

$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'admin');
get_language_pack($lang, 'index');

// This generates an URL so that the action script takes us back to the previous page
$this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
$this_page = str_replace('&', '&amp;', $this_page);

// The user must be a member of the global "Admin" group to use this page
if ($permissions['is_admin'] == '1')
{

   // Show the menu that stays visible, regardless of which area we're in
   echo '<div id="toolboxmenu">';

   echo '<small>|</small><a id="globprefslink" href="?do=admin&amp;area=prefs">' . $admin_text['preferences'] . '</a>';
   echo '<small>|</small><a id="globuglink" href="?do=admin&amp;area=groups">' . $admin_text['usergroups'] . '</a>';
   echo '<small>|</small><a id="globttlink" href="?do=admin&amp;area=tt">' . $admin_text['tasktypes'] . '</a>';
   echo '<small>|</small><a id="globreslink" href="?do=admin&amp;area=res">' . $admin_text['resolutions'] . '</a>';
   echo '<small>|</small><a id="globcatlink" href="?do=admin&amp;area=cat">' . $admin_text['categories'] . '</a>';
   echo '<small>|</small><a id="globoslink" href="?do=admin&amp;area=os">' . $admin_text['operatingsystems'] . '</a>';
   echo '<small>|</small><a id="globverlink" href="?do=admin&amp;area=ver">' . $admin_text['versions'] . '</a>';

   // End of the toolboxmenu
   echo '</div>';


   echo '<div id="toolbox">';

   //////////////////////////////////////
   // Start of application preferences //
   //////////////////////////////////////

   if ($_GET['area'] == "prefs")
   {
      echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['preferences'] . '</h3>';
      ?>

      <fieldset class="admin">

      <legend><?php echo $admin_text['general'];?></legend>

      <form action="index.php" method="post">

      <table class="admin">
         <tr>
            <td>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="globaloptions" />
            <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
            <label for="baseurl"><?php echo $admin_text['baseurl'];?></label>
            </td>
            <td>
            <input id="baseurl" name="base_url" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['base_url'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="adminemail"><?php echo $admin_text['replyaddress'];?></label>
            </td>
            <td>
            <input id="adminemail" name="admin_email" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['admin_email'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="defaultproject"><?php echo $admin_text['defaultproject'];?></label>
            </td>
            <td>
            <select name="default_project">
            <?php
            $get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects");
            while ($row = $fs->dbFetchArray($get_projects)) {
               if ($flyspray_prefs['default_project'] == $row['project_id']) {
                  echo '<option value="' . $row['project_id'] . '" SELECTED>' . stripslashes($row['project_title']) . '</option>';
               } else {
                  echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
               };
            };
            ?>
            </select>
            </td>
         </tr>
         <tr>
            <td>
            <label for="langcode"><?php echo $admin_text['language'];?></label>
            </td>
            <td>
            <select id="langcode" name="lang_code">
            <?php
            // Let's get a list of the available languages by reading the ./lang/ directory.
            if ($handle = opendir('lang/')) {
               $lang_array = array();
               while (false !== ($file = readdir($handle))) {
                  if ($file != "." && $file != ".." && file_exists("lang/$file/main.php"))
                  {
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
                  echo "<option class=\"adminlist\" selected=\"selected\">$val</option>\n";
                  // If it's not, don't pre-select it
               } else {
                  echo "<option class=\"adminlist\">$val</option>\n";
               };
            };
            ?>
            </select>
            </td>
         </tr>

         <tr>
            <td>
            <label for="dateformat"><?php echo $admin_text['dateformat'];?></label>
            </td>
            <td>
            <input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="<?php echo $flyspray_prefs['dateformat'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="dateformat_extended"><?php echo $admin_text['dateformat_extended'];?></label>
            </td>
            <td>
            <input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="<?php echo $flyspray_prefs['dateformat_extended'];?>" />
            </td>
         </tr>

      </table>

      </fieldset>

      <fieldset class="admin">

      <legend><?php echo $admin_text['userregistration'];?></legend>

      <table class="admin">
         <tr>
            <td>
            <label for="allowusersignups"><?php echo $admin_text['anonreg'];?></label>
            </td>
            <td>
            <input id="allowusersignups" type="checkbox" name="anon_reg" value="1" <?php if ($flyspray_prefs['anon_reg'] == '1') { echo "checked=\"checked\"";};?> />
            </td>
         </tr>
         <tr>
            <td>
            <label for="spamproof"><?php echo $admin_text['spamproof']; ?></label>
            </td>
            <td>
            <input id="spamproof" type="checkbox" name="spam_proof" value="1" <?php if ($flyspray_prefs['spam_proof'] == '1') { echo "checked=\"checked\"";};?> />
            </td>
         </tr>
         <tr>
            <td>
            <label for="defaultglobalgroup"><?php echo $admin_text['defaultglobalgroup'];?></label>
            </td>
            <td>
            <select id="defaultglobalgroup" name="anon_group">
            <?php // Get the group names
            $get_group_details = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups WHERE belongs_to_project = '0' ORDER BY group_id ASC");
            while ($row = $fs->dbFetchArray($get_group_details)) {
               if ($flyspray_prefs['anon_group'] == $row['group_id']) {
                  echo "<option value=\"{$row['group_id']}\" selected=\"selected\">{$row['group_name']}</option>";
               } else {
                  echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
               };
            };
            ?>
            </select>
            </td>
         </tr>
         <tr>
            <td><label><?php echo $admin_text['groupassigned'];?></label></td>
            <td class="admintext">
            <?php // Get the group names
            $get_group_details = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups WHERE belongs_to_project = '0' ORDER BY group_id ASC");
            while ($row = $fs->dbFetchArray($get_group_details)) {
               if (ereg($row['group_id'], $flyspray_prefs['assigned_groups']) ) {
                  echo "<input type=\"checkbox\" name=\"assigned_groups{$row['group_id']}\" value=\"{$row['group_id']}\" checked=\"checked\" />{$row['group_name']}<br />\n";
               } else {
                  echo "<input type=\"checkbox\" name=\"assigned_groups{$row['group_id']}\" value=\"{$row['group_id']}\" />{$row['group_name']}<br />\n";
               };
            };
            ?>
            </td>
         </tr>
      </table>

      </fieldset>

      <fieldset class="admin">

      <legend><?php echo $admin_text['notifications'];?></legend>

      <table class="admin">
         <tr>
            <td>
            <label for="usernotify"><?php echo $admin_text['forcenotify'];?></label>
            </td>
            <td>
            <select id="usernotify" name="user_notify">
               <option value="0" <?php if ($flyspray_prefs['user_notify'] == "0") { echo "selected=\"selected\"";};?>><?php echo $admin_text['none'];?></option>
               <option value="1" <?php if ($flyspray_prefs['user_notify'] == "1") { echo "selected=\"selected\"";};?>><?php echo $admin_text['userchoose'];?></option>
               <option value="2" <?php if ($flyspray_prefs['user_notify'] == "2") { echo "selected=\"selected\"";};?>><?php echo $admin_text['email'];?></option>
               <option value="3" <?php if ($flyspray_prefs['user_notify'] == "3") { echo "selected=\"\"";};?>><?php echo $admin_text['jabber'];?></option>
            </select>
            </td>
         </tr>

         <tr>
            <th colspan="2"><hr />
            <?php echo $admin_text['jabbernotify'];?>
            </th>
         </tr>
         <tr>
            <td>
            <label for="jabberserver"><?php echo $admin_text['jabberserver'];?></label>
            </td>
            <td>
            <input id="jabberserver" name="jabber_server" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_server'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="jabberport"><?php echo $admin_text['jabberport'];?></label>
            </td>
            <td>
            <input id="jabberport" name="jabber_port" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_port'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="jabberusername"><?php echo $admin_text['jabberuser'];?></label>
            </td>
            <td>
            <input id="jabberusername" name="jabber_username" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_username'];?>" />
            </td>
         </tr>
         <tr>
            <td>
            <label for="jabberpassword"><?php echo $admin_text['jabberpass'];?></label>
            </td>
            <td>
            <input id="jabberpassword" name="jabber_password" type="password" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_password'];?>" />
            </td>
         </tr>
      </table>

      </fieldset>

      <table>
         <tr>
            <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['saveoptions'];?>" /></td>
            <td class="buttons"><input class="adminbutton" type="reset" value="<?php echo $admin_text['resetoptions'];?>" /></td>
         </tr>
      </table>
      </form>


      <?php
      // End of application preferences

////////////////////////////
// Start of editing users //
////////////////////////////

   } elseif (isset($_GET['area']) && $_GET['area'] == "users" && isset($_GET['id']))
   {

         $get_user_details = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($_GET['id']));
         $user_details = $fs->dbFetchArray($get_user_details);

         echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['edituser'] . ': ' . $user_details['user_name'] . '</h3>';
         ?>
         <form action="index.php" method="post">
         <table class="admin">
            <tr>
               <td>
               <input type="hidden" name="do" value="modify" />
               <input type="hidden" name="action" value="edituser" />
               <input type="hidden" name="user_id" value="<?php echo $user_details['user_id'];?>" />
               <label for="realname"><?php echo $admin_text['realname'];?></label>
               </td>
               <td><input id="realname" type="text" name="real_name" size="50" maxlength="100" value="<?php echo $user_details['real_name'];?>" /></td>
            </tr>
            <tr>
               <td><label for="emailaddress"><?php echo $admin_text['emailaddress'];?></label></td>
               <td><input id="emailaddress" type="text" name="email_address" size="50" maxlength="100" value="<?php echo $user_details['email_address'];?>" /></td>
            </tr>
            <tr>
               <td><label for="jabberid"><?php echo $admin_text['jabberid'];?></label></td>
               <td><input id="jabberid" type="text" name="jabber_id" size="50" maxlength="100" value="<?php echo $user_details['jabber_id'];?>" /></td>
            </tr>
            <tr>
               <td><label for="notifytype"><?php echo $admin_text['notifytype'];?></label></td>
               <td>
               <?php if ($flyspray_prefs['user_notify'] == '1') { ?>
               <select id="notifytype" name="notify_type">
                  <option value="0" <?php if ($user_details['notify_type'] == "0") {echo "selected=\"selected\"";};?>>None</option>
                  <option value="1" <?php if ($user_details['notify_type'] == "1") {echo "selected=\"selected\"";};?>>Email</option>
                  <option value="2" <?php if ($user_details['notify_type'] == "2") {echo "selected=\"selected\"";};?>>Jabber</option>
               </select>
               <?php
               } else {
                  echo $admin_text['setglobally'];
               }; ?>
               </td>
            </tr>
            <tr>
               <td><label for="dateformat"><?php echo $admin_text['dateformat'];?></label></td>
               <td><input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="<?php echo $user_details['dateformat'];?>" /></td>
            </tr>
            <tr>
               <td><label for="dateformat_extended"><?php echo $admin_text['dateformat_extended'];?></label></td>
               <td><input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="<?php echo $user_details['dateformat_extended'];?>" /></td>
            </tr>
            <?php
            // This is for changing the user's global group ONLY
            if ($permissions['is_admin'] == '1') {
            ?>
            <tr>
               <td><label for="groupin"><?php echo $admin_text['globalgroup'];?></label></td>
               <td>
               <select id="groupin" name="group_in">
               <?php
               // Get the groups list
               $current_global_group = $fs->dbFetchArray($fs->dbQuery("SELECT * FROM flyspray_users_in_groups uig
                                                                        LEFT JOIN flyspray_groups g ON uig.group_id = g.group_id
                                                                        WHERE uig.user_id = ? AND g.belongs_to_project = ?
                                                                        ORDER BY g.group_id ASC",
                                                                        array($user_details['user_id'], '0')));

               // Now, get the list of global groups and compare for display
               $global_groups = $fs->dbQuery("SELECT * FROM flyspray_groups
                                              WHERE belongs_to_project = ?",
                                              array('0'));
               while ($row = $fs->dbFetchArray($global_groups)) {
                  if ($row['group_id'] == $current_global_group['group_id']) {
                     echo "<option value=\"{$row['group_id']}\" selected=\"selected\">{$row['group_name']}</option>";
                  } else {
                     echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
                  };
               };
               ?>
               </select>
               <input type="hidden" name="record_id" value="<?php echo $current_global_group['record_id'];?>" />
               </td>
            </tr>
            <tr>
               <td><label for="accountenabled"><?php echo $admin_text['accountenabled'];?></label></td>
               <td><input id="accountenabled" type="checkbox" name="account_enabled" value="1" <?php if ($user_details['account_enabled'] == "1") {echo "checked=\"checked\"";};?> /></td>
            </tr>
            <?php
            };
            ?>
            <tr>
               <td colspan="2"><hr /></td>
            </tr>
            <tr>
               <td><label for="changepass"><?php echo $admin_text['changepass'];?></label></td>
               <td><input id="changepass" type="password" name="changepass" size="40" maxlength="100" /></td>
            </tr>
            <tr>
               <td><label for="confirmpass"><?php echo $admin_text['confirmpass'];?></label></td>
               <td><input id="confirmpass" type="password" name="confirmpass" size="40" maxlength="100" /></td>
            </tr>
            <tr>
               <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
            </tr>
         </table>
         </form>

      <?php
      /////////////////////////////////
      // Start of the groups manager //
      /////////////////////////////////

      } elseif (isset($_GET['area']) && $_GET['area'] == 'groups')
      {
         echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['usergroups'] . '</h3>';

         echo '<fieldset class="admin">';
         echo '<legend>' . $admin_text['usergroups'] . '</legend>';


         // Only full admins need the link to add new users
         if ($project_id == '0' && $permissions['is_admin'] == '1') {
            echo "<p><a href=\"index.php?do=newuser\">{$admin_text['newuser']}</a> | \n";
         };

            echo "<a href=\"index.php?do=newgroup&amp;project=0\">{$admin_text['newgroup']}</a></p>\n\n";

            // We have to make sure that a user isn't displayed in the user list at the bottom of the page
            // if they're in a group from another project... so we set up an array...
            $user_checklist = array();

            // Cycle through the groups that belong to this project
            $get_groups = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE belongs_to_project = ? ORDER BY group_id ASC", array($project));
            while ($group = $fs->dbFetchArray($get_groups)) {

               echo '<h4><a href="?do=admin&amp;area=editgroup&amp;id=' . $group['group_id'] . '">' . stripslashes($group['group_name']) . '</a></h4>' . "\n";
               echo '<p>' . stripslashes($group['group_desc']) . "</p>\n";

               // Now, start a form to allow use to move multiple users between groups
               echo '<form action="index.php" method="post">' . "\n";

               echo "<table class=\"userlist\">\n<tr><th></th><th>{$admin_text['username']}</th><th>{$admin_text['realname']}</th><th>{$admin_text['accountenabled']}</th></tr>\n";

               $get_user_list = $fs->dbQuery("SELECT * FROM flyspray_users_in_groups uig
                                             LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                             WHERE uig.group_id = ? ORDER BY u.user_name ASC",
                                             array($group['group_id']));


               echo '<input type="hidden" name="do" value="modify" />' . "\n";
               echo '<input type="hidden" name="action" value="movetogroup" />' . "\n";
               echo '<input type="hidden" name="old_group" value="' . $group['group_id'] . '" />' . "\n";
               echo '<input type="hidden" name="project_id" value="' . $project_id . '" />'. "\n";
               echo '<input type="hidden" name="prev_page" value="' . $this_page . '" />'. "\n";

               $userincrement = 0;
               while ($row = $fs->dbFetchArray($get_user_list)) {
                  // Next line to ensure we only display each user once on this page
                  array_push($user_checklist, $row['user_id']);
                  // Now, assign each user a number for submission
                  $userincrement ++;
                  echo "<tr><td><input type=\"checkbox\" name=\"user$userincrement\" value=\"{$row['user_id']}\" /></td>\n";
                  echo "<td><a href=\"?do=admin&amp;area=users&amp;id={$row['user_id']}\">{$row['user_name']}</a></td>\n";
                  echo "<td>{$row['real_name']}</td>\n";
                  if ($row['account_enabled'] == "1") {
                     echo "<td>{$admin_text['yes']}</td>";
                  } else {
                     echo "<td>{$admin_text['no']}</td>";
                  };
                  echo "</tr>\n";
               };

               echo '<tr><td colspan="4">';
               echo '<input type="hidden" name="num_users" value="' . $userincrement . "\" />\n";
               echo '<input class="adminbutton" type="submit" value="' . $admin_text['moveuserstogroup'] . '" />' . "\n";

               // Show a list of groups to switch these users to
               echo '<select class="adminlist" name="switch_to_group">'. "\n";

               // Get the list of groups to choose from
               $groups = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE belongs_to_project = ? ORDER BY group_id ASC", array($project));
               while ($group = $fs->dbFetchArray($groups)) {
                  echo '<option value="' . $group['group_id'] . '">' . htmlspecialchars(stripslashes($group['group_name'])) . "</option>\n";
               };

               echo '</select>';

               echo '</td></tr>';
               echo "</table>\n\n";
               echo '</form>';
            };

   /////////////////////////////
   // Start of editing groups //
   /////////////////////////////
   } elseif (isset($_GET['area']) && $_GET['area'] == "editgroup")
   {
      echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['editgroup'] . '</h3>';

      $get_group_details = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = ?", array($_GET['id']));
      $group_details = $fs->dbFetchArray($get_group_details);


?>

  <form action="index.php?project=<?php echo $group_details['belongs_to_project'];?>" method="post">
  <table class="admin">
    <tr>
      <td>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="editgroup" />
      <input type="hidden" name="group_id" value="<?php echo $group_details['group_id'];?>" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />

      <label for="groupname"><?php echo $admin_text['groupname'];?></label></td>
      <td><input id="groupname" type="text" name="group_name" size="20" maxlength="20" value="<?php echo htmlspecialchars(stripslashes($group_details['group_name']));?>" /></td>
    </tr>
    <tr>
      <td><label for="groupdesc"><?php echo $admin_text['description'];?></label></td>
      <td><input id="groupdesc" type="text" name="group_desc" size="50" maxlength="100" value="<?php echo htmlspecialchars(stripslashes($group_details['group_desc']));?>" /></td>
    </tr>

    <?php
    // We don't need this stuff shown for the admin group
    if ($_GET['id'] == '1') {
      echo $admin_text['notshownforadmin'];
    } else {
    ?>

    <tr>
      <td><label for="projectmanager"><?php echo $admin_text['projectmanager'];?></label></td>
      <td><input id="projectmanager" type="checkbox" name="manage_project" value="1" <?php if ($group_details['manage_project'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewtasks"><?php echo $admin_text['viewtasks'];?></label></td>
      <td><input id="viewtasks" type="checkbox" name="view_tasks" value="1" <?php if ($group_details['view_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="canopenjobs"><?php echo $admin_text['opennewtasks'];?></label></td>
      <td><input id="canopenjobs" type="checkbox" name="open_new_tasks" value="1" <?php if ($group_details['open_new_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="modifyowntasks"><?php echo $admin_text['modifyowntasks'];?></label></td>
      <td><input id="modifyowntasks" type="checkbox" name="modify_own_tasks" value="1" <?php if ($group_details['modify_own_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="modifyalltasks"><?php echo $admin_text['modifyalltasks'];?></label></td>
      <td><input id="modifyalltasks" type="checkbox" name="modify_all_tasks" value="1" <?php if ($group_details['modify_all_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewcomments"><?php echo $admin_text['viewcomments'];?></label></td>
      <td><input id="viewcomments" type="checkbox" name="view_comments" value="1" <?php if ($group_details['view_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="canaddcomments"><?php echo $admin_text['addcomments'];?></label></td>
      <td><input id="canaddcomments" type="checkbox" name="add_comments" value="1" <?php if ($group_details['add_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="editcomments"><?php echo $admin_text['editcomments'];?></label></td>
      <td><input id="editcomments" type="checkbox" name="edit_comments" value="1" <?php if ($group_details['edit_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="deletecomments"><?php echo $admin_text['deletecomments'];?></label></td>
      <td><input id="deletecomments" type="checkbox" name="delete_comments" value="1" <?php if ($group_details['delete_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="createattachments"><?php echo $admin_text['createattachments'];?></label></td>
      <td><input id="createattachments" type="checkbox" name="create_attachments" value="1" <?php if ($group_details['create_attachments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="deleteattachments"><?php echo $admin_text['deleteattachments'];?></label></td>
      <td><input id="deleteattachments" type="checkbox" name="delete_attachments" value="1" <?php if ($group_details['delete_attachments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewhistory"><?php echo $admin_text['viewhistory'];?></label></td>
      <td><input id="viewhistory" type="checkbox" name="view_history" value="1" <?php if ($group_details['view_history'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="closeowntasks"><?php echo $admin_text['closeowntasks'];?></label></td>
      <td><input id="closeowntasks" type="checkbox" name="close_own_tasks" value="1" <?php if ($group_details['close_own_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="closeothertasks"><?php echo $admin_text['closeothertasks'];?></label></td>
      <td><input id="closeothertasks" type="checkbox" name="close_other_tasks" value="1" <?php if ($group_details['close_other_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="assigntoself"><?php echo $admin_text['assigntoself'];?></label></td>
      <td><input id="assigntoself" type="checkbox" name="assign_to_self" value="1" <?php if ($group_details['assign_to_self'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="assignotherstoself"><?php echo $admin_text['assignotherstoself'];?></label></td>
      <td><input id="assignotherstoself" type="checkbox" name="assign_others_to_self" value="1" <?php if ($group_details['assign_others_to_self'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewreports"><?php echo $admin_text['viewreports'];?></label></td>
      <td><input id="viewreports" type="checkbox" name="view_reports" value="1" <?php if ($group_details['view_reports'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <?php if ($group_details['belongs_to_project'] < '1') { ?>
    <tr>
      <td><label for="groupopen"><?php echo $admin_text['groupenabled'];?></label></td>
      <td><input id="groupopen" type="checkbox" name="group_open" value="1" <?php if ($group_details['group_open'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <?php }; ?>
    <tr>
      <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
    </tr>

    <?php
   // End of hiding this stuff for the admin group
   };
   ?>

  </table>
  </form>

<?php
// End of groups

/////////////////////////
// Start of task types //
/////////////////////////
} elseif ($_GET['area'] == 'tt')
{
   echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['tasktypes'] . '</h3>';
   ?>
   <p><?php echo $admin_text['listnote'];?></p>
   <div class="admin">
   <form action="index.php" method="post">
   <input type="hidden" name="do" value="modify" />
   <input type="hidden" name="action" value="update_list" />
   <input type="hidden" name="list_type" value="tasktype" />
   <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
   <table class="list">
   <?php
   $get_tasktypes = $fs->dbQuery("SELECT * FROM flyspray_list_tasktype ORDER BY list_position");
   $countlines = 0;
   while ($row = $fs->dbFetchArray($get_tasktypes)) {
   ?>
      <tr>
         <td>
         <input type="hidden" name="id[]" value="<?php echo $row['tasktype_id'];?>" />
         <label for="listname<?php echo $countlines?>"><?php echo $admin_text['name'];?></label>
         <input id="listname<?php echo $countlines?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['tasktype_name']));?>" /></td>
         <td title="The order these items will appear in the TaskType list">
         <label for="listposition<?php echo $countlines?>"><?php echo $admin_text['order'];?></label>
         <input id="listposition<?php echo $countlines?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
         </td>
         <td title="Show this item in the TaskType list">
         <label for="showinlist<?php echo $countlines?>"><?php echo $admin_text['show'];?></label>
         <input id="showinlist<?php echo $countlines?>" type="checkbox" name="show_in_list[<?php echo $countlines?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
         </td>
      </tr>
      <?php
      $countlines++;
   };
   ?>
      <tr>
         <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
   </table>
   </form>
   <hr />
   <form action="index.php" method="post">
   <table class="list">
      <tr>
         <td>
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="add_to_list" />
         <input type="hidden" name="list_type" value="tasktype" />
         <label for="listnamenew"><?php echo $admin_text['name'];?></label>
         <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" /></td>
         <td><label for="listpositionnew"><?php echo $admin_text['order'];?></label>
         <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" /></td>
         <td><label for="showinlistnew"><?php echo $admin_text['show'];?></label>
         <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" /></td>
         <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
   </table>
   </form>
   </div>

<?
// End of task types

//////////////////////////
// Start of Resolutions //
//////////////////////////
} elseif ($_GET['area'] == 'res')
{
   echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['resolutions'] . '</h3>';
   ?>

  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
  <form action="index.php" method="post">
  <input type="hidden" name="do" value="modify" />
  <input type="hidden" name="action" value="update_list" />
  <input type="hidden" name="list_type" value="resolution" />
  <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
  <table class="list">
    <?php
    $get_resolution = $fs->dbQuery("SELECT * FROM flyspray_list_resolution ORDER BY list_position");
    $countlines=0;
    while ($row = $fs->dbFetchArray($get_resolution)) {
    ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['resolution_id'];?>" />
          <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['resolution_name']));?>" />
        </td>
        <td title="The order these items will be shown in the Resolution list">
          <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="Show this item in the Resolution list">
          <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
      </tr>
    <?php
      $countlines++;
    };
    ?>
      <tr>
        <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
    </form>
    <hr />
    <form action="index.php" method="post">
    <table class="list">
      <tr>
        <td>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="add_to_list" />
          <input type="hidden" name="list_type" value="resolution" />
          <label for="listnamenew"><?php echo $admin_text['name'];?></label>
          <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
        </td>
        <td>
          <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
          <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
        </td>
        <td>
          <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
          <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
        </td>
        <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
    </form>
</div>

<?
// End of Resolutions



//////////////////////////////////
// Start of project preferences //
//////////////////////////////////

} elseif ($_GET['area'] == "projects"
           && $permissions['manage_project'] == '1')
          {

// Fetch the project details
$project_details = $fs->dbFetchArray($fs->dbQuery("SELECT * FROM flyspray_projects WHERE project_id = ?", array($project_id)));
echo '<h3>' . $admin_text['projectprefs'] . ' - ' . stripslashes($project_details['project_title']) . '</h3>';

echo '<span id="projectmenu">';

// Only show the 'create new project' link if the user is a full admin
if ($permissions['is_admin'] == '1') {
  echo '<small> | </small><a href="?do=newproject">' . $admin_text['createproject'] . '</a>';
};

echo '<small> | </small><a href="?do=admin&amp;area=users&amp;project=' . $project_id . '">' . $admin_text['usergroups'] . '</a>';

//if ($_GET['id']) {

$project_details = $fs->dbFetchArray($fs->dbQuery("SELECT * FROM flyspray_projects WHERE project_id = ?", array($project_id)));
?>
     <small> | </small><a href="?do=admin&amp;area=projects&amp;project=<?php echo $_GET['project'];?>&amp;show=category"><?php echo $language['categories'];?></a>
     <small> | </small><a href="?do=admin&amp;area=projects&amp;project=<?php echo $_GET['project'];?>&amp;show=os"><?php echo $language['operatingsystems'];?></a>
     <small> | </small><a href="?do=admin&amp;area=projects&amp;project=<?php echo $_GET['project'];?>&amp;show=version"><?php echo $language['versions'];?></a>
    </span>

<?php
// By default, show the project prefs first
if ($_GET['show'] == 'prefs') { ?>


<?php
// Show the list of categories
} elseif ($_GET['show'] == 'category') { ?>

<fieldset class="admin">

<legend><?php echo $admin_text['categorylist'];?></legend>

  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
  <form action="index.php" method="post">
  <input type="hidden" name="do" value="modify" />
  <input type="hidden" name="action" value="update_category" />
  <input type="hidden" name="list_type" value="category" />
  <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
  <table class="list">
    <?php
    $get_categories = $fs->dbQuery("SELECT * FROM flyspray_list_category WHERE project_id = ? AND parent_id < ? ORDER BY list_position", array($project_id, '1'));
    $countlines = 0;
    while ($row = $fs->dbFetchArray($get_categories)) {
    ?>
     <tr>
      <td>
        <input type="hidden" name="id[]" value="<?php echo $row['category_id'];?>" />
        <label for="categoryname<?php echo $countlines; ?>"><?php echo $admin_text['name'];?></label>
        <input id="categoryname<?php echo $countlines; ?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['category_name']));?>" />
      </td>
      <td title="<?php echo $admin_text['listordertip'];?>">
        <label for="listposition<?php echo $countlines; ?>"><?php echo $admin_text['order'];?></label>
        <input id="listposition<?php echo $countlines; ?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
      </td>
      <td title="<?php echo $admin_text['listshowtip'];?>">
        <label for="showinlist<?php echo $countlines; ?>"><?php echo $admin_text['show'];?></label>
        <input id="showinlist<?php echo $countlines; ?>" type="checkbox" name="show_in_list[<?php echo $countlines; ?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
      </td>
      <td title="<?php echo $admin_text['categoryownertip'];?>">
        <label for="categoryowner<?php echo $countlines; ?>"><?php echo $admin_text['owner'];?></label>
        <select id="categoryowner<?php echo $countlines; ?>" name="category_owner[]">
        <option value=""><?php echo $admin_text['selectowner'];?></option>
        <?php
        $fs->listUsers($row['category_owner'], $project_id);
        ?>
      </select>
      </td>
     </tr>
    <?php
      $countlines++;
      // Now we have to cycle through the subcategories
    $get_subcategories = $fs->dbQuery("SELECT * FROM flyspray_list_category WHERE project_id = ? AND parent_id = ? ORDER BY list_position", array($project_id, $row['category_id']));
    while ($subrow = $fs->dbFetchArray($get_subcategories)) {
    ?>
     <tr>
      <td>
        <input type="hidden" name="id[]" value="<?php echo $subrow['category_id'];?>" />
        &rarr;
        <label for="categoryname<?php echo $countlines; ?>"><?php echo $admin_text['name'];?></label>
        <input id="categoryname<?php echo $countlines; ?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo stripslashes($subrow['category_name']);?>" />
      </td>
      <td title="<?php echo $admin_text['listordertip'];?>">
        <label for="listposition<?php echo $countlines; ?>"><?php echo $admin_text['order'];?></label>
        <input id="listposition<?php echo $countlines; ?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $subrow['list_position'];?>" />
      </td>
      <td title="<?php echo $admin_text['listshowtip'];?>">
        <label for="showinlist<?php echo $countlines; ?>"><?php echo $admin_text['show'];?></label>
        <input id="showinlist<?php echo $countlines; ?>" type="checkbox" name="show_in_list[<?php echo $countlines; ?>]" value="1" <?php if ($subrow['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
      </td>
      <td title="<?php echo $admin_text['categoryownertip'];?>">
        <label for="categoryowner<?php echo $countlines; ?>"><?php echo $admin_text['owner'];?></label>
        <select id="categoryowner<?php echo $countlines; ?>" name="category_owner[]">
        <option value=""><?php echo $admin_text['selectowner'];?></option>
        <?php
        $fs->listUsers($subrow['category_owner'], $project_id);
        ?>
      </select>
      </td>
     </tr>
    <?php
      $countlines++;
     // End of cycling through subcategories
     };
    };
    ?>
      <tr>
        <td colspan="4"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
    </form>
    <?php
    // Form to add a new category to the list
    ?>
    <hr />
    <form action="index.php" method="post">
    <table class="list">
     <tr>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="add_category" />
        <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
      <td>
        <label for="listnamenew"><?php echo $admin_text['name'];?></label>
        <input id="listnamenew" type="text" size="15" maxlength="30" name="list_name" />
      </td>
      <td title="<?php echo $admin_text['listordertip'];?>">
        <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
        <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
      </td>
      <td title="<?php echo $admin_text['listshowtip'];?>">
        <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
        <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
      </td>
      <td title="<?php echo $admin_text['categoryownertip'];?>" colspan="2">
        <label for="categoryownernew" ><?php echo $admin_text['owner'];?></label>
        <select id="categoryownernew" name="category_owner">
          <option value=""><?php echo $admin_text['selectowner'];?></option>
          <?php
          $fs->listUsers($novar, $project_id);
          ?>
        </select>
      </td>
      <td colspan="2" title="<?php echo $admin_text['categoryparenttip'];?>">
      <label for="categoryparentnew"><?php echo $admin_text['subcategoryof'];?></label>
      <select name="parent_id">
        <option value=""><?php echo $admin_text['notsubcategory'];?></option>
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
        };
        ?>
      </select>
      </td>
      <td class="buttons"><br /><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
    </tr>
  </table>
    </form>
  </div>

</fieldset>

<?php
// Show the list of Operating Systems
} elseif ($_GET['show'] == 'os') { ?>

<fieldset class="admin">

<legend><?php echo $admin_text['oslist'];?></legend>

  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
  <form action="index.php" method="post">
  <input type="hidden" name="do" value="modify" />
  <input type="hidden" name="action" value="update_list" />
  <input type="hidden" name="list_type" value="os" />
  <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
  <table class="list">
    <?php
    $get_os = $fs->dbQuery("SELECT * FROM flyspray_list_os WHERE project_id = ? ORDER BY list_position", array($project_id));
    $countlines = 0;
    while ($row = $fs->dbFetchArray($get_os)) {
    ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['os_id'];?>" />
          <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['os_name']));?>" />
        </td>
        <td title="The order these items will appear in the Operating System list">
          <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="Show this item in the Operating System list">
          <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
      </tr>
    <?php
      $countlines++;
    };
    ?>
      <tr>
        <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
    </form>
    <hr />
    <?php
    // Form to add a new Operating System to the list
    ?>
    <form action="index.php" method="post">
    <table class="list">
    <tr>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="add_to_list" />
        <input type="hidden" name="list_type" value="os" />
        <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
        <input type="hidden" name="id" value="<?php echo $row['os_id'];?>" />
      <td>
        <label for="listnamenew"><?php echo $admin_text['name'];?></label>
        <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
      </td>
      <td>
        <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
        <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
      </td>
      <td>
        <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
        <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
      </td>
      <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
   </form>
</div>

</fieldset>

<?php
// Show the list of Versions
} elseif ($_GET['show'] == 'version') { ?>

<fieldset class="admin">

  <legend><?php echo $admin_text['versionlist'];?></legend>

  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
  <form action="index.php" method="post">
  <input type="hidden" name="do" value="modify" />
  <input type="hidden" name="action" value="update_version_list" />
  <input type="hidden" name="list_type" value="version" />
  <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
  <table class="list">

    <?php
    $get_version = $fs->dbQuery("SELECT * FROM flyspray_list_version WHERE project_id = ? ORDER BY list_position", array($project_id));
    $countlines = 0;
    while ($row = $fs->dbFetchArray($get_version)) {
    ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['version_id'];?>" />
          <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="20" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['version_name']));?>" />
        </td>
        <td title="<?php echo $admin_text['listordertip'];?>">
          <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="<?php echo $admin_text['listshowtip'];?>">
          <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
        <td title="<?php echo $admin_text['listtensetip'];?>">
          <label for="tense<?php echo $countlines;?>"><?php echo $admin_text['tense'];?></label>
          <select id="tense<?php echo $countlines;?>" name="version_tense[<?php echo $countlines;?>]">
            <option value="1" <?php if ($row['version_tense'] == '1') { echo "SELECTED";};?>><?php echo $admin_text['past'];?></option>
            <option value="2" <?php if ($row['version_tense'] == '2') { echo "SELECTED";};?>><?php echo $admin_text['present'];?></option>
            <option value="3" <?php if ($row['version_tense'] == '3') { echo "SELECTED";};?>><?php echo $admin_text['future'];?></option>
          </select>
        </td>
      </tr>
    <?php
      $countlines++;
    };
    ?>
      <tr>
        <td colspan="4"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
    </form>
    <hr />
    <?php
    // Form to add a new Version to the list
    ?>
    <form action="index.php" method="post">
    <table class="list">
      <tr>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="add_to_version_list" />
          <input type="hidden" name="list_type" value="version" />
          <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
        <td>
          <label for="listnamenew"><?php echo $admin_text['name'];?></label>
          <input id="listnamenew" type="text" size="15" maxlength="20" name="list_name" />
        </td>
        <td>
          <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
          <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
        </td>
        <td>
          <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
          <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
        </td>
       <td title="<?php echo $admin_text['listtensetip'];?>">
          <label for="tensenew"><?php echo $admin_text['tense'];?></label>
          <select id="tensenew" name="version_tense">
            <option value="1"><?php echo $admin_text['past'];?></option>
            <option value="2" SELECTED><?php echo $admin_text['present'];?></option>
            <option value="3"><?php echo $admin_text['future'];?></option>
          </select>
        </td>
        <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
    </form>
  </div>

</fieldset>

<?php
};
//};
// End of project preferences

////////////////////////////////
// Start of editing a comment //
////////////////////////////////

} elseif ($_GET['area'] == "editcomment" && $permissions['edit_comments'] == '1') {

// Get the comment details
    $getcomments = $fs->dbQuery("SELECT * FROM flyspray_comments WHERE comment_id = ?", array($_GET['id']));
    while ($row = $fs->dbFetchArray($getcomments)) {
      $getusername = $fs->dbQuery("SELECT real_name FROM flyspray_users WHERE user_id = ?", array($row['user_id']));
      list($user_name) = $fs->dbFetchArray($getusername);

      $formatted_date = $fs->formatDate($row['date_added'], true);
      $comment_text = stripslashes(htmlspecialchars($row['comment_text']));

    };
?>
<h3><?php echo $admin_text['editcomment'];?></h3>
<form action="index.php" method="post">
<div class="admin">
  <p><?php echo "{$admin_text['commentby']} $user_name - $formatted_date";?></p>
  <p>
    <textarea cols="72" rows="10" name="comment_text"><?php echo $comment_text;?></textarea>
  </p>
  <p class="buttons">
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="editcomment" />
    <input type="hidden" name="task_id" value="<?php echo $_GET['task_id'];?>" />
    <input type="hidden" name="comment_id" value="<?php echo $_GET['id'];?>" />
    <input type="hidden" name="previous_text" value="<?php echo $comment_text;?>" />
    <input class="adminbutton" type="submit" value="<?php echo $admin_text['saveeditedcomment'];?>" />
  </p>
</div>
</form>

<?php

// End of editing a comment


/////////////////////////////////////
// Start of pending admin requests //
/////////////////////////////////////

} elseif ($_GET['area'] == 'pendingreq'
          && ($permissions['is_admin'] == '1'
          OR $permissions['manage_project'] == '1')) {

  echo '<h2>' . $admin_text['pendingrequests'] . '</h2>';

  // For full admins
  if ($permissions['is_admin'] == '1') {
    // Requests for full admins go here.  (None have been implemented yet.)
  };

  // Requests for Project Managers go here

  echo '<h3>' . $admin_text['forcurrentproj'] . '</h3>';

  echo '<table class="admin" border="1"><tr>';
  echo '<th>' . $admin_text['eventdesc'] . '</th>';
  echo '<th>' . $admin_text['requestedby'] . '</th>';
  echo '<th>' . $admin_text['daterequested'] . '</th>';
  echo '</tr>';



  // Get a list of pending admin requests for this project
  $get_pending = $fs->dbQuery("SELECT * FROM flyspray_admin_requests ar
                               LEFT JOIN flyspray_tasks t ON ar.task_id = t.task_id
                               LEFT JOIN flyspray_users u ON ar.submitted_by = u.user_id
                               WHERE project_id = ? AND resolved_by = '0'",
                               array($project_id));

  // ...and cycle through them
  while($pending_req = $fs->dbFetchRow($get_pending)) {

    // Change the numerical request type into a readable value
    switch($pending_req['request_type']) {
      case "1": $request_type = $admin_text['closetask'] . ' - <a href="?do=details&amp;id=' . $pending_req['task_id'] . '">FS#' . $pending_req['task_id'] . ': ' . $pending_req['item_summary'] . '</a>';
      break;
      case "2": $request_type = $admin_text['reopentask'] . ' - <a href="?do=details&amp;id=' . $pending_req['task_id'] . '">FS#' . $pending_req['task_id'] . ': ' . $pending_req['item_summary'] . '</a>';
      break;
      case "3": $request_type = $admin_text['applymember'];
      break;
    };

    echo '<tr>';
    echo "<td>$request_type</td>";
    echo '<td><a href="?do=admin&amp;area=users&amp;id=' . $pending_req['user_id'] . '">' . $pending_req['real_name'] . '(' . $pending_req['user_name'] . ')</a></td>';
    echo '<td>' . $fs->formatDate($pending_req['time_submitted'], true) . '</td>';
  };

  echo '</table>';

// End of pending admin requests

//////////////////////////////////////////////////
// Start of a user requesting a password change //
//////////////////////////////////////////////////

} elseif ($_GET['area'] == 'lostpw'
          && !$_COOKIE['flyspray_userid']) {

  // Step One: user requests magic url
  if (!$_GET['magic']) {

    echo '<h3>' . $admin_text['lostpw'] . '</h3>' . "\n";
    echo $admin_text['lostpwexplain'] . "\n";

    echo '<br /><br />' . "\n";

    echo '<div class="admin">' . "\n";
    echo '<form action="index.php" method="post">' . "\n";
    echo '<input type="hidden" name="do" value="modify" />' . "\n";
    echo '<input type="hidden" name="action" value="sendmagic" />' . "\n";
    echo '<b>' . $admin_text['username'] . '</b>' . "\n";
    echo '<input class="admintext" type="text" name="user_name" size="20" maxlength="20" />' . "\n";
    echo '<input class="adminbutton" type="submit" value="' . $admin_text['sendlink'] . '" />' . "\n";
    echo '</form>' . "\n";
    echo '</div>' . "\n";


  // Step Two: user enters new password
} elseif ($_GET['magic']
          && !$_COOKIE['flyspray_userid']) {

    // Check that the magic url is valid
    $check_magic = $fs->dbQuery("SELECT * FROM flyspray_users
                                 WHERE magic_url = ?",
                                 array($_GET['magic'])
                               );

    if (!$fs->dbCountRows($check_magic)) {
      echo "<div class=\"redirectmessage\"><p><em>{$admin_text['badmagic']}</em></p></div>";
      echo '<meta http-equiv="refresh" content="2; URL=index.php">';

    } else {

      echo '<h3>' . $admin_text['changepass'] . '</h3>' . "\n";

      echo '<br />' . "\n";

      echo '<form action="index.php" method="post">' . "\n";

      echo '<table class="admin">' . "\n";
      echo '<input type="hidden" name="do" value="modify" />' . "\n";
      echo '<input type="hidden" name="action" value="chpass" />' . "\n";
      echo '<input type="hidden" name="magic_url" value="' . $_GET['magic'] . '" />' . "\n";
      echo '<tr><td><b>' . $admin_text['changepass'] . '</b></td>' . "\n";
      echo '<td><input class="admintext" type="password" name="pass1" size="20" /></td></tr>' . "\n";
      echo '<tr><td><b>' . $admin_text['confirmpass'] . '</b></td>' . "\n";
      echo '<td><input class="admintext" type="password" name="pass2" size="20" /></tr>' . "\n";
      echo '<tr><td></td><td><input class="adminbutton" type="submit" value="' . $admin_text['savenewpass'] . '" /></td></tr>' . "\n";
      echo '</table>' . "\n";
      echo '</form>' . "\n";
      echo '</div>' . "\n";

    // End of checking magic url validity
    };

  // End of checking for magic url
  };


// End of a user requesting a password change

// End of the toolbox div
echo '</div>';

///////////////////////////////////////////////////////
// If all else fails... show an authentication error //
///////////////////////////////////////////////////////

} else {
  echo "<br /><br />";
  echo $admin_text['nopermission'];
  echo "<br /><br />";


//////////////////////
// End of all areas //
//////////////////////
};

// End of checking if a user is in the global Admin group
}
?>
