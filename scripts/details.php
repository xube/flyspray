<?php

/*
   This script displays task details when in view mode, and allows the
   user to edit task details when in edit mode.  It also shows comments,
   attachments, notifications etc.
*/

$fs->get_language_pack('details');
$fs->get_language_pack('newtask');
$fs->get_language_pack('index');
$fs->get_language_pack('status');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');

// Only load this page if a valid task was actually requested
if ( !($task_details = $fs->GetTaskDetails(Get::val('id')))
        || !$user->can_view_task($task_details)) {
   $fs->Redirect( $fs->CreateURL('error', null) );
}

// Create an array with effective permissions for this user on this task
$eff_perms = array();
$eff_perms['can_edit'] = $user->can_edit_task($task_details);
$eff_perms['can_take_ownership'] = $user->can_take_ownership($task_details);
$eff_perms['can_close'] = $user->can_close_task($task_details);

////////////////////////////
// Start the details area //
////////////////////////////

// {{{ edit mode
if ($eff_perms['can_edit'] && $task_details['is_closed'] != '1' && Get::val('edit') == 'yep'):
    ///////////////////////////////////
    // If the user can modify tasks, //
    // and the task is still open,   //
    // and we're in edit mode,       //
    // then use this section.        //
    ///////////////////////////////////
?>
<div id="taskdetails">
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <h2 class="severity<?php echo $task_details['task_severity'];?>">
      <?php echo 'FS#' . $task_details['task_id'];?> &mdash;
      <input class="severity<?php echo $task_details['task_severity'];?>" type="text" name="item_summary" size="50" maxlength="100"
          value="<?php echo htmlspecialchars($task_details['item_summary'],ENT_COMPAT,'utf-8');?>" />
      </h2>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update" />
      <input type="hidden" name="task_id" value="<?php echo Get::val('id');?>" />
      <input type="hidden" name="edit_start_time" value="<?php echo date('U') ?>" />

      <?php echo $details_text['attachedtoproject'] . ' &mdash; ';?>
      <select name="attached_to_project">
        <?php
        if ($user->perms['global_view']) {
            // If the user has permission to view all projects
            $get_projects = $db->Query("SELECT  * FROM {projects}
                                         WHERE  project_is_active = '1'
                                      ORDER BY  project_title");
        }
        elseif (!$user->isAnon()) {
            // or, if the user is logged in
            $get_projects = $db->Query("SELECT  p.*
                                          FROM  {projects}        p
                                     LEFT JOIN  {groups}          g   ON p.project_id=g.belongs_to_project AND g.view_tasks=1
                                     LEFT JOIN  {users_in_groups} uig ON uig.group_id = g.group_id AND uig.user_id = ?
                                         WHERE  p.project_is_active='1' AND (p.others_view OR uig.user_id IS NOT NULL)
                                      ORDER BY  p.project_title", array($user->id));
        }
        else {
            // Anonymous users
            $get_projects = $db->Query("SELECT  * FROM {projects}
                                         WHERE  project_is_active = '1' AND others_view = '1'
                                      ORDER BY  project_title");
        }

        while ($row = $db->FetchArray($get_projects)) {
            if ($proj->id == $row['project_id']) {
                echo '<option value="' . $row['project_id'] . '" selected="selected">' . $row['project_title'] . '</option>';
            } else {
                echo '<option value="' . $row['project_id'] . '">' . $row['project_title'] . '</option>';
            }
        }
        ?>
      </select>

      <div id="fineprint">
        <?php
        // Get the user details of the person who opened this item
        if ($task_details['opened_by']) {
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {users} WHERE user_id = ?", array($task_details['opened_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);
        } else {
            $user_name = $details_text['anonymous'];
        }

        $date_opened = $fs->formatDate($task_details['date_opened'], true);

        echo $details_text['openedby'] . ' ' . tpl_userlink($task_details['opened_by']) . ' - ' . $date_opened;

        if ($task_details['last_edited_by']) {
            // If it's been edited, get the details
            $get_user_name = $db->Query("SELECT user_name, real_name FROM {users} WHERE user_id = ?", array($task_details['last_edited_by']));
            list($user_name, $real_name) = $db->FetchArray($get_user_name);

            $date_edited = $fs->formatDate($task_details['last_edited_time'], true);

            echo '<br />' . $details_text['editedby'] . ' ' . tpl_userlink($task_details['last_edited_by']) . ' - ' . $date_edited;
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
                $get_tasktypes = $db->Query("SELECT  tasktype_id, tasktype_name FROM {list_tasktype}
                                              WHERE  show_in_list = '1' AND (project_id = '0' OR project_id = ?)
                                           ORDER BY  list_position", array($proj->id));

                while ($row = $db->FetchArray($get_tasktypes)) {
                    if ($row['tasktype_id'] == $task_details['task_type']) {
                        echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>";
                    } else {
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
                $cat_list = $db->Query("SELECT  category_id, category_name
                                          FROM  {list_category}
                                         WHERE  show_in_list = '1' AND parent_id < '1' AND (project_id = '0' OR project_id = ?)
                                      ORDER BY  list_position", array($proj->id));

                while ($row = $db->FetchArray($cat_list)) {
                    $category_name = $row['category_name'];

                    if ($task_details['product_category'] == $row['category_id']) {
                        echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
                    } else {
                        echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
                    }

                    $subcat_list = $db->Query("SELECT  category_id, category_name
                                                FROM  {list_category}
                                               WHERE  show_in_list = '1' AND parent_id = ?
                                            ORDER BY  list_position", array($row['category_id']));

                    while ($subrow = $db->FetchArray($subcat_list)) {
                        $subcategory_name = $subrow['category_name'];
                        if ($task_details['product_category'] == $subrow['category_id']) {
                            echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&nbsp;&nbsp;&rarr;$subcategory_name</option>\n";
                        } else {
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
                foreach($status_list as $key => $val) {
                    if ($task_details['item_status'] == $key) {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                    } else {
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
                if ($task_details['assigned_to'] == "0") {
                    echo "<option value=\"0\" selected=\"selected\">{$details_text['noone']}</option>\n";
                } else {
                    echo "<option value=\"0\">{$details_text['noone']}</option>\n";
                }

                $fs->ListUsers($proj->id, $task_details['assigned_to']);
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
                $get_os = $db->Query("SELECT  os_id, os_name
                                        FROM  {list_os}
                                       WHERE  (project_id = ?  OR project_id = '0') AND show_in_list = '1'
                                    ORDER BY  list_position", array($proj->id));

                while ($row = $db->FetchArray($get_os)) {
                    if ($row['os_id'] == $task_details['operating_system']) {
                        echo "<option value=\"{$row['os_id']}\" selected=\"selected\">{$row['os_name']}</option>";
                    } else {
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
                foreach($severity_list as $key => $val) {
                    if ($task_details['task_severity'] == $key) {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                    } else {
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
                foreach($priority_list as $key => $val) {
                    if ($task_details['task_priority'] == $key) {
                        echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
                    } else {
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
              <?php echo $task_details['reported_version_name']; ?>
            </td>
          </tr>
          <tr>
            <td><label for="dueversion"><?php echo $details_text['dueinversion'];?></label></td>
            <td>
              <select id="dueversion" name="closedby_version">
                <?php
                // if we don't have a fix-it version, show undecided
                echo "<option value=\"\">{$details_text['undecided']}</option>\n";

                $get_version = $db->Query("SELECT  version_id, version_name
                                             FROM  {list_version}
                                            WHERE  show_in_list = '1' AND version_tense = '3' AND (project_id = '0' OR project_id = ?)
                                         ORDER BY  list_position", array($proj->id,));

                while ($row = $db->FetchArray($get_version)) {
                    if ($row['version_id'] == $task_details['closedby_version']) {
                        echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>\n";
                    } else {
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
              <?php
              if (!empty($task_details['due_date']) ) {
                  $due_date = $fs->formatDate($task_details['due_date'], false);
                  $view_date = $fs->formatDate($task_details['due_date'], false);
              } else {
                  $due_date = '0';
                  $view_date = $details_text['undecided'];
              }
              ?>
              <input id="duedatehidden" type="hidden" name="due_date" value="<?php echo $due_date;?>" />
              <span id="duedateview"><?php echo $view_date;?></span> <small>|</small>
              <a href="#" onclick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '<?php echo $details_text['undecided']?>'">X</a>
              <script type="text/javascript">
                Calendar.setup({
                   inputField  : "duedatehidden", // ID of the input field
                   ifFormat    : "%d-%b-%Y",      // the date format
                   displayArea : "duedateview",   // The display field
                   daFormat    : "%d-%b-%Y",
                   button      : "duedateview"    // ID of the button
                });
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
        <textarea id="details" name="detailed_desc" cols="70" rows="10"><?php echo htmlspecialchars($task_details['detailed_desc']);?></textarea>
        <table class="taskdetails">
          <tr>
            <td> </td>
          </tr>
          <tr>
            <td class="buttons" colspan="2">
              <input class="adminbutton" type="submit" accesskey="s" name="buSubmit" value="<?php echo $details_text['savedetails'];?>" />
              <input class="adminbutton" type="reset" name="buReset" value="<?php echo $details_text['reset'];?>" />
            </td>
          </tr>
        </table>
      </div>
    </div>
  </form>
</div>
<?php
// }}}
// {{{ view mode
elseif (($task_details['is_closed'] == '1' OR @$eff_perms['can_edit'] == '0' OR !Get::has('edit'))
        && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user->id)
            OR $user->perms['manage_project'] OR $task_details['mark_private'] != '1')):
    //////////////////////////////////////
    // If the user isn't an admin,      //
    // OR if the task is in VIEW mode,  //
    // OR if the job is closed          //
    //////////////////////////////////////

    $previous_id = 0;
    $next_id = 0;

    if ($id_list = @$_SESSION['tasklist']) {
        if (($i = array_search($task_details['task_id'], $id_list)) !== false) {
            if ($i > 0)
                $previous_id = $id_list[$i - 1];
            if ($i < count($id_list) - 1)
                $next_id = $id_list[$i + 1];
        }
    }

    // Check for task dependencies that block closing this task
    $check_deps   = $db->Query("SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?", array(Get::val('id')));

    // Check for tasks that this task blocks
    $check_blocks = $db->Query("SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.task_id = t.task_id
                                 WHERE  d.dep_task_id = ?", array(Get::val('id')));

    // Check for pending PM requests
    $get_pending  = $db->Query("SELECT  *
                                  FROM  {admin_requests}
                                 WHERE  task_id = ?  AND resolved_by = '0'",
                                 array($task_details['task_id']));
                 
    // Get info on the dependencies again
    $open_deps    = $db->Query("SELECT  COUNT(*) - SUM(is_closed)
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?", array(Get::val('id')));
                
    $watching     =  $db->Query("SELECT  COUNT(*)
                                   FROM  {notifications}
                                  WHERE  task_id = ?  AND user_id = ?",
                                  array(Get::val('id'), $user->id));

    $page->uses('task_details', 'details_text', 'newtask_text');
    $page->assign('previous_id', $previous_id);
    $page->assign('next_id', $next_id);
    $page->assign('deps',    $db->fetchAllArray($check_deps));
    $page->assign('blocks',  $db->fetchAllArray($check_blocks));
    $page->assign('penreqs', $db->fetchAllArray($get_pending));
    $page->assign('d_open',  $db->fetchOne($open_deps));
    $page->assign('watched', $db->fetchOne($watching));
    $page->display('details.view.tpl');

endif; // }}}

////////////////////////////
// Start the tabbed areas //
////////////////////////////

$sql = $db->Query("SELECT * FROM {comments} WHERE task_id = ?", array($task_details['task_id']));
$page->assign('comments', $db->fetchAllArray($sql));

$sql = $db->Query("SELECT  *
                     FROM  {related} r
                LEFT JOIN  {tasks} t ON r.related_task = t.task_id
                    WHERE  r.this_task = ?", array(Get::val('id')));
$page->assign('related', $db->fetchAllArray($sql));

$sql = $db->Query("SELECT  *
                     FROM  {related} r
                LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                    WHERE  r.related_task = ?", array(Get::val('id')));
$page->assign('related_to', $db->fetchAllArray($sql));

$sql = $db->Query("SELECT * FROM {notifications} WHERE task_id = ?", array(Get::val('id')));
$page->assign('notifications', $db->fetchAllArray($sql));

$sql = $db->Query("SELECT * FROM {reminders} WHERE task_id = ?", array(Get::val('id')));
$page->assign('reminders', $db->fetchAllArray($sql));

$page->display('details.tabs.tpl');

if ($user->perms['view_comments'] || $proj->prefs['others_view']) {
    $page->display('details.tabs.comment.tpl');
}

$page->display('details.tabs.related.tpl');
?>

<?php if ($user->perms['manage_project']): // {{{ ?>

<div id="notify" class="tab">
  <p><em><?php echo $details_text['theseusersnotify'];?></em></p>
  <?php
  $get_user_ids = $db->Query("SELECT  *
                                FROM  {notifications} n
                           LEFT JOIN  {users} u ON n.user_id = u.user_id
                               WHERE  n.task_id = ?", array(Get::val('id')));

  while ($row = $db->FetchArray($get_user_ids)) {
      echo '<p>' . tpl_userlink($row['user_id']) . ' &mdash; <a href="' . $conf['general']['baseurl'] . '?do=modify&amp;action=remove_notification&amp;ids=' 
          . Get::val('id') . '&amp;user_id=' . $row['user_id'] . '">' . $details_text['remove'] . '</a></p>';
  }

  if ($user->perms['manage_project']): ?>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="get">
    <p class="admin">
      <?php echo $details_text['addusertolist'];?>
      <select class="adminlist" name="user_id">
        <?php $fs->listUsers($proj->id); ?>
      </select>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_notification" />
      <input type="hidden" name="ids" value="<?php echo Get::val('id');?>" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      <input class="adminbutton" type="submit" value="<?php echo $details_text['addtolist'];?>" />
    </p>
  </form>
  <?php endif; ?>
</div>

<?php endif; // }}} ?>

<div id="remind" class="tab">
  <?php // {{{
  $get_reminders = $db->Query("SELECT  *
                                 FROM  {reminders} r
                            LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                                WHERE  task_id = ?
                             ORDER BY  reminder_id", array(Get::val('id')));

  while ($row = $db->FetchArray($get_reminders)) {
      if (($user->perms['is_admin'] || $user->perms['manage_project']) && $task_details['is_closed'] != '1') {
      ?>
      <div class="modifycomment">
        <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
          <p>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="deletereminder" />
            <input type="hidden" name="task_id" value="<?php echo Get::val('id');?>" />
            <input type="hidden" name="reminder_id" value="<?php echo $row['reminder_id'];?>" />
            <input class="adminbutton" type="submit" value="<?php echo $details_text['remove'];?>" />
          </p>
        </form>
      </div>
      <?php
      }

      echo "<em>{$details_text['remindthisuser']}:</em> <a href=\"?do=admin&amp;area=users&amp;id={$row['to_user_id']}\">{$row['real_name']} ( {$row['user_name']})</a><br />";

      // Work out the unit of time to display
      if ($row['how_often'] < 86400) {
          $how_often = $row['how_often'] / 3600 . " " . $details_text['hours'];
      } elseif ($row['how_often'] < 604800) {
          $how_often = $row['how_often'] / 86400 . " " . $details_text['days'];
      } else {
          $how_often = $row['how_often'] / 604800 . " " . $details_text['weeks'];
      }

      echo "<em>{$details_text['thisoften']}:</em> $how_often";
      echo "<br />";
      echo '<em>' . $details_text['message'] . ':</em>' . nl2br($row['reminder_message']);
      echo "<br /><br />";
  }

  if ($user->perms['is_admin'] && $task_details['is_closed'] != '1'):
  ?>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="formaddreminder">
    <p class="admin">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addreminder" />
      <input type="hidden" name="task_id" value="<?php echo Get::val('id');?>" />

      <em><?php echo $details_text['remindthisuser'];?></em>
      <select class="adminlist" name="to_user_id">
        <?php $fs->listUsers($novar, $proj->id); ?>
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
      <textarea class="admintext" name="reminder_message" rows="10" cols="72"><?php echo $details_text['defaultreminder'] . "\n\n" . $fs->CreateURL('details', Get::val('id'));?></textarea>
      <br />
      <input class="adminbutton" type="submit" value="<?php echo $details_text['addreminder'];?>" />
    </p>
  </form>
  <?php endif; // }}} ?>
</div>

<?php if ($user->perms['view_history']): // {{{ ?>

<div id="history" class="tab">
  <table class="history">
    <tr>
      <th><?php echo $details_text['eventdate'];?></th>
      <th><?php echo $details_text['user'];?></th>
      <th><?php echo $details_text['event'];?></th>
    </tr>
    <?php
    if (is_numeric($details = Get::val('details'))) {
        $details = " AND h.history_id = $details";
        echo '<b>' . $details_text['selectedhistory'] . '</b>';
        echo '&nbsp;&mdash;&nbsp;<a href="?do=details&amp;id=' . Get::val('id') . '#history">' . $details_text['showallhistory'] . '</a>';
    } else {
        $details = '';
    }

    $query_history = $db->Query("SELECT  h.*, u.user_name, u.real_name
                                   FROM  {history} h
                              LEFT JOIN  {users} u ON h.user_id = u.user_id
                                  WHERE  h.task_id = ? {$details}
                               ORDER BY  h.event_date ASC, h.event_type ASC", array(Get::val('id')));

    if ($db->CountRows($query_history) == 0): ?>
    <tr>
      <td colspan="3"><?php echo $details_text['nohistory'];?></td>
    </tr>
    <?php endif;

    while ($history = $db->FetchRow($query_history)):
    ?>
    <tr>
      <td><?php echo $fs->formatDate($history['event_date'], true);?></td>
      <td>
        <?php
        if ($history['user_id'] == 0) {
            echo $details_text['anonymous'];
        } else {
            echo tpl_userlink($history['user_id']);
        }
        ?>
      </td>
      <td>
        <?php
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
                    break;
                case 'attached_to_project':
                    $field = $details_text['attachedtoproject'];
                    $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($oldvalue));
                    list($oldprojecttitle) = $db->FetchRow($result);
                    $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($newvalue));
                    list($newprojecttitle) = $db->FetchRow($result);
                    $oldvalue = "<a href=\"?project={$oldvalue}\">{$oldprojecttitle}</a>";
                    $newvalue = "<a href=\"?project={$newvalue}\">{$newprojecttitle}</a>";
                    break;
                case 'task_type':
                    $field = $details_text['tasktype'];
                    $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($oldvalue));
                    list($oldvalue) = $db->FetchRow($result);
                    $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($newvalue));
                    list($newvalue) = $db->FetchRow($result);
                    break;
                case 'product_category':
                    $field = $details_text['category'];
                    $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($oldvalue));
                    list($oldvalue) = $db->FetchRow($result);
                    $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($newvalue));
                    list($newvalue) = $db->FetchRow($result);
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
                    $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($oldvalue));
                    list($oldvalue) = $db->FetchRow($result);
                    $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($newvalue));
                    list($newvalue) = $db->FetchRow($result);
                    break;
                case 'task_severity':
                    $field = $details_text['severity'];
                    $oldvalue = $severity_list[$oldvalue];
                    $newvalue = $severity_list[$newvalue];
                    break;
                case 'product_version':
                    $field = $details_text['reportedversion'];
                    $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($oldvalue));
                    list($oldvalue) = $db->FetchRow($result);
                    $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($newvalue));
                    list($newvalue) = $db->FetchRow($result);
                    break;
                case 'closedby_version':
                    $field = $details_text['dueinversion'];
                    if ($oldvalue == '0') {
                        $oldvalue = $details_text['undecided'];
                    } else {
                        $result = $db->Query("SELECT version_name
                                FROM {list_version}
                                WHERE version_id = ?", array(intval($oldvalue)));
                        list($oldvalue) = $db->FetchRow($result);
                    }
                    if ($newvalue == '0') {
                        $newvalue = $details_text['undecided'];
                    } else {
                        $result = $db->Query("SELECT version_name
                                FROM {list_version}
                                WHERE version_id = ?", array(intval($newvalue)));
                        list($newvalue) = $db->FetchRow($result);
                    }
                    break;
                 case 'due_date':
                    $field = $details_text['duedate'];
                    if (empty($oldvalue)) {
                        $oldvalue = $details_text['undecided'];
                    } else {
                        $oldvalue = $fs->FormatDate($oldvalue, false);
                    }
                    if (empty($newvalue)) {
                        $newvalue = $details_text['undecided'];
                    } else {
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
                    if (!empty($details)) {
                        $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                        $details_new = htmlspecialchars($newvalue,ENT_COMPAT,'utf-8');
                        $details_previous = nl2br($details_previous);
                        $details_new      = nl2br($details_new);
                    }
                    $oldvalue = '';
                    $newvalue = '';
                    break;
            }

            echo "{$details_text['fieldchanged']}: {$field}";
            if ($oldvalue != '' || $newvalue != '') {
                echo " ({$oldvalue} &nbsp;&nbsp;&rarr; {$newvalue})";
            }

        } elseif ($history['event_type'] == '1') {      //Task opened
            echo $details_text['taskopened'];

        } elseif ($history['event_type'] == '2') {      //Task closed
            echo $details_text['taskclosed'];
            $result = $db->Query("SELECT resolution_name FROM {list_resolution} WHERE resolution_id = ?", array($newvalue));
            $res_name = $db->FetchRow($result);
            echo " ({$res_name['resolution_name']}";
            if (!empty($oldvalue)) {
                echo ': ' . tpl_formatText($oldvalue);
            }
            echo ')';

        } elseif ($history['event_type'] == '3') {      //Task edited
            echo $details_text['taskedited'];

        } elseif ($history['event_type'] == '4') {      //Comment added
            echo '<a href="#comments">' . $details_text['commentadded'] . '</a>';

        } elseif ($history['event_type'] == '5') {      //Comment edited
            echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentedited']}</a>";
            $comment = $db->Query("SELECT user_id, date_added FROM {comments} WHERE comment_id = ?", array($history['field_changed']));
            if ($db->CountRows($comment) != 0) {
                $comment = $db->FetchRow($comment);
                echo " ({$details_text['commentby']} " . tpl_userlink($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
            }
            if ($details != '') {
                $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                $details_new = htmlspecialchars($newvalue,ENT_COMPAT,'utf-8');
                $details_previous = nl2br($details_previous);
                $details_new      = nl2br($details_new);
            }

        } elseif ($history['event_type'] == '6') {     //Comment deleted
            echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentdeleted']}</a>";
            if ($newvalue != '' && $history['field_changed'] != '') {
                echo " ({$details_text['commentby']} " . tpl_userlink($newvalue) . " - " . $fs->formatDate($history['field_changed'], true) . ")";
            }
            if (!empty($details)) {
                $details_previous = htmlspecialchars($oldvalue,ENT_COMPAT,'utf-8');
                $details_previous = nl2br($details_previous);
                $details_new = '';
            }

        } elseif ($history['event_type'] == '7') {    //Attachment added
            echo $details_text['attachmentadded'];
            $attachment = $db->Query("SELECT orig_name, file_desc FROM {attachments} WHERE attachment_id = ?", array($newvalue));
            if ($db->CountRows($attachment) != 0) {
                $attachment = $db->FetchRow($attachment);
                echo ": <a href=\"{$baseurl}?getfile={$newvalue}\">{$attachment['orig_name']}</a>";
                if ($attachment['file_desc'] != '') {
                    echo " ({$attachment['file_desc']})";
                }
            }

        } elseif ($history['event_type'] == '8') {    //Attachment deleted
           echo "{$details_text['attachmentdeleted']}: {$newvalue}";

        } elseif ($history['event_type'] == '9') {    //Notification added
           echo "{$details_text['notificationadded']}: " . tpl_userlink($newvalue);

        } elseif ($history['event_type'] == '10') {  //Notification deleted
           echo "{$details_text['notificationdeleted']}: " . tpl_userlink($newvalue);

        } elseif ($history['event_type'] == '11') {  //Related task added
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($related) = $db->FetchRow($result);
            echo "{$details_text['relatedadded']}: <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

        } elseif ($history['event_type'] == '12') {  //Related task deleted
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($related) = $db->FetchRow($result);
            echo "{$details_text['relateddeleted']}: <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

        } elseif ($history['event_type'] == '13') {  //Task reopened
            echo $details_text['taskreopened'];

        } elseif ($history['event_type'] == '14') {  //Task assigned
            if ($oldvalue == '0') {
                echo "{$details_text['taskassigned']} " . tpl_userlink($newvalue);
            } elseif ($newvalue == '0') {
                echo $details_text['assignmentremoved'];
            } else {
                echo "{$details_text['taskreassigned']} " . tpl_userlink($newvalue);
            }

        } elseif ($history['event_type'] == '15') { //Task added to related list of another task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($related) = $db->FetchRow($result);
            echo "{$details_text['addedasrelated']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

        } elseif ($history['event_type'] == '16') { //Task deleted from related list of another task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($related) = $db->FetchRow($result);
            echo "{$details_text['deletedasrelated']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$related}</a>";

        } elseif ($history['event_type'] == '17') { //Reminder added
            echo "{$details_text['reminderadded']}: " . tpl_userlink($newvalue);

        } elseif ($history['event_type'] == '18') { //Reminder deleted
            echo "{$details_text['reminderdeleted']}: " . tpl_userlink($newvalue);

        } elseif ($history['event_type'] == '19') { //User took ownership
            echo "{$details_text['ownershiptaken']}: " . tpl_userlink($newvalue);

        } elseif ($history['event_type'] == '20') { //User requested task closure
            echo $details_text['closerequestmade'] . ' - ' . $newvalue;

        } elseif ($history['event_type'] == '21') { //User requested task
            echo $details_text['reopenrequestmade'] . ' - ' . $newvalue;

        } elseif ($history['event_type'] == '22') { // Dependency added
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($dependency) = $db->FetchRow($result);
            echo "{$details_text['depadded']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

        } elseif ($history['event_type'] == '23') { // Dependency added to other task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($dependency) = $db->FetchRow($result);
            echo "{$details_text['depaddedother']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

        } elseif ($history['event_type'] == '24') { // Dependency removed
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($dependency) = $db->FetchRow($result);
            echo "{$details_text['depremoved']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

        } elseif ($history['event_type'] == '25') { // Dependency removed from other task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
            list($dependency) = $db->FetchRow($result);
            echo "{$details_text['depremovedother']} <a href=\"" . $fs->CreateURL('details', $newvalue) . "\">FS#{$newvalue} &mdash; {$dependency}</a>";

        } elseif ($history['event_type'] == '26') { // Task marked private
            echo $details_text['taskmadeprivate'];

        } elseif ($history['event_type'] == '27') { // Task privacy removed - task made public
            echo $details_text['taskmadepublic'];

        } elseif ($history['event_type'] == '28') { // PM request denied
            echo $details_text['pmreqdenied'] . ' - ' . $newvalue;
        }

        ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <?php if (Get::val('details')): ?>
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
  <?php endif; ?>
</div>
<?php endif; // }}} ?>
