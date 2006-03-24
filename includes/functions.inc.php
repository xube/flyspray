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

    /**{{{ Redirect to $url
     * Redirects the browser to the page in $url
     * This function is based on PEAR HTTP class , released under
     * the BSD license
     */


    function Redirect ($url, $exit = true, $rfc2616 = true)
    {
        @ob_clean();

        if (count($_SESSION)) {
            session_write_close();
        }

        if (headers_sent()) {

            return false;
        }

        $url = FlySpray::absoluteURI($url);

        header('Location: '. $url);

        if (    $rfc2616 && isset($_SERVER['REQUEST_METHOD']) &&
                $_SERVER['REQUEST_METHOD'] != 'HEAD') {
            printf('%s to: <a href="%s?RTFM=cea78d14a44e252bd6d82bdb08a691af">%s</a>.',$language['Redirect'] , $url, $url);
        }
        if ($exit) {
            exit;
        }
            return true;

    } // }}}

    /**
     * Absolute URI (This function is part of PEAR::HTTP licensed under the BSD) {{{
     *
     * This function returns the absolute URI for the partial URL passed.
     * The current scheme (HTTP/HTTPS), host server, port, current script
     * location are used if necessary to resolve any relative URLs.
     *
     * Offsets potentially created by PATH_INFO are taken care of to resolve
     * relative URLs to the current script.
     *
     * You can choose a new protocol while resolving the URI.  This is
     * particularly useful when redirecting a web browser using relative URIs
     * and to switch from HTTP to HTTPS, or vice-versa, at the same time.
     *
     * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
     * @static
     * @access  public
     * @return  string  The absolute URI.
     * @param   string  $url Absolute or relative URI the redirect should go to.
     * @param   string  $protocol Protocol to use when redirecting URIs.
     * @param   integer $port A new port number.
     */
    function absoluteURI($url = null, $protocol = null, $port = null)
    {
        // filter CR/LF
        $url = str_replace(array("\r", "\n"), ' ', $url);

        // Mess around with already absolute URIs
        if (preg_match('!^([a-z0-9]+)://!i', $url)) {
            if (empty($protocol) && empty($port)) {
                return $url;
            }
            if (!empty($protocol)) {
                $url = $protocol .':'. end($array = explode(':', $url, 2));
            }
            if (!empty($port)) {
                $url = preg_replace('!^(([a-z0-9]+)://[^/:]+)(:[\d]+)?!i',
                    '\1:'. $port, $url);
            }
            return $url;
        }

        $host = 'localhost';
        if (!empty($_SERVER['HTTP_HOST'])) {
            list($host) = explode(':', $_SERVER['HTTP_HOST']);
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            list($host) = explode(':', $_SERVER['SERVER_NAME']);
        }

        if (empty($protocol)) {
            if (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) {
                $protocol = 'https';
            } else {
                $protocol = 'http';
            }
            if (!isset($port) || $port != intval($port)) {
                $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
            }
        }

        if ($protocol == 'http' && $port == 80) {
            unset($port);
        }
        if ($protocol == 'https' && $port == 443) {
            unset($port);
        }

        $server = $protocol .'://'. $host . (isset($port) ? ':'. $port : '');

        if (!strlen($url)) {
            $url = isset($_SERVER['REQUEST_URI']) ?
                $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        }

        if ($url{0} == '/') {
            return $server . $url;
        }

        // Check for PATH_INFO
        if (isset($_SERVER['PATH_INFO']) && strlen($_SERVER['PATH_INFO']) &&
                $_SERVER['PHP_SELF'] != $_SERVER['PATH_INFO']) {
            $path = dirname(substr($_SERVER['PHP_SELF'], 0, -strlen($_SERVER['PATH_INFO'])));
        } else {
            $path = dirname($_SERVER['PHP_SELF']);
        }

        if (substr($path = strtr($path, '\\', '/'), -1) != '/') {
            $path .= '/';
        }

        return $server . $path . $url;
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
        global $db, $severity_list, $priority_list;

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
                if ($file != "." && $file != ".." && is_file("themes/$file/theme.css")) {
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
        $lang_array = array();
        if ($handle = dir(BASEDIR . '/lang')) {
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
    function logEvent($task, $type, $newvalue = '', $oldvalue = '', $field = '', $time = null)
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
                array($task, intval($user->id), ( (is_null($time)) ? time() : $time ), $type, $field, $oldvalue, $newvalue));
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
        $result = $db->Query("SELECT * FROM {users} WHERE user_id = ?", array(intval($user_id)));
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

    /**
     * XXX:  I will rewrite this stuff from the scratch later
    */

        $script  = 'scripts/daemon.php';
        $include = 'schedule.php';
        $runfile = 'running';
        $timeout = 600;

        if (!is_file($runfile) or filemtime($runfile) < time() - ($timeout * 2)) {
            // Starting runner...
            $php = '';
         /**
          * Fixme : move the function CheckPhpCli( ) here and let it to do this work.
          */

            foreach (array('/usr/local/bin/php', '/usr/bin/php') as $path) {
                if (is_file($path) || is_executable($path)) {
                    $php = $path;
                    break;
                }
            }

            if (!$php || strpos( ini_get( 'disable_functions' , 'exec') ) ) {
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
        foreach ($old as $key => $value)
        {
            if (!in_array($key, $comp) || ($key == 'due_date' && intval($old[$key]) == intval($new[$key]))) {
                continue;
            }

            if($old[$key] != $new[$key]) {
                switch ($key)
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

    // Explode string to the array of integers {{{
    function int_explode($separator, $string)
    {
    	$ret = array();
    	foreach (explode($separator, $string) as $v)
    	{
            if (ctype_digit($v)) {// $v is always string, this func returns false if $v == ''
    			$ret[] = intval($v); // convert to int
            }
    	}
    	return $ret;
    } /// }}}
}
?>
