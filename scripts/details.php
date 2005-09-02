<?php

/*
   This script displays task details when in view mode, and allows the
   user to edit task details when in edit mode.  It also shows comments,
   attachments, notifications etc.
*/

$fs->get_language_pack($lang, 'details');
$fs->get_language_pack($lang, 'newtask');
$fs->get_language_pack($lang, 'index');

// Only load this page if a valid task was actually requested
if (!$fs->GetTaskDetails($_GET['id']))
   $fs->Redirect( $fs->CreateURL('error', null) );

$task_details = $fs->GetTaskDetails($_GET['id']);

// declare variables
$deps_open = null;

// and the user has permission to view it
if ($task_details['project_is_active'] == '1'
  && ($project_prefs['others_view'] == '1'
      OR @$permissions['view_tasks'] == '1')
  && (($task_details['mark_private'] == '1'
       && $task_details['assigned_to'] == $current_user['user_id'])
           OR @$permissions['manage_project'] == '1'
           OR $task_details['mark_private'] != '1')
   )
{

   // Create an array with effective permissions for this user on this task
   $effective_permissions = array();

   // Confirm that the user can modify this task or not
   if (@$permissions['modify_all_tasks'] == '1'
       OR (@$permissions['modify_own_tasks'] == '1' && $task_details['assigned_to'] == $current_user['user_id']))
         $effective_permissions['can_edit'] = '1';

   // Check if the user can take ownership of this task
   if ((@$permissions['assign_to_self'] == '1' && empty($task_details['assigned_to']))
    OR @$permissions['assign_others_to_self'] == '1'
    && $task_details['assigned_to'] != $current_user['user_id'])
      $effective_permissions['can_take_ownership'] = '1';


   // Check if the user can close this task
   if ((@$permissions['close_own_tasks'] == '1' && ($task_details['assigned_to'] == $current_user['user_id']))
    OR @$permissions['close_other_tasks'] == '1')
      $effective_permissions['can_close'] = '1';

   ///////////////////////////////////
   // If the user can modify tasks, //
   // and the task is still open,   //
   // and we're in edit mode,       //
   // then use this section.        //
   ///////////////////////////////////

   if (@$effective_permissions['can_edit'] == '1'
    && $task_details['is_closed'] != '1'
    && isset($_GET['edit']) && $_GET['edit'] == 'yep')
   {

   ?>

      <!-- create some columns -->
      <div id="taskdetails">
      <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
       <div>
         <h2 class="severity<?php echo $task_details['task_severity'];?>">
         <?php echo 'FS#' . $task_details['task_id'];?> &mdash;
         <input class="severity<?php echo stripslashes($task_details['task_severity']);?>" type="text" name="item_summary" size="50" maxlength="100" value="<?php echo htmlspecialchars(stripslashes($task_details['item_summary']),ENT_COMPAT,'utf-8');?>" />
         </h2>
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="update" />
         <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
         <input type="hidden" name="edit_start_time" value="<?php echo date('U'); ?>" />

         <?php echo $details_text['attachedtoproject'] . ' &mdash; ';?>
         <select name="attached_to_project">
         <?php
         // If the user has permission to view all projects
         if (@$permissions['global_view'] == '1')
         {
            $get_projects = $db->Query("SELECT * FROM {$dbprefix}projects
                                        WHERE project_is_active = '1'
                                        ORDER BY project_title");

         // or, if the user is logged in
         } elseif (isset($_COOKIE['flyspray_userid']))
         {
            // This query needs fixing.  It returns double results if a project has the 'others view' option turned on
            // This means I had to make it strip duplicate results when cycling through projects a bit further down...
               $get_projects = $db->Query("SELECT p.*
                                          FROM {$dbprefix}projects p
                                          LEFT JOIN {$dbprefix}groups g ON p.project_id = g.belongs_to_project
                                          LEFT JOIN {$dbprefix}users_in_groups uig ON g.group_id = uig.group_id
                                          WHERE ((uig.user_id = ?
                                          AND g.view_tasks = '1')
                                          OR p.others_view = '1')
                                          AND p.project_is_active = '1'",
                                          array($current_user['user_id'])
                                        );
         } else
         {
            // Anonymous users
            $get_projects = $db->Query("SELECT * FROM {$dbprefix}projects
                                        WHERE project_is_active = '1'
                                        AND others_view = '1'
                                        ORDER BY project_title");
         }

         // Cycle through the results from whichever query above
         // The query above is dodgy, and returns duplicate results... so I add each result to an array and filter dupes - FIXME
         $project_list = array();
         while ($row = $db->FetchArray($get_projects))
         {
            if ($project_id == $row['project_id'] && $_GET['project'] != '0' && !in_array($row['project_id'], $project_list))
            {
               echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
               $project_list[] = $row['project_id'];
            } elseif (!in_array($row['project_id'], $project_list))
            {
               echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
               $project_list[] = $row['project_id'];
            }

         }
         ?>
         </select>

         <div id="fineprint">
         <?php
         // Get the user details of the person who opened this item
         if ($task_details['opened_by'])
         {
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($task_details['opened_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);
         } else
         {
            $user_name = $details_text['anonymous'];
         }

         $date_opened = $fs->formatDate($task_details['date_opened'], true);

         echo $details_text['openedby'] . ' ' . $fs->LinkedUserName($task_details['opened_by']) . ' - ' . $date_opened;


         // If it's been edited, get the details
         if ($task_details['last_edited_by'])
         {
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($task_details['last_edited_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);

            $date_edited = $fs->formatDate($task_details['last_edited_time'], true);

            echo '<br />' . $details_text['editedby'] . ' ' . $fs->LinkedUserName($task_details['last_edited_by']) . ' - ' . $date_edited;
         }
         ?>
         </div>

         <div id="taskfields1">
            <table class="taskdetails">
               <tr>
                  <td><label for="tasktype"><?php echo $details_text['tasktype'];?></label></td>
                  <td>
                  <select id="tasktype" name="task_type">
                  <?php
                  // Get list of task types
                  $get_tasktypes = $db->Query("SELECT tasktype_id, tasktype_name FROM {$dbprefix}list_tasktype
                                              WHERE show_in_list = '1'
                                              AND (project_id = '0'
                                              OR project_id = ?)
                                              ORDER BY list_position",
                                              array($project_id)
                                            );

                  while ($row = $db->FetchArray($get_tasktypes))
                  {
                     if ($row['tasktype_id'] == $task_details['task_type'])
                     {
                        echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>";
                     } else
                     {
                        echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="category"><?php echo $details_text['category'];?></label></td>
                  <td>
                  <select id="category" name="product_category">
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

                     if ($task_details['product_category'] == $row['category_id'])
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

                        if ($task_details['product_category'] == $subrow['category_id'])
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
                  </td>
               </tr>
               <tr>
                  <td><label for="status"><?php echo $details_text['status'];?></label></td>
                  <td>
                  <select id="status" name="item_status">
                  <?php
                  // let's get a list of statuses and compare it to the saved one
                  require("lang/$lang/status.php");
                  foreach($status_list as $key => $val)
                  {
                     if ($task_details['item_status'] == $key)
                     {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                     } else
                     {
                        echo "<option value=\"$key\">$val</option>\n";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="assignedto"><?php echo $details_text['assignedto'];?></label></td>
                  <td>
                  <input type="hidden" name="old_assigned" value="<?php echo $task_details['assigned_to'];?>" />
                  <select id="assignedto" name="assigned_to">
                  <?php
                  // see if it's been assigned
                  if ($task_details['assigned_to'] == "0")
                  {
                     echo "<option value=\"0\" selected=\"selected\">{$details_text['noone']}</option>\n";
                  } else
                  {
                     echo "<option value=\"0\">{$details_text['noone']}</option>\n";
                  }

                  $fs->ListUsers($task_details['assigned_to'], $project_id);

                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="os"><?php echo $details_text['operatingsystem'];?></label></td>
                  <td>
                  <select id="os" name="operating_system">
                  <?php
                  // Get list of operating systems
                  $get_os = $db->Query("SELECT os_id, os_name
                                        FROM {$dbprefix}list_os
                                        WHERE (project_id = ?
                                               OR project_id = '0')
                                        AND show_in_list = '1'
                                        ORDER BY list_position",
                                        array($project_id)
                                      );

                  while ($row = $db->FetchArray($get_os))
                  {
                     if ($row['os_id'] == $task_details['operating_system'])
                     {
                        echo "<option value=\"{$row['os_id']}\" selected=\"selected\">{$row['os_name']}</option>";
                     } else
                     {
                        echo "<option value=\"{$row['os_id']}\">{$row['os_name']}</option>";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
            </table>
         </div>


         <div id="taskfields2">
            <table class="taskdetails">
               <tr>
                  <td><label for="severity"><?php echo $details_text['severity'];?></label></td>
                  <td>
                  <select id="severity" name="task_severity">
                  <?php
                  // Get list of severities
                  require("lang/$lang/severity.php");
                  foreach($severity_list as $key => $val)
                  {
                     if ($task_details['task_severity'] == $key)
                     {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                     } else
                     {
                        echo "<option value=\"$key\">$val</option>\n";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="priority"><?php echo $details_text['priority'];?></label></td>
                  <td>
                  <select id="priority" name="task_priority">
                  <?php
                  // Get list of priorities
                  require("lang/$lang/priority.php");
                  foreach($priority_list as $key => $val)
                  {
                     if ($task_details['task_priority'] == $key)
                     {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                     } else
                     {
                        echo "<option value=\"$key\">$val</option>\n";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="reportedver"><?php echo $details_text['reportedversion'];?></label><span id="reportedver"></span></td>
                  <td>
                  <?php
                  // Print the version name
                  echo $task_details['reported_version_name'];
                  ?>
                  </td>
               </tr>
               <tr>
                  <td><label for="dueversion"><?php echo $details_text['dueinversion'];?></label></td>
                  <td>
                  <select id="dueversion" name="closedby_version">
                  <?php
                  // if we don't have a fix-it version, show undecided
                  if (!isset($closedby))
                  {
                     echo "<option value=\"\">{$details_text['undecided']}</option>\n";
                  } else
                  {
                     echo "<option value=\"\" selected=\"selected\">{$details_text['undecided']}</option>\n";
                  }

                  $get_version = $db->Query("SELECT version_id, version_name
                                             FROM {$dbprefix}list_version
                                             WHERE show_in_list = '1' AND version_tense = '3'
                                             AND (project_id = '0'
                                                OR project_id = ?)
                                             ORDER BY list_position",
                                             array($project_id,)
                                           );

                  while ($row = $db->FetchArray($get_version))
                  {
                     if ($row['version_id'] == $task_details['closedby_version'])
                     {
                        echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>\n";
                     } else
                     {
                        echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>\n";
                     }
                  }
                  ?>
                  </select>
                  </td>
               </tr>
               <tr>
                  <td><label for="duedate"><?php echo $details_text['duedate'];?></label></td>
                  <td id="duedate">
                  <!--<input id="due_date" type="text" name="due_date" size="10" value="
                  <?php if (!empty($task_details['due_date']))echo date("d-M-Y", $task_details['due_date']);?>" readonly="1" />-->

                  <select id="due_date" name="due_date">
                     <option value=""><?php echo $index_text['dueanytime'];?></option>
                     <option id="date_d"<?php if (!empty($task_details['due_date'])) { echo ' selected="1">' . date("d-M-Y", $task_details['due_date']);}else{echo '>' . $index_text['selectduedate'];};?></option>
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

                  </td>
               </tr>
               <tr>
                  <td><label for="percent"><?php echo $details_text['percentcomplete'];?></label></td>
                  <td>
                  <select id="percent" name="percent_complete">
                     <option value="0" <?php if ($task_details['percent_complete'] == '0') { echo 'selected="selected"';};?>>0%</option>
                     <option value="10" <?php if ($task_details['percent_complete'] == '10') { echo 'selected="selected"';};?>>10%</option>
                     <option value="20" <?php if ($task_details['percent_complete'] == '20') { echo 'selected="selected"';};?>>20%</option>
                     <option value="30" <?php if ($task_details['percent_complete'] == '30') { echo 'selected="selected"';};?>>30%</option>
                     <option value="40" <?php if ($task_details['percent_complete'] == '40') { echo 'selected="selected"';};?>>40%</option>
                     <option value="50" <?php if ($task_details['percent_complete'] == '50') { echo 'selected="selected"';};?>>50%</option>
                     <option value="60" <?php if ($task_details['percent_complete'] == '60') { echo 'selected="selected"';};?>>60%</option>
                     <option value="70" <?php if ($task_details['percent_complete'] == '70') { echo 'selected="selected"';};?>>70%</option>
                     <option value="80" <?php if ($task_details['percent_complete'] == '80') { echo 'selected="selected"';};?>>80%</option>
                     <option value="90" <?php if ($task_details['percent_complete'] == '90') { echo 'selected="selected"';};?>>90%</option>
                     <option value="100" <?php if ($task_details['percent_complete'] == '100') { echo 'selected="selected"';};?>>100%</option>
                  </select>
                  </td>
               </tr>
            </table>
         </div>

         <div id="taskdetailsfull">
           <label for="details"><?php echo $details_text['details'];?></label>
           <textarea id="details" name="detailed_desc" cols="70" rows="10"><?php echo htmlspecialchars(stripslashes($task_details['detailed_desc']));?></textarea>

            <table class="taskdetails">
               <tr>
               <td> </td>
               </tr>
               <tr>
                  <td class="buttons" colspan="2">
                  <input class="adminbutton" type="submit" accesskey="s" name="buSubmit" value="<?php echo $details_text['savedetails'];?>" />
                  <input class="adminbutton" type="reset" name="buReset" />
                  </td>
               </tr>
            </table>
         </div>
       </div>
      </form>
      </div>


   <?php
   } elseif (($task_details['is_closed'] == '1'
      OR @$effective_permissions['can_edit'] == '0'
      OR !isset($GET['edit']))
      && (($task_details['mark_private'] == '1'
            && $task_details['assigned_to'] == $current_user['user_id'])
          OR @$permissions['manage_project'] == '1'
         OR $task_details['mark_private'] != '1'))
   {
   //////////////////////////////////////
   // If the user isn't an admin,      //
   // OR if the task is in VIEW mode,  //
   // OR if the job is closed          //
   //////////////////////////////////////

        // Display the next/previous navigation links
        if (isset($_SESSION['tasklist']))
        {
            $previous_id = 0;
            $next_id = 0;

            $id_list = $_SESSION['tasklist'];
            $n = count($id_list);

            // Search for current task to get the adjacent IDs
            for ($i = 0; $i < $n; $i++)
            {
                if ($id_list[$i] == $task_details['task_id'])
                {
                    if ($i > 0)
                        $previous_id = $id_list[$i - 1];
                    if ($i < $n - 1)
                        $next_id = $id_list[$i + 1];
                    break;
                }
            }

            if ($previous_id > 0 || $next_id > 0)
            {
                // Get the summary of the next/previous task for use as tooltips
                $summary = array();
                $get_summary = $db->Query("SELECT task_id, item_summary
                                             FROM {$dbprefix}tasks
                                            WHERE task_id = ? OR task_id = ?",
                                          array($previous_id, $next_id));

                while ($row = $db->FetchRow($get_summary))
                {
                    $summary[$row['task_id']] = htmlentities(stripslashes($row['item_summary']),ENT_COMPAT,'utf-8');
                }

                echo "<span id=\"navigation\">";
                if ($previous_id > 0)
                {
                   echo "<a id=\"prev\" title=\"FS#{$previous_id} &mdash; {$summary[$previous_id]}\" href=\"" . $fs->CreateURL('details', $previous_id) . "\">{$details_text['previoustask']}</a>";
                   if ($next_id > 0)
                       echo ' | ';
                }
                if ($next_id > 0)
                {
                   echo "<a id=\"next\" title=\"FS#{$next_id} &mdash; {$summary[$next_id]}\" href=\"" . $fs->CreateURL('details', $next_id) . "\">{$details_text['nexttask']}</a>";
                }
                echo '</span>';
            }
        }
        ?>

         <div id="taskdetails" ondblclick='openTask("<?php echo $fs->CreateURL('edittask', $task_details['task_id']);?>")'>
         <h2 class="severity<?php echo $task_details['task_severity'];?>">
         <?php echo 'FS#' . $task_details['task_id'] . ' &mdash; ' . $fs->formatText($task_details['item_summary']);?>
         </h2>

         <div id="fineprint">
         <?php
         echo $details_text['attachedtoproject'] . '&mdash; <a href="' . $conf['general']['baseurl'] . '?project=' .  $task_details['attached_to_project'] . '">' . stripslashes($task_details['project_title']) . '</a><br />';
         // Get the user details of the person who opened this task
         if ($task_details['opened_by'])
         {
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($task_details['opened_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);
         } else
         {
            $user_name = $details_text['anonymous'];
         }

         $date_opened = $task_details['date_opened'];
         $date_opened = $fs->formatDate($date_opened, true);

         echo $details_text['openedby'] . ' ' . $fs->LinkedUserName($task_details['opened_by']) . ' - ' . $date_opened;

         // If it's been edited, get the details
         if ($task_details['last_edited_by'])
         {
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($task_details['last_edited_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);

            $date_edited = $task_details['last_edited_time'];
            $date_edited = $fs->formatDate($date_edited, true);

            echo '<br />' . $details_text['editedby'] . ' ' . $fs->LinkedUserName($task_details['last_edited_by']) . ' - ' . $date_edited;
         }
         ?>
         </div>

         <div id="taskfields1">

            <table>
               <tr>
                  <td><label for="tasktype"><?php echo $details_text['tasktype'];?></label></td>
                  <td id="tasktype"><?php echo $task_details['tasktype_name'];?></td>
               </tr>
               <tr>
                  <td><label for="category"><?php echo $details_text['category'];?></label></td>
                  <td id="category">
                  <?php
                  if ($task_details['parent_id'] > '0')
                  {
                     $get_parent_cat = $db->FetchArray($db->Query("SELECT category_name
                                                                   FROM {$dbprefix}list_category
                                                                   WHERE category_id = ?",
                                                                   array($task_details['parent_id'])
                                                                  )
                                                       );

                     echo $get_parent_cat['category_name'] . " &nbsp;&nbsp;&rarr; ";
                  }
                  echo $task_details['category_name'];?>
                  </td>
               </tr>
               <tr>
                  <td><label for="status"><?php echo $details_text['status'];?></label></td>
                  <td id="status">
                  <?php
                  if($task_details['is_closed'] == '1')
                  {
                     echo $details_text['closed'];
                  } else
                  {
                     echo $task_details['status_name'];
                  }
                  ?>
                  </td>
               </tr>
               <tr>
                  <td><label for="assignedto"><?php echo $details_text['assignedto'];?></label></td>
                  <td id="assignedto">
                  <?php
                  // see if it's been assigned
                  if (!$task_details['assigned_to'])
                  {
                     echo $details_text['noone'];
                  } else
                  {
                     echo stripslashes($task_details['assigned_to_name']);
                  }
                  ?>
                  </td>
               </tr>
               <tr>
                  <td><label for="os"><?php echo $details_text['operatingsystem'];?></label></td>
                  <td id="os"><?php echo $task_details['os_name'];?></td>
               </tr>
            </table>
         </div>


         <div id="taskfields2">

            <table>
               <tr>
                  <td><label for="severity"><?php echo $details_text['severity'];?></label></td>
                  <td id="severity"><?php echo $task_details['severity_name'];?></td>
               </tr>
               <tr>
                  <td><label for="priority"><?php echo $details_text['priority'];?></label></td>
                  <td id="priority">
                  <?php echo $task_details['priority_name'];?>
                  </td>
               </tr>
               <tr>
                  <td><label for="reportedver"><?php echo $details_text['reportedversion'];?></label></td>
                  <td id="reportedver"><?php echo $task_details['reported_version_name'];?></td>
               </tr>
               <tr>
                  <td><label for="dueversion"><?php echo $details_text['dueinversion'];?></label></td>
                  <td id="dueversion">
                  <?php
                  if (isset($task_details['due_in_version_name']))
                  {
                     echo $task_details['due_in_version_name'];
                  } else
                  {
                     echo $details_text['undecided'];
                  }
                  ?>
                  </td>
               </tr>
               <tr>
                  <td><label for="duedate"><?php echo $details_text['duedate'];?></label></td>
                  <td id="duedate">
                  <?php
                  if (!empty($task_details['due_date']))
                  {
                     echo $fs->formatDate($task_details['due_date'], false);
                  } else
                  {
                     echo $details_text['undecided'];
                  }
                  ?>
                  </td>
               </tr>
               <tr>
                  <td><label for="percent"><?php echo $details_text['percentcomplete'];?></label></td>
                  <td id="percent">
                  <?php echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/percent-' . $task_details['percent_complete'] . '.png" title="' . $task_details['percent_complete'] . '% ' . $details_text['complete'] . '" alt="' . $task_details['percent_complete'] . '%" />';?></td>
               </tr>
            </table>
         </div>


         <div id="taskdetailsfull">
            <label for="details"><?php echo $details_text['details'];?></label><span id="details"></span>
            <?php
            // New function to strip html, convert urls to links etc
            echo $fs->formatText($task_details['detailed_desc']);

            // Display attachments that came with this task when it opened
            $attachments = $db->Query("SELECT * FROM {$dbprefix}attachments
                                       WHERE task_id = ?
                                       AND comment_id = '0'
                                       ORDER BY attachment_id ASC",
                                       array($task_details['task_id'])
                                     );

            if (@$permissions['view_attachments'] == '1' OR $project_prefs['others_view'] == '1')
            {


               while ($attachment = $db->FetchArray($attachments))
               {
                  echo '<span class="attachments">';
                  echo '<a href="' . $conf['general']['baseurl'] . '?getfile=' . $attachment['attachment_id'] . '" title="' . $attachment['file_type'] . '">';

                  // Let's strip the mimetype to get the icon image name
                  list($main, $specific) = split('[/]', $attachment['file_type']);

                  $imgpath = $basedir . "themes/{$project_prefs['theme_style']}/mime/{$attachment['file_type']}.png";
                  if (file_exists($imgpath))
                  {
                     echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/mime/' . $attachment['file_type'] . '.png" title="' . $attachment['file_type'] . '" />';
                  }else
                  {
                     echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/mime/' . $main . '.png" title="' . $attachment['file_type'] . '" />';
                  }

                  echo '&nbsp;&nbsp;' . $attachment['orig_name'];
                  echo "</a>\n";

                  // Delete link
                  if (@$permissions['delete_attachments'] == '1')
                  {
                  ?>
                     &nbsp;-&nbsp;<a href="<?php echo $conf['general']['baseurl'];?>?do=modify&amp;action=deleteattachment&amp;id=<?php echo $attachment['attachment_id'];?>"
                     onclick="if(confirm('<?php echo $details_text['confirmdeleteattach'];?>')) {
                     return true
                     } else {
                     return false }
                     ">
                     <?php
                     echo $details_text['delete'] . '</a>';
                  }
                  echo '</span>';
               }

               echo '<br />';

            // End of permission check
            }

            if ($db->CountRows($attachments)
                && ((!isset($_COOKIE['flyspray_userid']) OR @$permissions['view_attachments'] != '1') && $project_prefs['others_view'] != '1') )
            {
               echo '<span class="attachments">' . $details_text['attachnoperms'] . '</span><br />';
            }

         ?>
         </div>


         <?php
         // Check for task dependencies that block closing this task
         $check_deps = $db->Query("SELECT * FROM {$dbprefix}dependencies d
                                   LEFT JOIN {$dbprefix}tasks t on d.dep_task_id = t.task_id
                                   WHERE d.task_id = ?",
                                   array($_GET['id'])
                                 );

         // Check for tasks that this task blocks
         $check_blocks = $db->Query("SELECT * FROM {$dbprefix}dependencies d
                                     LEFT JOIN {$dbprefix}tasks t on d.task_id = t.task_id
                                     WHERE d.dep_task_id = ?",
                                     array($_GET['id'])
                                   );

    $total = $db->CountRows($check_deps) + $db->CountRows($check_blocks);

         echo '<div id="deps">';
         // Show tasks that this task depends upon
         echo '<div id="taskdeps">';
         echo '<b>' . $details_text['taskdependson'] . '</b><br />';
         while ($dependency = $db->FetchArray($check_deps))
         {
            if ($dependency['is_closed'] == '1')
            {
               echo '<a class="closedtasklink" href="' . $fs->CreateURL('details', $dependency['dep_task_id']) . '">FS#' . $dependency['task_id'] . ' - ' . stripslashes($dependency['item_summary']) . "</a>";
            } else
            {
         echo '<a href="' . $fs->CreateURL('details', $dependency['dep_task_id']) . '">FS#' . $dependency['task_id'] . ' - ' . stripslashes($dependency['item_summary']) . "</a>\n";
            }

            // If the user has permission, show a link to remove a dependency
            if (@$effective_permissions['can_edit'] == '1'
             && $task_details['is_closed'] != '1')
            {
               echo '<span class="DoNotPrint">&nbsp;&mdash;&nbsp;<a class="removedeplink" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=removedep&amp;depend_id=' . $dependency['depend_id'] . '">' . $details_text['remove'] . "</a></span>\n";
            }

            echo '<br />';
         }
         echo "<br class=\"DoNotPrint\" />\n";
    // If there are dependencies, show a link for the dependency graph
    if ($total>0) {
      echo '<a class="DoNotPrint" href="' . $fs->CreateURL('depends', $id) .
        '">' . $details_text['depgraph'] . '</a><br />&nbsp;<br />';
    }
         // If the user has permission, show a form to add a new dependency
         if (@$effective_permissions['can_edit'] == '1'
          && $task_details['is_closed'] != '1')
         {
         ?>
            <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
              <div>
               <input type="hidden" name="do" value="modify" />
               <input type="hidden" name="action" value="newdep" />
               <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
               <input class="admintext" type="text" name="dep_task_id" size="5" maxlength="10" />
               <input class="adminbutton" type="submit" name="submit" value="<?php echo $details_text['addnew'];?>" />
              </div>
            </form>

         <?php
         }

         echo '</div>';

         echo '<div id="taskblocks">';
         // Show tasks that this task blocks
         echo '<b>' . $details_text['taskblocks'] . '</b><br />';
         while ($block = $db->FetchArray($check_blocks))
         {
            if ($block['is_closed'] == '1')
            {
               // Put a line through the blocking task if it's closed
               echo '<a class="closedtasklink" href="' . $fs->CreateURL('details', $block['task_id']) . '">FS#' . $block['task_id'] . ' - ' . stripslashes($block['item_summary']) . "</a><br />\n";
            } else
            {
          echo '<a href="' . $fs->CreateURL('details', $block['task_id']) . '">FS#' . $block['task_id'] . ' - ' . stripslashes($block['item_summary']) . "</a><br />\n";
            }

         }

         echo '</div>';
         echo '</div>';

         echo "\n\n";

         // If the task is closed, show the closure reason
         if ($task_details['is_closed'] == '1')
         {
            $get_closedby_name = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($task_details['closed_by']));
            list($closedby_username, $closedby_realname) = $db->FetchArray($get_closedby_name);
            $date_closed = $task_details['date_closed'];
            $date_closed = $fs->formatDate($date_closed, true);
            echo $details_text['closedby'] . '&nbsp;&nbsp;' . $fs->LinkedUserName($task_details['closed_by']) . '<br />';
            echo $details_text['date'] . '&nbsp;&nbsp;' . $date_closed . '<br />';;
            echo $details_text['reasonforclosing'] . '&nbsp;&nbsp;';
            echo $task_details['resolution_name'];
            echo '<br />';

            if (!empty($task_details['closure_comment']))
            {
               echo $details_text['closurecomment'] . '&nbsp;&nbsp;';
               echo $fs->FormatText($task_details['closure_comment']);
            }

         // End of showing task closure reason
         }

         // Check for pending PM requests
         $get_pending = $db->Query("SELECT * FROM {$dbprefix}admin_requests
                                    WHERE task_id = ?
                                    AND resolved_by = '0'",
                                    array($task_details['task_id']));

         if ($db->CountRows($get_pending))
         {
            echo '<span id="pendingreq">' . $details_text['taskpendingreq'] . '</span>';
         }

         echo '<div id="actionbuttons">';

         // Check permissions and task status, then show the "re-open task" button
         if (@$effective_permissions['can_close'] == '1' && $task_details['is_closed'] == '1')
         {
            echo '<a href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=reopen&amp;task_id=' . $_GET['id'] . '">' . $details_text['reopenthistask'] . '</a>';

            // If they can't re-open this, show a button to request a PM re-open it
         } elseif (@$effective_permissions['can_close'] != '1'
           && $task_details['is_closed'] == '1'
           && $fs->AdminRequestCheck(2, $task_details['task_id']) != '1'
           && isset($current_user['user_id']))
         {
         ?>
               <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
               <?php echo $details_text['reopenrequest']; ?>
               </a>
               <div id="closeform">
                  <form name="form3" action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formclosetask">
                    <div>
                     <input type="hidden" name="do" value="modify" />
                     <input type="hidden" name="action" value="requestreopen" />
                     <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
                     <label for="reason"><?php echo $details_text['givereason'];?></label>
                     <textarea id="reason" name="reason_given"></textarea><br />
                     <input class="adminbutton" type="submit" value="<?php echo $details_text['submitreq'];?>" />
                    </div>
                  </form>
               </div>
         <?php
         // End of re-opening a task
         }

    // Get info on the dependencies again
    $check_deps = $db->Query("SELECT * FROM {$dbprefix}dependencies d
                                LEFT JOIN {$dbprefix}tasks t on d.dep_task_id = t.task_id
                                WHERE d.task_id = ?",
                                array($_GET['id']));
    // Cycle through the dependencies, checking if any are still open
    while ($deps_details = $db->FetchArray($check_deps)) {
      if ($deps_details['is_closed'] != '1') {
        $deps_open = 'yes';
      };
    };

    // Check permissions and task status, then show the "close task" form
   if (@$effective_permissions['can_close'] == '1'
       && $task_details['is_closed'] != '1'
       && $deps_open != 'yes')
   {
   ?>
      <a href="#close" id="closetask" class="button" onclick="showhidestuff('closeform');">
      <?php
      echo $details_text['closetask'];
      ?></a>
      <div id="closeform">
         <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formclosetask">
           <div>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="close" />
            <input type="hidden" name="assigned_to" value="<?php echo $task_details['assigned_to'];?>" />
            <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />

            <select class="adminlist" name="resolution_reason">
               <option value="0"><?php echo $details_text['selectareason']; ?></option>
               <?php
               $get_resolution = $db->Query("SELECT resolution_id, resolution_name
                                             FROM {$dbprefix}list_resolution
                                             WHERE (project_id = '0'
                                             OR project_id = ?)
                                             AND show_in_list = '1'
                                             ORDER BY list_position",
                                             array($project_id)
                                           );

               while ($row = $db->FetchArray($get_resolution))
               {
                  echo "<option value=\"{$row['resolution_id']}\">{$row['resolution_name']}</option>\n";
               }
               ?>
            </select>

            <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $details_text['closetask'];?>" />
            <?php echo $details_text['closurecomment'];?>
            <textarea class="admintext" name="closure_comment" rows="3" cols="30"></textarea>
            <input type="checkbox" name="mark100" value="1" checked="checked" />&nbsp;&nbsp;<?php echo $details_text['mark100'];?>
           </div>
         </form>
      </div>


   <?php
   // If the user is assigned this task but can't close it, show a button to request closure
   } elseif (@$effective_permissions['can_close'] != '1'
       && !isset($deps_open)
       && isset($current_user)
       && $task_details['assigned_to'] == $current_user['user_id']
       && $fs->AdminRequestCheck(1, $task_details['task_id']) != '1')
   {
   ?>
      <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
      <?php echo $details_text['requestclose']; ?>
      </a>
      <div id="closeform">
<!--          <a id="hideclosetask" href="#close" onclick="hidestuff('closeform');"></a> -->
         <form name="form3" action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formclosetask">
           <div>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="requestclose" />
            <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
            <label for="reason"><?php echo $details_text['givereason'];?></label>
            <textarea id="reason" name="reason_given"></textarea><br />
            <input class="adminbutton" type="submit" value="<?php echo $details_text['submitreq'];?>" />
           </div>
         </form>
      </div>


   <?php
   }

   // Check permissions and task status, then show the "take ownership" button
   if (@$effective_permissions['can_take_ownership'] == '1' && $task_details['is_closed'] != '1')
   {
      echo '<a id="own" class="button" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=takeownership&amp;ids=' . $_GET['id'] . '">' . $details_text['assigntome'] . '</a> ';
   }

   // Check permissions, then show the "edit task" button
   if (@$effective_permissions['can_edit'] == '1'
   && $task_details['is_closed'] != '1')
   {
      echo '<a id="edittask" class="button" href="' . $fs->CreateURL('edittask', $_GET['id']) . '">' . $details_text['edittask'] . '</a> ';
   }

   // Start of marking private/public
   if (@$permissions['manage_project'] == '1'
        && $task_details['is_closed'] != '1'
        && $task_details['mark_private'] != '1')
   {
      echo '<a id="private" class="button" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=makeprivate&amp;id=' . $_GET['id'] . '">' . $details_text['makeprivate'] . '</a> ';

   } elseif (@$permissions['manage_project'] == '1'
        && $task_details['is_closed'] != '1'
        && $task_details['mark_private'] == '1')
   {
      echo '<a id="public" class="button" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=makepublic&amp;id=' . $_GET['id'] . '">' . $details_text['makepublic'] . '</a> ';
   }

   if (!empty($current_user['user_id']) && $task_details['is_closed'] != '1')
   {
      $result = $db->Query("SELECT * FROM {$dbprefix}notifications
                            WHERE task_id = ?
                            AND user_id = ?",
                            array($_GET['id'], $current_user['user_id'])
                          );
      if (!$db->CountRows($result))
      {
         echo '<a id="addnotif" class="button" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=add_notification&amp;ids=' . $_GET['id'] . '&amp;user_id=' . $current_user['user_id'] . '">' . $details_text['watchtask'] . '</a>';

      } else
      {
         echo '<a id="removenotif" class="button" href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=remove_notification&amp;ids=' . $_GET['id'] . '&amp;user_id=' . $current_user['user_id'] . '">' . $details_text['stopwatching'] . '</a>';
      }
   }

   // End of actionbuttons area
   echo '</div>';
   // End of taskdetails area
   echo '</div>';

/////////////////////////////////////////////////
// End of checking if a job should be editable //
/////////////////////////////////////////////////
}
?>


<?php
////////////////////////////
// Start the tabbed areas //
////////////////////////////

$num_comments = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}comments WHERE task_id = ?", array($task_details['task_id'])));
//$num_attachments = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}attachments WHERE task_id = ?", array($task_details['task_id'])));
$num_related = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}related WHERE this_task = ?", array($task_details['task_id'])));
$num_related_to = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}related WHERE related_task = ?", array($task_details['task_id'])));
$num_notifications = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}notifications WHERE task_id = ?", array($_GET['id'])));
$num_reminders = $db->CountRows($db->Query("SELECT * FROM {$dbprefix}reminders WHERE task_id = ?", array($_GET['id'])));
?>

<ul id="submenu">

   <?php
   if (@$permissions['view_comments'] == '1' OR @$permissions['add_comments'] == '1' OR $project_prefs['others_view'] == '1')
   {
      echo '<li id="commentstab"><a href="#comments">'. $details_text['comments'] . " ($num_comments)" . '</a></li>';
   }

   if (@$permissions['view_attachments'] == '1' OR $project_prefs['others_view'] == '1')
   {
      //echo '<li id="attachtab"><a href="#attach">' . $details_text['attachments'] . " ($num_attachments)" . '</a></li>';
   }


   echo '<li id="relatedtab"><a href="#related">' . $details_text['relatedtasks'] . " ($num_related/$num_related_to)" . '</a></li>';

   if (@$permissions['manage_project'] == '1')
   {
      echo '<li id="notifytab"><a href="#notify">' . $details_text['notifications'] . " ($num_notifications) " . '</a></li>';
      echo '<li id="remindtab"><a href="#remind">' . $details_text['reminders'] . " ($num_reminders)" . '</a></li>';
   }

   if (@$permissions['view_history'] == '1')
   {
      echo '<li id="historytab"><a href="#history">' . $details_text['history'] . '</a></li>';
   }
   ?>

</ul>


<?php
////////////////////////////
// Start of comments area //
////////////////////////////
?>

<div id="comments" class="tab">
<?php
// if there are comments, show them
$getcomments = $db->Query("SELECT * FROM {$dbprefix}comments
                           WHERE task_id = ?
                           ORDER BY date_added ASC",
                           array($task_details['task_id'])
                         );

while ($row = $db->FetchArray($getcomments))
{
   $user_info        = $fs->getUserDetails($row['user_id']);
   $formatted_date   = $fs->formatDate($row['date_added'], true);
   $comment_text     = $fs->formatText($row['comment_text']);

   // If the user has permissions, show the comments already added
   if (@$permissions['view_comments'] == '1' OR $project_prefs['others_view'] == '1')
   {
//       echo $row['comment_id'];
      echo '<em><a name="comment' . $row['comment_id'] . '" id="comment' . $row['comment_id'] . '" href="' . $fs->CreateURL('details', $task_details['task_id']) . '#comment' . $row['comment_id'] . '">';
      echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/menu/comment.png" title="' . $details_text['commentlink'] . '" alt="" /></a>';
      echo $details_text['commentby']. ' ' . $fs->LinkedUserName($row['user_id']) . ' - ' . $formatted_date . "</em>\n";

      // If the user has permission, show the edit button
      if (@$permissions['edit_comments'] == '1')
      {
         echo '<span class="DoNotPrint">&nbsp; - <a href="' . $conf['general']['baseurl'] . '?do=editcomment&amp;task_id=' . $_GET['id'] . '&amp;id=' . $row['comment_id'] . '">' . $details_text['edit'] . '</a>';
      }
      // If the user has permission, show the delete button
      if (@$permissions['delete_comments'] == '1')
      {
         ?>
         &nbsp;-&nbsp;<a href="<?php echo $conf['general']['baseurl'];?>?do=modify&amp;action=deletecomment&amp;task_id=<?php echo $_GET['id'];?>&amp;comment_id=<?php echo $row['comment_id'];?>"
         onclick="if(confirm('<?php echo $details_text['confirmdeletecomment'];?>')) {
         return true
         } else {
         return false }
         ">
         <?php
         echo $details_text['delete'] . '</a></span>';
      }

      echo '<p class="comment">';
      echo $comment_text;
      echo '</p>';

      $attachments = $db->Query("SELECT * FROM {$dbprefix}attachments
                                 WHERE comment_id = ?
                                 ORDER BY attachment_id ASC",
                                 array($row['comment_id'])
                                );

      if (@$permissions['view_attachments'] == '1' OR $project_prefs['others_view'] == '1')
      {


         while ($attachment = $db->FetchArray($attachments))
         {
            echo '<span class="attachments">';
            echo '<a href="' . $conf['general']['baseurl'] . '?getfile=' . $attachment['attachment_id'] . '" title="' . $attachment['file_type'] . '">';

            // Let's strip the mimetype to get the icon image name
            list($main, $specific) = split('[/]', $attachment['file_type']);

            $imgpath = $basedir . "{$conf['general']['baseurl']}themes/{$project_prefs['theme_style']}/mime/{$attachment['file_type']}.png";
            if (file_exists($imgpath))
            {
               echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/mime/' . $attachment['file_type'] . '.png" title="' . $attachment['file_type'] . '" />';
            }else
            {
               echo '<img src="' . $conf['general']['baseurl'] . 'themes/' . $project_prefs['theme_style'] . '/mime/' . $main . '.png" title="' . $attachment['file_type'] . '" />';
            }

            echo '&nbsp;&nbsp;' . $attachment['orig_name'];
            echo "</a>\n";

            // Delete link
            if (@$permissions['delete_attachments'] == '1')
            {
            ?>
            &nbsp;-&nbsp;<a href="<?php echo $conf['general']['baseurl'];?>?do=modify&amp;action=deleteattachment&amp;id=<?php echo $attachment['attachment_id'];?>"
            onclick="if(confirm('<?php echo $details_text['confirmdeleteattach'];?>')) {
            return true
            } else {
            return false }
            ">
            <?php
            echo $details_text['delete'] . '</a>';
            }
            echo '</span>';
         }

         echo '<br />';

      // End of permission check
      }

         if ($db->CountRows($attachments)
             && ((!isset($_COOKIE['flyspray_userid']) OR @$permissions['view_attachments'] != '1') && $project_prefs['others_view'] != '1') )
         {
            echo '<span class="attachments">' . $details_text['attachnoperms'] . '</span><br />';
         }
   }

// End of cycling through the comments for display
}

// Now, show a form to add a comment (but only if the user has the rights!)

if (@$permissions['add_comments'] == "1" && $task_details['is_closed'] != '1')
{
?>
   <form enctype="multipart/form-data" action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
   <div class="admin">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addcomment" />
      <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
      <?php echo $details_text['addcomment'];?>
      <textarea id="comment_text" name="comment_text" cols="72" rows="10"></textarea>

      <?php if (@$permissions['create_attachments'] == '1') { ?>
      <div id="uploadfilebox">
         <?php echo $details_text['uploadafile'];?>
         <input type="file" size="55" name="userfile[]" /><br />
      </div>

      <input class="adminbutton" type="button" onclick="addUploadFields()" value="<?php echo $details_text['selectmorefiles'];?>" />
      <?php } ?>

      <input class="adminbutton" type="submit" value="<?php echo $details_text['addcomment'];?>" />
      <?php
      $check_watch = $db->Query("SELECT user_id
                                 FROM {$dbprefix}notifications
                                 WHERE user_id = ?
                                 AND task_id = ?",
                                 array($current_user['user_id'], $task_details['task_id'])
                               );
      if ( !$db->CountRows($check_watch) )
         echo "<input name=\"notifyme\" type=\"checkbox\" value=\"1\" checked=\"checked\" />{$newtask_text['notifyme']}";
      ?>

   </div>

   </form>

<?php
// End of checking if the comments form should be displayed
};

echo '</div>';

// End of comments area

////////////////////////////////////
// Start of file attachments area //
////////////////////////////////////

if (@$permissions['view_attachments'] == '1' OR $project_prefs['others_view'] == '1')
{/*
   echo '<div id="attach" class="tab">';


   // if there are attachments, show them
   $getattachments = $db->Query("SELECT * FROM {$dbprefix}attachments WHERE task_id = ?", array($task_details['task_id']));
   while ($row = $db->FetchArray($getattachments))
   {
      $getusername = $db->Query("SELECT real_name FROM {$dbprefix}users WHERE user_id = ?", array($row['added_by']));
      list($user_name) = $db->FetchArray($getusername);
      $formatted_date = $fs->formatDate($row['date_added'], true);
      $file_desc = stripslashes($row['file_desc']);

   //  "Deleting attachments" code contributed by Harm Verbeek <info@certeza.nl>
   if (@$permissions['delete_attachments'] == '1')
   {
   ?>
      <div class="modifycomment">
      <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" onSubmit="if(confirm('Really delete this attachment?')) {return true} else {return false }">
      <p>
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="deleteattachment" />
         <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
         <input type="hidden" name="attachment_id" value="<?php echo $row['attachment_id'];?>" />
         <input class="adminbutton" type="submit" value="<?php echo $details_text['delete'];?>" />
      </p>
      </form>
      </div>
   <?php
   }

// Divide the attachments area into two columns for display
echo "<table><tr><td><p>";

// Detect if the attachment is an image
$pos = strpos($row['file_type'], "image/");
if($pos===0 && $project_prefs['inline_images'] == '1')
{
   // Find out the size of the image
   list($width, $height, $type, $string) = getimagesize("attachments/{$row['file_name']}");

   // If the image is too wide, let's scale it down so that it doesn't destroy the page layout
   if ($width > "200")
   {
      $v_fraction = 200/$width;
      $new_height = round(($height*$v_fraction),0);

      // Display the resized image, with a link to the fullsized one
      echo '<a href="' . $conf['general']['baseurl'] . "?getfile={$row['attachment_id']}\"><img src=\"?getfile={$row['attachment_id']}\" width=\"$new_height\" alt=\"\" /></a>";
   } else
   {
      // If the image is already small, just display it.
      echo "<br /><img src=\"?getfile={$row['attachment_id']}\" />";
   }

// If the attachment isn't an image, or the inline images is OFF,
// show a mimetype icon instead of a thumbnail
} else
{
   // Let's strip the mimetype to get the image name
   list($main, $specific) = split('[/]', $row['file_type']);
   echo $fs->ShowImg("themes/{$project_prefs['theme_style']}/mime/{$row['file_type']}.png", $row['file_type']);
}

   // The second column, for the descriptions
   echo "</p></td><td>";
   echo "<table>";
   echo "<tr><td><em>{$details_text['filename']}</em></td><td><a href=\"?getfile={$row['attachment_id']}\">{$row['orig_name']}</a></td></tr>";
   echo "<tr><td><em>{$details_text['description']}</em></td><td>$file_desc</td></tr>";
   echo "<tr><td><em>{$details_text['fileuploadedby']}</em></td><td>" . $fs->LinkedUserName($row['added_by']) . '</td></tr>';
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

// End of cycling through the attachments for display
};

// Now, show a form to attach a file (but only if the user has the rights!)
if (@$permissions['create_attachments'] == "1" && $task_details['is_closed'] != '1')
{ ?>

   <form enctype="multipart/form-data" action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formupload">
   <table class="admin">
      <tr>
         <td>
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="addattachment" />
         <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
         <label><?php echo $details_text['uploadafile'];?></label>
         </td>
         <td>
         <input type="file" size="55" name="userfile" />
         </td>
      </tr>
      <tr>
         <td>
         <label><?php echo $details_text['description'];?></label>
         </td>
         <td>
         <input class="admintext" type="text" name="file_desc" size="70" maxlength="100" />
         </td>
      </tr>
      <tr>
         <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $details_text['uploadnow'];?>" /></td>
      </tr>
   </table>
   </form>

<?php
// End of checking permissions to upload a new attachment
}
// End of attachments area
echo '</div>';
*/}

/////////////////////////////////
// Start of related tasks area //
/////////////////////////////////
?>

<div id="related" class="tab">

   <p><em><?php echo $details_text['thesearerelated'];?></em></p>
   <?php
   $get_related = $db->Query("SELECT *
                              FROM {$dbprefix}related r
                              LEFT JOIN {$dbprefix}tasks t ON r.related_task = t.task_id
                              WHERE r.this_task = ?",
                              array($_GET['id'])
                             );

   while ($row = $db->FetchArray($get_related))
   {
      $summary = stripslashes($row['item_summary']);
      ?>
      <?php
      // If the user can modify jobs, then show them a form to remove related tasks
      if (@$effective_permissions['can_edit'] == '1' && $task_details['is_closed'] != '1')
      {
      ?>
         <div class="modifycomment">
            <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
            <p>
               <input type="hidden" name="do" value="modify" />
               <input type="hidden" name="action" value="remove_related" />
               <input type="hidden" name="id" value="<?php echo $_GET['id'];?>" />
               <input type="hidden" name="related_id" value="<?php echo $row['related_id'];?>" />
               <input type="hidden" name="related_task" value="<?php echo $row['related_task'];?>" />
               <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>" />
               </p>
            </form>
         </div>

      <?php
      // End of checking for permission to remove a related task
      }
      echo '<p><a href="' . $fs->CreateURL('details', $row['related_task']) . '">FS#' . $row['related_task'] . ' &mdash; ' . stripslashes($row['item_summary']) . '</a></p>';

      //echo '<br />' . $row['item_summary'];

   // End of cycling through related tasks
   }

   if (@$effective_permissions['can_edit'] == "1" && $task_details['is_closed'] != '1')
   {
   ?>
      <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formaddrelatedtask">
         <p class="admin">
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="add_related" />
         <input type="hidden" name="this_task" value="<?php echo $_GET['id'];?>" />
         <label><?php echo $details_text['addnewrelated'];?>
         <input name="related_task" size="10" maxlength="10" /></label>
         <input class="adminbutton" type="submit" value="<?php echo $details_text['add'];?>" />
         </p>
      </form>

   <?php
   }
   ?>


   <p><em><?php echo $details_text['otherrelated'];?></em></p>
   <?php
   $get_related = $db->Query("SELECT *
                              FROM {$dbprefix}related r
                              LEFT JOIN {$dbprefix}tasks t ON r.this_task = t.task_id
                              WHERE r.related_task = ?",
                              array($_GET['id'])
                            );

   while ($row = $db->FetchArray($get_related))
   {
      echo '<p>';
      $summary = stripslashes($row['summary']);
      echo '<a href="' . $fs->CreateURL('details', $row['this_task']) . '">FS#' . $row['this_task'] . ' &mdash; ' . stripslashes($row['item_summary']) . '</a><br />';
      echo '</p>';
   }

// End of related area
echo '</div>';

/////////////////////////////////
// Start of notifications area //
/////////////////////////////////

if (@$permissions['manage_project'] == '1')
{
?>

   <div id="notify" class="tab">
      <p><em><?php echo $details_text['theseusersnotify'];?></em></p>

      <?php
      $get_user_ids = $db->Query("SELECT * FROM {$dbprefix}notifications n
                                  LEFT JOIN {$dbprefix}users u ON n.user_id = u.user_id
                                  WHERE n.task_id = ?",
                                  array($_GET['id'])
                                );

      while ($row = $db->FetchArray($get_user_ids))
      {
         echo '<p>' . $fs->LinkedUserName($row['user_id']) . ' &mdash; <a href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=remove_notification&amp;ids=' . $_GET['id'] . '&amp;user_id=' . $row['user_id'] . '">' . $details_text['remove'] . '</a></p>';
      }

      if (@$permissions['manage_project'] == '1')
      {
      ?>
         <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="get">
            <p class="admin">
            <?php echo $details_text['addusertolist'];?>
            <select class="adminlist" name="user_id">
               <?php
               // Get list of users
               $fs->listUsers($novar, $project_id);
               ?>
            </select>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="add_notification" />
            <input type="hidden" name="ids" value="<?php echo $_GET['id'];?>" />
            <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
            <input class="adminbutton" type="submit" value="<?php echo $details_text['addtolist'];?>" />
            </p>
         </form>
      <?php
      }

      echo "</div>";

// End of checking PM permissions
}

// End of notifications area

///////////////////////////////////////
// Start of scheduled reminders area //
///////////////////////////////////////
?>

<div id="remind" class="tab">

  <?php
    $get_reminders = $db->Query("SELECT *
                                 FROM {$dbprefix}reminders r
                                 LEFT JOIN {$dbprefix}users u ON r.to_user_id = u.user_id
                                 WHERE task_id = ?
                                 ORDER BY reminder_id",
                                 array($_GET['id'])
                               );

   while ($row = $db->FetchArray($get_reminders))
   {
      // If the user has permission, then show them a form to remove a reminder
      if ((@$permissions['is_admin'] == '1' OR @$permissions['manage_project'] == '1') && $task_details['is_closed'] != '1')
      {
      ?>
         <div class="modifycomment">
         <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
            <p>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="deletereminder" />
            <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />
            <input type="hidden" name="reminder_id" value="<?php echo $row['reminder_id'];?>" />
            <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>" />
            </p>
         </form>
         </div>


      <?php
      // End of checking permissions to remove a reminder
      }
      echo "<em>{$details_text['remindthisuser']}:</em> <a href=\"?do=admin&amp;area=users&amp;id={$row['to_user_id']}\">{$row['real_name']} ( {$row['user_name']})</a><br />";

      // Work out the unit of time to display
      if ($row['how_often'] < 86400)
      {
         $how_often = $row['how_often'] / 3600 . " " . $details_text['hours'];
      } elseif ($row['how_often'] < 604800)
      {
         $how_often = $row['how_often'] / 86400 . " " . $details_text['days'];
      } else
      {
         $how_often = $row['how_often'] / 604800 . " " . $details_text['weeks'];
      }

      echo "<em>{$details_text['thisoften']}:</em> $how_often";
      echo "<br />";
      echo '<em>' . $details_text['message'] . ':</em>' . nl2br($row['reminder_message']);
      echo "<br /><br />";

   // End of cycling through reminders
   }

   // Show a form to add a new reminder
   if (@$permissions['is_admin'] == '1' && $task_details['is_closed'] != '1')
   {
   ?>
      <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formaddreminder">
         <p class="admin">
         <input type="hidden" name="do" value="modify" />
         <input type="hidden" name="action" value="addreminder" />
         <input type="hidden" name="task_id" value="<?php echo $_GET['id'];?>" />

         <em><?php echo $details_text['remindthisuser'];?></em>

         <select class="adminlist" name="to_user_id">
            <?php
            // Get list of users
            $fs->listUsers($novar, $project_id);
            ?>
         </select>

         <br />

         <em><?php echo $details_text['thisoften'];?></em>

         <input type="text" name="timeamount1" size="3" maxlength="3" />

         <select class="adminlist" name="timetype1">
            <option value="3600"><?php echo $details_text['hours'];?></option>
            <option value="86400"><?php echo $details_text['days'];?></option>
            <option value="604800"><?php echo $details_text['weeks'];?></option>
         </select>

         <br />

         <em><?php echo $details_text['startafter'];?></em>

         <input type="text" name="timeamount2" size="3" maxlength="3" />

         <select class="adminlist" name="timetype2">
            <option value="3600"><?php echo $details_text['hours'];?></option>
            <option value="86400"><?php echo $details_text['days'];?></option>
            <option value="604800"><?php echo $details_text['weeks'];?></option>
         </select>

         <br />

         <textarea class="admintext" name="reminder_message" rows="10" cols="72"><?php echo $details_text['defaultreminder'] . "\n\n" . $fs->CreateURL('details', $_GET['id']);?></textarea>

         <br />

         <input class="adminbutton" type="submit" value="<?php echo $details_text['addreminder'];?>" />
         </p>
      </form>

   <?php
   // End of checking permissions to add a new reminder
   }
// End of scheduled reminders area
echo '</div>';

//////////////////////////
// Start of History Tab //
//////////////////////////

if (@$permissions['view_history'] == '1')
{
?>

<div id="history" class="tab">

   <table class="history">
      <tr>
         <th><?php echo $details_text['eventdate'];?></th>
         <th><?php echo $details_text['user'];?></th>
         <th><?php echo $details_text['event'];?></th>
      </tr>
      <?php
      if (isset($_GET['details']) && is_numeric($_GET['details']))
      {
         $details = " AND h.history_id = {$_GET['details']}";

         echo '<b>' . $details_text['selectedhistory'] . '</b>';
         echo '&nbsp;&mdash;&nbsp;<a href="?do=details&amp;id=' . $_GET['id'] . '#history">' . $details_text['showallhistory'] . '</a>';
      } else
      {
         $details = '';
      }

      $query_history = $db->Query("SELECT h.*, u.user_name, u.real_name
                                   FROM {$dbprefix}history h
                                   LEFT JOIN {$dbprefix}users u ON h.user_id = u.user_id
                                   WHERE h.task_id = ? {$details}
                                   ORDER BY h.event_date ASC, h.event_type ASC",
                                   array($_GET['id'])
                                 );

      if ($db->CountRows($query_history) == 0)
      { ?>
      <tr>
         <td colspan="3"><?php echo $details_text['nohistory'];?></td>
      </tr>
      <?php
      }

      while ($history = $db->FetchRow($query_history))
      {
      ?>
      <tr>
         <td><?php echo $fs->formatDate($history['event_date'], true);?></td>
         <td>
         <?php
         if ($history['user_id'] == 0)
         {
            echo $details_text['anonymous'];
         } else
         {
            echo $fs->LinkedUserName($history['user_id']);
         }
         ?>
         </td>
            <td><?php
            $newvalue = $history['new_value'];
            $oldvalue = $history['old_value'];

            //Create an event description
            if ($history['event_type'] == 0) {            //Field changed

                $field = $history['field_changed'];

                switch ($field) {
                case 'item_summary':
                    $field = $details_text['summary'];
                    $oldvalue = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                    $newvalue = htmlspecialchars($newvalue,ENT_COMPAT,'utf-8');
                    if (!get_magic_quotes_gpc()) {
                      $oldvalue = str_replace("\\", "&#92;", $oldvalue);
                      $newvalue = str_replace("\\", "&#92;", $newvalue);
                    };
                    $oldvalue = stripslashes($oldvalue);
                    $newvalue = stripslashes($newvalue);
                    break;
                case 'attached_to_project':
                    $field = $details_text['attachedtoproject'];
                    list($oldprojecttitle) = $db->FetchRow($db->Query("SELECT project_title FROM {$dbprefix}projects WHERE project_id = ?", array($oldvalue)));
                    list($newprojecttitle) = $db->FetchRow($db->Query("SELECT project_title FROM {$dbprefix}projects WHERE project_id = ?", array($newvalue)));
                    $oldvalue = "<a href=\"?project={$oldvalue}\">{$oldprojecttitle}</a>";
                    $newvalue = "<a href=\"?project={$newvalue}\">{$newprojecttitle}</a>";
                    break;
                case 'task_type':
                    $field = $details_text['tasktype'];
                    list($oldvalue) = $db->FetchRow($db->Query("SELECT tasktype_name FROM {$dbprefix}list_tasktype WHERE tasktype_id = ?", array($oldvalue)));
                    list($newvalue) = $db->FetchRow($db->Query("SELECT tasktype_name FROM {$dbprefix}list_tasktype WHERE tasktype_id = ?", array($newvalue)));
                    break;
                case 'product_category':
                    $field = $details_text['category'];
                    list($oldvalue) = $db->FetchRow($db->Query("SELECT category_name FROM {$dbprefix}list_category WHERE category_id = ?", array($oldvalue)));
                    list($newvalue) = $db->FetchRow($db->Query("SELECT category_name FROM {$dbprefix}list_category WHERE category_id = ?", array($newvalue)));
                    break;
                case 'item_status':
                    $field = $details_text['status'];
                    $oldvalue = $status_list[$oldvalue];
                    $newvalue = $status_list[$newvalue];
                    break;
                case 'task_priority':
                    $field = $details_text['priority'];
                    $oldvalue = $priority_list[$oldvalue];
                    $newvalue = $priority_list[$newvalue];
                    break;
                case 'operating_system':
                    $field = $details_text['operatingsystem'];
                    list($oldvalue) = $db->FetchRow($db->Query("SELECT os_name FROM {$dbprefix}list_os WHERE os_id = ?", array($oldvalue)));
                    list($newvalue) = $db->FetchRow($db->Query("SELECT os_name FROM {$dbprefix}list_os WHERE os_id = ?", array($newvalue)));
                    break;
                case 'task_severity':
                    $field = $details_text['severity'];
                    $oldvalue = $severity_list[$oldvalue];
                    $newvalue = $severity_list[$newvalue];
                    break;
                case 'product_version':
                    $field = $details_text['reportedversion'];
                    list($oldvalue) = $db->FetchRow($db->Query("SELECT version_name FROM {$dbprefix}list_version WHERE version_id = ?", array($oldvalue)));
                    list($newvalue) = $db->FetchRow($db->Query("SELECT version_name FROM {$dbprefix}list_version WHERE version_id = ?", array($newvalue)));
                    break;
                case 'closedby_version':
                    $field = $details_text['dueinversion'];
                    if ($oldvalue == '0') {
                        $oldvalue = $details_text['undecided'];
                    } else {
                        list($oldvalue) = $db->FetchRow($db->Query("SELECT version_name
                        FROM {$dbprefix}list_version
                        WHERE version_id = ?", array($db->emptyToZero($oldvalue))));
                    };
                    if ($newvalue == '0') {
                        $newvalue = $details_text['undecided'];
                    } else {
                        list($newvalue) = $db->FetchRow($db->Query("SELECT version_name
                        FROM {$dbprefix}list_version
                        WHERE version_id = ?", array($db->emptyToZero($newvalue))));
                    };
                    break;
                 case 'due_date':
                     $field = $details_text['duedate'];
                     if (empty($oldvalue))
                     {
                        $oldvalue = $details_text['undecided'];
                     } else
                     {
                        $oldvalue = $fs->FormatDate($oldvalue, false);
                     }
                     if (empty($newvalue))
                     {
                        $newvalue = $details_text['undecided'];
                     } else
                     {
                        $newvalue = $fs->FormatDate($newvalue, false);
                     }
                     break;
                case 'percent_complete':
                    $field = $details_text['percentcomplete'];
                    $oldvalue .= '%';
                    $newvalue .= '%';
                    break;
                case 'detailed_desc':
                    $field = "<a href=\"index.php?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['details']}</a>";
                    if (!empty($details))
                    {
                       $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                       $details_new = htmlspecialchars($newvalue,ENT_COMPAT,'utf-8');
                       if (!get_magic_quotes_gpc())
                       {
                          $details_previous = str_replace("\\", "&#92;", $details_previous);
                          $details_new = str_replace("\\", "&#92;", $details_new);
                       }
                       $details_previous = nl2br(stripslashes($details_previous));
                       $details_new = nl2br(stripslashes($details_new));
                    }
                    $oldvalue = '';
                    $newvalue = '';
                    break;
                };

                echo "{$details_text['fieldchanged']}: {$field}";
                if ($oldvalue != '' || $newvalue != '') {
                    echo " ({$oldvalue} &nbsp;&nbsp;&rarr; {$newvalue})";
                };

            } elseif ($history['event_type'] == '1') {      //Task opened
                echo $details_text['taskopened'];

            } elseif ($history['event_type'] == '2') {      //Task closed
                echo $details_text['taskclosed'];
                $res_name = $db->FetchRow($db->Query("SELECT resolution_name FROM {$dbprefix}list_resolution WHERE resolution_id = ?", array($newvalue)));
                echo " ({$res_name['resolution_name']}";
                if (!empty($oldvalue))
                {
                  echo ': ' . $fs->formatText($oldvalue);
                }
                echo ')';

            } elseif ($history['event_type'] == '3') {      //Task edited
                echo $details_text['taskedited'];

            } elseif ($history['event_type'] == '4') {      //Comment added
                echo '<a href="#comments">' . $details_text['commentadded'] . '</a>';

            } elseif ($history['event_type'] == '5') {      //Comment edited
                echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentedited']}</a>";
                $comment = $db->Query("SELECT user_id, date_added FROM {$dbprefix}comments WHERE comment_id = ?", array($history['field_changed']));
                if ($db->CountRows($comment) != 0) {
                    $comment = $db->FetchRow($comment);
                    echo " ({$details_text['commentby']} " . $fs->LinkedUsername($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
                };
                if ($details != '') {
                    $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                    $details_new = htmlspecialchars($newvalue,ENT_COMPAT,'utf-8');
                    if (!get_magic_quotes_gpc()) {
                      $details_previous = str_replace("\\", "&#92;", $details_previous);
                      $details_new = str_replace("\\", "&#92;", $details_new);
                    };
                    $details_previous = nl2br(stripslashes($details_previous));
                    $details_new = nl2br(stripslashes($details_new));
                };

            } elseif ($history['event_type'] == '6') //Comment deleted
            {
               echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentdeleted']}</a>";
               if ($newvalue != '' && $history['field_changed'] != '')
               {
                  echo " ({$details_text['commentby']} " . $fs->LinkedUsername($newvalue) . " - " . $fs->formatDate($history['field_changed'], true) . ")";
               }
               if (!empty($details))
               {
                  $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                  if (!get_magic_quotes_gpc())
                  {
                     $details_previous = str_replace("\\", "&#92;", $details_previous);
                  }
                  $details_previous = nl2br(stripslashes($details_previous));
                  $details_new = '';
               }

            } elseif ($history['event_type'] == '7')      //Attachment added
            {
               echo $details_text['attachmentadded'];
               $attachment = $db->Query("SELECT orig_name, file_desc FROM {$dbprefix}attachments WHERE attachment_id = ?", array($newvalue));
               if ($db->CountRows($attachment) != 0)
               {
                  $attachment = $db->FetchRow($attachment);
                  echo ": <a href=\"{$baseurl}?getfile={$newvalue}\">{$attachment['orig_name']}</a>";
                  if ($attachment['file_desc'] != '')
                  {
                     echo " ({$attachment['file_desc']})";
                  }
               }

            } elseif ($history['event_type'] == '8')      //Attachment deleted
            {
               echo "{$details_text['attachmentdeleted']}: {$newvalue}";

            } elseif ($history['event_type'] == '9')      //Notification added
            {
               echo "{$details_text['notificationadded']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == '10')    //Notification deleted
            {
               echo "{$details_text['notificationdeleted']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == '11')    //Related task added
            {
          list($related) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $related = stripslashes($related);
               echo "{$details_text['relatedadded']}: <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

            } elseif ($history['event_type'] == '12')    //Related task deleted
            {
               list($related) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $related = stripslashes($related);
               echo "{$details_text['relateddeleted']}: <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

            } elseif ($history['event_type'] == '13')   //Task reopened
            {
               echo $details_text['taskreopened'];

            } elseif ($history['event_type'] == '14')   //Task assigned
            {
               if ($oldvalue == '0')
               {
                  echo "{$details_text['taskassigned']} " . $fs->LinkedUsername($newvalue);
               } elseif ($newvalue == '0')
               {
                  echo $details_text['assignmentremoved'];
               } else
               {
                  echo "{$details_text['taskreassigned']} " . $fs->LinkedUsername($newvalue);
               }

            } elseif ($history['event_type'] == '15')   //Task added to related list of another task
            {
               list($related) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $related = stripslashes($related);
               echo "{$details_text['addedasrelated']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

            } elseif ($history['event_type'] == '16')   //Task deleted from related list of another task
            {
               list($related) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $related = stripslashes($related);
               echo "{$details_text['deletedasrelated']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

            } elseif ($history['event_type'] == '17')   //Reminder added
            {
               echo "{$details_text['reminderadded']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == '18')   //Reminder deleted
            {
               echo "{$details_text['reminderdeleted']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == '19')   //User took ownership
            {
               echo "{$details_text['ownershiptaken']}: " . $fs->LinkedUsername($newvalue);

            } elseif ($history['event_type'] == '20')   //User requested task closure
            {
               echo $details_text['closerequestmade'] . ' - ' . stripslashes($newvalue);

            } elseif ($history['event_type'] == '21')   //User requested task
            {
               echo $details_text['reopenrequestmade'] . ' - ' . stripslashes($newvalue);

            } elseif ($history['event_type'] == '22')   // Dependency added
            {
          list($dependency) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $dependency = stripslashes($dependency);
               echo "{$details_text['depadded']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

            } elseif ($history['event_type'] == '23')   // Dependency added to other task
            {
          list($dependency) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $dependency = stripslashes($dependency);
               echo "{$details_text['depaddedother']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

            } elseif ($history['event_type'] == '24')   // Dependency removed
            {
               list($dependency) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $dependency = stripslashes($dependency);
               echo "{$details_text['depremoved']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

            } elseif ($history['event_type'] == '25')   // Dependency removed from other task
            {
               list($dependency) = $db->FetchRow($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($newvalue)));
          $dependency = stripslashes($dependency);
               echo "{$details_text['depremovedother']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

            } elseif ($history['event_type'] == '26')   // Task marked private
            {
               echo $details_text['taskmadeprivate'];

            } elseif ($history['event_type'] == '27')   // Task privacy removed - task made public
            {
               echo $details_text['taskmadepublic'];

            } elseif ($history['event_type'] == '28')   // PM request denied
            {
               echo $details_text['pmreqdenied'] . ' - ' . stripslashes($newvalue);
            }

            ?>
            </td>
         </tr>

      <?php
      // End of cycling through history entries for this task
      }
      echo '</table>';

      if (isset($_GET['details']) && !empty($_GET['details']))
      {
      ?>
         <table class="history">
            <tr>
               <th><?php echo $details_text['previousvalue'];?></th>
               <th><?php echo $details_text['newvalue'];?></th>
            </tr>
            <tr>
               <td><?php echo $details_previous;?></td>
               <td><?php echo $details_new;?></td>
            </tr>
         </table>
      <?php
      }
      ?>


   <?php
   // End of History Tab
   echo '</div>';
// End of checking for permission to view the history
}
?>

<?php
} else {
// If no task was actually requested, redirect to the error page
   $fs->Redirect( $fs->CreateURL('error', null) );
   //echo "<p><strong>{$details_text['showdetailserror']}</strong></p>";

};
?>
