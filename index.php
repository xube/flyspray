<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/

require_once(dirname(__FILE__).'/header.php');
require_once(dirname(__FILE__).'/includes/class.tpl.php');
require_once(dirname(__FILE__).'/includes/permissions.inc.php');

// Background daemon that does scheduled reminders
if ($conf['general']['reminder_daemon'] == '1') {
    $fs->startReminderDaemon();
}

/* permission stuff */
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user_id = Cookie::val('flyspray_userid');

    // Check to see if the user has been trying to hack their cookies to perform sql-injection
    if (!is_numeric($user_id) || !is_numeric(Cookie::val('flyspray_project'))) {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }

    // Only logged in users get to use the 'last search' functionality
    foreach (array('string','type','sev','due','dev','cat','status') as $key) {
        if (Get::has($key)) {
            $db->Query("UPDATE  {users}
                           SET  last_search = ?
                         WHERE  user_id = ?",
                    array($_SERVER['REQUEST_URI'], $user_id)
            );
            break;
        }
    }

    $current_user = $fs->getUserDetails($user_id);
    $permissions  = $fs->getPermissions($user_id, $proj->id);

    // Check that the user hasn't spoofed the cookie contents somehow
    // And that their account/group are enabled
    if (Cookie::val('flyspray_passhash') !=
            crypt($current_user['user_pass'], $conf['general']['cookiesalt'])
            || $permissions['account_enabled'] != '1'
            || $permissions['group_open'] != '1')
    {
        $fs->setcookie('flyspray_userid',   '', time()-60);
        $fs->setcookie('flyspray_passhash', '', time()-60);
        $fs->Redirect($fs->CreateURL('logout', null));
    }
} else {
    $permissions  = array();
}

if (Get::has('getfile') && Get::val('getfile')) {
    // If a file was requested, deliver it
    $result = $db->Query("SELECT  task_id, orig_name, file_name, file_type
                            FROM  {attachments}
                           WHERE  attachment_id = ?", array($_GET['getfile']));
    list($task_id, $orig_name, $file_name, $file_type) = $db->FetchArray($result);

    // Retrieve permissions!
    $task_details = $fs->GetTaskDetails($task_id);
    $proj_prefs   = $fs->GetProjectPrefs($task_details['attached_to_project']);
    $user_permissions = @$fs->getPermissions(intval($current_user['user_id']), $task_details['attached_to_project']);

    // Check if file exists, and user permission to access it!
    if (file_exists("attachments/$file_name")
            && ($proj->prefs['others_view'] || $user_permissions['view_attachments']))
    {
        $path = "$basedir/attachments/$file_name";

        header('Pragma: public');
        header("Content-type: $file_type");
        header("Content-Disposition: filename=$orig_name");
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

// see http://www.w3.org/TR/html401/present/styles.html#h-14.2.1
header('Content-Style-Type: text/css');
header('Content-type: text/html; charset=utf-8');

if ($conf['general']['output_buffering'] == 'gzip') {
    // Start Output Buffering and gzip encoding if setting is present.
    // This functionality provided Mariano D'Arcangelo
    include_once( 'includes/gzip_compress.php' );
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
    }
    else {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
}

if ($fs->requestDuplicated()) {
    // Check that this page isn't being submitted twice
    $_SESSION['ERROR'] = $language['duplicated'];
    $fs->Redirect( "?id=".$proj->id );
}

if (Cookie::has('flyspray_userid') && empty($permissions['global_view'])) {
    // or, if the user is logged in
    $get_projects = $db->Query(
            "SELECT  p.project_id, p.project_title
               FROM  {projects} p
          LEFT JOIN  {groups} g ON p.project_id=g.belongs_to_project AND g.view_tasks=1
          LEFT JOIN  {users_in_groups} uig ON uig.group_id = g.group_id AND uig.user_id = ?
              WHERE  p.project_is_active='1' AND (p.others_view OR uig.user_id IS NOT NULL)
           ORDER BY  p.project_title", array($current_user['user_id']));
}
else {
    // XXX kludge, to merge request for power users with anonymous ones.
    $get_projects = $db->Query("SELECT  project_id, project_title
                                  FROM  {projects}
                                 WHERE  project_is_active = '1'
                                        AND ('1' = ? OR others_view = '1')
                              ORDER BY  project_title",
                              array($permissions['global_view']));
}

if ($permissions['manage_project']) {
    // Find out if there are any PM requests wanting attention
    $get_req = $db->Query(
            "SELECT * FROM {admin_requests} WHERE project_id = ? AND resolved_by = '0'",
            array($proj->id));

    $page->assign('pm_pendingreq_num', $db->CountRows($get_req));
}

// Show the project blurb if the project manager defined one
$do = Req::val('do', 'index');
if ($proj->prefs['project_is_active'] == '1'
    && ($proj->prefs['others_view'] == '1' OR @$permissions['view_tasks'] == '1')
    && !empty($proj->prefs['intro_message'])
    && in_array($do, array('details', 'index', 'newtask', 'reports', 'depends'))
    || (Get::val('project') == '0'))
{
    require_once ( "$basedir/includes/markdown.php" );
    $page->assign('intro_message', Markdown($proj->prefs['intro_message']));
}

$page->assign('project_list', $db->FetchAllArray($get_projects));
$page->display('header.tpl');
unset($_SESSION['ERROR'], $_SESSION['SUCCESS']);

// Show the page the user wanted
require("$basedir/scripts/$do.php");

$page->display('footer.tpl');

if ($conf['debug']) {
    require ($basedir . '/includes/debug.inc.php');
}
?>
