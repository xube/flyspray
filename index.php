<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/

require_once(dirname(__FILE__).'/header.php');
require_once(dirname(__FILE__).'/includes/class.tpl.php');

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

/* permission stuff */
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->get_perms($proj);
    $user->check_account_ok();
    $user->save_search();
} else {
    $user = new User();
    $user->get_perms($proj);
}

if (Get::has('getfile') && Get::val('getfile')) {
    // If a file was requested, deliver it
    $result = $db->Query("SELECT  t.task_id, t.attached_to_project,
                                  a.orig_name, a.file_name, a.file_type
                            FROM  {attachments} a
                      INNER JOIN  {tasks}       t ON a.task_id = t.task_id
                           WHERE  attachment_id = ?", array($_GET['getfile']));
    list($task_id, $proj_id, $orig_name, $file_name, $file_type) = $db->FetchArray($result);

    if ($proj_id != $proj->id) {
        // XXX project_id comes from the cookie
        $proj = new Project($proj_id);
        $user->get_perms($proj);
    }

    // Check if file exists, and user permission to access it!
    if (file_exists("attachments/$file_name")
            && ($proj->prefs['others_view'] || $user->perms['view_attachments']))
    {
        $path = "$basedir/attachments/$file_name";

        header('Pragma: public');
        header("Content-type: $file_type");
        header('Content-Disposition: filename="'.$orig_name.'"');
        header('Content-transfer-encoding: binary');
        header('Content-length: ' . filesize($path));

        readfile($path);
    }
    else {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
    exit;
}

/*******************************************************************************/
/* Here begins the deep flyspray : html rendering                              */
/*******************************************************************************/

// Get the translation for the wrapper page (this page)
$fs->get_language_pack('main');
setlocale(LC_ALL, str_replace('-','_',$language['locale']));

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
if (Req::val('project')) {
    $page->setTheme($proj->prefs['theme_style']);
} else {
    $page->setTheme($fs->prefs['global_theme']);
}

if ($show_task = Get::val('show_task')) {
    // If someone used the 'show task' form, redirect them
    if (is_numeric($show_task)) {
        $fs->Redirect( $fs->CreateURL('details', $show_task) );
    } else {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
}

if ($fs->requestDuplicated()) {
    // Check that this page isn't being submitted twice
    $_SESSION['ERROR'] = $language['duplicated'];
    $fs->Redirect( '?id='.$proj->id );
}

if ($user->perms['manage_project']) {
    // Find out if there are any PM requests wanting attention
    $sql = $db->Query(
            "SELECT COUNT(*) FROM {admin_requests} WHERE project_id = ? AND resolved_by = '0'",
            array($proj->id));
    list($count) = $db->fetchRow($sql);

    $page->assign('pm_pendingreq_num', $count);
}

// Show the project blurb if the project manager defined one
$do = Req::val('do', 'index');
if ($proj->prefs['project_is_active']
    && ($proj->prefs['others_view'] || $user->perms['view_tasks'])
    && in_array($do, array('details', 'index', 'newtask', 'reports', 'depends')))
{
   $page->assign('intro_message', $proj->prefs['intro_message']);
}

if (!$user->isAnon() && !$user->perms['global_view']) {
    // or, if the user is logged in
    $sql = $db->Query(
            "SELECT  p.project_id, p.project_title
               FROM  {projects} p
          LEFT JOIN  {groups} g ON p.project_id=g.belongs_to_project AND g.view_tasks=1
          LEFT JOIN  {users_in_groups} uig ON uig.group_id = g.group_id AND uig.user_id = ?
              WHERE  p.project_is_active='1' AND (p.others_view OR uig.user_id IS NOT NULL)
           ORDER BY  p.project_title", array($user->id));
}
else {
    // XXX kludge, to merge request for power users with anonymous ones.
    $sql = $db->Query("SELECT  project_id, project_title
                         FROM  {projects}
                        WHERE  project_is_active = '1'
                               AND ('1' = ? OR others_view = '1')
                     ORDER BY  project_title",
                     array($user->perms['global_view']));
}
$page->assign('project_list', $project_list = $db->FetchAllArray($sql));

// default title;
$page->setTitle("Flyspray :: {$proj->prefs['project_title']}:");

$page->pushTpl('header.tpl');
unset($_SESSION['ERROR'], $_SESSION['SUCCESS']);

// Show the page the user wanted
require("$basedir/scripts/$do.php");

$page->pushTpl('footer.tpl');
$page->render();

if (!empty($conf['debug'])) {
    require ($basedir . '/includes/debug.inc.php');
}
?>
