<?php
/**
 * Flyspray
 *
 * Flyspray class
 *
 * This script contains all the functions we use often in
 * Flyspray to do miscellaneous things.
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Tony Collins
 * @author Florian Schmitz
 * @author Cristian Rodriguez
 */

class Flyspray
{

    /**
     * Current Flyspray version. Change this for each release.  Don't forget!
     * @access public
     * @var string
     */
    var $version = '1.0.0 dev';

    /**
     * Flyspray preferences
     * @access public
     * @var array
     */
    var $prefs   = array();

    /**
     * Max. file size for file uploads. 0 = no uploads allowed
     * @access public
     * @var integer
     */
    var $max_file_size = 0;

    /**
     * List of projects the user is allowed to view
     * @access public
     * @var array
     */
    var $projects = array();

    /**
     * List of severities. Loaded in i18n.inc.php
     * @access public
     * @var array
     */
    var $severities = array();

    // Application-wide preferences {{{
    /**
     * Constructor, starts session, loads settings
     * @access private
     * @return void
     * @version 1.0
     */
    function Flyspray()
    {
        global $db;

        $this->startSession();

        $res = $db->Execute('SELECT pref_name, pref_value FROM {prefs}');

        while ($row = $res->FetchRow()) {
            $this->prefs[$row['pref_name']] = $row['pref_value'];
        }

        $sizes = array();
        foreach (array(ini_get('memory_limit'), ini_get('post_max_size'), ini_get('upload_max_filesize')) as $val) {
            if (!$val) {
                continue;
            }

            $val = trim($val);
            $last = strtolower($val{strlen($val)-1});
            switch ($last) {
                // The 'G' modifier is available since PHP 5.1.0
                case 'g':
                    $val *= 1024;
                case 'm':
                    $val *= 1024;
                case 'k':
                    $val *= 1024;
            }

            $sizes[] = $val;
        }

        $this->max_file_size = ((bool) ini_get('file_uploads')) ? round((min($sizes)/1024/1024), 1) : 0;
    } // }}}

    function base_version($version)
    {
        if (strpos($version, ' ') === false) {
            return $version;
        }
        return substr($version, 0, strpos($version, ' '));
    }

    // {{{ Redirect to $url
    /**
     * Redirects the browser to the page in $url
     * This function is based on PEAR HTTP class
     * @param string $url
     * @param bool $exit
     * @param bool $rfc2616
     * @license BSD
     * @access public static
     * @return bool
     * @version 1.0
     */
    function Redirect($url, $exit = true, $rfc2616 = true)
    {

        @ob_clean();

        if (isset($_SESSION) && count($_SESSION)) {
            session_write_close();
        }

        if (headers_sent()) {
            //if this happends we need to trace the root cause anyway, so return false
            return false;
        }

        $url = FlySpray::absoluteURI($url);


        header('Location: '. $url);

        if ($rfc2616 && isset($_SERVER['REQUEST_METHOD']) &&
            $_SERVER['REQUEST_METHOD'] != 'HEAD') {
            $url = htmlspecialchars($url, ENT_QUOTES, 'utf-8');
            printf('%s to: <a href="%s">%s</a>.', eL('Redirect'), $url, $url);
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


        if (!strlen($url) || $url{0} == '?' || $url{0} == '#') {
            $uri = isset($_SERVER['REQUEST_URI']) ?
                $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
            if ($url && $url{0} == '?' && false !== ($q = strpos($uri, '?'))) {
                $url = substr($uri, 0, $q) . $url;
            } else {
                $url = $uri . $url;
            }
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
    /**
     * Test to see if user resubmitted a form.
     * Checks only newtask and addcomment actions.
     * @return bool true if user has submitted the same action within less than 6 hours, false otherwise
     * @access public static
     * @version 1.0
     */
    function requestDuplicated()
    {
        // garbage collection -- clean entries older than 6 hrs
        $now = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        if (!empty($_SESSION['requests_hash'])) {
            foreach ($_SESSION['requests_hash'] as $key => $val) {
                if ($val < $now-6*60*60) {
                    unset($_SESSION['requests_hash'][$key]);
                }
            }
        }

      if (count($_POST)) {

        $requestarray = array_merge(array_keys($_POST), array_values($_POST));

        if (preg_match('/^newtask.newtask|details.addcomment$/', Post::val('action', '')))
        {
            $currentrequest = md5(serialize($requestarray));
            if (!empty($_SESSION['requests_hash'][$currentrequest])) {
                return true;
            }
            $_SESSION['requests_hash'][$currentrequest] = time();
        }
      }
        return false;
    } // }}}
    // Retrieve task details {{{
    /**
     * Gets all information about a task (and caches information if wanted)
     * @param integer $task_id
     * @param bool $cache_enabled
     * @access public static
     * @return mixed an array with all taskdetails or false on failure
     * @version 1.0
     */
    function GetTaskDetails($task_id, $cache_enabled = false)
    {
        global $db, $fs, $proj;

        static $cache = array();

        if (isset($cache[$task_id]) && $cache_enabled) {
            return $cache[$task_id];
        }

        //for some reason, task_id is not here
        // run away inmediately..
        if (!is_numeric($task_id)) {
            return false;
        }

        $task = $db->Execute('  SELECT  t.*,
                                      r.item_name AS resolution_name,
                                      uo.real_name      AS opened_by_name,
                                      ue.real_name      AS last_edited_by_name,
                                      uc.real_name      AS closed_by_name
                                FROM  {tasks}              t
                           LEFT JOIN  {list_items}    r  ON t.resolution_reason = r.list_item_id
                           LEFT JOIN  {users}              uo ON t.opened_by = uo.user_id
                           LEFT JOIN  {users}              ue ON t.last_edited_by = ue.user_id
                           LEFT JOIN  {users}              uc ON t.closed_by = uc.user_id
                               WHERE  t.task_id = ?', array($task_id));

        if ($task = $task->FetchRow()) {
            $task += array('severity_name' => $fs->severities[$task['task_severity']]);
        } else {
            return false;
        }

        // Now add custom fields
        $sql = $db->Execute('SELECT field_value, field_name, f.field_id, li.item_name, lc.category_name
                               FROM {field_values} fv
                          LEFT JOIN {fields} f ON f.field_id = fv.field_id
                          LEFT JOIN {list_items} li ON (f.list_id = li.list_id AND field_value = li.list_item_id)
                          LEFT JOIN {list_category} lc ON (f.list_id = lc.list_id AND field_value = lc.category_id)
                              WHERE task_id = ?', array($task['task_id']));
        while ($row = $sql->FetchRow()) {
            $task['f' . $row['field_id']] = $row['field_value'];
            $task['f' . $row['field_id'] . '_name'] = ($row['item_name'] ? $row['item_name'] : $row['category_name']);
        }
        // Add fields for tasks which have not all values set
        foreach ($proj->fields as $field) {
            if (!isset($task['f'. $field->id])) {
                $task['f'. $field->id] = '';
            }
        }

        $task['assigned_to'] = $task['assigned_to_name'] = array();
        if ($assignees = Flyspray::GetAssignees($task_id, true)) {
            $task['assigned_to'] = $assignees[0];
            $task['assigned_to_name'] = $assignees[1];
        }

        $cache[$task_id] = $task;

        return $task;
    } // }}}
    // List projects {{{
    /**
     * Returns a list of all projects
     * @access public static
     * @return array
     * @version 1.0
     */
    function listProjects()
    {
        global $db;

        $sql = $db->Execute('SELECT  project_id, project_title FROM {projects}');
        return $sql->GetArray();
    } // }}}
    // List themes {{{
    /**
     * Returns a list of all themes
     * @access public static
     * @return array
     * @version 1.0
     */
    function listThemes()
    {
        if ($handle = opendir(dirname(dirname(__FILE__)) . '/themes/')) {
            $theme_array = array();
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && is_file("themes/$file/theme.css")) {
                    $theme_array[] = $file;
                }
            }
            closedir($handle);
        }

        sort($theme_array);
        return $theme_array;
    } // }}}
    // List a project's group {{{
    /**
     * Returns a list of a project's groups
     * @param integer $proj_id
     * @access public static
     * @return array
     * @version 1.0
     */
    function listGroups($proj_id = 0)
    {
        global $db;
        $res = $db->Execute('SELECT  *
                             FROM  {groups}
                            WHERE  project_id = ?
                         ORDER BY  group_id ASC', array($proj_id));
        return $res->GetArray();
    }
    /**
     * Returns a list of all groups, sorted by project
     * @param integer $user_id restrict to groups the user is member of
     * @access public static
     * @return array
     * @version 1.0
     */
    function listallGroups($user_id = null)
    {
        global $db, $fs;

        $group_list = array(L('global') => null);
        $params = array();

        $query = 'SELECT g.group_id, group_name, group_desc, g.project_id, project_title
                    FROM {groups} g
               LEFT JOIN {projects} p ON p.project_id = g.project_id';
        // Limit to groups a specific user is in
        if (!is_null($user_id)) {
            $query .= ' LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                            WHERE uig.user_id = ? ';
            $params[] = $user_id;
        }
        $sql = $db->Execute($query, $params);

        while ($row = $sql->FetchRow()) {
            // make sure that the user only sees projects he is allowed to
            if ($row['project_id'] != '0' && Flyspray::array_find('project_id', $row['project_id'], $fs->projects) === false) {
                continue;
            }
            $group_list[$row['project_title']][] = $row;
        }
        $group_list[L('global')] = $group_list[''];
        unset($group_list['']);

        return $group_list;
    }
    // }}}
    // List languages {{{
    /**
     * Returns a list of installed languages
     * @access public static
     * @return array
     * @version 1.0
     */
    function listLangs()
    {
        return str_replace('.php', '', array_map('basename', glob_compat(BASEDIR ."/lang/*.php")));

    } // }}}
    // Log events to the history table {{{
    /**
     * Saves an event to the database
     * @param integer $task_id
     * @param integer $type
     * @param string $newvalue
     * @param string $oldvalue
     * @param string $field
     * @param integer $time for synchronisation with other functions
     * @access public static
     * @return void
     * @version 1.0
     */
    function logEvent($task_id, $type, $newvalue = '', $oldvalue = '', $field = '', $time = null)
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
        // 30: New user registration
        // 31: User deletion

        $query_params = array(intval($task_id), intval($user->id),
                             ((!is_numeric($time)) ? time() : $time),
                              $type, $field, $oldvalue, $newvalue);

        if($db->Execute('INSERT INTO {history} (task_id, user_id, event_date, event_type, field_changed,
                       old_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?)', $query_params)) {
            return true;
         }

        return false;
    } // }}}
    // Log a request for an admin/project manager to do something {{{
    /**
     * Adds an admin request to the database
     * @param integer $type 1: Task close, 2: Task re-open
     * @param integer $project_id
     * @param integer $task_id
     * @param integer $submitter
     * @param string $reason
     * @access public static
     * @return void
     * @version 1.0
     */
    function AdminRequest($type, $project_id, $task_id, $submitter, $reason)
    {
        global $db;
        $db->Execute('INSERT INTO {admin_requests} (project_id, task_id, submitted_by, request_type, reason_given, time_submitted)
                         VALUES (?, ?, ?, ?, ?, ?)',
                    array($project_id, $task_id, $submitter, $type, $reason, time()));
    } // }}}
    // Check for an existing admin request for a task and event type {{{;
    /**
     * Checks whether or not there is an admin request for a task
     * @param integer $type 1: Task close, 2: Task re-open
     * @param integer $task_id
     * @access public static
     * @return bool
     * @version 1.0
     */
    function AdminRequestCheck($type, $task_id)
    {
        global $db;

        $check = $db->Execute('SELECT request_id
                                 FROM {admin_requests}
                                WHERE request_type = ? AND task_id = ? AND resolved_by = 0',
                               array($type, $task_id));
        return (bool) $check->FetchRow();
    } // }}}
    // Get the current user's details {{{
    /**
     * Gets all user details of a user
     * @param integer $user_id
     * @access public static
     * @return array
     * @version 1.0
     */
    function getUserDetails($user_id)
    {
        global $db;

        $result = $db->Execute('SELECT * FROM {users} WHERE user_id = ?', array(intval($user_id)));
        return $result->FetchRow();
    } // }}}
    // Get group details {{{
    /**
     * Gets all information about a group
     * @param integer $group_id
     * @access public static
     * @return array
     * @version 1.0
     */
    function getGroupDetails($group_id)
    {
        global $db;
        $sql = $db->Execute('SELECT *, count(uig.user_id) AS num_users
                             FROM {groups} g
                        LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                            WHERE g.group_id = ?
                         GROUP BY g.group_id',
                          array($group_id));
        return $sql->FetchRow();
    } // }}}
    //  {{{
    /**
     * Crypt a password with md5
     * @param string $password
     * @access public static
     * @return string
     * @version 1.0
     */
    function cryptPassword($password)
    {
        return md5($password);
    } // }}}
    // {{{
    /**
     * Check if a user provided the right credentials
     * @param string $username
     * @param string $password
     * @access public static
     * @return integer user_id on success, 0 if account or user is disabled, -1 if password is wrong
     * @version 1.0
     */
    function checkLogin($username, $password)
    {
        global $db;

        $result = $db->Execute("SELECT  uig.*, g.group_open, u.account_enabled, u.user_pass
                                FROM  {users_in_groups} uig
                           LEFT JOIN  {groups} g ON uig.group_id = g.group_id
                           LEFT JOIN  {users} u ON uig.user_id = u.user_id
                               WHERE  u.user_name = ? AND g.project_id = ?
                            ORDER BY  g.group_id ASC", array($username, 0));

        $auth_details = $result->FetchRow();

        if(!$result || !count($auth_details)) {
            return 0;
        }

        $password = Flyspray::cryptPassword($password);

        // Compare the crypted password to the one in the database
        if ($password == $auth_details['user_pass']
                && $auth_details['account_enabled'] == '1'
                && $auth_details['group_open'] == '1')
        {
            return $auth_details['user_id'];
        }

        return ($auth_details['account_enabled'] && $auth_details['group_open']) ? 0 : -1;
    } // }}}
    // Set cookie {{{
    /**
     * Sets a cookie, automatically setting the URL
     * @param string $name
     * @param string $val
     * @param integer $time
     * @access public static
     * @return bool
     * @version 1.0
     */
    function setCookie($name, $val, $time = null)
    {
        $url = parse_url($GLOBALS['baseurl']);
        if (!is_int($time)) {
            $time = time()+60*60*24*30;
        }
        return setcookie($name, $val, $time, $url['path']);
    } // }}}
    // Reminder daemon {{{
    /**
     * Starts the reminder daemon
     * @access public static
     * @return void
     * @version 1.0
     */
    function startReminderDaemon()
    {
        global $baseurl;

        $runfile = Flyspray::get_tmp_dir() . '/flysprayreminders.run';
        $timeout = 600;

        if (!is_file($runfile) or filemtime($runfile) < time() - ($timeout * 2)) {

            $include = 'schedule.php';
            /* "localhost" is on **purpose** not a mistake ¡¡
             * almost any server accepts requests to itself in localhost ;)
             * firewalls will not block it.
             * the "Host" http header will tell the webserver where flyspray is running.
             */
            Flyspray::remote_request($baseurl . $include, !GET_CONTENTS, $_SERVER['SERVER_PORT'], 'localhost', $_SERVER['HTTP_HOST']);
        }
    }
            // Start the session {{{
    /**
     * Starts the session
     * @access public static
     * @return void
     * @version 1.0
     * @notes smile intented
     */
    function startSession()
    {
        if (defined('IN_FEED') || isset($_SESSION['SESSNAME'])) {
            return;
        }

        $names = array( 'GetFirefox',
                        'UseLinux',
                        'NoMicrosoft',
                        'ThinkB4Replying',
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

            $_SESSION = array();
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

    // Compare tasks {{{
    /**
     * Compares two tasks and returns an array of differences
     * @param array $old
     * @param array $new
     * @access public static
     * @return array array('field', 'old', 'new')
     * @version 1.0
     */
    function compare_tasks($old, $new)
    {
        global $proj;
        $comp = array('task_severity' => 'severity_name',
                      'assigned_to_name', 'percent_complete',
                      'item_summary', 'detailed_desc', 'mark_private');
        $translation = array('severity_name' => L('severity'),
                             'assigned_to_name' => L('assignedto'),
                             'percent_complete' => L('percentcomplete'),
                             'mark_private' => L('visibility'),
                             'item_summary' => L('summary'),
                             'detailed_desc' => L('taskedited'));
        $changes = array();
        foreach ($comp as $db => $key)
        {
            if ($old[$key] != $new[$key]) {
                switch ($key)
                {
                    case 'percent_complete':
                        $new[$key] .= '%';
                        $old[$key] .= '%';
                        break;

                    case 'mark_private':
                        $new[$key] = $new[$key] ? L('private') : L('public');
                        $old[$key] = $old[$key] ? L('private') : L('public');
                        break;
                }
                $changes[] = array($key, $old[$key], $new[$key], $translation[$key], (is_numeric($db)) ? $key : $db );
            }
        }

        foreach ($proj->fields as $field) {
            $key = 'f'. $field->id;
            if ($old[$key] != $new[$key]) {
                if ($field->prefs['field_type'] == FIELD_DATE) {
                    $changes[] = array($key, formatDate($old[$key]), formatDate($new[$key]), $field->prefs['field_name'], $field->id);
                } else {
                    $old[$key . '_name'] = isset($old[$key . '_name']) ? $old[$key . '_name'] : $old[$key];
                    $new[$key . '_name'] = isset($new[$key . '_name']) ? $new[$key . '_name'] : $new[$key];
                    $changes[] = array($key, $old[$key . '_name'], $new[$key . '_name'], $field->prefs['field_name'], $field->id);
                }
            }
        }

        return $changes;
    } // }}}
    // {{{
    /**
     * Get a list of assignees for a task
     * @param integer $task_id
     * @param bool $name whether or not names of the assignees should be returned as well
     * @access public static
     * @return array
     * @version 1.0
     */
    function GetAssignees($task_id, $name = false)
    {
        global $db;

        $sql = $db->Execute('SELECT u.real_name, u.user_id
                             FROM {users} u, {assigned} a
                            WHERE task_id = ? AND u.user_id = a.user_id',
                              array($task_id));

        $assignees = array();
        while ($row = $sql->FetchRow()) {
            if ($name) {
                $assignees[0][] = $row['user_id'];
                $assignees[1][] = $row['real_name'];
            } else {
                $assignees[] = $row['user_id'];
            }
        }

        return $assignees;
    } /// }}}

    // {{{
    /**
     * Explode string to the array of integers
     * @param string $separator
     * @param string $string
     * @access public static
     * @return array
     * @version 1.0
     */
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
    } /// }} }

    /**
     * Checks if a function is disabled
     * @param string $func_name
     * @access public static
     * @return bool
     * @version 1.0
     */
    function function_disabled($func_name)
    {
        $disabled_functions = explode(',', ini_get('disable_functions'));
        return in_array($func_name, $disabled_functions);
    }

    /**
     * Returns the key number of an array which contains an array like array($key => $value)
     * For use with SQL result arrays
     * @param string $key
     * @param string $value
     * @param array $array
     * @access public static
     * @return integer
     * @version 1.0
     */
    function array_find($key, $value, $array)
    {
        foreach ($array as $num => $part) {
            if (isset($part[$key]) && $part[$key] == $value) {
                return $num;
            }
        }
    }

    /**
     * Shows an error message
     * @param string $error_message if it is an integer, an error message from the language file will be loaded
     * @param bool $die enable/disable redirection (if outside the database modification script)
     * @param string $advanced_info append a string to the error message
     * @param string $url alternate redirection
     * @access public static
     * @return void
     * @version 1.0
     * @notes if a success and error happens on the same page, a mixed error message will be shown
     */
    function show_error($error_message, $die = true, $advanced_info = null, $url = null)
    {
        global $modes, $baseurl;

        if (!is_int($error_message)) {
            // in modify.inc.php
            $_SESSION['ERROR'] = $error_message;
        } else {
            $_SESSION['ERROR'] = L('error#') . $error_message . ': ' . L('error' . $error_message);
            if (!is_null($advanced_info)) {
                $_SESSION['ERROR'] .= ' ' . $advanced_info;
            }
            if ($die) {
                Flyspray::Redirect( (is_null($url) ? $baseurl : $url) );
            }
        }
    }

    /**
     * Returns the ID of a user with $name
     * @param string $name
     * @access public static
     * @return integer 0 if the user does not exist
     * @version 1.0
     */
    function username_to_id($name)
    {
        global $db;

        $uid = $db->GetOne('SELECT user_id FROM {users} WHERE ' .
                           (is_numeric($name) ? 'user_id' : 'user_name') . ' = ?',
                            array($name));

        return intval($uid);
    }
    /**
     * check_email
     *  checks if an email is valid
     * @param string $email
     * @access public
     * @return bool
     */
    function check_email($email)
    {
        include_once dirname(__FILE__) . '/external/Validate.php';

        return is_string($email) && Validate::email($email);
    }

    /**
     * get_tmp_dir
     * Based on PEAR System::tmpdir() by Tomas V.V.Cox.
     * @access public
     * @return void
     */
    function get_tmp_dir()
    {
        if(function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();

        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($var = isset($_ENV['TEMP']) ? $_ENV['TEMP'] : getenv('TEMP')) {
                return $var;
            }
            if ($var = isset($_ENV['TMP']) ? $_ENV['TMP'] : getenv('TMP')) {
                return $var;
            }
            if ($var = isset($_ENV['windir']) ? $_ENV['windir'] : getenv('windir')) {
                return $var;
            }
            return getenv('SystemRoot') . '\temp';

        } elseif ($var = isset($_ENV['TMPDIR']) ? $_ENV['TMPDIR'] : getenv('TMPDIR')) {
             return $var;
        }
            return '/tmp';
    }

    /**
     * check_mime_type
     *
     * @param string $fname path to filename
     * @access public
     * @return string the mime type of the offended file.
     * @notes DO NOT use this function for any security related
     * task (i.e limiting file uploads by type)
     * it wasn't designed for that purpose but to UI related tasks.
     */
    function check_mime_type($fname) {

        $type = '';

        if (extension_loaded('fileinfo') && class_exists('finfo')) {

            $info = new finfo(FILEINFO_MIME);
            $type = $info->file($fname);

        } elseif(function_exists('mime_content_type')) {

            $type = mime_content_type($fname);
        // I hope we don't have to...
        } elseif(!FlySpray::function_disabled('exec') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {

               $type = trim(@exec('file -bi ' . escapeshellarg($fname)));

        }
        // if wasn't possible to determine , return empty string so
        // we can use the browser reported mime-type (probably fake)
        return $type;
    }

    /**
     * Works like strtotime, but it considers the user's timezone
     * @access public
     * @param string $time
     * @return integer
     */
    function strtotime($time)
    {
        global $user;

        $time = strtotime($time);

        if (!$user->isAnon()) {
            $st = date('Z')/3600; // server GMT timezone
            // Example: User is GMT+3, Server GMT-2.
            // User enters 7:00. For the server it must be converted to 2:00 (done below)
            $time += ($st - $user->infos['time_zone']) * 60 * 60;
            // later it adds 5 hours to 2:00 for the user when the date is displayed.
        }
        //strtotime()  may return false, making this method to return bool instead of int.
        return $time ? $time : 0;
    }

    /**
     * file_get_contents replacement for remote files
     * @access public
     * @param string $url
     * @param bool $get_contents whether or not to return file contents, use GET_CONTENTS for true
     * @param integer $port
     * @param string $connect manually choose server for connection
     * @return string an empty string is not necessarily a failure
     */
    function remote_request($url, $get_contents = false, $port = 80, $connect = '', $host = null)
    {
        $url = parse_url($url);
        if (!$connect) {
            $connect = $url['host'];
        }

        if ($host) {
            $url['host'] = $host;
        }

        $data = '';

        if ($conn = @fsockopen($connect, $port, $errno, $errstr, 10)) {
            $out =  "GET {$url['path']} HTTP/1.0\r\n";
            $out .= "Host: {$url['host']}\r\n\r\n";
            $out .= "Connection: Close\r\n\r\n";

            fwrite($conn, $out);

            if ($get_contents) {
                while (!feof($conn)) {
                    $data .= fgets($conn, 128);
                }

                fclose($conn);

                $pos = strpos($data, "\r\n\r\n");

                if ($pos !== false) {
                   //strip the http headers.
                    $data = substr($data, $pos + 2 * strlen("\r\n"));
                }
            }
        }

        return $data;
    }

    /**
     * getSvnRev
     *  For internal use
     * @access public
     * @return string
     */
    function getSvnRev()
    {
        if(is_file(BASEDIR. '/REVISION') && is_dir(BASEDIR . '/.svn')) {

            return 'r' . intval(file_get_contents(BASEDIR .'/REVISION'));
        }

        return '';
    }

}
?>
