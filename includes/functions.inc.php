<?php

/*
   This script contains all the functions we use often in
   Flyspray to do various things, like database access etc.
*/


/** Get translation for specified language and page.  It loads default
language (en) and then merges with requested one. Thus it makes English
messages available even if translation is not present.
*/
function get_language_pack($lang, $module) {
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

class Flyspray {
    var $version = '0.9.7 (devel)';

   function getGlobalPrefs() {
      $get_prefs = $this->dbQuery("SELECT pref_name, pref_value FROM flyspray_prefs");

      $global_prefs = array();
      while ($row = $this->dbFetchRow($get_prefs)) {
	 $global_prefs[$row['pref_name']] = $row['pref_value'];
      }

      return $global_prefs;
   }

   function getProjectPrefs($project_id) {
      $get_prefs = $this->dbQuery("SELECT * FROM flyspray_projects WHERE project_id = ?", array($project_id));

      $project_prefs = $this->dbFetchArray($get_prefs);

      return $project_prefs;
   }

   function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '') {

      $this->dbtype = $dbtype;

      $this->dblink = NewADOConnection($dbtype);
      $res = $this->dblink->Connect($dbhost, $dbuser, $dbpass, $dbname);
      $this->dblink->SetFetchMode(ADODB_FETCH_BOTH);

      return $res;

   }

   function dbClose() {
      $this->dblink->Close();
   }

   /* Replace undef values (treated as NULL in SQL database) with empty
   strings.
   @param arr        input array or false
   @return        SQL safe array (without undefined values)
   */
   function dbUndefToEmpty($arr)
   {
       if (is_array($arr))
       {
           $c = count($arr);

           for($i=0; $i<$c; $i++)
           {
               if (!isset($arr[$i]))
               {
                  $arr[$i] = '';
               }
               // This line safely escapes sql before it goes to the db
               $this->dblink->qmagic($arr[$i]);
           }
       }
       return $arr;
   }

    /** Replace empty values with 0. Useful when inserting values from
    checkboxes.
    */
    function emptyToZero($arg)
    {
        return empty($arg) ? 0 : $arg;
    }

   function dbExec($sql, $inputarr=false, $numrows=-1, $offset=-1)
   {
      // replace undef values (treated as NULL in SQL database) with empty
      // strings
      $inputarr = $this->dbUndefToEmpty($inputarr);
      //$inputarr = $this->dbMakeSqlSafe($inputarr);

      $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
      if (($numrows>=0) or ($offset>=0)) {
          $result =  $this->dblink->SelectLimit($sql, $numrows, $offset, $inputarr);
      } else {
          $result =  $this->dblink->Execute($sql, $inputarr);
      }
      if (!$result) {
          if (function_exists("debug_backtrace")) {
              echo "<pre style='text-align: left;'>";
              var_dump(debug_backtrace());
              echo "</pre>";
          }

          die (sprintf("Query {%s} with params {%s} Failed! (%s)",
                    $sql, implode(', ', $inputarr),
                    $this->dblink->ErrorMsg()));
      }
      return $result;
   }

   function dbCountRows($result) {
      $num_rows = $result->RecordCount();
           return $num_rows;
   }

   function dbFetchRow(&$result) {
      $row = $result->FetchRow();
           return $row;
   }

/* compatibility functions */
   function dbQuery($sql, $inputarr=false, $numrows=-1, $offset=-1) {
      $result = $this->dbExec($sql, $inputarr, $numrows, $offset);
      return $result;
   }

   function dbFetchArray(&$result) {
      $row = $this->dbFetchRow($result);
      return $row;
   }

 // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
// Check him out at http://www.rhinosw.com/
function GetTaskDetails($task_id) {

   $flyspray_prefs = $this->GetGlobalPrefs();
   $lang = $flyspray_prefs['lang_code'];

        $get_details = $this->dbQuery("SELECT t.*,
                                              p.*,
                                              c.category_name,
                                              o.os_name,
                                              r.resolution_name,
                                              tt.tasktype_name,
                                              vr.version_name as reported_version_name,
                                              vd.version_name as due_in_version_name
                                              FROM flyspray_tasks t
                                              LEFT JOIN flyspray_projects p ON t.attached_to_project = p.project_id
                                              LEFT JOIN flyspray_list_category c ON t.product_category = c.category_id
                                              LEFT JOIN flyspray_list_os o ON t.operating_system = o.os_id
                                              LEFT JOIN flyspray_list_resolution r ON t.resolution_reason = r.resolution_id
                                              LEFT JOIN flyspray_list_tasktype tt ON t.task_type = tt.tasktype_id
                                              LEFT JOIN flyspray_list_version vr ON t.product_version = vr.version_id
                                              LEFT JOIN flyspray_list_version vd ON t.closedby_version = vd.version_id

                                              WHERE t.task_id = ?
                                              ", array($task_id));
        
        $get_details = $this->dbFetchArray($get_details);
    
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


// Thank you to Mr Lance Conry for this awesome, FAST Jabber message function
// Check out his company at http://www.rhinosw.com/
function JabberMessage( $sHost, $sPort, $sUsername, $sPassword, $vTo, $sSubject, $sBody, $sClient='Flyspray' ) {

   if ($sHost != ''
       && $sPort != ''
       && $sUsername != ''
       && $sPassword != ''
       && !empty($vTo)
      ) {

   $sBody = str_replace('&amp;', '&', $sBody);

   $socket = fsockopen ( $sHost, $sPort, $errno, $errstr, 30 );
   if ( !$socket ) {
      return '$errstr (' . $errno . ')';
   } else {

   fputs($socket, '<?xml version="1.0" encoding="UTF-8"?><stream:stream to="' . $sHost . '" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">' );
   fgets( $socket, 1 );

   fputs( $socket, '<iq type="set" id="AUTH_01"><query xmlns="jabber:iq:auth"><username>' . $sUsername . '</username><password>' . $sPassword . '</password><resource>' . $sClient . '</resource></query></iq>' );
   fgets( $socket, 1 );
   //sleep(1);
   if ( is_array( $vTo )) {
       foreach ($vTo as $sTo) {
           //fputs ( $socket, '<message to="' . $sTo . '" ><body>' . $sBody . '</body><subject>' . $sSubject . '</subject></message>' );
           fputs ( $socket, '<message to="' . $sTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
       }
    }
    else {
           fputs ( $socket, '<message to="' . $vTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
    }

   fclose ( $socket );
   }

   // End of checking that jabber is set up
   }

   //return TRUE;
}


   function SendEmail($to, $subject, $message) {
   if (empty($to)){
     return;
   } elseif (is_array($to)) {
     $to = implode(",", $to);
   }

 $flyspray_prefs = $this->GetGlobalPrefs();
 require('lang/'.$flyspray_prefs['lang_code'].'/functions.inc.php');
// $subject = $functions_text['notifyfrom'].' '.$flyspray_prefs['project_title'];
 $message = str_replace('&amp;', '&', $message);
 $message = $functions_text['autogenerated']."\n".str_repeat('-', 72)."\n\n".$message;
 $message = stripslashes(wordwrap($message, 72));
 $headers = array();
 $headers[] = "Content-Type: text/plain; charset=UTF-8";
 $headers[] = trim('From: '.$flyspray_prefs['project_title'].' <'.$flyspray_prefs['admin_email'].'>');
// We are using Mutt as the user-agent so spamassassin likes us
 $headers[] = "User-Agent: Mutt";
 $headers = implode("\n", $headers);
 mail($to, $subject, $message, $headers);
}

   // This function sends out basic messages at specified times.
   function SendBasicNotification($to, $subject, $message) {

        if (empty($to) OR $to == $_COOKIE['flyspray_userid'])
            return;

     // Check that we're not trying to send a notification to the user who triggered it
     //if ($to != $current_user['user_id']) {

       $flyspray_prefs = $this->GetGlobalPrefs();

       $lang = $flyspray_prefs['lang_code'];
       require("lang/$lang/functions.inc.php");

           $get_user_details = $this->dbQuery("SELECT real_name, jabber_id, email_address, notify_type FROM flyspray_users WHERE user_id = ?", array($to));
           list($real_name, $jabber_id, $email_address, $notify_type) = $this->dbFetchArray($get_user_details);

           // if app preferences say to use jabber, or if the user can (and has) selected jabber
           // and the jabber options are entered in the applications preferences
           if (($flyspray_prefs['user_notify'] == '3')
             OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '2')

                 && $flyspray_prefs['jabber_server'] != ''
             && $flyspray_prefs['jabber_port'] != ''
             && $flyspray_prefs['jabber_username'] != ''
             && $flyspray_prefs['jabber_password'] != ''
             ) {

             $subject = stripslashes($subject);
             $message = stripslashes($message);

                $this->JabberMessage(
                                                  $flyspray_prefs['jabber_server'],
                                                $flyspray_prefs['jabber_port'],
                                                $flyspray_prefs['jabber_username'],
                                                $flyspray_prefs['jabber_password'],
                                                $jabber_id,
                                                //"{$functions_text['notifyfrom']} {$flyspray_prefs['project_title']}",
                                                $subject,
                                                $message,
                                                "Flyspray"
                                                );
                //return TRUE;

            // if app preferences say to use email, or if the user can (and has) selected email
                } elseif (($flyspray_prefs['user_notify'] == '2') OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '1')) {

                        $to = $email_address;
                        $this->SendEmail($to, $subject, $message);
                };
                
        // End of basic notification function
   }


    // Detailed notification function - generates and passes arrays of recipients
    // These are the additional people who want to be notified of a task changing
    function SendDetailedNotification($task_id, $subject, $message) {

        $flyspray_prefs = $this->GetGlobalPrefs();
        $this_task = $this->GetTaskDetails($task_id);

        $lang = $flyspray_prefs['lang_code'];
        require("lang/$lang/functions.inc.php");

        $jabber_users = array();
        $email_users = array();

        $get_users = $this->dbQuery("SELECT user_id FROM flyspray_notifications WHERE task_id = ?", array($task_id));

        while ($row = $this->dbFetchArray($get_users)) {
           
           // Check for current user
           if ($row['user_id'] != $_COOKIE['flyspray_userid'] &&  $row['user_id'] != $this_task['assigned_to']) {
           
              $get_details = $this->dbQuery("SELECT notify_type, jabber_id, email_address
                      FROM flyspray_users
                      WHERE user_id = ?",
                      array($row['user_id']));

              while ($subrow = $this->dbFetchArray($get_details)) {

                  if (($flyspray_prefs['user_notify'] == '1' 
                         && $subrow['notify_type'] == '1')
                          OR ($flyspray_prefs['user_notify'] == '2')
                          ) {
                      array_push($email_users, $subrow['email_address']);
                  } elseif (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '2')
                          OR ($flyspray_prefs['user_notify'] == '3')) {
                      array_push($jabber_users, $subrow['jabber_id']);
                  };
              
              };
           
           // End of checking for current user
           };
        
        };

        $subject = stripslashes($subject);
        $message = stripslashes($message);

        // Pass the recipients and message onto the Jabber Message function
        $this->JabberMessage(
                $flyspray_prefs['jabber_server'],
                $flyspray_prefs['jabber_port'],
                $flyspray_prefs['jabber_username'],
                $flyspray_prefs['jabber_password'],
                $jabber_users,
                $subject,
                $message,
                "Flyspray"
                );


        // Pass the recipients and message onto the mass email function
        $this->SendEmail($email_users, $subject, $message);

        //return TRUE;
        // End of detailed notification function
    }



   // This function generates a query of users for the "Assigned To" list
   function listUsers($current, $in_project) {
     $flyspray_prefs = $this->getGlobalPrefs();

      $these_groups = explode(" ", $flyspray_prefs['assigned_groups']);
      while (list($key, $val) = each($these_groups)) {
        if (empty($val))
          continue;
        $group_details = $this->dbFetchArray($this->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = ?", array($val)));

        // Check that there is a user in the selected group prior to display
        $check_group = $this->dbQuery("SELECT * FROM flyspray_users_in_groups WHERE group_id = ?", array($group_details['group_id']));
        if (!$this->dbCountRows($check_group)) {
          continue;
        } else {

          echo "<optgroup label=\"{$group_details['group_name']}\">\n";

  //         $user_query = $this->dbQuery("SELECT * FROM flyspray_users WHERE account_enabled = ? AND group_in = ? ORDER BY real_name", array('1', $val));

          $user_query = $this->dbQuery("SELECT * FROM flyspray_users_in_groups uig
                                        LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                        WHERE group_id = ? AND u.account_enabled = ?
                                        ORDER BY u.real_name ASC",
                                        array($group_details['group_id'], '1'));

          while ($row = $this->dbFetchArray($user_query)) {
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
      $get_group_details = $this->dbQuery("SELECT * FROM flyspray_groups WHERE belongs_to_project = ?", array($in_project));
      while ($group_details = $this->dbFetchArray($get_group_details)) {
        
        // Check that there is a user in the selected group prior to display
        $check_group = $this->dbQuery("SELECT * FROM flyspray_users_in_groups WHERE group_id = ?", array($group_details['group_id']));
        if (!$this->dbCountRows($check_group)) {
          continue;
        } else {

        // print the group name 
        echo "<optgroup label=\"{$group_details['group_name']}\">\n";
        // Get the users that belong to this group
          $user_query = $this->dbQuery("SELECT * FROM flyspray_users_in_groups uig
                                        LEFT JOIN flyspray_users u on uig.user_id = u.user_id
                                        WHERE group_id = ?",
                                        array($group_details['group_id']));
          
          while ($row = $this->dbFetchArray($user_query)) {
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
  function pagenums($pagenum, $perpage, $pagesper, $totalcount, $extraurl)
  {
    $flyspray_prefs = $this->GetGlobalPrefs();
    $lang = $flyspray_prefs['lang_code'];
    require("lang/$lang/functions.inc.php");

    if (!($totalcount / $perpage <= 1)) {
  
      if ($pagenum - 1000 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1000) . $extraurl . "\">{$functions_text['back']} 1,000</a> - ";
      if ($pagenum - 100 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 100) . $extraurl . "\">{$functions_text['back']} 100</a> - ";
      if ($pagenum - 10 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 10) . $extraurl . "\">{$functions_text['back']} 10</a> - ";
      if ($pagenum - 1 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">{$functions_text['back']}</a> - ";
      $start = floor($pagenum - ($pagesper / 2)) + 1;
      if ($start <= 0) $start = 0;
      $finish = $pagenum + ceil($pagesper / 2);
      if ($finish >= $totalcount / $perpage) $finish = floor($totalcount / $perpage);
      for ($pagelink = $start; $pagelink <= $finish;  $pagelink++)
      {
          if ($pagelink != $start) $output .= " - ";
          if ($pagelink == $pagenum) {
              $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\"><b>" . ($pagelink + 1) . "</b></a>";
          } else {
              $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\">" . ($pagelink + 1) . "</a>";
          }
      }
      if ($pagenum + 1 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1) . $extraurl . "\">{$functions_text['forward']}</a> ";
      if ($pagenum + 10 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 10) . $extraurl . "\">{$functions_text['forward']} 10</a> ";
      if ($pagenum + 100 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 100) . $extraurl . "\">{$functions_text['forward']} 100</a> ";
      if ($pagenum + 1000 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1000) . $extraurl . "\">{$functions_text['forward']} 1,000</a> ";
      return $output;
    }
  }

  function formatDate($timestamp, $extended)
  {	
    $dateformat = '';
    $format_id = $extended ? "dateformat_extended" : "dateformat";

    if(isset($_SESSION['userid']))
      {
      $get_user_details = $this->dbQuery("SELECT {$format_id} FROM flyspray_users WHERE user_id = " . $_SESSION['userid']);
      $user_details = $this->dbFetchArray($get_user_details);
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
  

  $this->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type, field_changed, old_value, new_value) 
                  VALUES(?, ?, ?, ?, ?, ?, ?)",
                  array($task, $this->emptyToZero($_COOKIE['flyspray_userid']), date(U), $type, $field, $oldvalue, $newvalue));
  }

	
  function LinkedUsername($user_id)
  {
    $result = $this->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($user_id));
    if ($this->dbCountRows($result) == 0) return '';
    $result = $this->dbFetchRow($result);
    return "<a href=\"?do=admin&amp;area=users&amp;id={$user_id}\">{$result['real_name']} ({$result['user_name']})</a>";	
  }

  // To stop some browsers showing a blank box when an image doesn't exist
  function ShowImg($path)
  {
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
    $this->dbQuery("INSERT INTO flyspray_admin_requests (project_id, task_id, submitted_by, request_type, time_submitted)
                    VALUES(?, ?, ?, ?, ?)",
                    array($project, $task, $submitter, $type, date(U)));

  }
  
  
  // Check for an existing admin request for a task and event type
  function AdminRequestCheck($type, $task)
  {
    $check = $this->dbQuery("SELECT * FROM flyspray_admin_requests
                             WHERE request_type = ? AND task_id = ? AND resolved_by = '0'",
                             array($type, $task));
    if ($this->dbCountRows($check)) {
      return true;
    } else {
      return false;
    };
  }

   // Get the current user's details
   function getUserDetails($user_id)
   {
    
      // Get current user details.  We need this to see if their account is enabled or disabled
      $result = $this->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($user_id));
      $user_details = $this->dbFetchArray($result);
  
      return $user_details;
   
   // End of getUserDetails() function      
   }


   // Get the permissions for the current user
   function checkPermissions($user_id, $project_id)
   {
   
   $current_user = $this->getUserDetails($user_id);
   
  // Get the global group permissions for the current user
  $global_permissions = $this->dbFetchArray($this->dbQuery("SELECT *
                                                        FROM flyspray_groups g
                                                        LEFT JOIN flyspray_users_in_groups uig ON g.group_id = uig.group_id
                                                        WHERE uig.user_id = ? and g.belongs_to_project = '0'",
                                                        array($user_id)
                                                       ));
  

  // Get the project-level group for this user, and put the permissions into an array
  $search_project_group = $this->dbQuery("SELECT * FROM flyspray_groups WHERE belongs_to_project = ?", array($project_id));
  while ($row = $this->dbFetchRow($search_project_group)) {
    $check_in = $this->dbQuery("SELECT * FROM flyspray_users_in_groups WHERE user_id = ? AND group_id = ?", array($user_id, $row['group_id']));
    if ($this->dbCountRows($check_in) > '0') {
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
   
   $permissions['account_enabled'] = $current_user['account_enabled'];
   $permissions['user_pass'] = $current_user['user_pass'];
   $permissions['group_open'] = $global_permissions['group_open'];
   
   return $permissions;
   
   // End of checkPermissions() function
   }


///////////////////////////
// End of Flyspray class //
///////////////////////////
}

?>
