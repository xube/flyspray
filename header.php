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

// Get available do-modes and include the classes
$modes = str_replace('.php', '', array_map('basename', glob_compat(BASEDIR ."/scripts/*.php")));
// yes, we need all of them for now
foreach ($modes as $mode) {
    require_once(BASEDIR . '/scripts/' . $mode . '.php');
}
$do = Req::val('do');

// Any "do" mode that accepts a task_id or id field should be added here.
if (Req::num('task_id')) {
    $project_id = $db->GetOne('SELECT  project_id
                                 FROM  {tasks}
                                WHERE task_id = ?',
                               array(Req::num('task_id')));
    $do = Filters::enum($do, array('details', 'depends', 'editcomment'));
} else {
    if ($do == 'admin' && Get::has('switch') && Get::val('project') != '0') {
        $do = 'pm';
    } elseif ($do == 'pm' && Get::has('switch') && Get::val('project') == '0') {
        $do = 'admin';
    } elseif (Get::has('switch') && ($do == 'details')) {
        $do = 'index';
    }

    if ($do && class_exists('FlysprayDo' . ucfirst($do)) &&
        !call_user_func(array('FlysprayDo' . ucfirst($do), 'is_projectlevel'))) {
        $project_id = 0;
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
// reset do for default project level entry page
if (!in_array($do, $modes)) {
    $do = ($do) ? Req::enum('do', $modes, $proj->prefs['default_entry']) : $proj->prefs['default_entry'];
}

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

?>
