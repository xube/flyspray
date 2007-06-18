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
    $proj_id = $task['project_id'];
    $orig_name = $task['orig_name'];
    $file_name = $task['file_name'];
    $file_type = $task['file_type'];
    $disk_filename = FS_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $file_name;
    // Check if file exists, and user permission to access it!
    if (!is_file($disk_filename) || !$user->can_view_task($task)) {
        header('HTTP/1.1 410 Gone');
        echo 'File does not exist.';
        exit();
    }
    output_reset_rewrite_vars();

    header('Pragma: public');
    header("Content-type: $file_type");
    header('Content-Disposition: filename="'.$orig_name.'"');
    header('Content-transfer-encoding: binary');
    header('Content-length: ' . filesize($disk_filename));

    readfile($disk_filename);
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

if (Get::val('opensearch')) {
    $page->finish('opensearch.tpl');
}

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
    $count = $db->GetOne(
            'SELECT COUNT(*) FROM {admin_requests} WHERE project_id = ? AND resolved_by = 0',
            array($proj->id));

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


/* XXX:
 * there is something fishy in the new design, users actions
 * should never require the admin specific class.
 */

$class = 'FlysprayDo' . $do;
$mode =& new $class;
$mode->show(Req::val('area'));

if (isset($_SESSION)) {
    // remove dupe data on error, since no submission happened
    if (isset($_SESSION['ERROR']) && isset($_SESSION['requests_hash'])) {
        $currentrequest = md5(serialize($_POST));
        unset($_SESSION['requests_hash'][$currentrequest]);
    }
}

$page->finish('footer.tpl');

?>