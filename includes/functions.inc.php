<?php

/*
   ----------------------------------------------------------
   | This script contains all the functions we use often in |
   |Flyspray to do various things.                          |
   ----------------------------------------------------------
*/

class Flyspray
{
    // Change this for each release.  Don't forget!
    var $version = '0.9.9 (devel)';

    var $prefs   = array();

    function Flyspray()
    {
        global $db;
        session_start();

        $res = $db->Query("SELECT pref_name, pref_value FROM {prefs}");

        while ($row = $db->FetchRow($res)) {
            $this->prefs[$row['pref_name']] = $row['pref_value'];
        }
    }


    /** Get translation for specified language and page.  It loads default
      language (en) and then merges with requested one. Thus it makes English
      messages available even if translation is not present.
     */
    function get_language_pack($module)
    {
        if ($module == 'functions')
            $module .= '.inc';

        $lang     = $this->prefs['lang_code'];
        $basedir  = dirname(dirname(__FILE__));
        $before   = get_defined_vars();

        require("$basedir/lang/en/$module.php");
        $after_en = get_defined_vars();
        $new_var  = array_keys(array_diff($after_en, $before));

        if ($lang != 'en' && isset($new_var[1])) {
            $new_var_name   = $new_var[1];
            $new_var['en']  = $$new_var_name;
            @include("$basedir/lang/$lang/$module.php");
            $new_var[$lang] = $$new_var_name;
            $$new_var_name  = $new_var['en'];
            foreach ($new_var[$lang] as $key => $val) {
                ${$new_var_name}[$key] = $val;
            }
        }
    }


    //  Redirects the browser to the page in $url
    function Redirect($url)
    {
        @ob_clean();
        if (count($_SESSION)) {
            session_write_close();
        }
        header('Location: ' . $url);
        header('Refresh: 0; URL=' . $url);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="refresh" content="0; url='<?php echo htmlspecialchars($server_prefix . $url, ENT_QUOTES) ?>'">
    <title>Redirect</title>
  </head>
  <body>
    <div align="center">
    If your browser does not support meta redirection please click
    <a href="<?php echo htmlspecialchars($url) ?>">HERE</a> to be redirected
    </div>
  </body>
</html>
<?php
        exit;
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

        if (Post::val('do') == 'modify' and preg_match('/^newtask|addcomment$/',
                    Post::val('action')))
        {
            $currentrequest = md5(join(':', $requestarray));
            if (!empty($_SESSION['requests_hash'][$currentrequest])) {
                return true;
            }
            $_SESSION['requests_hash'][$currentrequest] = time();
        }
        return false;
    }

    // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
    // Check him out at http://www.rhinosw.com/
    function GetTaskDetails($task_id)
    {
        global $db;
        global $project_prefs;
        global $status_list, $severity_list, $priority_list;
        $this->get_language_pack('status');
        $this->get_language_pack('severity');
        $this->get_language_pack('priority');

        $get_details = $db->Query("SELECT t.*, p.*,
                                          c.category_name, c.category_owner, c.parent_id,
                                          pc.category_name AS parent_category_name,
                                          o.os_name,
                                          r.resolution_name,
                                          tt.tasktype_name,
                                          vr.version_name   AS reported_version_name,
                                          vd.version_name   AS due_in_version_name,
                                          uo.real_name      AS opened_by_name,
                                          ue.real_name      AS last_edited_by_name,
                                          uc.real_name      AS closed_by_name
                                    FROM  {tasks}              t
                               LEFT JOIN  {projects}           p  ON t.attached_to_project = p.project_id
                               LEFT JOIN  {list_category}      c  ON t.product_category = c.category_id
                               LEFT JOIN  {list_category}      pc ON c.parent_id = pc.category_id
                               LEFT JOIN  {list_os}            o  ON t.operating_system = o.os_id
                               LEFT JOIN  {list_resolution}    r  ON t.resolution_reason = r.resolution_id
                               LEFT JOIN  {list_tasktype}      tt ON t.task_type = tt.tasktype_id
                               LEFT JOIN  {list_version}       vr ON t.product_version = vr.version_id
                               LEFT JOIN  {list_version}       vd ON t.closedby_version = vd.version_id
                               LEFT JOIN  {users}              uo ON t.opened_by = uo.user_id
                               LEFT JOIN  {users}              ue ON t.last_edited_by = ue.user_id
                               LEFT JOIN  {users}              uc ON t.closed_by = uc.user_id
                                   WHERE  t.task_id = ?", array($task_id));

        if (!$db->CountRows($get_details)) {
           return false;
        }

        if ($get_details = $db->FetchArray($get_details)) {
            $status_id    = $get_details['item_status'];
            $severity_id  = $get_details['task_severity'];
            $priority_id  = $get_details['task_priority'];
            $get_details += array('status_name'   => $status_list[$status_id]);
            $get_details += array('severity_name' => $severity_list[$severity_id]);
            $get_details += array('priority_name' => $priority_list[$priority_id]);
        }
        
        $get_details['assigned_to'] = $this->GetAssignees($task_id);
        $get_details['assigned_to_name'] = $this->GetAssignees($task_id, true);

        return $get_details;
    }


    // {{{ functions that should go in a Project class

    // This function generates a query of users for the "Assigned To" list
    function listUsers($in_project, $current=null)
    {
        global $db;
        global $conf;

        $these_groups = explode(" ", $this->prefs['assigned_groups']);

        foreach ($these_groups as $key => $val) {
            if (empty($val))
                continue;

            $result = $db->Query("SELECT * FROM {groups} WHERE group_id = ?", array($val));
            $group_details = $db->FetchArray($result);

            // Check that there is a user in the selected group prior to display
            $check_group = $db->Query("SELECT * FROM {users_in_groups} WHERE group_id = ?", array($group_details['group_id']));
            if (!$db->CountRows($check_group))
                continue;

            $user_query = $db->Query("SELECT  *
                                        FROM  {users_in_groups} uig
                                   LEFT JOIN  {users} u on uig.user_id = u.user_id
                                       WHERE  group_id = ? AND u.account_enabled = '1'
                                    ORDER BY  u.real_name ASC", array($group_details['group_id']));

            echo '<optgroup label="' . $group_details['group_name'] . "\">\n";
            $content = '';
            while ($row = $db->FetchArray($user_query)) {
                if ($current == $row['user_id']) {
                    $content .= '<option value="' . $row['user_id'] . '" selected="selected">' . $row['real_name'] . "</option>\n";
                } else {
                    $content .= '<option value="' . $row['user_id'] . '">' . $row['real_name'] . "</option>\n";
                }
            }
            if (!$content) {
                echo '<option>---</option>';
            } else {
                echo $content;
            }
            echo "</optgroup>\n";
        }

        if (!$in_project)
            return;

        // Now, we get the users from groups in the current project
        $get_group_details = $db->Query("SELECT group_id, group_name FROM {groups} WHERE belongs_to_project = ?", array($in_project));
        while ($group_details = $db->FetchArray($get_group_details)) {
            // Check that there is a user in the selected group prior to display
            $check_group = $db->Query("SELECT * FROM {users_in_groups} WHERE group_id = ?", array($group_details['group_id']));
            if (!$db->CountRows($check_group))
                continue;

            $user_query = $db->Query("SELECT  *
                                        FROM  {users_in_groups} uig
                                   LEFT JOIN  {users} u on uig.user_id = u.user_id
                                       WHERE  group_id = ?", array($group_details['group_id']));

            echo "<optgroup label=\"{$group_details['group_name']}\">\n";
            $content = '';
            while ($row = $db->FetchArray($user_query)) {
                if ($current == $row['user_id']) {
                    $content .= '<option value="' . $row['user_id'] . '" selected="selected">' . $row['real_name'] . "</option>\n";
                } else {
                    $content .= '<option value="' . $row['user_id'] . '">' . $row['real_name'] . "</option>\n";
                }
            }
            if (!$content) {
                echo '<option>---</option>';
            } else {
                echo $content;
            }
            echo "</optgroup>\n";
        }
    }


   // New function to replace the ListUsers() function above
   // It returns an array of user ids
   function UserList($project_id)
   {
      global $db, $fs;
      global $conf;

      // Create an empty array to put our users into
      $users = array();

      // Retrieve all the users in this project.  A tricky query is required...
      $get_project_users = $db->Query("SELECT uig.user_id, u.real_name, u.user_name, g.group_name
                                       FROM {users_in_groups} uig
                                       LEFT JOIN {users} u ON uig.user_id = u.user_id
                                       LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                       LEFT JOIN {projects} p ON g.belongs_to_project = p.project_id
                                       WHERE g.belongs_to_project = ?
                                       ORDER BY g.group_id ASC",
                                       array($project_id)
                                     );

      while ($row = $db->FetchArray($get_project_users))
      {
         if (!in_array($row['user_id'], $users))
               $users = $users + array($row['user_id'] => '[' . $row['group_name'] . '] ' . $row['real_name'] . ' (' . $row['user_name'] . ')');
      }

      // Get the list of global groups that can be assigned tasks
      $these_groups = explode(" ", $fs->prefs['assigned_groups']);
      foreach ($these_groups AS $key => $val)
      {
         // Get the list of users from the global groups above
         $get_global_users = $db->Query("SELECT uig.user_id, u.real_name, u.user_name, g.group_name
                                         FROM {users_in_groups} uig
                                         LEFT JOIN {users} u ON uig.user_id = u.user_id
                                         LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                         WHERE uig.group_id = ?",
                                         array($val)
                                       );

         // Cycle through the global userlist, adding each user to the array
         while ($row = $db->FetchArray($get_global_users))
         {
            if (!in_array($row['user_id'], $users))
               $users = $users + array($row['user_id'] => '[' . $row['group_name'] . '] ' . $row['real_name'] . ' (' . $row['user_name'] . ')');
         }
      }

      return $users;

   // End of UserList() function
   }

    function listProjects()
    {
        global $db;

        $sql = $db->Query("SELECT  project_id, project_title FROM {projects}
                            WHERE  project_is_active = 1");
        return $db->fetchAllArray($sql);
    }

    function listThemes()
    {
        if ($handle = opendir(dirname(dirname(__FILE__)).'/themes/')) {
            $theme_array = array();
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && file_exists("themes/$file/theme.css")) {
                    $theme_array[] = $file;
                }
            }
            closedir($handle);
        }

        sort($theme_array);
        return $theme_array;
    }

    function listLangs()
    {
        if ($handle = opendir(dirname(dirname(__FILE__)).'/lang/')) {
            $lang_array = array();
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && file_exists("lang/$file/main.php")) {
                    $lang_array[] = $file;
                }
            }
            closedir($handle);
        }

        sort($lang_array);
        return $lang_array;
    }

    // }}}


    function logEvent($task, $type, $newvalue = '', $oldvalue = '', $field = '')
    {
        global $db, $user;

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
        // 29: User added to the list of assignees


        $db->Query("INSERT INTO {history} (task_id, user_id, event_date, event_type, field_changed, old_value, new_value)
                                VALUES(?, ?, ?, ?, ?, ?, ?)",
                array($task, intval($user->id), date('U'), $type, $field, $oldvalue, $newvalue));
    }


    // Log a request for an admin/project manager to do something
    function AdminRequest($type, $project, $task, $submitter, $reason)
    {
        global $db;
        // Types are:
        //  1: Task close
        //  2: Task re-open
        //  3: Application for project membership (not implemented yet)

        $db->Query("INSERT INTO {admin_requests} (project_id, task_id, submitted_by, request_type, reason_given, time_submitted)
                VALUES(?, ?, ?, ?, ?, ?)",
                array($project, $task, $submitter, $type, $reason, date(U)));
    }


    // Check for an existing admin request for a task and event type
    function AdminRequestCheck($type, $task)
    {
        global $db;

        $check = $db->Query("SELECT * FROM {admin_requests}
                WHERE request_type = ? AND task_id = ? AND resolved_by = '0'",
                array($type, $task));
        return (bool)($db->CountRows($check));
    }


    // Get the current user's details
    function getUserDetails($user_id)
    {
        global $db;

        // Get current user details.  We need this to see if their account is enabled or disabled
        $result = $db->Query("SELECT * FROM {users} WHERE user_id = ?", array($user_id));
        return $db->FetchArray($result);
    }

    function getGroupDetails($group_id)
    {
        global $db;
        $sql = $db->Query("SELECT * FROM {groups} WHERE group_id = ?", array($group_id));
        return $db->FetchArray($sql);
    }


    // Crypt a password with the method set in the configfile
    function cryptPassword($password)
    {
        global $conf;
        $pwcrypt = $conf['general']['passwdcrypt'];

        if(strtolower($pwcrypt) == 'sha1') {
            return sha1($password);
        } elseif(strtolower($pwcrypt) == 'md5') {
            return md5($password);
        }
        // use random salted crypt by default
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return crypt($password, substr($letters, rand(0, 52), 1).substr($letters, rand(0, 52), 1) );
    }


    // This function checks if a user provided the right credentials
    function checkLogin($username, $password)
    {
        global $db;

        $result = $db->Query("SELECT  uig.*, g.group_open, u.account_enabled, u.user_pass
                                FROM  {users_in_groups} uig
                           LEFT JOIN  {groups} g ON uig.group_id = g.group_id
                           LEFT JOIN  {users} u ON uig.user_id = u.user_id
                               WHERE  u.user_name = ? AND g.belongs_to_project = ?
                            ORDER BY  g.group_id ASC", array($username, '0'));

        $auth_details = $db->FetchArray($result);

        //encrypt the password with the method used in the db
        switch (strlen($auth_details['user_pass'])) {
            case 40:
                $password = sha1($password);
                break;
            case 32:
                $password = md5($password);
                break;
            case 13:
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
        }

        return false;
    }


    function setCookie($name, $val, $time)
    {
        global $baseurl;
        $url = parse_url($baseurl);
        setcookie($name, $val, $time, $url['path']);
    }

    function startReminderDaemon()
    {
        $script  = 'scripts/daemon.php';
        $include = 'schedule.php';
        $runfile = 'running';
        $timeout = 600;

        if (!file_exists($runfile) or filemtime($runfile) < time() - ($timeout * 2)) {
            // Starting runner...
            $php = '';
            foreach (array('/usr/local/bin/php', '/usr/bin/php') as $path) {
                if (file_exists($path) and is_executable($path)) {
                    $php = $path;
                    break;
                }
            }

            if (!$php) {
                // No PHP executable found... sorry!";
                return;
            }

            exec("$php $script $include $timeout ../$runfile >/dev/null &");
        }
    }


// FIXME: TEMPLATING FUNCTIONS, SHOULD MOVE AWAY AT SOME POINT

    /* Check if we should use address rewriting
       and return an appropriate URL
     */
    function CreateURL($type, $arg1 = null, $arg2 = null)
    {
        global $conf;

        $url = $conf['general']['baseurl'];

        // If we do want address rewriting
        if(!empty($conf['general']['address_rewriting'])) {
            switch ($type) {
                case 'depends':   return $url . 'task/' .  $arg1 . '/' . $type;
                case 'details':   return $url . 'task/' . $arg1;
                case 'edittask':  return $url . 'task/' .  $arg1 . '/edit';
                case 'pm':        return $url . 'pm/proj' . $arg2 . '/' . $arg1;

                case 'admin':
                case 'user':      return $url . $type . '/' . $arg1;

                case 'newgroup':
                case 'newtask':   return $url . $type .  '/proj' . $arg1;

                case 'editgroup':
                case 'projgroup': return $url . $type . '/' . $arg1;

                case 'error':
                case 'logout':
                case 'lostpw':
                case 'myprofile':
                case 'newuser':
                case 'register':
                case 'reports':  return $url . $type;
            }
        }

        if ($type == 'edittask') {
            $url .= '?do=details';
        } else {
            $url .= '?do=' . $type;
        }

        switch ($type) {
            case 'admin':     return $url . '&area=' . $arg1;
            case 'edittask':  return $url . '&id=' . $arg1 . '&edit=yep';
            case 'pm':        return $url . '&area=' . $arg1 . '&project=' . $arg2;
            case 'user':      return '?do=admin&area=users&id=' . $arg1;
            case 'logout':    return '?do=authenticate&action=logout';

            case 'details':
            case 'depends':   return $url . '&id=' . $arg1;

            case 'newgroup':
            case 'newtask':   return $url . '&project=' . $arg1;

            case 'editgroup':
            case 'projgroup': return $conf['general']['baseurl'] . '?do=admin&area=editgroup&id=' . $arg1;

            case 'error':
            case 'lostpw':
            case 'myprofile':
            case 'newuser':
            case 'register':
            case 'reports':   return $url;
        }
    }

    function formatDate($timestamp, $extended, $default = '')
    {
        global $db;
        global $conf;

        if (!$timestamp) {
            return $default;
        }

        $dateformat = '';
        $format_id  = $extended ? "dateformat_extended" : "dateformat";

        if(isset($_SESSION['userid'])) {
            $get_user_details = $db->Query("SELECT {$format_id} FROM {users} WHERE user_id = " . $_SESSION['userid']);
            $user_details     = $db->FetchArray($get_user_details);
            $dateformat       = $user_details[$format_id];
        }

        if($dateformat == '') {
            $dateformat = $this->prefs[$format_id];
        }

        if($dateformat == '')
            $dateformat = $extended ? '%A, %d %B %Y, %I:%M%p' : '%Y-%m-%d';

        return strftime($dateformat, $timestamp);
    }

    // This provides funky page numbering
    // Thanks to Nathan Fritz for this.  http://www.netflint.net/
    function pagenums($pagenum, $perpage, $totalcount, $extraurl)
    {
        global $db;
        global $functions_text;

        $this->get_language_pack('functions');

        $extraurl = '&amp;' . $extraurl;

        // Just in case $perpage is something weird, like 0, fix it here:
        if ($perpage < 1) {
            $perpage = $totalcount > 0 ? $totalcount : 1;
        }
        $pages  = ceil($totalcount / $perpage);
        $output = sprintf($functions_text['page'], $pagenum, $pages);

        if (!($totalcount / $perpage <= 1)) {
            $output .= "<span class=\"DoNotPrint\"> &nbsp;&nbsp;--&nbsp;&nbsp; ";

            $start  = max(1, $pagenum - 3);
            $finish = min($pages, $pagenum + 3);

            if ($start > 1)
                $output .= "<a href=\"?pagenum=1" . $extraurl . "\">&lt;&lt; {$functions_text['first']} </a>";

            if ($pagenum > 1)
                $output .= "<a id=\"previous\" accesskey=\"p\" href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">&lt; {$functions_text['previous']}</a> - ";

            for ($pagelink = $start; $pagelink <= $finish;  $pagelink++) {
                if ($pagelink != $start)
                    $output .= " - ";

                if ($pagelink == $pagenum) {
                    $output .= "<strong>" . $pagelink . "</strong>";
                } else {
                    $output .= "<a href=\"?pagenum=" . $pagelink . $extraurl . "\">" . $pagelink . "</a>";
                }
            }

            if ($pagenum < $pages)
                $output .= " - <a id=\"next\" accesskey=\"n\" href=\"?pagenum=" . ($pagenum + 1). $extraurl . "\">{$functions_text['next']} &gt;</a>";
            if ($finish < $pages)
                $output .= "<a href=\"?pagenum=" . $pages . $extraurl . "\"> {$functions_text['last']} &gt;&gt;</a>";
            $output .= '</span>';
        }

        return $output;
    }

    /* This function came from the php function page for mt_srand()
       seed with microseconds to create a random filename
     */
    function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }
    
    function compare_tasks($old, $new)
    {
        $comp = array('priority_name','severity_name','status_name','assigned_to_name','due_in_version_name',
                     'reported_version_name','tasktype_name','os_name', 'category_name',
                     'due_date','percent_complete','item_summary', 'due_in_version_name',
                     'detailed_desc','project_title');
                     
        $changes = array();
        foreach($old as $key => $value)
        {
            if(is_numeric($key) || !in_array($key, $comp)) {
                continue;
            }
            
            if($old[$key] != $new[$key]) {
                switch($key)
                {
                    case 'due_date':
                        $new[$key] = $this->formatDate($new[$key],0);
                        $value = $this->formatDate($value,0);
                        break;
                        
                    case 'percent_complete':
                        $new[$key] .= '%';
                        $value .= '%';
                        break;
                    
                    case 'category_name':
                        if($new['parent_category_name']) {
                            $new['parent_category_name'] . ' ... ' . $new[$key];
                        }
                        
                        if($old['parent_category_name'])  {
                            $value = $old['parent_category_name'] . ' ... ' . $value;
                        }
                        break;                    
                }
                $changes[] = array($key, $value, $new[$key]);
            }
        }

        return $changes;
    }
    
    function GetAssignees($taskid, $name = false)
    {
        global $db;
        
        if($name) {
            $sql = $db->Query("SELECT u.real_name
                                FROM {users} u, {assigned} a
                                WHERE task_id = ? AND u.user_id = a.user_id",
                                  array($taskid));
        } else {
            $sql = $db->Query("SELECT user_id
                                FROM {assigned}
                                WHERE task_id = ?",
                                  array($taskid));
        }
        
        $assignees = array();
        while ($row = $db->FetchArray($sql)) {
            $assignees[] = ($name) ? $row['real_name'] : $row['user_id'];
        }

        return $assignees;
    }
}

?>
