<?php

/*
  -------------------------------------------------------------
  | Project Managers Toolbox                                  |
  | ------------------------                                  |
  | This script is for Project Managers to modify settings    |
  | for their project, including general permissions,         |
  | members, group permissions, and dropdown list items.      |
  -------------------------------------------------------------
*/

// Import the language strings
$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'index');
$fs->get_language_pack($lang, 'admin');
$fs->get_language_pack($lang, 'pm');


// This generates an URL so that the action script takes us back to the previous page
$this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
$this_page = str_replace('&', '&amp;', $this_page);

// You need to be a Project Manager to use this script
if ($permissions['manage_project'] == '1')
{

   // Show the menu that stays visible, regardless of which area we're in
   echo '<div id="toolboxmenu">';

   echo '<small>|</small> <a id="projprefslink" href="?do=pm&amp;project=' . $project_id . '&amp;area=prefs">' . $admin_text['preferences'] . '</a> ';
   echo '<small>|</small> <a id="projuglink" href="?do=pm&amp;project=' . $project_id . '&amp;area=groups">' . $pm_text['usergroups'] . '</a> ';
   echo '<small>|</small> <a id="projttlink" href="?do=pm&amp;project=' . $project_id . '&amp;area=tt">' . $admin_text['tasktypes'] . '</a> ';
   echo '<small>|</small> <a id="projreslink" href="?do=pm&amp;project=' . $project_id . '&amp;area=res">' . $admin_text['resolutions'] . '</a> ';
   echo '<small>|</small> <a id="projcatlink" href="?do=pm&amp;project=' . $project_id . '&amp;area=cat">' . $admin_text['categories'] . '</a> ';
   echo '<small>|</small> <a id="projoslink" href="?do=pm&amp;project=' . $project_id . '&amp;area=os">' . $admin_text['operatingsystems'] . '</a> ';
   echo '<small>|</small> <a id="projverlink" href="?do=pm&amp;project=' . $project_id . '&amp;area=ver">' . $admin_text['versions'] . '</a> ';

   // End of the toolboxmenu
   echo '</div>';

   // Start of the toolbox content
   echo '<div id="toolbox">';

   /////////////////////////////////////////////
   // Start the main project preferences area //
   /////////////////////////////////////////////

   if ((isset($_GET['area']) && $_GET['area'] == 'prefs') OR !isset($_GET['area']))
   {

      echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $admin_text['preferences'] . '</h3>';
   ?>

      <fieldset class="admin">

      <legend><?php echo $admin_text['general'];?></legend>

      <form action="index.php" method="post">
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="updateproject" />
         <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
         <table class="admin">
            <tr>
               <td>
               <label for="projecttitle"><?php echo $admin_text['projecttitle'];?></label>
               </td>
               <td>
               <input id="projecttitle" name="project_title" type="text" size="40" maxlength="100" value="<?php echo stripslashes($project_prefs['project_title']);?>" />
               </td>
            </tr>

            <tr>
               <td>
               <label for="themestyle"><?php echo $admin_text['themestyle'];?></label>
               </td>
               <td>
               <select id="themestyle" name="theme_style">
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
                  if ($val == $project_prefs['theme_style']) {
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
               <label for="showlogo"><?php echo $admin_text['showlogo'];?></label>
               </td>
               <td>
               <input id="showlogo" type="checkbox" name="show_logo" value="1" <?php if ($project_prefs['show_logo'] == '1') { echo "CHECKED"; }; ?> />
               </td>
            </tr>
            <tr>
               <td>
               <label for="inlineimages"><?php echo $admin_text['showinlineimages'];?></label>
               </td>
               <td>
               <input id="inlineimages" type="checkbox" name="inline_images" value="1" <?php if ($project_prefs['inline_images'] == '1') { echo "CHECKED"; }; ?> />
               </td>
            </tr>
            <tr>
               <td>
               <label for="defaultcatowner"><?php echo $admin_text['defaultcatowner'];?></label>
               </td>
               <td>
                <select id="defaultcatowner" name="default_cat_owner">
                  <option value=""><?php echo $admin_text['noone'];?></option>
                  <?php
                  // Get list of developers
                  $fs->listUsers($project_prefs['default_cat_owner'], $project_id);
                  ?>
               </select>
               </td>
            </tr>
            <tr>
               <td>
               <label for="intromessage"><?php echo $admin_text['intromessage'];?></label>
               </td>
               <td>
               <textarea id="intromessage" name="intro_message" rows="12" cols="70"><?php echo stripslashes($project_prefs['intro_message']);?></textarea>
               </td>
            </tr>
            <tr>
               <td>
               <label for="isactive"><?php echo $admin_text['isactive'];?></label>
               </td>
               <td>
               <input id="isactive" type="checkbox" name="project_is_active" value="1" <?php if ($project_prefs['project_is_active'] == '1') { echo "CHECKED";};?> />
               </td>
            </tr>
            <tr>
               <td>
               <label for="othersview"><?php echo $admin_text['othersview'];?></label>
               </td>
               <td>
               <input id="othersview" type="checkbox" name="others_view" value="1" <?php if ($project_prefs['others_view'] == '1') { echo "CHECKED";};?> />
               </td>
            </tr>
            <tr>
               <td>
               <label for="anonopen"><?php echo $admin_text['allowanonopentask'];?></label>
               </td>
               <td>
               <input id="anonopen" type="checkbox" name="anon_open" value="1" <?php if ($project_prefs['anon_open'] == '1') { echo "CHECKED"; }; ?> />
               </td>
            </tr>
            <tr>
               <td colspan="2"><hr /></td>
            </tr>

            <!-- Column display selector -->
            <tr>
               <td><label><?php echo $admin_text['visiblecolumns'];?></label></td>
               <td class="admintext">
               <?php // Set the selectable column names
               $columnnames = array('id','project','tasktype','category','severity','priority','summary','dateopened','status','openedby','assignedto', 'lastedit','reportedin','dueversion','comments','attachments','progress');
               foreach ($columnnames AS $column) {
                  if (ereg($column, $project_prefs['visible_columns']) ) {
                     echo "<input type=\"checkbox\" name=\"visible_columns{$column}\" value=\"1\" checked=\"checked\" />$index_text[$column]<br />\n";
                  } else {
                     echo "<input type=\"checkbox\" name=\"visible_columns{$column}\" value=\"1\" />$index_text[$column]<br />\n";
                  };
               };
                  ?>
               </td>
            </tr>
            <tr>
               <td colspan="2"><hr /></td>
            </tr>
            <tr>
               <td class="buttons" colspan="2"><input class="adminbutton" type="submit" value="<?php echo $admin_text['saveoptions'];?>" /></td>
            </tr>

         </table>
         </form>

      </fieldset>

   <?
   ////////////////////////////////////////////////
   // Start of managing project user-groups area //
   ////////////////////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'groups')
   {

      echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $pm_text['groupmanage'] . '</h3>';

      echo '<fieldset class="admin">';
      echo '<legend>' . $admin_text['usergroups'] . '</legend>';

      echo "<a href=\"index.php?do=newgroup&amp;project=$project_id\">{$admin_text['newgroup']}</a></p>\n\n";

      // We have to make sure that a user isn't displayed in the user list at the bottom of the page
      // if they're already in a project group... so we set up an array...
      $user_checklist = array();

      // Cycle through the groups that belong to this project
      $get_groups = $db->Query("SELECT * FROM flyspray_groups WHERE belongs_to_project = ? ORDER BY group_id ASC", array($project_id));
      while ($group = $db->FetchArray($get_groups)) {

         echo '<h4><a href="?do=pm&amp;area=editgroup&amp;id=' . $group['group_id'] . '">' . stripslashes($group['group_name']) . '</a></h4>' . "\n";
         echo '<p>' . stripslashes($group['group_desc']) . "</p>\n";

         // Now, create a form used for moving multiple users between groups
         echo '<form action="index.php" method="post">' . "\n";

         echo "<table class=\"userlist\">\n<tr><th></th><th>{$admin_text['username']}</th><th>{$admin_text['realname']}</th><th>{$admin_text['accountenabled']}</th></tr>\n";

         $get_user_list = $db->Query("SELECT * FROM flyspray_users_in_groups uig
                                       LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                       WHERE uig.group_id = ? ORDER BY u.user_name ASC",
                                       array($group['group_id']));


         echo '<input type="hidden" name="do" value="modify" />' . "\n";
         echo '<input type="hidden" name="action" value="movetogroup" />' . "\n";
         echo '<input type="hidden" name="old_group" value="' . $group['group_id'] . '" />' . "\n";
         echo '<input type="hidden" name="project_id" value="' . $project_id . '" />'. "\n";
         echo '<input type="hidden" name="prev_page" value="' . $this_page . '" />'. "\n";

         $userincrement = 0;
         while ($row = $db->FetchArray($get_user_list)) {
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

         // Show an option to remove a user from a project entirely
         echo '<option value="0">' . $admin_text['nogroup'] . '</option>';


         // Get the list of groups to choose from
         $groups = $db->Query("SELECT * FROM flyspray_groups WHERE belongs_to_project = ? ORDER BY group_id ASC", array($project_id));
         while ($group = $db->FetchArray($groups)) {
            echo '<option value="' . $group['group_id'] . '">' . htmlspecialchars(stripslashes($group['group_name'])) . "</option>\n";
         };

         echo '</select>';

         echo '</td></tr>';
         echo "</table>\n\n";
         echo '</form>';
      };


      // Create a form used for adding users to a project group
      echo '<form action="index.php" method="post">' . "\n";
      echo '<input type="hidden" name="do" value="modify" />'. "\n";
      echo '<input type="hidden" name="action" value="addtogroup" />'. "\n";
      echo '<input type="hidden" name="project_id" value="' . $project_id . '" />'. "\n";
      echo '<input type="hidden" name="prev_page" value="' . $this_page . '" />'. "\n";
      echo '<br />';
      echo '<select class="adminlist" name="user_list[]" multiple="multiple" size="15">'. "\n";

      // Get a list of the users not in any groups for this project
      $user_query = $db->Query("SELECT * FROM flyspray_users_in_groups uig
                                 LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                 LEFT JOIN flyspray_groups g on uig.group_id = g.group_id
                                 WHERE g.belongs_to_project <> ? AND u.account_enabled = ?
                                 ORDER BY user_name ASC",
                                 array($project_id, '1'));


      while ($row = $db->FetchArray($user_query)) {
         // Check if the user is in the checklist of shown users...
         if (!in_array($row['user_id'], $user_checklist)) {
            // ...if not, we display them, and add them to the array so that they don't get shown again!
            echo "<option value=\"{$row['user_id']}\">{$row['user_name']} ({$row['real_name']})</option>\n";
            array_push($user_checklist, $row['user_id']);
         };
      };

      echo '</select><br />';
      echo '<input class="adminbutton" type="submit" value="' . $admin_text['addtogroup'] . '" />'. "\n";
      echo '<select class="adminbutton" name="add_to_group">'. "\n";

      // Get the list of groups to choose from
      $get_groups = $db->Query("SELECT * FROM flyspray_groups WHERE belongs_to_project = ? ORDER BY group_id ASC", array($project_id));
      while ($group = $db->FetchArray($get_groups)) {
      echo '<option value="' . $group['group_id'] . '">' . htmlspecialchars(stripslashes($group['group_name'])) . "</option>\n";
      };

      echo '</select>';

      echo '</form>';

      echo '</fieldset>';


   /////////////////////////////
   // Start of editing groups //
   /////////////////////////////
   } elseif (isset($_GET['area']) && $_GET['area'] == "editgroup")
   {
      echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $admin_text['editgroup'] . '</h3>';

      $get_group_details = $db->Query("SELECT * FROM flyspray_groups WHERE group_id = ?", array($_GET['id']));
      $group_details = $db->FetchArray($get_group_details);

      // PMs are only allowed to edit groups in their project
      if ($group_details['belongs_to_project'] != $project_id)
      {
         die($admin_text['nopermission']);
      }
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
         <tr>
            <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
         </tr>
      </table>
      </form>

   <?php
   //////////////////////////////////
   // Start of the Task Types area //
   //////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'tt')
   {


   ///////////////////////////////////
   // Start of the Resolutions area //
   ///////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'res')
   {


   //////////////////////////////////
   // Start of the Categories area //
   //////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'cat')
   {

   echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $pm_text['catlisted'] . '</h3>';
   ?>
      <fieldset class="admin">

      <legend><?php echo $admin_text['categories'];?></legend>

      <p><?php echo $admin_text['listnote'];?></p>
      <div class="admin">
      <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update_category" />
      <input type="hidden" name="list_type" value="category" />
      <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      <table class="list">
         <?php
         $get_categories = $db->Query("SELECT * FROM flyspray_list_category WHERE project_id = ? AND parent_id < ? ORDER BY list_position", array($project_id, '1'));
         $countlines = 0;
         while ($row = $db->FetchArray($get_categories)) {
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
            $get_subcategories = $db->Query("SELECT * FROM flyspray_list_category WHERE project_id = ? AND parent_id = ? ORDER BY list_position", array($project_id, $row['category_id']));
            while ($subrow = $db->FetchArray($get_subcategories)) {
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

      <hr />

      <!-- Form to add a new category to the list -->
      <form action="index.php" method="post">
      <table class="list">
         <tr>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="add_category" />
            <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
            <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
            <td>
            <label for="listnamenew"><?php echo $admin_text['name'];?></label>
            <input id="listnamenew" type="text" size="15" maxlength="30" name="list_name" />
            </td>
            <td title="<?php echo $admin_text['listordertip'];?>">
            <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
            <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
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
            $cat_list = $db->Query('SELECT category_id, category_name
                                       FROM flyspray_list_category
                                       WHERE project_id=? AND show_in_list=? AND parent_id < ?
                                       ORDER BY list_position', array($project_id, '1', '1')
                                   );

            while ($row = $db->FetchArray($cat_list))
            {
               $category_name = stripslashes($row['category_name']);
               if ($_GET['cat'] == $row['category_id'])
               {
                  echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
               } else
               {
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

   /////////////////////////////////////////
   // Start of the Operating Systems area //
   /////////////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'os')
   {
      echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $pm_text['oslisted'] . '</h3>';
   ?>

   <fieldset class="admin">

   <legend><?php echo $admin_text['operatingsystems'];?></legend>

   <p><?php echo $admin_text['listnote'];?></p>
   <div class="admin">
   <form action="index.php" method="post">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update_list" />
      <input type="hidden" name="list_type" value="os" />
      <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      <table class="list">
         <?php
         $get_os = $db->Query("
             SELECT *, count(t.task_id) AS used_in_tasks
             FROM flyspray_list_os os
             LEFT JOIN flyspray_tasks t ON (t.operating_system = os.os_id AND t.attached_to_project = os.project_id)
             WHERE os.project_id = ?
             GROUP BY os.os_id
             ORDER BY list_position", array($project_id));
         $countlines = 0;
         while ($row = $db->FetchArray($get_os)) {
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
            <?php if ($row['used_in_tasks'] == 0): ?>
            <td title="Delete this item from the Operating System list">
            <label for="delete<?php echo $row['os_id']?>"><?php echo $admin_text['delete'];?></label>
            <input id="delete<?php echo $row['os_id']?>" type="checkbox" name="delete[<?php echo $row['os_id']?>]" value="1" />
            <?php else: ?>
            <td>&nbsp;
            <?php endif; ?>
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

      <!-- Form to add a new operating system to the list -->
      <form action="index.php" method="post">
      <table class="list">
         <tr>
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="add_to_list" />
         <input type="hidden" name="list_type" value="os" />
         <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
         <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
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
   ////////////////////////////////
   // Start of the Versions area //
   ////////////////////////////////

   } elseif(isset($_GET['area']) && $_GET['area'] == 'ver')
   {
      echo '<h3>' . $pm_text['pmtoolbox'] . ':: ' . $project_prefs['project_title'] . ': ' . $pm_text['verlisted'] . '</h3>';
      ?>
      <fieldset class="admin">

      <legend><?php echo $admin_text['versions'];?></legend>

      <p><?php echo $admin_text['listnote'];?></p>
      <div class="admin">
      <form action="index.php" method="post">
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="update_version_list" />
         <input type="hidden" name="list_type" value="version" />
         <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
         <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
         <table class="list">

         <?php
         $get_version = $db->Query("
             SELECT *, count(t.task_id) AS used_in_tasks
             FROM flyspray_list_version v
             LEFT JOIN flyspray_tasks t ON (t.product_version = v.version_id OR t.closedby_version = v.version_id AND t.attached_to_project = v.project_id)
             WHERE v.project_id = ?
             GROUP BY v.version_id
             ORDER BY list_position", array($project_id));
         $countlines = 0;
         while ($row = $db->FetchArray($get_version)) {
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
            <?php if ($row['used_in_tasks'] == 0): ?>
            <td title="<?php echo $admin_text['listdeletetip'];?>">
            <label for="delete<?php echo $row['version_id']?>"><?php echo $admin_text['delete'];?></label>
            <input id="delete<?php echo $row['version_id']?>" type="checkbox" name="delete[<?php echo $row['version_id']?>]" value="1" />
            <?php else: ?>
            <td>&nbsp;
            <?php endif; ?>
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

      <!-- Form to add a new version to the list -->
      <form action="index.php" method="post">
      <table class="list">
         <tr>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="add_to_version_list" />
            <input type="hidden" name="list_type" value="version" />
            <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
            <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
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
   // End of areas
   }

   echo '</div>';

// End of checking if the user is a Project Manager
}
?>
