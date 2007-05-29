<?php
/**
 * Basic constants/variables required for flyspray operation
 *
 * @notes be a real paranoid here.
 * @version $Id$
 * @notes : do not modify this file, use constants.$_SERVER['SERVER_NAME'].php if you need
 * to alter some behaviuor.
 */

if (!defined('BASEDIR')) {
    define('BASEDIR', dirname(dirname(__FILE__)));
}

// Change this line if you move flyspray.conf.php elsewhere
$conf = @parse_ini_file(Flyspray::get_config_path(), true);

// $baseurl
if (isset($conf['general']['force_baseurl']) && $conf['general']['force_baseurl'] != '') {
    $baseurl = $conf['general']['force_baseurl'];
} else {
    if (!isset($webdir)) {
        $webdir = dirname($_SERVER['SCRIPT_NAME']);
        if (substr($webdir, -9) == 'index.php') {
            $webdir = dirname($webdir);
        }
    }

    $baseurl = rtrim(Flyspray::absoluteURI($webdir),'/\\') . '/' ;
}

if(isset($conf['general']['syntax_plugin']) && preg_match('/^[a-z0-9_]+$/iD', $conf['general']['syntax_plugin'])) {

$path_to_plugin = sprintf('%s/plugins/%s/%s_constants.inc.php',
                          BASEDIR, $conf['general']['syntax_plugin'], $conf['general']['syntax_plugin']);

    if (is_readable($path_to_plugin)) {
        include($path_to_plugin);
    }
}

define('NOTIFY_TASK_OPENED',      1);
define('NOTIFY_TASK_CHANGED',     2);
define('NOTIFY_TASK_CLOSED',      3);
define('NOTIFY_TASK_REOPENED',    4);
define('NOTIFY_DEP_ADDED',        5);
define('NOTIFY_DEP_REMOVED',      6);
define('NOTIFY_COMMENT_ADDED',    7);
define('NOTIFY_REL_ADDED',        9);
define('NOTIFY_OWNERSHIP',       10);
define('NOTIFY_CONFIRMATION',    11);
define('NOTIFY_PM_REQUEST',      12);
define('NOTIFY_PM_DENY_REQUEST', 13);
define('NOTIFY_NEW_ASSIGNEE',    14);
define('NOTIFY_REV_DEP',         15);
define('NOTIFY_REV_DEP_REMOVED', 16);
define('NOTIFY_ADDED_ASSIGNEES', 17);
define('NOTIFY_ANON_TASK',       18);
define('NOTIFY_PW_CHANGE',       19);
define('NOTIFY_NEW_USER',        20);
define('NOTIFY_REMINDER',        21);
define('NOTIFY_DIGEST',          21);

define('NOTIFY_EMAIL',            1);
define('NOTIFY_JABBER',           2);
define('NOTIFY_BOTH',             3);
define('ADDRESS_TASK',            1);
define('ADDRESS_USER',            2);
define('ADDRESS_EMAIL',           3);
define('ADDRESS_DONE',            4);

define('FIELD_LIST',              1);
define('FIELD_DATE',              2);
define('FIELD_TEXT',              3);
define('FIELD_USER',              4);

define('LIST_BASIC',              1);
define('LIST_VERSION',            2);
define('LIST_CATEGORY',           3);

// Do modes
define('MENU_GLOBAL',             1);
define('MENU_PROJECT',            2);

define('ERROR_INTERNAL',          7);
define('ERROR_INPUT',             6);
define('ERROR_DB',                5);
define('ERROR_PERMS',             4);
define('ERROR_RECOVER',           3);
define('SUBMIT_OK',               2);
define('NO_SUBMIT',               1);

// Function parameters
define('REINDEX',              true);
define('GET_CONTENTS',         true);
define('LOCK_FIELD',           true);
define('USE_DEFAULT',          true);
define('ADODB_AUTOQUOTE',      true);
define('PLAINTEXT',            true);

// Others
define('MIN_PW_LENGTH', 5);
define('LOGIN_ATTEMPTS', 5);
define('FS_CACHE_DIR', Flyspray::get_tmp_dir() . DIRECTORY_SEPARATOR . md5($_SERVER['SERVER_NAME']  . BASEDIR));

is_dir(FS_CACHE_DIR) || mkdir(FS_CACHE_DIR, 0700);
//local installation constants, this file must not exist in the svn repository.

if(is_readable(BASEDIR . '/includes/constants.' . $_SERVER['SERVER_NAME'] . '.php')) {
    include(BASEDIR . '/includes/constants.' . $_SERVER['SERVER_NAME'] . '.php');
}

defined('FS_ATTACHMENTS_DIR') || define('FS_ATTACHMENTS_DIR', BASEDIR . DIRECTORY_SEPARATOR . 'attachments');

// developers or advanced users only
//define('DEBUG_SQL',          true);
//define('FS_MAIL_DEBUG', true);
//define('FS_NO_EMAIL',   true);
?>
