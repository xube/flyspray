<?php

// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

require dirname(__FILE__) . '/includes/fix.inc.php';
require dirname(__FILE__) . '/includes/class.flyspray.php';
require dirname(__FILE__) . '/includes/constants.inc.php';
require BASEDIR . '/includes/i18n.inc.php';

// Get the translation for the wrapper page (this page)
setlocale(LC_ALL, str_replace('-', '_', L('locale')) . '.utf8');

// If it is empty,take the user to the setup page

if (!$conf) {
    Flyspray::Redirect('setup/index.php');
}

require BASEDIR . '/includes/class.gpc.php';
require BASEDIR . '/includes/utf8.inc.php';
require BASEDIR . '/includes/class.database.php';
require BASEDIR . '/includes/class.backend.php';
require BASEDIR . '/includes/class.project.php';
require BASEDIR . '/includes/class.user.php';
require BASEDIR . '/includes/class.tpl.php';
require BASEDIR . '/includes/class.do.php';

$db = NewDatabase($conf['database']);
$fs =& new Flyspray;

if (is_readable(BASEDIR . '/setup/index.php') && strpos($fs->version, 'dev') === false) {
    die('Please empty the folder "' . BASEDIR . DIRECTORY_SEPARATOR . "setup\"  before you start using Flyspray.\n".
        "If you are upgrading, please go to the setup directory and launch upgrade.php");
}

// Any "do" mode that accepts a task_id or id field should be added here.
if (in_array(Req::val('do'), array('details', 'depends'))) {
    if (Req::num('task_id')) {
        $project_id = $db->GetOne('SELECT  project_id
                                     FROM  {tasks}
                                    WHERE task_id = ?',
                                   array(Req::num('task_id')));
    }
}

if (!isset($project_id)) {
    // Determine which project we want to see
    if (($project_id = Cookie::val('flyspray_project')) == '') {
        $project_id = $fs->prefs['default_project'];
    }
    $project_id = Req::val('project', Req::val('project_id', $project_id));
}

$proj =& new Project($project_id);
$proj->setCookie();

/* permission stuff */
if (Cookie::val('flyspray_userid') && Cookie::val('flyspray_passhash')) {
    $user =& new User(Cookie::val('flyspray_userid'));
    $user->check_account_ok();
} else {
    $user =& new User(0);
}

// Load translations
load_translations();

for ($i = 5; $i >= 1; $i--) {
    $fs->severities[$i] = L('severity' . $i);
}

?>
