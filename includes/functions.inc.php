<?php

/*
   ----------------------------------------------------------
   | This script contains all the functions we use often in |
   |Flyspray to do various things.                          |
   ----------------------------------------------------------
*/

class Flyspray {

   // Change this for each release.  Don't forget!
   var $version = '0.9.8 (devel)';

   /** Get translation for specified language and page.  It loads default
      language (en) and then merges with requested one. Thus it makes English
      messages available even if translation is not present.
   */
   function get_language_pack($lang, $module)
   {
      $before = get_defined_vars();
      require_once("lang/en/$module.php");
      $after_en = get_defined_vars();
      $new_var = array_keys(array_diff($after_en, $before));
      $new_var_name = $new_var[1];
      $new_var['en'] = $$new_var_name;
      require_once("lang/$lang/$module.php");
      $new_var[$lang] = $$new_var_name;

      $$new_var_name = array_merge($new_var['en'], $new_var[$lang]);
   }

   /** Test to see if user resubmitted a form.
      Checks only newtask and addcomment actions.
      @return   true if user has submitted the same action within less than
               6 hours, false otherwise
   */
   function requestDuplicated() {
      // garbage collection -- clean entries older than 6 hrs
      $now = time();
      if (!empty($_SESSION['requests_hash'])) {
         foreach ($_SESSION['requests_hash'] as $key => $val) {
            if ($val < $now-6*60*60) {
               unset($_SESSION['requests_hash'][$key]);
            }
         }
      }

      $requestarray = array_merge(array_keys($_POST), array_values($_POST));
      if ($_POST['do']=='modify'
        and preg_match('/^newtask|addcomment$/',$_POST['action'])) {
         $currentrequest = md5(join(':', $requestarray));
         if (!empty($_SESSION['requests_hash'][$currentrequest])) {
            return true;
         }
      }

      $_SESSION['requests_hash'][$currentrequest] = time();
      return false;
   }

   function getGlobalPrefs() {

      global $db;

      $get_prefs = $db->Query("SELECT pref_name, pref_value FROM flyspray_prefs");

      $global_prefs = array();
      while ($row = $db->FetchRow($get_prefs)) {
         $global_prefs[$row['pref_name']] = $row['pref_value'];
      }

      return $global_prefs;
   }

   function getProjectPrefs($project) {

      global $db;

      $get_prefs = $db->Query("SELECT * FROM flyspray_projects WHERE project_id = ?", array($project));

      $project_prefs = $db->FetchArray($get_prefs);

      return $project_prefs;
   }



 // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
// Check him out at http://www.rhinosw.com/
function GetTaskDetails($task_id) {

      global $db;

      $flyspray_prefs = $this->GetGlobalPrefs();
      $lang = $flyspray_prefs['lang_code'];

        $get_details = $db->Query("SELECT t.*,
                                              p.*,
                                              c.category_name,
                                              c.category_owner,
                                              c.parent_id,
                                              o.os_name,
                                              r.resolution_name,
                                              tt.tasktype_name,
                                              vr.version_name as reported_version_name,
                                              vd.version_name as due_in_version_name,
                                              uo.real_name as opened_by_name,
                                              ue.real_name as last_edited__by_name,
                                              uc.real_name as closed_by_name,
                                              ua.real_name as assigned_to_name

                                              FROM flyspray_tasks t
                                              LEFT JOIN flyspray_projects p ON t.attached_to_project = p.project_id
                                              LEFT JOIN flyspray_list_category c ON t.product_category = c.category_id
                                              LEFT JOIN flyspray_list_os o ON t.operating_system = o.os_id
                                              LEFT JOIN flyspray_list_resolution r ON t.resolution_reason = r.resolution_id
                                              LEFT JOIN flyspray_list_tasktype tt ON t.task_type = tt.tasktype_id
                                              LEFT JOIN flyspray_list_version vr ON t.product_version = vr.version_id
                                              LEFT JOIN flyspray_list_version vd ON t.closedby_version = vd.version_id
                                              LEFT JOIN flyspray_users uo ON t.opened_by = uo.user_id
                                              LEFT JOIN flyspray_users ue ON t.last_edited_by = ue.user_id
                                              LEFT JOIN flyspray_users uc ON t.closed_by = uc.user_id
                                              LEFT JOIN flyspray_users ua ON t.assigned_to = ua.user_id

                                              WHERE t.task_id = ?
                                              ", array($task_id));

        $get_details = $db->FetchArray($get_details);

    if (empty($get_details))
           $get_details = array();

        $status_id = $get_details['item_status'];
    require("lang/$lang/status.php");
        $tmp_array = array("status_name" => $status_list[$status_id]);
        $get_details = $get_details + $tmp_array;

        $severity_id = $get_details['task_severity'];
    require("lang/$lang/severity.php");
        $tmp_array = array("severity_name" => $severity_list[$severity_id]);
        $get_details = $get_details + $tmp_array;

        $priority_id = $get_details['task_priority'];
    require("lang/$lang/priority.php");
        $tmp_array = array("priority_name" => $priority_list[$priority_id]);
        $get_details = $get_details + $tmp_array;

        return $get_details;
}


   // This function generates a query of users for the "Assigned To" list
   function listUsers($current, $in_project) {

      global $db;

     $flyspray_prefs = $this->getGlobalPrefs();

      $these_groups = explode(" ", $flyspray_prefs['assigned_groups']);
      while (list($key, $val) = each($these_groups)) {
        if (empty($val))
          continue;
        $group_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_groups WHERE group_id = ?", array($val)));

        // Check that there is a user in the selected group prior to display
        $check_group = $db->Query("SELECT * FROM flyspray_users_in_groups WHERE group_id = ?", array($group_details['group_id']));
        if (!$db->CountRows($check_group)) {
          continue;
        } else {

          echo "<optgroup label=\"{$group_details['group_name']}\">\n";

  //         $user_query = $db->Query("SELECT * FROM flyspray_users WHERE account_enabled = ? AND group_in = ? ORDER BY real_name", array('1', $val));

          $user_query = $db->Query("SELECT * FROM flyspray_users_in_groups uig
                                        LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                        WHERE group_id = ? AND u.account_enabled = ?
                                        ORDER BY u.real_name ASC",
                                        array($group_details['group_id'], '1'));

          while ($row = $db->FetchArray($user_query)) {
            if ($current == $row['user_id']) {
              echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']}</option>\n";
            } else {
              echo "<option value=\"{$row['user_id']}\">{$row['real_name']}</option>\n";
            };
          };

          echo "</optgroup>\n";
        };
      };

      // Now, we get the users from groups in the current project
      $get_group_details = $db->Query("SELECT * FROM flyspray_groups WHERE belongs_to_project = ?", array($in_project));
      while ($group_details = $db->FetchArray($get_group_details)) {

        // Check that there is a user in the selected group prior to display
        $check_group = $db->Query("SELECT * FROM flyspray_users_in_groups WHERE group_id = ?", array($group_details['group_id']));
        if (!$db->CountRows($check_group)) {
          continue;
        } else {

        // print the group name
        echo "<optgroup label=\"{$group_details['group_name']}\">\n";
        // Get the users that belong to this group
          $user_query = $db->Query("SELECT * FROM flyspray_users_in_groups uig
                                        LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                        WHERE group_id = ?",
                                        array($group_details['group_id']));

          while ($row = $db->FetchArray($user_query)) {
            if ($current == $row['user_id']) {
              echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']}</option>\n";
            } else {
              echo "<option value=\"{$row['user_id']}\">{$row['real_name']}</option>\n";
            };
          };

          echo "</optgroup>\n";
        };
     };
  }


  // This provides funky page numbering
  // Thanks to Nathan Fritz for this.  http://www.netflint.net/
  function pagenums($pagenum, $perpage, $totalcount, $extraurl)
  {

      global $db;
      global $lang;

      //$flyspray_prefs = $this->GetGlobalPrefs();

      require("lang/$lang/functions.inc.php");

      $pages = ceil($totalcount / $perpage);
      $output = sprintf($functions_text['page'], $pagenum, $pages);

      if (!($totalcount / $perpage <= 1))
      {

         $output .= " &nbsp;&nbsp;--&nbsp;&nbsp; ";

         $start = max(1, $pagenum - 3);
         $finish = min($pages, $pagenum + 3);

         if ($start > 1) $output .= "<a href=\"?pagenum=1" . $extraurl . "\">&lt;&lt; {$functions_text['first']} </a>";
         if ($pagenum > 1) $output .= "<a href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">&lt; {$functions_text['previous']}</a> - ";

         for ($pagelink = $start; $pagelink <= $finish;  $pagelink++)
         {

            if ($pagelink != $start) $output .= " - ";

            if ($pagelink == $pagenum)
            {
               $output .= "<strong>" . $pagelink . "</strong>";
            } else {
               $output .= "<a href=\"?pagenum=" . $pagelink . $extraurl . "\">" . $pagelink . "</a>";
            };
         };

         if ($pagenum < $pages) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1). $extraurl . "\">{$functions_text['next']} &gt;</a> ";
         if ($finish < $pages) $output .= "<a href=\"?pagenum=" . $pages . $extraurl . "\"> {$functions_text['last']} &gt;&gt;</a>";
      };
      return $output;
   }


  function formatDate($timestamp, $extended)
  {

      global $db;

    $dateformat = '';
    $format_id = $extended ? "dateformat_extended" : "dateformat";

    if(isset($_SESSION['userid']))
      {
      $get_user_details = $db->Query("SELECT {$format_id} FROM flyspray_users WHERE user_id = " . $_SESSION['userid']);
      $user_details = $db->FetchArray($get_user_details);
      $dateformat = $user_details[$format_id];
      }

    if($dateformat == '')
    {
      $flyspray_prefs = $this->GetGlobalPrefs();
      $dateformat = $flyspray_prefs[$format_id];
    }

    if($dateformat == '')
      $dateformat = $extended ? "l, j M Y, g:ia" : "Y-m-d";

    return date($dateformat, $timestamp);
  }


  function logEvent($task, $type, $newvalue = '', $oldvalue = '', $field = '')
  {

      global $db;

  // This function creates entries in the history table.  These are the event types:
  //  0: Fields changed in a task
  //  1: New task created
  //  2: Task closed
  //  3: Task edited (for backwards compatibility with events prior to the history system)
  //  4: Comment added
  //  5: Comment edited
  //  6: Comment deleted
  //  7: Attachment added
  //  8: Attachment deleted
  //  9: User added to notification list
  // 10: User removed from notification list
  // 11: Related task added to this task
  // 12: Related task removed from this task
  // 13: Task re-opened
  // 14: Task assigned to user / re-assigned to different user / Unassigned
  // 15: This task was added to another task's related list
  // 16: This task was removed from another task's related list
  // 17: Reminder added
  // 18: Reminder deleted
  // 19: User took ownership
  // 20: Closure request made
  // 21: Re-opening request made
  // 22: Adding a new dependency
  // 23: This task added as a dependency of another task
  // 24: Removing a dependency
  // 25: This task removed from another task's dependency list
  // 26: Task was made private
  // 27: Task was made public


  $db->Query("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type, field_changed, old_value, new_value)
                  VALUES(?, ?, ?, ?, ?, ?, ?)",
                  array($task, $db->emptyToZero($_COOKIE['flyspray_userid']), date(U), $type, $field, $oldvalue, $newvalue));
  }


  function LinkedUsername($user_id)
  {

      global $db;

    $result = $db->Query("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($user_id));
    if ($db->CountRows($result) == 0) return '';
    $result = $db->FetchRow($result);
    return "<a href=\"?do=admin&amp;area=users&amp;id={$user_id}\">{$result['real_name']} ({$result['user_name']})</a>";
  }

  // To stop some browsers showing a blank box when an image doesn't exist
  function ShowImg($path)
  {

      global $db;

    if(file_exists($path))
    {
      return '<img src="' . $path . '" alt="" />';
    }
  }

  // Log a request for an admin/project manager to do something
  // Types are:
  //  1: Task close
  //  2: Task re-open
  //  3: Application for project membership

  function AdminRequest($type, $project, $task, $submitter)
  {

      global $db;

    $db->Query("INSERT INTO flyspray_admin_requests (project_id, task_id, submitted_by, request_type, time_submitted)
                    VALUES(?, ?, ?, ?, ?)",
                    array($project, $task, $submitter, $type, date(U)));

  }


  // Check for an existing admin request for a task and event type
  function AdminRequestCheck($type, $task)
  {

      global $db;

    $check = $db->Query("SELECT * FROM flyspray_admin_requests
                             WHERE request_type = ? AND task_id = ? AND resolved_by = '0'",
                             array($type, $task));
    if ($db->CountRows($check)) {
      return true;
    } else {
      return false;
    };
  }

   // Get the current user's details
   function getUserDetails($user_id)
   {

      global $db;


      // Get current user details.  We need this to see if their account is enabled or disabled
      $result = $db->Query("SELECT * FROM flyspray_users WHERE user_id = ?", array($user_id));
      $user_details = $db->FetchArray($result);

      return $user_details;

   // End of getUserDetails() function
   }


   // Get the permissions for the current user
   function checkPermissions($user_id, $project_id)
   {

      global $db;


   $current_user = $this->getUserDetails($user_id);

  // Get the global group permissions for the current user
  $global_permissions = $db->FetchArray($db->Query("SELECT *
                                                        FROM flyspray_groups g
                                                        LEFT JOIN flyspray_users_in_groups uig ON g.group_id = uig.group_id
                                                        WHERE uig.user_id = ? and g.belongs_to_project = '0'",
                                                        array($user_id)
                                                       ));


  // Get the project-level group for this user, and put the permissions into an array
  $search_project_group = $db->Query("SELECT * FROM flyspray_groups WHERE belongs_to_project = ?", array($project_id));
  while ($row = $db->FetchRow($search_project_group)) {
    $check_in = $db->Query("SELECT * FROM flyspray_users_in_groups WHERE user_id = ? AND group_id = ?", array($user_id, $row['group_id']));
    if ($db->CountRows($check_in) > '0') {
      $project_permissions = $row;
    };
  };

  // Define which fields we care about from the groups information
  $field = array(
                  '1'  => 'is_admin',
                  '2'  => 'manage_project',
                  '3'  => 'view_tasks',
                  '4'  => 'open_new_tasks',
                  '5'  => 'modify_own_tasks',
                  '6'  => 'modify_all_tasks',
                  '7'  => 'view_comments',
                  '8'  => 'add_comments',
                  '9'  => 'edit_comments',
                  '10' => 'delete_comments',
                  '11' => 'view_attachments',
                  '12' => 'create_attachments',
                  '13' => 'delete_attachments',
                  '14' => 'view_history',
                  '15' => 'close_own_tasks',
                  '16' => 'close_other_tasks',
                  '17' => 'assign_to_self',
                  '18' => 'assign_others_to_self',
                  '19' => 'view_reports',
                 );

  // Now, merge the two arrays, making the highest permission active (basically, use a boolean OR)
  $permissions = array();

  while (list($key, $val) = each($field)) {
    if ($global_permissions[$val] == '1' OR $project_permissions[$val] == '1') {
      $permissions[$val] = '1';
    } else {
      $permissions[$val] = '0';

    };

  };

   $permissions['account_enabled']  = $current_user['account_enabled'];
   $permissions['user_pass']        = $current_user['user_pass'];
   $permissions['group_open']       = $global_permissions['group_open'];
   $permissions['global_view']      = $global_permissions['view_tasks'];

   return $permissions;

   // End of checkPermissions() function
   }



   // This function removes html, slashes and other nasties
   function formatText($text)
   {

      global $db;

      $text = htmlspecialchars($text);
      $text = nl2br($text);

      // Change URLs into hyperlinks
      $text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\">\\0</a>", $text);

      // Change FS#123 into hyperlinks to tasks
      $text = preg_replace("/\b(FS#)(\d+)\b/", "<a href=\"?do=details&amp;id=$2\">$0</a>", $text);

      if (!get_magic_quotes_gpc()) {
        $text = str_replace("\\", "&#92", $text);
      };
      $text = stripslashes($text);

      return $text;

   // End of formatText function
   }

   // This function checks if a user provided the right credentials
   function checkLogin($username, $password)
   {

      global $db;

      $result = $db->Query("SELECT uig.*, g.group_open, u.account_enabled, u.user_pass FROM flyspray_users_in_groups uig
                              LEFT JOIN flyspray_groups g ON uig.group_id = g.group_id
                              LEFT JOIN flyspray_users u ON uig.user_id = u.user_id
                              WHERE u.user_name = ? AND g.belongs_to_project = ?
                              ORDER BY g.group_id ASC",
                              array($username, '0'));

      $auth_details = $db->FetchArray($result);

      // Encrypt the password, and compare it to the one in the database
      if (crypt($password, '4t6dcHiefIkeYcn48B') == $auth_details['user_pass']
         && $auth_details['account_enabled'] == '1'
         && $auth_details['group_open'] == '1')
      {
         return $auth_details['user_id'];

      } else
      {
         return false;
      }

   // End of checkLogin function
   }


///////////////////////////
// End of Flyspray class //
///////////////////////////
}

?>
