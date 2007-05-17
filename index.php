<?php
/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/
define('IN_FS', true);

require dirname(__FILE__). '/header.php';

// Background daemon that does scheduled reminders
if ($conf['general']['reminder_daemon'] == '1') {
    Flyspray::startReminderDaemon();
}

// Get available do-modes and include the classes
$modes = str_replace('.php', '', array_map('basename', glob_compat(BASEDIR ."/scripts/*.php")));

$do = Req::enum('do', $modes, $proj->prefs['default_entry']);

if ($do == 'admin' && Get::has('switch') && Get::val('project') != '0') {
    $do = 'pm';
} elseif ($do == 'pm' && Get::has('switch') && Get::val('project') == '0') {
    $do = 'admin';
} elseif (Get::has('show') || (Get::has('switch') && ($do == 'details'))) {
    $do = 'index';
}

$user->save_search($do);

if (Get::val('logout')) {
    $user->logout();
    Flyspray::Redirect($baseurl);
}

if (Get::val('getfile')) {
    // If a file was requested, deliver it
    $result = $db->Execute("SELECT  t.project_id,
                                  a.orig_name, a.file_name, a.file_type, t.*
                            FROM  {attachments} a
                      INNER JOIN  {tasks}       t ON a.task_id = t.task_id
                           WHERE  attachment_id = ?", array(Get::val('getfile')));
    $task = $result->FetchRow();
    list($proj_id, $orig_name, $file_name, $file_type) = $task;

    // Check if file exists, and user permission to access it!
    if (!is_file(BASEDIR . "/attachments/$file_name") || !$user->can_view_task($task)) {
        header('HTTP/1.1 410 Gone');
        echo 'File does not exist.';
        exit();
    }

    output_reset_rewrite_vars();
    $path = BASEDIR . "/attachments/$file_name";

    header('Pragma: public');
    header("Content-type: $file_type");
    header('Content-Disposition: filename="'.$orig_name.'"');
    header('Content-transfer-encoding: binary');
    header('Content-length: ' . filesize($path));

    readfile($path);
    exit;
}

/*******************************************************************************/
/* Here begins the deep flyspray : html rendering                              */
/*******************************************************************************/

// make browsers back button work
header('Expires: -1');
header('Pragma: no-cache');
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');

// see http://www.w3.org/TR/html401/present/styles.html#h-14.2.1
header('Content-Style-Type: text/css');
header('Content-type: text/html; charset=utf-8');

if ($conf['general']['output_buffering'] == 'gzip' && extension_loaded('zlib'))
{
    // Start Output Buffering and gzip encoding if setting is present.
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

// our own error handler, so that especially notices don't stay unnoticed
if (version_compare(phpversion(), '5.0.0', '>=')) {
    set_error_handler(array('FlysprayDo', 'error'), ini_get('error_reporting'));
} else {
    set_error_handler(array('FlysprayDo', 'error'));
}

$page =& new FSTpl();

if ($show_task = Get::val('show_task')) {
    // If someone used the 'show task' form, redirect them
    if (is_numeric($show_task)) {
        Flyspray::Redirect( CreateURL(array('details', 'task' . $show_task)) );
    } else {
        if (strpos($show_task, '#')) {
            list($prefix, $prefix_id) = explode('#', $show_task);
            $task_id = $db->GetOne('SELECT task_id
                                      FROM {tasks} t
                                 LEFT JOIN {projects} p ON t.project_id = p.project_id
                                     WHERE prefix_id = ? AND project_prefix = ?', array($prefix_id, $prefix));
            if ($task_id) {
                Flyspray::Redirect( CreateURL(array('details', 'task' . $task_id)) );
            }
        }
        Flyspray::Redirect(Createurl('index', array('string' => $show_task)));
    }
}

if ($proj->id && $user->perms('manage_project')) {
    // Find out if there are any PM requests wanting attention
    $sql = $db->Execute(
            'SELECT COUNT(*) FROM {admin_requests} WHERE project_id = ? AND resolved_by = 0',
            array($proj->id));
    list($count) = $sql->FetchRow();

    $page->assign('pm_pendingreq_num', $count);
}

// Get e-mail addresses of the admins
if ($user->isAnon() && !$fs->prefs['user_notify']) {
    $sql = $db->Execute('SELECT email_address
                         FROM {users} u
                    LEFT JOIN {users_in_groups} g ON u.user_id = g.user_id
                        WHERE g.group_id = 1');
    $page->assign('admin_emails', $sql->GetArray());
}

// default title
$page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title']);
$page->setTheme($proj->prefs['theme_style']);

$page->assign('do', $do);
$page->pushTpl('header.tpl');

if (Flyspray::requestDuplicated()) {
    // Check that this page isn't being submitted twice
    FlysprayDo::error(array(ERROR_INPUT, L('error3')));
}


/* XXX: this is a temporal workaround. 
 * there is something fishy in the new design, users actions
 * should never require the admin specific class.
 */

$require_admin = array('myprofile', 'pm', 'register');

if(in_array($do, $require_admin)) {
    include BASEDIR . '/scripts/admin.php';    
}

require BASEDIR . '/scripts/' . $do . '.php';

$class = 'FlysprayDo' . $do;
$mode =& new $class;
$mode->show(Req::val('area'));

$page->finish('footer.tpl');

?>