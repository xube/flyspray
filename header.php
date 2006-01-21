<?php
// As of 24 July 2004, all editable config is stored in flyspray.conf.php
// There should be no reason to edit this file anymore, except if you
// move flyspray.conf.php to a directory where a browser can't access it.
// (RECOMMENDED).

// Change this line if you move flyspray.conf.php elsewhere
$basedir = dirname(__FILE__);
$conf    = @parse_ini_file($basedir . '/flyspray.conf.php', true);

// If it is empty, or lacks 0.9.8 variables, take the user to the setup page
if (count($conf) == 0 || !isset($conf['general']['baseurl'])) {
    header('Location:'. 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') .  "/" .'setup/index.php');
    exit;
}
if (substr($baseurl = $conf['general']['baseurl'], -1) != '/') {
    $baseurl .= '/';
}

require_once "$basedir/includes/fix.inc.php";
require_once "$basedir/includes/class.gpc.php";

require_once "$basedir/includes/utf8.inc.php";
require_once "$basedir/includes/db.inc.php";
require_once "$basedir/includes/functions.inc.php";

require_once "$basedir/includes/class.backend.php";
require_once "$basedir/includes/class.project.php";
require_once "$basedir/includes/class.user.php";
require_once "$basedir/includes/class.tpl.php";

$db = new Database;
$db->dbOpenFast($conf['database']);
$fs = new Flyspray;
$be = new Backend;

require_once "$basedir/includes/regexp.php";

// Any "do" mode that accepts a task_id or id field should be added here.
if (in_array(Req::val('do'), array('details', 'depends', 'modify'))) {
    $id = Req::val('task_id', Req::val('id'));

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
    } elseif(!($project_id = Cookie::val('flyspray_project'))) {
        $project_id = $fs->prefs['default_project'];
    }
}

$proj = new Project($project_id);
$proj->checkExists();
$proj->setCookie();

?>
