<?php

// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

require_once dirname(__FILE__) . '/includes/fix.inc.php';
require_once dirname(__FILE__) . '/includes/functions.inc.php';
require_once dirname(__FILE__) . '/includes/constants.inc.php';

// If it is empty,take the user to the setup page

if (!$conf) {
    Flyspray::Redirect('setup/index.php');
}

require_once BASEDIR . '/includes/class.gpc.php';
require_once BASEDIR . '/includes/utf8.inc.php';
require_once BASEDIR . '/includes/db.inc.php';
require_once BASEDIR . '/includes/class.backend.php';
require_once BASEDIR . '/includes/class.project.php';
require_once BASEDIR . '/includes/class.user.php';
require_once BASEDIR . '/includes/class.tpl.php';
require_once BASEDIR . '/includes/i18n.inc.php';

$db = new Database;
$db->dbOpenFast($conf['database']);
$fs = new Flyspray;
$be = new Backend;

if (is_readable(BASEDIR . '/sql/index.html') && strpos($fs->version, 'dev') === false) {
    die('Please empty the folder "' . BASEDIR . DIRECTORY_SEPARATOR . 'sql" before you start using Flyspray.');
}

// Any "do" mode that accepts a task_id or id field should be added here.
if (in_array(Req::val('do'), array('details', 'depends', 'modify'))) {
    $id = Req::num('task_id', Req::num('id'));

    if (is_numeric($id)) {
        $result = $db->Query('SELECT  attached_to_project
                                FROM  {tasks} WHERE task_id = ?', array($id));
        $project_id = $db->FetchOne($result);
    }
}

if (empty($project_id) || (Req::has('project') && Req::has('switch'))) {
    // Determine which project we want to see
    if (Req::has('project')) {
        $project_id = Req::val('project');
    } elseif (!($project_id = Cookie::val('flyspray_project'))) {
        $project_id = $fs->prefs['default_project'];
    }
}

if (Post::val('action') == 'movetogroup') {
    $sql = $db->Query('SELECT belongs_to_project FROM {groups} WHERE group_id = ? OR group_id = ?',
                      array(Post::val('switch_to_group'), Post::val('old_group')));
    $old_pr = $db->FetchOne($sql);
    $new_pr = $db->FetchOne($sql);
    if ($new_pr !== $old_pr && $new_pr) {
        Flyspray::Redirect(CreateURL('error'));
    }
    $project_id = $new_pr;
}

$proj = new Project($project_id);
$proj->checkExists();
$proj->setCookie();

// Load translations
load_translations();

for ($i = 6; $i >= 1; $i--) {
    $priority_list[$i] = L('priority' . $i);
}
for ($i = 5; $i >= 1; $i--) {
    $severity_list[$i] = L('severity' . $i);
}

?>
