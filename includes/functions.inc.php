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
      $new_var_name = @$new_var[1];
      $new_var['en'] = @$$new_var_name;
      if (file_exists("lang/$lang/$module.php"))
      {
         require_once("lang/$lang/$module.php");
      }
      $new_var[$lang] = @$$new_var_name;

      $$new_var_name = @array_merge($new_var['en'], $new_var[$lang]);
   }

   /**   Redirects the browser to the page in $url
   */
   function Redirect($url)
   {
      // Many of our URLs will be given to us from the new CreateURL() function,
      // which creates the URLs with &amp; for display.  We want to strip that.
      $url = str_replace('&amp;', '&', $url);

      // Redirect via an HTML form for PITA webservers
      if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
      {
/*
         This stuff shouldn't be needed anymore, since we pass the full URL to this function.

         $server_prefix = 'http';                                                    //Start building prefix

         if ($_SERVER['HTTPS'] == 'on') {       $server_prefix = $server_prefix . 's';  }  //If secure, use https://
         $server_prefix = $server_prefix . '://';                                    //|User:Pass@Host not supported.Bug?
         $server_prefix = $server_prefix . $_SERVER['HTTP_HOST'];                    //|Or never required with FS?

         if (!empty($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != '80'))
         {
            $server_prefix = $server_prefix . ':' . $_SERVER['SERVER_PORT'];         //If nonstandard port, append port
         }
         $server_prefix = $server_prefix . dirname($_SERVER['SCRIPT_NAME']);         //Throw away 'index.php' part
         if (substr($server_prefix, -1, 1) != '/')
         {
            $server_prefix = $server_prefix . '/';                                   //Make sure prefix ends with '/'
         }
         header('Refresh: 0; URL=' . $server_prefix . $url);
         echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $server_prefix . $url . '"><title>Redirect</title></head><body><div align="center">If your browser does not support meta redirection please click <a href="' . $server_prefix . $url . '">HERE</a> to be redirected</div></body></html>';
*/
         header('Refresh: 0; URL=' . $url);
         echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . $server_prefix . $url . '"><title>Redirect</title></head><body><div align="center">If your browser does not support meta redirection please click <a href="' . $url . '">HERE</a> to be redirected</div></body></html>';

      } else
      {
         // Behave as per HTTP/1.1 spec for others
         header('Location: ' . $url);
      }
   }

   /** Test to see if user resubmitted a form.
      Checks only newtask and addcomment actions.
      @return   true if user has submitted the same action within less than
               6 hours, false otherwise
   */
   function requestDuplicated()
   {
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

      if (isset($_POST['do']) && $_POST['do']=='modify'
        and preg_match('/^newtask|addcomment$/',$_POST['action']))
      {
         $currentrequest = md5(join(':', $requestarray));
         if (!empty($_SESSION['requests_hash'][$currentrequest]))
         {
            return true;
         }
      $_SESSION['requests_hash'][$currentrequest] = time();
      }
      return false;
   }

   function getGlobalPrefs()
   {
      global $db;
      global $dbprefix;
      global $conf;

      $get_prefs = $db->Query("SELECT pref_name, pref_value FROM {$dbprefix}prefs");

      $global_prefs = array();

      while ($row = $db->FetchRow($get_prefs))
         $global_prefs[$row['pref_name']] = $row['pref_value'];

      return $global_prefs;
   }

   function getProjectPrefs($project)
   {
      global $db;
      global $dbprefix;

      $get_prefs = $db->Query("SELECT * FROM {$dbprefix}projects WHERE project_id = ?", array($project));

      $project_prefs = $db->FetchArray($get_prefs);

      return $project_prefs;
   }



 // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
// Check him out at http://www.rhinosw.com/
function GetTaskDetails($task_id)
{
      global $db;
      global $dbprefix;
      global $flyspray_prefs;
      global $project_prefs;

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

                                              FROM {$dbprefix}tasks t
                                              LEFT JOIN {$dbprefix}projects p ON t.attached_to_project = p.project_id
                                              LEFT JOIN {$dbprefix}list_category c ON t.product_category = c.category_id
                                              LEFT JOIN {$dbprefix}list_os o ON t.operating_system = o.os_id
                                              LEFT JOIN {$dbprefix}list_resolution r ON t.resolution_reason = r.resolution_id
                                              LEFT JOIN {$dbprefix}list_tasktype tt ON t.task_type = tt.tasktype_id
                                              LEFT JOIN {$dbprefix}list_version vr ON t.product_version = vr.version_id
                                              LEFT JOIN {$dbprefix}list_version vd ON t.closedby_version = vd.version_id
                                              LEFT JOIN {$dbprefix}users uo ON t.opened_by = uo.user_id
                                              LEFT JOIN {$dbprefix}users ue ON t.last_edited_by = ue.user_id
                                              LEFT JOIN {$dbprefix}users uc ON t.closed_by = uc.user_id
                                              LEFT JOIN {$dbprefix}users ua ON t.assigned_to = ua.user_id

                                              WHERE t.task_id = ?
                                              ", array($task_id));

      if (!$db->CountRows($get_details))
         return false;

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
   function listUsers($current, $in_project)
   {
      global $db;
      global $dbprefix;
      global $flyspray_prefs;
      global $conf;

      $these_groups = explode(" ", $flyspray_prefs['assigned_groups']);

      while (list($key, $val) = each($these_groups))
      {
         if (empty($val))
            continue;

         $group_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}groups WHERE group_id = ?", array($val)));

         // Check that there is a user in the selected group prior to display
         $check_group = $db->Query("SELECT * FROM {$dbprefix}users_in_groups WHERE group_id = ?", array($group_details['group_id']));
         if (!$db->CountRows($check_group))
         {
            continue;
         } else
         {
            echo '<optgroup label="' . stripslashes($group_details['group_name']) . "\">\n";

            $user_query = $db->Query("SELECT * FROM {$dbprefix}users_in_groups uig
                                        LEFT JOIN {$dbprefix}users u on uig.user_id = u.user_id
                                        WHERE group_id = ? AND u.account_enabled = '1'
                                        ORDER BY u.real_name ASC",
                                        array($group_details['group_id'])
                                     );

            while ($row = $db->FetchArray($user_query))
            {
               if ($current == $row['user_id'])
               {
                  echo '<option value="' . $row['user_id'] . '" selected="selected">' . stripslashes($row['real_name']) . "</option>\n";
               } else
               {
                  echo '<option value="' . $row['user_id'] . '">' . stripslashes($row['real_name']) . "</option>\n";
               }
            }

            echo "</optgroup>\n";
         }
      }

      // Now, we get the users from groups in the current project
      $get_group_details = $db->Query("SELECT * FROM {$dbprefix}groups WHERE belongs_to_project = ?", array($in_project));
      while ($group_details = $db->FetchArray($get_group_details) AND $in_project > '0')
      {
         // Check that there is a user in the selected group prior to display
         $check_group = $db->Query("SELECT * FROM {$dbprefix}users_in_groups WHERE group_id = ?", array($group_details['group_id']));
         if (!$db->CountRows($check_group))
         {
            continue;
         } else
         {
           // print the group name
            echo "<optgroup label=\"{$group_details['group_name']}\">\n";

            // Get the users that belong to this group
            $user_query = $db->Query("SELECT * FROM {$dbprefix}users_in_groups uig
                                        LEFT JOIN {$dbprefix}users u on uig.user_id = u.user_id
                                        WHERE group_id = ?",
                                        array($group_details['group_id'])
                                     );

            while ($row = $db->FetchArray($user_query))
            {
               if ($current == $row['user_id'])
               {
                  echo '<option value="' . $row['user_id'] . '" selected="selected">' . stripslashes($row['real_name']) . "</option>\n";
               } else
               {
                  echo '<option value="' . $row['user_id'] . '">' . stripslashes($row['real_name']) . "</option>\n";
               }
            }

            echo "</optgroup>\n";
         }
      }
   }


  // This provides funky page numbering
  // Thanks to Nathan Fritz for this.  http://www.netflint.net/
   function pagenums($pagenum, $perpage, $totalcount, $extraurl)
   {
      global $db;
      global $dbprefix;
      global $lang;

      require("lang/$lang/functions.inc.php");

      // Just in case $perpage is something weird, like 0, fix it here:
      if ($perpage < 1) { $perpage = ($totalcount > 0 ? $totalcount : 1); }
      $pages = ceil($totalcount / $perpage);
      $output = sprintf($functions_text['page'], $pagenum, $pages);

      if (!($totalcount / $perpage <= 1))
      {
         $output .= "<span class=\"DoNotPrint\"> &nbsp;&nbsp;--&nbsp;&nbsp; ";

         $start = max(1, $pagenum - 3);
         $finish = min($pages, $pagenum + 3);

         if ($start > 1)
            $output .= "<a href=\"?pagenum=1" . $extraurl . "\">&lt;&lt; {$functions_text['first']} </a>";

         if ($pagenum > 1)
            $output .= "<a href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">&lt; {$functions_text['previous']}</a> - ";

         for ($pagelink = $start; $pagelink <= $finish;  $pagelink++)
         {
            if ($pagelink != $start)
               $output .= " - ";

            if ($pagelink == $pagenum)
            {
               $output .= "<strong>" . $pagelink . "</strong>";
            } else
            {
               $output .= "<a href=\"?pagenum=" . $pagelink . $extraurl . "\">" . $pagelink . "</a>";
            }
         }

         if ($pagenum < $pages)
            $output .= " - <a href=\"?pagenum=" . ($pagenum + 1). $extraurl . "\">{$functions_text['next']} &gt;</a></span> ";
         if ($finish < $pages)
            $output .= "<a href=\"?pagenum=" . $pages . $extraurl . "\"> {$functions_text['last']} &gt;&gt;</a></span>";
      }

      return $output;
   // End of pagenums function
   }


   function formatDate($timestamp, $extended)
   {
      global $db;
      global $dbprefix;
      global $conf;
      global $flyspray_prefs;

      $dateformat = '';
      $format_id = $extended ? "dateformat_extended" : "dateformat";

      if(isset($_SESSION['userid']))
      {
         $get_user_details = $db->Query("SELECT {$format_id} FROM {$dbprefix}users WHERE user_id = " . $_SESSION['userid']);
         $user_details = $db->FetchArray($get_user_details);
         $dateformat = $user_details[$format_id];
      }

      if($dateformat == '')
      {
         $dateformat = $flyspray_prefs[$format_id];
      }

      if($dateformat == '')
         $dateformat = $extended ? "%A, %d %B %Y, %I:%M%p" : "%Y-%m-%d";

      return strftime($dateformat, $timestamp);
   }


   function logEvent($task, $type, $newvalue = '', $oldvalue = '', $field = '')
   {
      global $db;
      global $dbprefix;

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
      // 28: PM request denied


      $db->Query("INSERT INTO {$dbprefix}history (task_id, user_id, event_date, event_type, field_changed, old_value, new_value)
                  VALUES(?, ?, ?, ?, ?, ?, ?)",
                  array($task, $db->emptyToZero($_COOKIE['flyspray_userid']), date('U'), $type, $field, $oldvalue, $newvalue));

   // End of logEvent function
   }


   function LinkedUsername($user_id)
   {
      global $db;
      global $dbprefix;

      $result = $db->Query("SELECT user_name, real_name FROM {$dbprefix}users WHERE user_id = ?", array($user_id));
      if ($db->CountRows($result) == 0)
         return '';

      $result = $db->FetchRow($result);

      return '<a href="' . $this->CreateURL('user', $user_id) . '">' . stripslashes($result['real_name']) . ' (' . $result['user_name'] . ')</a>';
   }


   // To stop some browsers showing a blank box when an image doesn't exist
   function ShowImg($path, $alt_text)
   {
      global $conf;

      if(file_exists($path))
      {
         list($width, $height, $type, $attr) = getimagesize($path);
         return "<img src=\"{$conf['general']['baseurl']}$path\" width=\"$width\" height=\"$height\" alt=\"$alt_text\" title=\"$alt_text\" />";
      }
   // End of ShowImg function
   }


   // Log a request for an admin/project manager to do something
   // Types are:
   //  1: Task close
   //  2: Task re-open
   //  3: Application for project membership (not implemented yet)
   function AdminRequest($type, $project, $task, $submitter, $reason)
   {
      global $db;
      global $dbprefix;

      $db->Query("INSERT INTO {$dbprefix}admin_requests (project_id, task_id, submitted_by, request_type, reason_given, time_submitted)
                    VALUES(?, ?, ?, ?, ?, ?)",
                    array($project, $task, $submitter, $type, $reason, date(U)));
   // End of AdminRequest function
   }


   // Check for an existing admin request for a task and event type
   function AdminRequestCheck($type, $task)
   {
      global $db;
      global $dbprefix;

      $check = $db->Query("SELECT * FROM {$dbprefix}admin_requests
                             WHERE request_type = ? AND task_id = ? AND resolved_by = '0'",
                             array($type, $task));
      if ($db->CountRows($check))
      {
         return true;
      } else
      {
         return false;
      }
   }

   // Get the current user's details
   function getUserDetails($user_id)
   {
      global $db;
      global $dbprefix;

      // Get current user details.  We need this to see if their account is enabled or disabled
      $result = $db->Query("SELECT * FROM {$dbprefix}users WHERE user_id = ?", array($user_id));
      $user_details = $db->FetchArray($result);

      return $user_details;

   // End of getUserDetails() function
   }


   // Get the permissions for the current user
   function getPermissions($user_id, $project_id)
   {
      global $db;
      global $dbprefix;

      $current_user = $this->getUserDetails($user_id);

      // Get the global group permissions for the current user
      $global_permissions = $db->FetchArray($db->Query("SELECT *
                                                        FROM {$dbprefix}groups g
                                                        LEFT JOIN {$dbprefix}users_in_groups uig ON g.group_id = uig.group_id
                                                        WHERE uig.user_id = ? and g.belongs_to_project = '0'",
                                                        array($user_id)
                                                       ));


      // Get the project-level group for this user, and put the permissions into an array
      $search_project_group = $db->Query("SELECT * FROM {$dbprefix}groups
                                          WHERE belongs_to_project = ?",
                                          array($project_id));

      // Default to the global permissions, but allow it to override if we find a match
      $project_permissions = $global_permissions;
      while ($row = $db->FetchRow($search_project_group))
      {
         $check_in = $db->Query("SELECT * FROM {$dbprefix}users_in_groups
                                 WHERE user_id = ? AND group_id = ?",
                                 array($user_id, $row['group_id'])
                               );

         if ($db->CountRows($check_in) > '0')
         {
            $project_permissions = $row;
         }
      }

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

      while (list($key, $val) = each($field))
      {
         if ($global_permissions[$val] == '1' OR $project_permissions[$val] == '1')
         {
            $permissions[$val] = '1';
         } else
         {
            $permissions[$val] = '0';
         }

      }

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
      //global $conf;

      $text = htmlspecialchars($text);
      $text = nl2br($text);

      // Change URLs into hyperlinks
      $text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\">\\0</a>", $text);

      // Change FS#123 into hyperlinks to tasks

      if (preg_match_all("/\b(FS#|bug )(\d+)\b/",$text,$hits,PREG_SET_ORDER)) {
   foreach ($hits as $n => $hit) { $tasks[$hit[2]] = $hit[0]; }
   foreach ($tasks as $id => $str) {
     $link = "<a href=\"" . $this->CreateURL('details', $id) .
       "\" title=\"" . $this->taskTitle($id) . "\">$str</a>";
     $text = preg_replace("/\b$str\b/",$link,$text);
   }
      }

      if (!get_magic_quotes_gpc())
         $text = str_replace("\\", "&#92", $text);

      $text = stripslashes($text);

      return $text;

   // End of formatText function
   }


   function taskTitle($taskid)
   {
      global $details_text;

      $task_details = $this->GetTaskDetails($taskid);

      if ($task_details['is_closed'] == '1')
      {
         $status = $details_text['closed'] . ':: ' . $task_details['resolution_name'];
      } else {
         $status = $details_text['open'] . ':: ' . $task_details['status_name'];
      }

      return $status . ': ' . substr($task_details['item_summary'],0,40);
   }


   // Crypt a password with the method set in the configfile
   function cryptPassword($password)
   {
      global $conf;
      $pwcrypt = $conf['general']['passwdcrypt'];

      if(strtolower($pwcrypt) == 'sha1')
      {
         return sha1($password);
      } elseif(strtolower($pwcrypt) == 'md5')
      {
         return md5($password);
      }
      // use random salted crypt by default
      $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      return crypt($password, substr($letters, rand(0, 52), 1).substr($letters, rand(0, 52), 1) );
   // End of cryptPassword function
   }


   // This function checks if a user provided the right credentials
   function checkLogin($username, $password)
   {
      global $db;
      global $dbprefix;

      $result = $db->Query("SELECT uig.*, g.group_open, u.account_enabled, u.user_pass FROM {$dbprefix}users_in_groups uig
                              LEFT JOIN {$dbprefix}groups g ON uig.group_id = g.group_id
                              LEFT JOIN {$dbprefix}users u ON uig.user_id = u.user_id
                              WHERE u.user_name = ? AND g.belongs_to_project = ?
                              ORDER BY g.group_id ASC",
                              array($username, '0'));

      $auth_details = $db->FetchArray($result);

      //encrypt the password with the method used in the db
      switch (strlen($auth_details['user_pass']))
      {
         case 40:
            $password = sha1($password);
            break;
         case 32:
            $password = md5($password);
            break;
         case 13;
            $password = crypt($password, $auth_details['user_pass']); //using the salt from db
            break;
         default:
          //unknown encryption!?
          return false;
      }

      // Compare the crypted password to the one in the database
      if ($password == $auth_details['user_pass']
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

   function startReminderDaemon()
   {
      $script  = 'scripts/daemon.php';
      $include = 'schedule.php';
      $runfile = 'running';
      $timeout = 600;

      if (!file_exists($runfile) or filemtime($runfile) < time() - ($timeout * 2))
      {
         // Starting runner...
         $php = '';
         foreach (array('/usr/local/bin/php', '/usr/bin/php') as $path)
         {
            if (file_exists($path) and is_executable($path))
            {
               $php = $path;
               break;
            }
         }

         if (!$php)
         {
            // No PHP executable found... sorry!";
            return;
         }

         exec("$php $script $include $timeout ../$runfile >/dev/null &");
      }
   // End of startReminderDaemon function
   }

   // set empty values for $_GET[...] variables
   function fixMissingIndices()
   {
      $indexes = 'project index date order class order order2 sort sort2 tasks permissions sev dev due string pagenum type cat status';
      $indexes = split(' ', $indexes);
      foreach ($indexes as $index)
      {
         if (!isset($_GET[$index]) && !isset($_GET['do']))
            $_GET[$index] = '';
         if (!isset($_REQUEST[$index]) && !isset($_GET['do']))
            $_REQUEST[$index] = '';
      }
   }


   /* Check if we should use address rewriting
      and return an appropriate URL
   */
   function CreateURL($type, $arg1, $arg2=0, $arg3=0)
   {
      global $conf;

      // If we do want address rewriting
      if(isset($conf['general']['address_rewriting']) &&
      $conf['general']['address_rewriting'] == '1')
      {
         switch ($type)
         {
            case "details": $url = $conf['general']['baseurl'] . 'task/' . $arg1;
            break;
            case "depends": $url = $conf['general']['baseurl'] . 'task/' . $arg1 . '/depends';
            break;
            case "edittask": $url = $conf['general']['baseurl'] . 'task/' . $arg1 . '/edit';
            break;
            case "admin": $url = $conf['general']['baseurl'] . 'admin/' . $arg1;
            break;
            case "pm": $url = $conf['general']['baseurl'] . 'pm/proj' . $arg2 . '/' . $arg1;
            break;
            case "newtask": $url = $conf['general']['baseurl'] . 'newtask/proj' . $arg1;
            break;
            case "reports": $url = $conf['general']['baseurl'] . 'reports';
            break;
            case "myprofile": $url = $conf['general']['baseurl'] . 'myprofile';
            break;
            case "user": $url = $conf['general']['baseurl'] . 'user/' . $arg1;
            break;
            case "newuser": $url = $conf['general']['baseurl'] . 'newuser';
            break;
            case "register": $url = $conf['general']['baseurl'] . 'register';
            break;
            case "newgroup": $url = $conf['general']['baseurl'] . 'newgroup/proj' . $arg1;
            break;
            case "group": $url = $conf['general']['baseurl'] . 'group/' . $arg1;
            break;
            case "projgroup": $url = $conf['general']['baseurl'] . 'projgroup/' . $arg1;
            break;
            case "logout": $url = $conf['general']['baseurl'] . 'logout';
            break;
            case "error":  $url = $conf['general']['baseurl'] . 'error';
            break;
            case "lostpw":  $url = $conf['general']['baseurl'] . 'lostpw';
            break;
         }


         return $url;
      }

      switch ($type)
      {
         case "details": $url = $conf['general']['baseurl'] . '?do=details&amp;id=' . $arg1;
         break;
         case "depends": $url = $conf['general']['baseurl'] . '?do=depends&amp;id=' . $arg1;
         break;
         case "edittask": $url = $conf['general']['baseurl'] . '?do=details&amp;id=' . $arg1 . '&amp;edit=yep';
         break;
         case "admin": $url = $conf['general']['baseurl'] . '?do=admin&amp;area=' . $arg1;
         break;
         case "pm": $url = $conf['general']['baseurl'] . '?do=pm&amp;area=' . $arg1 . '&amp;project=' . $arg2;
         break;
         case "newtask": $url = $conf['general']['baseurl'] . '?do=newtask&amp;project=' . $arg1;
         break;
         case "reports": $url = $conf['general']['baseurl'] . '?do=reports';
         break;
         case "myprofile": $url = $conf['general']['baseurl'] . '?do=myprofile';
         break;
         case "user": $url = $conf['general']['baseurl'] . '?do=admin&amp;area=users&amp;id=' . $arg1;
         break;
         case "newuser": $url = $conf['general']['baseurl'] . '?do=newuser';
         break;
         case "register": $url = $conf['general']['baseurl'] . '?do=register';
         break;
         case "newgroup": $url = $conf['general']['baseurl'] . '?do=newgroup&project=' . $arg1;
         break;
         case "group": $url = $conf['general']['baseurl'] . '?do=admin&area=editgroup&id=' . $arg1;
         break;
         case "projgroup": $url = $conf['general']['baseurl'] . '?do=pm&area=editgroup&id=' . $arg1;
         break;
         case "logout": $url = $conf['general']['baseurl'] . '?do=authenticate&amp;action=logout';
         break;
         case "error": $url = $conf['general']['baseurl'] . '?do=error';
         break;
         case "lostpw": $url = $conf['general']['baseurl'] . '?do=lostpw';
         break;
      }

      return $url;

   // End of CreateURL() function
   }

///////////////////////////
// End of Flyspray class //
///////////////////////////
}

?>
