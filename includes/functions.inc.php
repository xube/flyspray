<?php
/*
   ----------------------------------------------------------
   | This script contains all the functions we use often in |
   |Flyspray to do miscellaneous things.                    |
   ----------------------------------------------------------
*/

class Flyspray
{
    // Change this for each release.  Don't forget!
    var $version = '0.9.9 dev';

    var $prefs   = array();
    
    // Application-wide preferences {{{
    function Flyspray()
    {
        global $db;
        
        $this->startSession();
        
        $res = $db->Query("SELECT pref_name, pref_value FROM {prefs}");

        while ($row = $db->FetchRow($res)) {
            $this->prefs[$row['pref_name']] = $row['pref_value'];
        }
    } // }}}

    //  Redirects the browser to the page in $url {{{
    function Redirect($url)
    {
        @ob_clean();
        if (count($_SESSION)) {
            session_write_close();
        }
        header('Location: ' . $url);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="refresh" content="0; url='<?php echo htmlspecialchars($url, ENT_QUOTES) ?>'">
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
    } // }}}
    // Duplicate submission check {{{
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
    } // }}}
    // Retrieve task details {{{
    function GetTaskDetails($task_id, $cacheenabled = false)
    {
        global $db, $language, $severity_list, $priority_list;

        static $cache = array();

        if(isset($cache[$task_id]) && $cacheenabled) {
            return $cache[$task_id];
        }
        
        $get_details = $db->Query('SELECT t.*, p.*,
                                          c.category_name, c.category_owner, c.parent_id,
                                          pc.category_name AS parent_category_name,
                                          o.os_name,
                                          r.resolution_name,
                                          tt.tasktype_name,
                                          vr.version_name   AS reported_version_name,
                                          vd.version_name   AS due_in_version_name,
                                          uo.real_name      AS opened_by_name,
                                          ue.real_name      AS last_edited_by_name,
                                          uc.real_name      AS closed_by_name,
                                          lst.status_name   AS status_name
                                    FROM  {tasks}              t
                               LEFT JOIN  {projects}           p  ON t.attached_to_project = p.project_id
                               LEFT JOIN  {list_category}      c  ON t.product_category = c.category_id
                               LEFT JOIN  {list_category}      pc ON c.parent_id = pc.category_id
                               LEFT JOIN  {list_os}            o  ON t.operating_system = o.os_id
                               LEFT JOIN  {list_resolution}    r  ON t.resolution_reason = r.resolution_id
                               LEFT JOIN  {list_tasktype}      tt ON t.task_type = tt.tasktype_id
                               LEFT JOIN  {list_version}       vr ON t.product_version = vr.version_id
                               LEFT JOIN  {list_version}       vd ON t.closedby_version = vd.version_id
                               LEFT JOIN  {list_status}       lst ON t.item_status = lst.status_id
                               LEFT JOIN  {users}              uo ON t.opened_by = uo.user_id
                               LEFT JOIN  {users}              ue ON t.last_edited_by = ue.user_id
                               LEFT JOIN  {users}              uc ON t.closed_by = uc.user_id
                                   WHERE  t.task_id = ?', array($task_id));

        if (!$db->CountRows($get_details)) {
           return false;
        }

        if ($get_details = $db->FetchArray($get_details)) {
            $status_id    = $get_details['item_status'];
            $severity_id  = $get_details['task_severity'];
            $priority_id  = $get_details['task_priority'];
            $get_details += array('severity_name' => $severity_list[$severity_id]);
            $get_details += array('priority_name' => $priority_list[$priority_id]);
        }
        
        $get_details['assigned_to'] = $this->GetAssignees($task_id);
        $get_details['assigned_to_name'] = $this->GetAssignees($task_id, true);
        $cache[$task_id] = $get_details;

        return $get_details;
    } // }}}
    // List projects {{{
    function listProjects($activeOnly=true)
    {
        global $db;
        
        $query = 'SELECT  project_id, project_title FROM {projects}';
        
        if ($activeOnly)  {
            
            $query .= " WHERE  project_is_active = 1";
        }

        $sql = $db->Query($query);
        return $db->fetchAllArray($sql);
    } // }}}
    // List themes {{{
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
    } // }}}
    // List languages {{{
    function listLangs()
    {
        global $basedir;
        $lang_array = array();
        if ($handle = dir($basedir . '/lang')) {
            while (false !== ($file = $handle->read())) {
                if ($file{0} != '.') {
                    $lang_array[] = str_replace('.php', '', $file);
                }
            }
            $handle->close();
        }

        sort($lang_array);
        return $lang_array;
    } // }}}
    // List groups {{{
    function listGroups()
    {
        global $proj;
        return $proj->listGroups(true);
    } // }}}
    // User list {{{
    function UserList($excluded = array())
    {
        global $proj;
        return $proj->UserList($excluded, true);
    } // }}}
    // Log events to the history table {{{
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
    } // }}}
    // Log a request for an admin/project manager to do something {{{
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
    } // }}}
    // Check for an existing admin request for a task and event type {{{
    function AdminRequestCheck($type, $task)
    {
        global $db;

        $check = $db->Query("SELECT * FROM {admin_requests}
                WHERE request_type = ? AND task_id = ? AND resolved_by = '0'",
                array($type, $task));
        return (bool)($db->CountRows($check));
    } // }}}
    // Get the current user's details {{{
    function getUserDetails($user_id)
    {
        global $db;

        // Get current user details.  We need this to see if their account is enabled or disabled
        $result = $db->Query("SELECT * FROM {users} WHERE user_id = ?", array($user_id));
        return $db->FetchArray($result);
    } // }}}
    // Get group details {{{
    function getGroupDetails($group_id)
    {
        global $db;
        $sql = $db->Query("SELECT * FROM {groups} WHERE group_id = ?", array($group_id));
        return $db->FetchArray($sql);
    } // }}}
    // Crypt a password with the method set in the configfile {{{
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
    } // }}}
    // Check if a user provided the right credentials {{{
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
    } // }}}
    // Set cookie {{{
    function setCookie($name, $val, $time)
    {
        global $baseurl;
        $url = parse_url($baseurl);
        setcookie($name, $val, $time, $url['path']);
    } // }}}
    // Reminder daemon {{{
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
    } // }}}
    // Start the session {{{
    function startSession()
    {
        $names = array( 'GetFirefox',
                        'UseLinux',
                        'NoMicrosoft',
                        'ThinkB4Replying',
                        'BuyTonyAMac',
                        'FreeSoftware',
                        'ReadTheFAQ',
                        'RTFM',
                        'VisitAU',
                        'SubliminalAdvertising',
                      );
        
        foreach ($names as $val)
        {
            session_name($val);
            session_start();
            
            if (isset($_SESSION['SESSNAME']))
            {
                $sessname = $_SESSION['SESSNAME'];
                break;
            }
            session_destroy();
            setcookie(session_name(), '', time()-60, '/');
        }
                
        if (empty($sessname))
        {
            $rand_key = array_rand($names);
            $sessname = $names[$rand_key];
            session_name($sessname);
            session_start();
            $_SESSION['SESSNAME'] = $sessname;
        }
        
            
    }  // }}}

    // Generate a long random number {{{
    function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    } // }}}
    
    // Compare tasks {{{
    function compare_tasks($old, $new)
    {        
        $comp = array('priority_name','severity_name','status_name','assigned_to_name','due_in_version_name',
                     'reported_version_name','tasktype_name','os_name', 'category_name',
                     'due_date','percent_complete','item_summary', 'due_in_version_name',
                     'detailed_desc','project_title');
                     
        $changes = array();
        foreach($old as $key => $value)
        {
            if(!in_array($key, $comp)) {
                continue;
            }
            
            if($old[$key] != $new[$key]) {
                switch($key)
                {
                    case 'due_date':
                        $new[$key] = formatDate($new[$key]);
                        $value = formatDate($value);
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
    } // }}}
    // Get a list of assignees for a task {{{
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
    } /// }}}
}
?>
