<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/
define('IN_FS', true);

require_once(dirname(__FILE__).'/header.php');

// Get the translation for the wrapper page (this page)
setlocale(LC_ALL, str_replace('-', '_', L('locale')));

/*
   FS#329 allows tasks to be assigned to multiple users.
   We need to convert the old data by including this script.
   It will need to be added to the installer/upgrader for release
*/
include_once('sql/upgrade_assignments.php');

// Background daemon that does scheduled reminders
if ($conf['general']['reminder_daemon'] == '1') {
    $fs->startReminderDaemon();
}

$do = Req::val('do', 'index');
if($do == 'admin' && Req::has('switch') && Req::val('project') != '0') {
    $do = 'pm';
} elseif($do == 'pm' && Req::has('switch') && Req::val('project') == '0') {
    $do = 'admin';
} elseif(Req::has('show') || (Req::has('switch') && (Req::val('project') == '0' || $do == 'details' ))) {
    $do = 'index';
}

/* permission stuff */
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'), $proj);
    $user->check_account_ok();
    $user->save_search($do);
} else {
    $user = new User(0, $proj);
}

if (Get::has('getfile') && Get::val('getfile')) {
    // If a file was requested, deliver it
    $result = $db->Query("SELECT  t.task_id, t.attached_to_project,
                                  a.orig_name, a.file_name, a.file_type
                            FROM  {attachments} a
                      INNER JOIN  {tasks}       t ON a.task_id = t.task_id
                           WHERE  attachment_id = ?", array(Get::val('getfile')));
    list($task_id, $proj_id, $orig_name, $file_name, $file_type) = $db->FetchArray($result);

    if ($proj_id != $proj->id) {
        // XXX project_id comes from the cookie
        $proj = new Project($proj_id);
        $user->get_perms($proj);
    }

    // Check if file exists, and user permission to access it!
    if (is_file(BASEDIR . "/attachments/$file_name")
            && ($proj->prefs['others_view'] || $user->perms['view_attachments']))
    {
        output_reset_rewrite_vars();
        ob_end_clean();
        $path = BASEDIR . "/attachments/$file_name";

        header('Pragma: public');
        header("Content-type: $file_type");
        header('Content-Disposition: filename="'.$orig_name.'"');
        header('Content-transfer-encoding: binary');
        header('Content-length: ' . filesize($path));

        readfile($path);
        exit();
    }
    else {
        Flyspray::Redirect( CreateURL('error', null) );
    }
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

$page = new FSTpl();

if ($show_task = Get::val('show_task')) {
    // If someone used the 'show task' form, redirect them
    if (is_numeric($show_task)) {
        Flyspray::Redirect( CreateURL('details', $show_task) );
    } else {
        Flyspray::Redirect( CreateURL('error', null) );
    }
}

if ($fs->requestDuplicated()) {
    // Check that this page isn't being submitted twice
    $_SESSION['ERROR'] = L('duplicated');
    Flyspray::Redirect( '?id='.$proj->id );
}

if ($user->perms['manage_project']) {
    // Find out if there are any PM requests wanting attention
    $sql = $db->Query(
            "SELECT COUNT(*) FROM {admin_requests} WHERE project_id = ? AND resolved_by = '0'",
            array($proj->id));
    list($count) = $db->fetchRow($sql);

    $page->assign('pm_pendingreq_num', $count);
}

$sql = $db->Query(
        "SELECT  DISTINCT p.project_id, p.project_title,
                 upper(p.project_title) as sort_names
           FROM  {projects} p
      LEFT JOIN  {groups} g ON p.project_id=g.belongs_to_project OR g.belongs_to_project=0
      LEFT JOIN  {users_in_groups} uig ON uig.group_id = g.group_id AND uig.user_id = ?
          WHERE  (p.project_is_active='1' AND p.others_view = '1')
                 OR (uig.user_id IS NOT NULL AND (g.is_admin=1 OR g.view_tasks=1))
       ORDER BY  sort_names", array($user->id));

$page->assign('project_list', $project_list = $db->FetchAllArray($sql));

// Get e-mail addresses of the admins
if ($user->isAnon() && !$fs->prefs['user_notify']) {
    $sql = $db->Query('SELECT email_address
                         FROM {users} u
                    LEFT JOIN {users_in_groups} g ON u.user_id = g.user_id
                        WHERE g.group_id = 1');
    $page->assign('admin_emails', $db->FetchAllArray($sql));
}

// default title
$page->setTitle("Flyspray :: {$proj->prefs['project_title']}");

$page->assign('do', $do);
$page->pushTpl('header.tpl');

// Show the page the user wanted
require_once BASEDIR . "/scripts/$do.php" ;

$page->pushTpl('footer.tpl');
$page->setTheme($proj->prefs['theme_style']);
$page->render();

unset($_SESSION['ERROR'], $_SESSION['SUCCESS']);

if (!empty($conf['debug'])) {
    include_once BASEDIR . '/includes/debug.inc.php';
}
?>
